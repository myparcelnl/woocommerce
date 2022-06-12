<?php

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\includes\admin;

use MyParcelNL\Pdk\Concerns\PdkConfigInterface;
use MyParcelNL\WooCommerce\includes\admin\settings\StorageAccess;

class MyParcelPdkConfig implements PdkConfigInterface
{
    /**
     * @return string
     */
    public function getStorageClass(): string
    {
        return StorageAccess::class;
    }
}
