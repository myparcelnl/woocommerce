<?php

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Migration;

use MyParcelNL\Pdk\App\Installer\Contract\MigrationInterface;
use MyParcelNL\Pdk\Facade\Logger;
use MyParcelNL\Pdk\Facade\Pdk;
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
     * @param  \WC_Data $object
     *
     * @return void
     */
    protected function markMigrated(WC_Data $object): void
    {
        $migratedKey = Pdk::get('metaKeyMigrated');

        $executedMigrations = $object->get_meta($migratedKey) ?: [];

        if (in_array($this->getVersion(), $executedMigrations, true)) {
            return;
        }

        update_post_meta($object->get_id(), $migratedKey, array_merge($executedMigrations, [$this->getVersion()]));
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
