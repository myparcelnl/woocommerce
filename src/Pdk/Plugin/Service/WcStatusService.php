<?php

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Pdk\Plugin\Service;

use MyParcelNL\Pdk\Plugin\Contract\OrderStatusServiceInterface;

class WcStatusService implements OrderStatusServiceInterface
{
    public function all(): array
    {
        return wc_get_order_statuses();
    }
}
