<?php

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\includes\admin;

use ExportActions;
use MyParcelNL\Pdk\Facade\Pdk;
use MyParcelNL\WooCommerce\Pdk\Plugin\Repository\PdkOrderRepository;
use WC_Order;
use WCMP_Log;

if (! defined('ABSPATH')) {
    exit;
} // Exit if accessed directly

if (class_exists('OrderStatus')) {
    return;
}

class OrderStatus
{
    /**
     * Update the status of given order based on the automatic order status settings.
     *
     * @param WC_Order $order
     * @param string   $thisMoment
     */
    public static function updateOrderStatus(WC_Order $order, string $thisMoment = ''): void
    {
        $statusAutomation     = WCMYPA()->settingCollection->isEnabled('order_status_automation');
        $momentOfStatusChange = WCMYPA()->settingCollection->getByName('change_order_status_after');
        $newStatus            = WCMYPA()->settingCollection->getByName('automatic_order_status');

        if ($statusAutomation && (! $thisMoment || $thisMoment === $momentOfStatusChange)) {
            $order->update_status(
                $newStatus,
                __('myparcel_export', 'woocommerce-myparcel')
            );

            WCMP_Log::add("Status of order {$order->get_id()} updated to \"$newStatus\"");
        }
    }

    /**
     * @param  array $orderIds
     *
     * @throws \JsonException|\MyParcelNL\Pdk\Base\Exception\InvalidCastException
     */
    public function updateOrderBarcode(array $orderIds): void
    {
        $orderRepository = Pdk::get(PdkOrderRepository::class);

        foreach ($orderIds as $orderId) {
            /** @var \MyParcelNL\Pdk\Plugin\Model\PdkOrder $order */
            $order = $orderRepository->get($orderId);
            $shipments = $order->shipments;

            if ($shipments->isEmpty()) {
                continue;
            }

            self::updateOrderStatus(wc_get_order($orderId), 'after_printing');

            $trackTraceArray = $shipments->pluck('barcode')->last();

            if ($trackTraceArray) {
                return;
            }

            ExportActions::addTrackTraceNoteToOrder((int) $orderId, (array) $trackTraceArray);
        }
    }

    /**
     * @param  array     $lastShipmentIds
     * @param  \WC_Order $order
     *
     * @return array
     * @throws \JsonException|\MyParcelNL\Pdk\Base\Exception\InvalidCastException
     * @throws \Exception
     */
    private function getTrackTraceForOrder(array $lastShipmentIds, WC_Order $order): array
    {
        $shipmentData       = (new ExportActions())->getShipmentData($lastShipmentIds, $order);
        $trackTraceArray    = [];

        foreach ($shipmentData as $shipment) {
            $trackTraceArray[] = $shipment['barcode'] ?? null;
        }

        return $trackTraceArray;
    }
}
