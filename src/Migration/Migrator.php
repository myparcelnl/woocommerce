<?php

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Migration;

use MyParcelNL\Pdk\Facade\Pdk;

final class Migrator
{
    /**
     * @param  string $version
     *
     * @return void
     */
    public function migrate(string $version): void
    {
        foreach ($this->getMigrations() as $migrationClass) {
            /** @var \MyParcelNL\WooCommerce\Migration\Contract\MigrationInterface $instance */
            $instance = Pdk::get($migrationClass);

            if (! version_compare($instance->getVersion(), $version, '>')) {
                continue;
            }

            $instance->up();
        }
    }

    /**
     * @return array<class-string<\MyParcelNL\WooCommerce\Migration\Contract\MigrationInterface>>
     */
    protected function getMigrations(): array
    {
        return [
            Migration2_0_0::class,
            Migration2_4_0_beta_4::class,
            Migration3_0_4::class,
            Migration4_0_0::class,
            Migration4_1_0::class,
            Migration4_2_1::class,
            Migration4_4_1::class,
            Migration5_0_0::class,
        ];
    }
}
