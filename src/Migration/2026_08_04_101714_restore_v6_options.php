<?php

declare(strict_types=1);

use MyParcelNL\Pdk\App\Installer\Migration\AbstractTimestampedMigration;
use MyParcelNL\Pdk\Facade\Pdk;
use MyParcelNL\Pdk\Settings\Contract\PdkSettingsRepositoryInterface;
use MyParcelNL\Pdk\Storage\Contract\StorageInterface;

return new class extends AbstractTimestampedMigration {
    private const CURRENT_PREFIX = '_myparcelcom_';
    private const LEGACY_PREFIX  = '_myparcelnl_';

    public function up(): void
    {
        $legacyVersion = get_option(self::LEGACY_PREFIX . 'installed_version', null);

        if (! $this->isV6OrLater($legacyVersion)) {
            return;
        }

        /** @var PdkSettingsRepositoryInterface $settingsRepository */
        $settingsRepository = Pdk::get(PdkSettingsRepositoryInterface::class);
        /** @var StorageInterface $storage */
        $storage = Pdk::get(StorageInterface::class);

        $storage->delete('settings_all');

        foreach ($this->getLegacyOptionNames() as $legacyName) {
            $this->restoreOption($legacyName, $settingsRepository, $storage);
        }
    }

    /**
     * @return string[]
     */
    private function getLegacyOptionNames(): array
    {
        global $wpdb;

        $query = $wpdb->prepare(
            "SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE %s",
            $wpdb->esc_like(self::LEGACY_PREFIX) . '%'
        );

        if (! is_string($query)) {
            throw new \RuntimeException('Could not prepare legacy option query.');
        }

        $optionNames = $wpdb->get_col($query);

        if (! empty($wpdb->last_error)) {
            throw new \RuntimeException(sprintf('Could not read legacy options: %s', $wpdb->last_error));
        }

        if (! is_array($optionNames)) {
            throw new \RuntimeException('Could not read legacy options.');
        }

        $optionNames = array_values(array_filter($optionNames, 'is_string'));
        $versionKey  = self::LEGACY_PREFIX . 'installed_version';

        usort($optionNames, static function (string $left, string $right) use ($versionKey): int {
            return ((int) ($left === $versionKey)) <=> ((int) ($right === $versionKey));
        });

        return $optionNames;
    }

    private function restoreOption(
        string                         $legacyName,
        PdkSettingsRepositoryInterface $settingsRepository,
        StorageInterface               $storage
    ): void {
        $missing     = new \stdClass();
        $legacyValue = get_option($legacyName, $missing);

        if ($legacyValue === $missing) {
            return;
        }

        $currentName = self::CURRENT_PREFIX . substr($legacyName, strlen(self::LEGACY_PREFIX));

        if (get_option($currentName, $missing) === $missing) {
            $settingsRepository->store($currentName, $legacyValue);

            if (get_option($currentName, $missing) === $missing) {
                throw new \RuntimeException(sprintf('Could not restore option "%s".', $currentName));
            }
        }

        $storage->delete('settings_' . $legacyName);

        if (! delete_option($legacyName) && get_option($legacyName, $missing) !== $missing) {
            throw new \RuntimeException(sprintf('Could not remove legacy option "%s".', $legacyName));
        }
    }

    /**
     * @param  mixed $version
     *
     * @return bool
     */
    private function isV6OrLater($version): bool
    {
        return is_string($version)
            && preg_match('/^(\d+)(?:\.|$)/', $version, $matches)
            && (int) $matches[1] >= 6;
    }
};
