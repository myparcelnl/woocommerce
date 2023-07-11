<?php

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Service;

use Automattic\WooCommerce\Utilities\OrderUtil;
use MyParcelNL\WooCommerce\Contract\WcFeatureServiceInterface;

class WcFeatureService implements WcFeatureServiceInterface
{
    /**
     * @return bool
     */
    public function isUsingHpos(): bool
    {
        return OrderUtil::custom_orders_table_usage_is_enabled();
    }
}
