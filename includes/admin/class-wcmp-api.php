<?php

use MyParcelNL\Sdk\src\Helper\MyParcelCollection;
use MyParcelNL\Sdk\src\Model\MyParcelRequest;
use MyParcelNL\WooCommerce\includes\adapter\PdkOrderCollectionFromWCOrdersAdapter;
use WPO\WC\MyParcel\Compatibility\Order as WCX_Order;
use WPO\WC\MyParcel\Compatibility\WC_Core;
use WPO\WC\MyParcel\Compatibility\WCMP_ChannelEngine_Compatibility as ChannelEngine;

if (! defined("ABSPATH")) {
    exit;
} // Exit if accessed directly

if (class_exists('WCMP_API')) {
    return;
}

class WCMP_API extends WCMP_Rest
{
    /**
     * @var string
     */
    private $key;

    /**
     * Default constructor
     *
     * @param string $key API Key provided by MyParcel
     *
     * @throws Exception
     */
    public function __construct($key)
    {
        parent::__construct();

        $this->key       = (string) $key;
    }

    /**
     * Get shipment labels, save them to the orders before showing them.
     *
     * @param array $shipment_ids Shipment ids.
     * @param array $order_ids
     * @param array $positions    Print position(s).
     * @param  bool $display      Download or display.
     *
     * @throws Exception
     */
    public function getShipmentLabels(array $shipment_ids, array $order_ids, array $positions = [], bool $display = true): void
    {
        $collection = MyParcelCollection::findMany($shipment_ids, $this->key);

        /**
         * @see https://github.com/MyParcelNL/Sdk#label-format-and-position
         */
        if (WCMYPA()->settingCollection->getByName(WCMYPA_Settings::SETTING_LABEL_FORMAT) === "A6") {
            $positions = false;
        }

        if ($display) {
            $collection->setPdfOfLabels($positions);
            $this->updateOrderBarcode($order_ids);
            $collection->downloadPdfOfLabels($display);
        }

        if (! $display) {
            $collection->setLinkOfLabels($positions);
            $this->updateOrderBarcode($order_ids);
            echo $collection->getLinkOfLabels();
            die();
        }
    }

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
     * @throws \JsonException
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

            WCMP_Export::addTrackTraceNoteToOrder($orderId, $trackTraceArray);

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
        $shipmentData       = (new WCMP_Export())->getShipmentData($pdkOrderCollection->generateShipments(), $order);
        $trackTraceArray    = [];

        foreach ($shipmentData as $shipment) {
            $trackTraceArray[] = $shipment['track_trace'] ?? null;
        }

        return $trackTraceArray;
    }
}
