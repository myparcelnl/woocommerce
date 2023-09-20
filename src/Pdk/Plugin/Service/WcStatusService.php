<?php

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Pdk\Plugin\Service;

use MyParcelNL\Pdk\App\Order\Contract\OrderStatusServiceInterface;

class WcStatusService implements OrderStatusServiceInterface
{
    /**
     * @return array<string, string>
     */
    public function all(): array
    {
        return wc_get_order_statuses();
    }

    /**
     * @param  array  $orderIds
     * @param  string $status
     *
     * @return void
     */
    public function updateStatus(array $orderIds, string $status): void
    {
        foreach ($orderIds as $orderId) {
            $order = wc_get_order($orderId);

            if (! $order) {
                continue;
            }

            $order->update_status($status);
        }
    }
}
