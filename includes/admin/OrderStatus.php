<?php

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\includes\admin;

use ExportActions;
use MyParcelNL\Pdk\Facade\Pdk;
use MyParcelNL\WooCommerce\includes\adapter\PdkOrderCollectionFromWCOrdersAdapter;
use MyParcelNL\WooCommerce\PdkOrderRepository;
use WC_Order;
use WCMP_Log;
use WCMYPA_Admin;
use WCMYPA_Settings;
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
    public function updateOrderBarcode(array $orderIds): void
    {
        foreach ($orderIds as $orderId) {
            $order           = WC_Core::get_order($orderId);
            $lastShipmentIds = WCX_Order::get_meta($order, WCMYPA_Admin::META_LAST_SHIPMENT_IDS);

            if (empty($lastShipmentIds)) {
                continue;
            }

            $trackTraceArray = $this->getTrackTraceForOrder($lastShipmentIds, $order);

            ExportActions::addTrackTraceNoteToOrder((int) $orderId, $trackTraceArray);

//            self::updateOrderStatus($order, WCMP_Settings_Data::CHANGE_STATUS_AFTER_PRINTING);
            self::updateOrderStatus($order, 'after_printing');
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
