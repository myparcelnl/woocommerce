<?php

declare(strict_types=1);

use MyParcelNL\WooCommerce\includes\adapter\PdkOrderCollectionFromWCOrdersAdapter;
use WPO\WC\MyParcel\Compatibility\Order as WCX_Order;
use WPO\WC\MyParcel\Compatibility\WC_Core;

if (! defined("ABSPATH")) {
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
        $statusAutomation     = WCMYPA()->settingCollection->isEnabled(WCMYPA_Settings::SETTING_ORDER_STATUS_AUTOMATION);
        $momentOfStatusChange = WCMYPA()->settingCollection->getByName(WCMYPA_Settings::SETTING_CHANGE_ORDER_STATUS_AFTER);
        $newStatus            = WCMYPA()->settingCollection->getByName(WCMYPA_Settings::SETTING_AUTOMATIC_ORDER_STATUS);

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
    private function updateOrderBarcode(array $orderIds): void
    {
        foreach ($orderIds as $orderId) {
            $order           = WC_Core::get_order($orderId);
            $lastShipmentIds = WCX_Order::get_meta($order, WCMYPA_Admin::META_LAST_SHIPMENT_IDS);

            if (empty($lastShipmentIds)) {
                continue;
            }

            $trackTraceArray = $this->getTrackTraceForOrder($lastShipmentIds, $order);

            ExportActions::addTrackTraceNoteToOrder($orderId, $trackTraceArray);

            self::updateOrderStatus($order, WCMP_Settings_Data::CHANGE_STATUS_AFTER_PRINTING);
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
        $pdkOrderCollection = (new PdkOrderCollectionFromWCOrdersAdapter($lastShipmentIds))->convert();
        $shipments = $pdkOrderCollection->generateShipments();
        $shipmentData       = (new ExportActions())->getShipmentData($shipments, $order);
        $trackTraceArray    = [];

        foreach ($shipmentData as $shipment) {
            $trackTraceArray[] = $shipment['track_trace'] ?? null;
        }

        return $trackTraceArray;
    }
}
