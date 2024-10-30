<?php

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Migration\Pdk;

use MyParcelNL\WooCommerce\Migration\AbstractMigration;

abstract class AbstractPdkMigration extends AbstractMigration
{
    public function getVersion(): string
    {
        return '5.0.0';
    }
}
