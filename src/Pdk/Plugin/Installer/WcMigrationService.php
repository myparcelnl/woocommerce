<?php

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Pdk\Plugin\Installer;

use MyParcelNL\Pdk\App\Installer\Contract\MigrationServiceInterface;
use MyParcelNL\WooCommerce\Migration\Migration2_0_0;
use MyParcelNL\WooCommerce\Migration\Migration2_4_0_beta_4;
use MyParcelNL\WooCommerce\Migration\Migration3_0_4;
use MyParcelNL\WooCommerce\Migration\Migration4_0_0;
use MyParcelNL\WooCommerce\Migration\Migration4_1_0;
use MyParcelNL\WooCommerce\Migration\Migration4_2_1;
use MyParcelNL\WooCommerce\Migration\Migration4_4_1;
use MyParcelNL\WooCommerce\Migration\Migration5_0_0;

final class WcMigrationService implements MigrationServiceInterface
{
    public function all(): array
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
