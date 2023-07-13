<?php

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Service;

use Automattic\WooCommerce\Utilities\OrderUtil;
use MyParcelNL\Pdk\Facade\Pdk;
use MyParcelNL\WooCommerce\Contract\WooCommerceServiceInterface;

/**
 * @see /config/pdk.php
 */
class WooCommerceService implements WooCommerceServiceInterface
{
    /**
     * @return string
     */
    public function getVersion(): string
    {
        return Pdk::get('wooCommerceVersion');
    }

    /**
     * @return bool
     */
    public function isActive(): bool
    {
        return Pdk::get('wooCommerceIsActive');
    }

    /**
     * @return bool
     */
    public function isUsingHpos(): bool
    {
        if (version_compare($this->getVersion(), '7.1.0', '<')) {
            return false;
        }

        return OrderUtil::custom_orders_table_usage_is_enabled();
    }
}
