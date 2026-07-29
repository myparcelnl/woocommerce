<?php

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Service;

use MyParcelNL\Pdk\Base\PdkBootstrapper;
use MyParcelNL\Pdk\Storage\Contract\StorageInterface;
use RuntimeException;
use Throwable;

final class WpOptionNamespaceService
{
    public const CONFLICT_KEEP_TARGET    = 'keep_target';
    public const CONFLICT_REPLACE_TARGET = 'replace_target';

    private const LEGACY_NAMESPACE = 'myparcelnl';

    /**
     * @var \MyParcelNL\Pdk\Storage\Contract\StorageInterface
     */
    private $storage;

    public function __construct(StorageInterface $storage)
    {
        $this->storage = $storage;
    }

    /**
     * Restores a v6+ installation whose options were moved to the v5 namespace during deactivation.
     *
     * A current version below v6 means a genuine or interrupted v5 upgrade. That state must continue
     * through Migration6_0_0 instead of being reconciled as a deactivated v6 installation.
     */
    public function restoreDeactivatedV6Installation(): bool
    {
        $currentVersionOption = $this->createOptionName(PdkBootstrapper::PLUGIN_NAMESPACE, 'installed_version');
        $currentVersion       = get_option($currentVersionOption, null);
        $legacyVersion        = get_option(
            $this->createOptionName(self::LEGACY_NAMESPACE, 'installed_version'),
            null
        );

        if ($this->isVersionBeforeV6($currentVersion)) {
            return false;
        }

        if (! $this->isV6OrLater($currentVersion) && ! $this->isV6OrLater($legacyVersion)) {
            return false;
        }

        if (empty($this->getOptionNames(self::LEGACY_NAMESPACE))) {
            return false;
        }

        $this->migrateOptions(
            self::LEGACY_NAMESPACE,
            PdkBootstrapper::PLUGIN_NAMESPACE,
            self::CONFLICT_KEEP_TARGET,
            $this->isV6OrLater($legacyVersion) && null === $this->getMajorVersion($currentVersion)
                ? [$currentVersionOption]
                : []
        );

        return true;
    }

    /**
     * Moves all options under one exact MyParcel namespace prefix to another namespace.
     *
     * @param  string   $from
     * @param  string   $to
     * @param  string   $conflictStrategy
     * @param  string[] $replaceTargetOptions
     *
     * @return void
     */
    public function migrateOptions(
        string $from,
        string $to,
        string $conflictStrategy,
        array  $replaceTargetOptions = []
    ): void {
        if (! in_array($conflictStrategy, [self::CONFLICT_KEEP_TARGET, self::CONFLICT_REPLACE_TARGET], true)) {
            throw new RuntimeException(sprintf('Unknown option conflict strategy "%s".', $conflictStrategy));
        }

        if ($from === $to) {
            return;
        }

        global $wpdb;

        $inTransaction = false;
        $touchedOptions = [];

        try {
            $this->assertDatabaseResult($wpdb->query('START TRANSACTION'), 'start option namespace transaction');
            $inTransaction = true;

            $sourcePrefix = $this->createOptionPrefix($from);
            $targetPrefix = $this->createOptionPrefix($to);
            $optionNames  = $this->sortOptionNames($this->getOptionNames($from, true));

            foreach ($optionNames as $sourceName) {
                $targetName = $targetPrefix . substr($sourceName, strlen($sourcePrefix));
                $touchedOptions[] = $sourceName;
                $touchedOptions[] = $targetName;

                if ($this->optionExists($targetName)) {
                    if (
                        self::CONFLICT_KEEP_TARGET === $conflictStrategy
                        && ! in_array($targetName, $replaceTargetOptions, true)
                    ) {
                        $this->deleteOption($sourceName);

                        continue;
                    }

                    $this->deleteOption($targetName);
                }

                $this->renameOption($sourceName, $targetName);
            }

            $this->assertDatabaseResult($wpdb->query('COMMIT'), 'commit option namespace transaction');
            $inTransaction = false;
        } catch (Throwable $e) {
            $rollbackError = null;

            if ($inTransaction) {
                try {
                    $this->assertDatabaseResult($wpdb->query('ROLLBACK'), 'roll back option namespace transaction');
                } catch (Throwable $rollbackException) {
                    $rollbackError = $rollbackException->getMessage();
                }
            }

            $this->invalidateCaches($touchedOptions);

            throw new RuntimeException(
                sprintf(
                    'Could not migrate WordPress options from "%s" to "%s": %s%s',
                    $from,
                    $to,
                    $e->getMessage(),
                    $rollbackError ? sprintf(' Rollback failed: %s', $rollbackError) : ''
                ),
                0,
                $e
            );
        }

        $this->invalidateCaches($touchedOptions);
    }

    /**
     * @param  string $namespace
     * @param  bool   $forUpdate
     *
     * @return string[]
     */
    private function getOptionNames(string $namespace, bool $forUpdate = false): array
    {
        global $wpdb;

        $like  = $wpdb->esc_like($this->createOptionPrefix($namespace)) . '%';
        $query = "SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE %s ORDER BY option_name";

        if ($forUpdate) {
            $query .= ' FOR UPDATE';
        }

        $prepared = $wpdb->prepare($query, $like);

        if (! is_string($prepared)) {
            throw new RuntimeException('Could not prepare option namespace query.');
        }

        $optionNames = $wpdb->get_col($prepared);
        $this->assertNoDatabaseError('select option namespace rows');

        return is_array($optionNames) ? array_map('strval', $optionNames) : [];
    }

    private function optionExists(string $optionName): bool
    {
        global $wpdb;

        $query = $wpdb->prepare(
            "SELECT option_id FROM {$wpdb->options} WHERE option_name = %s LIMIT 1 FOR UPDATE",
            $optionName
        );

        if (! is_string($query)) {
            throw new RuntimeException('Could not prepare option existence query.');
        }

        $optionId = $wpdb->get_var($query);
        $this->assertNoDatabaseError(sprintf('check whether option "%s" exists', $optionName));

        return null !== $optionId;
    }

    private function deleteOption(string $optionName): void
    {
        global $wpdb;

        $result = $wpdb->delete($wpdb->options, ['option_name' => $optionName]);

        $this->assertSingleChangedRow($result, sprintf('delete option "%s"', $optionName));
    }

    private function renameOption(string $sourceName, string $targetName): void
    {
        global $wpdb;

        $result = $wpdb->update(
            $wpdb->options,
            ['option_name' => $targetName],
            ['option_name' => $sourceName]
        );

        $this->assertSingleChangedRow(
            $result,
            sprintf('rename option "%s" to "%s"', $sourceName, $targetName)
        );
    }

    /**
     * @param  false|int $result
     * @param  string    $operation
     *
     * @return void
     */
    private function assertSingleChangedRow($result, string $operation): void
    {
        $this->assertDatabaseResult($result, $operation);

        if (1 !== $result) {
            throw new RuntimeException(sprintf('Could not %s: expected one changed row, got %s.', $operation, $result));
        }
    }

    /**
     * @param  false|int $result
     * @param  string    $operation
     *
     * @return void
     */
    private function assertDatabaseResult($result, string $operation): void
    {
        global $wpdb;

        if (false === $result || ! empty($wpdb->last_error)) {
            throw new RuntimeException(sprintf(
                'Could not %s: %s',
                $operation,
                $wpdb->last_error ?: 'unknown database error'
            ));
        }
    }

    private function assertNoDatabaseError(string $operation): void
    {
        global $wpdb;

        if (! empty($wpdb->last_error)) {
            throw new RuntimeException(sprintf('Could not %s: %s', $operation, $wpdb->last_error));
        }
    }

    /**
     * @param  string[] $optionNames
     *
     * @return string[]
     */
    private function sortOptionNames(array $optionNames): array
    {
        usort($optionNames, static function (string $left, string $right): int {
            $leftPriority  = self::getOptionPriority($left);
            $rightPriority = self::getOptionPriority($right);

            return $leftPriority === $rightPriority
                ? strcmp($left, $right)
                : $leftPriority <=> $rightPriority;
        });

        return $optionNames;
    }

    private static function getOptionPriority(string $optionName): int
    {
        if ('_installed_version' === substr($optionName, -strlen('_installed_version'))) {
            return 2;
        }

        if ('_applied_migrations' === substr($optionName, -strlen('_applied_migrations'))) {
            return 1;
        }

        return 0;
    }

    /**
     * @param  string[] $optionNames
     *
     * @return void
     */
    private function invalidateCaches(array $optionNames): void
    {
        $optionNames = array_values(array_unique($optionNames));

        if (empty($optionNames)) {
            return;
        }

        foreach ($optionNames as $optionName) {
            wp_cache_delete($optionName, 'options');
            $this->storage->delete('settings_' . $optionName);
        }

        wp_cache_delete('alloptions', 'options');
        wp_cache_delete('notoptions', 'options');
        $this->storage->delete('settings_all');
    }

    /**
     * @param  mixed $version
     *
     * @return bool
     */
    private function isV6OrLater($version): bool
    {
        return null !== ($major = $this->getMajorVersion($version)) && $major >= 6;
    }

    /**
     * @param  mixed $version
     *
     * @return bool
     */
    private function isVersionBeforeV6($version): bool
    {
        return null !== ($major = $this->getMajorVersion($version)) && $major < 6;
    }

    /**
     * @param  mixed $version
     *
     * @return null|int
     */
    private function getMajorVersion($version): ?int
    {
        if (! is_string($version) || ! preg_match('/^(\d+)(?:\.|$)/', $version, $matches)) {
            return null;
        }

        return (int) $matches[1];
    }

    private function createOptionPrefix(string $namespace): string
    {
        return "_{$namespace}_";
    }

    private function createOptionName(string $namespace, string $name): string
    {
        return $this->createOptionPrefix($namespace) . $name;
    }
}
