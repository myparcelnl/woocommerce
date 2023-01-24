<?php

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Migration;

class Migrator
{
    /**
     * @param  string $version
     *
     * @return void
     */
    public function migrate(string $version)
    {
        $migrations = $this->getMigrations();

        foreach ($migrations as $migration) {
            if (version_compare($migration->getVersion(), $version, '>')) {
                $migration->up();
            }
        }
    }

    /**
     * @return array
     */
    protected function getMigrations(): array
    {
        return [
            new Migration2_0_0(),
            new Migration2_4_0_beta_4(),
            new Migration3_0_4(),
            new Migration4_0_0(),
            new Migration4_1_0(),
            new Migration4_2_1(),
            new Migration4_4_1(),
            new Migration5_0_0(),
        ];
    }
}
