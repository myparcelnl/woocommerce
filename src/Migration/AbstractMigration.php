<?php

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Migration;

use MyParcelNL\Pdk\App\Installer\Contract\MigrationInterface;
use MyParcelNL\Pdk\Facade\Logger;
use WC_Data;

abstract class AbstractMigration implements MigrationInterface
{
    /**
     * @param  string $message
     * @param  array  $context
     *
     * @return void
     */
    protected function debug(string $message, array $context = []): void
    {
        Logger::debug($message, $context + $this->getCommonLogContext());
    }

    /**
     * @param  string $message
     * @param  array  $context
     *
     * @return void
     */
    protected function error(string $message, array $context = []): void
    {
        Logger::error($message, $context + $this->getCommonLogContext());
    }

    /**
     * @param  WC_Data|int $objectOrId
     * @param  string      $key
     * @param  bool        $single
     *
     * @return mixed
     */
    protected function getMeta($objectOrId, string $key = '', bool $single = false)
    {
        $value = get_post_meta($this->getPostId($objectOrId), $key, $single);

        if (is_string($value) && preg_match('/^[{\[]/', $value)) {
            $decoded = json_decode($value, true);
            // json_decode returns null if there was a syntax error, meaning input was not valid JSON.
            $value = $decoded ?? $value;
        }

        return $value;
    }

    /**
     * @param  WC_Data|int $objectOrId
     *
     * @return int
     */
    protected function getPostId($objectOrId): int
    {
        return is_int($objectOrId) ? $objectOrId : $objectOrId->get_id();
    }

    /**
     * @param  string $message
     * @param  array  $context
     *
     * @return void
     */
    protected function info(string $message, array $context = []): void
    {
        Logger::info($message, $context + $this->getCommonLogContext());
    }

    /**
     * Mark an object as migrated by updating the migration meta key with the current version.
     *
     * @param  int|\WC_Data $objectOrId
     *
     * @return void
     */
    protected function markObjectMigrated($objectOrId): void
    {
        $existing = $this->getMeta($objectOrId, 'metaKeyMigrated') ?: [];

        $executedMigrations   = is_array($existing) ? $existing : [];
        $executedMigrations[] = $this->getVersion();

        $this->updateMeta($objectOrId, 'metaKeyMigrated', $executedMigrations);
    }

    /**
     * Update the meta value of an object. Ignores null and encodes non-scalar values as JSON.
     *
     * @param  WC_Data|int  $objectOrId
     * @param  string       $key
     * @param  string|array $value
     *
     * @return void
     */
    protected function updateMeta($objectOrId, string $key, $value): void
    {
        if (null === $value) {
            return;
        }

        update_post_meta($this->getPostId($objectOrId), $key, is_scalar($value) ? $value : json_encode($value));
    }

    /**
     * @param  string $message
     * @param  array  $context
     *
     * @return void
     */
    protected function warning(string $message, array $context = []): void
    {
        Logger::warning($message, $context + $this->getCommonLogContext());
    }

    /**
     * @return string[]
     */
    private function getCommonLogContext(): array
    {
        return ['migration' => static::class];
    }
}
