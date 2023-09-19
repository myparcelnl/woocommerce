<?php

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Pdk\Plugin\Service;

use MyParcelNL\Pdk\App\Order\Contract\OrderStatusServiceInterface;

class WcStatusService implements OrderStatusServiceInterface
{
    public function all(): array
    {
        return wc_get_order_statuses();
    }

    public function updateStatus(array $orderIds, string $status)
    {
        foreach ($orderIds as $orderId) {
            $order = wc_get_order($orderId);

            if (false === $order) {
                continue;
            }

            $order->update_status($status);
        }
    }
}
