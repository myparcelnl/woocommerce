<?php

use MyParcelNL\Sdk\src\Helper\MyParcelCollection;
use MyParcelNL\Sdk\src\Model\MyParcelRequest;
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
     * @var string
     */
    private $userAgent;

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

        $this->userAgent = $this->getUserAgent();
        $this->key       = (string) $key;
    }

    /**
     * Add shipment
     *
     * @param array  $shipments array of shipments
     * @param string $type      shipment type: standard/return/unrelated_return
     *
     * @return array
     * @throws Exception
     * @deprecated Use MyParcel SDK instead
     */
    public function add_shipments(array $shipments, string $type = "standard"): array
    {
        $endpoint = 'shipments';

        // define content type
        switch ($type) {
            case 'return':
                $contentType = 'application/vnd.return_shipment+json';
                $dataKey     = 'return_shipments';
                break;
            case 'unrelated_return':
                $contentType = 'application/vnd.unrelated_return_shipment+json';
                $dataKey     = 'unrelated_return_shipments';
                break;
            default:
                $contentType = 'application/vnd.shipment+json';
                $dataKey     = 'shipments';
                break;
        }

        $data = [
            'data' => [
                $dataKey => $shipments,
            ],
        ];

        $json = json_encode($data);

        $headers = [
            'Content-type'  => $contentType . '; charset=UTF-8',
            'Authorization' => 'basic ' . base64_encode($this->key),
            'user-agent'    => $this->userAgent,
        ];

        $requestUrl = MyParcelRequest::REQUEST_URL . '/' . $endpoint;

        return $this->post($requestUrl, $json, $headers);
    }

    /**
     * Get shipments
     *
     * @param int|array $ids
     * @param array     $params request parameters
     *
     * @return array            response
     * @throws Exception
     */
    public function get_shipments($ids, array $params = []): array
    {
        $endpoint = 'shipments';

        $headers = [
            'headers' => [
                'Accept'        => 'application/json; charset=UTF-8',
                'Authorization' => 'basic ' . base64_encode($this->key),
                'user-agent'    => $this->userAgent,
            ],
        ];

        $requestUrl = MyParcelRequest::REQUEST_URL . '/' . $endpoint . '/' . implode(';', (array) $ids);
        $requestUrl = add_query_arg($params, $requestUrl);

        return $this->get($requestUrl, $headers);
    }

    /**
     * Get Wordpress, WooCommerce, MyParcel version and place theme in a array. Implode the array to get an UserAgent.
     *
     * @return string
     */
    private function getUserAgent(): string
    {
        $userAgents = [
            'Wordpress',
            get_bloginfo('version')
            . 'WooCommerce/'
            . WOOCOMMERCE_VERSION
            . 'MyParcelNL-WooCommerce/'
            . WC_MYPARCEL_NL_VERSION,
        ];

        // Place white space between the array elements
        return implode(" ", $userAgents);
    }

    /**
     * Get shipment labels, save them to the orders before showing them.
     *
     * @param array $shipment_ids Shipment ids.
     * @param array $order_ids
     * @param array $positions    Print position(s).
     * @param bool  $display      Download or display.
     *
     * @throws Exception
     */
    public function getShipmentLabels(array $shipment_ids, array $order_ids, array $positions = [], $display = true)
    {
        $collection = MyParcelCollection::findMany($shipment_ids, $this->key);

        /**
         * @see https://github.com/MyParcelNL/Sdk#label-format-and-position
         */
        if (WCMYPA()->setting_collection->getByName(WCMYPA_Settings::SETTING_LABEL_FORMAT) === "A6") {
            $positions = false;
        }

        if ($display) {
            $collection->setPdfOfLabels($positions);
            $this->updateOrderBarcode($order_ids, $collection);
            $collection->downloadPdfOfLabels($display);
        }

        if (! $display) {
            $collection->setLinkOfLabels($positions);
            $this->updateOrderBarcode($order_ids, $collection);
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
        $statusAutomation     = WCMYPA()->setting_collection->isEnabled(WCMYPA_Settings::SETTING_ORDER_STATUS_AUTOMATION);
        $momentOfStatusChange = WCMYPA()->setting_collection->getByName(WCMYPA_Settings::SETTING_CHANGE_ORDER_STATUS_AFTER);
        $newStatus            = WCMYPA()->setting_collection->getByName(WCMYPA_Settings::SETTING_AUTOMATIC_ORDER_STATUS);

        if ($statusAutomation && (! $thisMoment || $thisMoment === $momentOfStatusChange)) {
            $order->update_status(
                $newStatus,
                __('myparcel_export', 'woocommerce-myparcel')
            );

            WCMP_Log::add("Status of order {$order->get_id()} updated to \"$newStatus\"");
        }
    }

    /**
     * @param array              $orderIds
     * @param MyParcelCollection $collection
     *
     * @throws Exception
     */
    private function updateOrderBarcode(array $orderIds, MyParcelCollection $collection): void
    {
        foreach ($orderIds as $orderId) {
            $order           = WC_Core::get_order($orderId);
            $lastShipmentIds = WCX_Order::get_meta($order, WCMYPA_Admin::META_LAST_SHIPMENT_IDS);

            if (empty($lastShipmentIds)) {
                continue;
            }

            $shipmentData = (new WCMP_Export())->getShipmentData($lastShipmentIds, $order);
            $trackTrace   = $shipmentData['track_trace'] ?? null;

            self::updateOrderStatus($order, WCMP_Settings_Data::CHANGE_STATUS_AFTER_PRINTING);

            ChannelEngine::updateMetaOnExport($order, $trackTrace);
        }

        WCMP_Export::saveTrackTracesToOrders($collection, $orderIds);
    }
}
