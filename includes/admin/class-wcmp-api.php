<?php

use MyParcelNL\Sdk\src\Helper\MyParcelCollection;
use WPO\WC\MyParcel\Compatibility\WC_Core;
use WPO\WC\MyParcel\Compatibility\Order as WC_Order;
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
    public $apiUrl = "https://api.myparcel.nl/";

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

        $this->apiUrl    = WCMP_Data::API_URL;
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
        $endpoint = "shipments";

        // define content type
        switch ($type) {
            case "return":
                $content_type = "application/vnd.return_shipment+json";
                $data_key     = "return_shipments";
                break;
            case "unrelated_return":
                $content_type = "application/vnd.unrelated_return_shipment+json";
                $data_key     = "unrelated_return_shipments";
                break;
            default:
                $content_type = "application/vnd.shipment+json";
                $data_key     = "shipments";
                break;
        }

        $data = [
            "data" => [
                $data_key => $shipments,
            ],
        ];

        $json = json_encode($data);

        $headers = [
            "Content-type"  => $content_type . "; charset=UTF-8",
            "Authorization" => "basic " . base64_encode("{$this->key}"),
            "user-agent"    => $this->userAgent,
        ];

        $request_url = $this->apiUrl . $endpoint;

        return $this->post($request_url, $json, $headers);
    }

    /**
     * Get shipments
     *
     * @param int|array $ids
     * @param array     $params request parameters
     *
     * @return array          response
     * @throws Exception
     */
    public function get_shipments($ids, array $params = []): array
    {
        $endpoint = "shipments";

        $headers = [
            "headers" => [
                "Accept"        => "application/json; charset=UTF-8",
                "Authorization" => "basic " . base64_encode("{$this->key}"),
                "user-agent"    => $this->userAgent,
            ],
        ];

        $request_url = $this->apiUrl . $endpoint . "/" . implode(";", (array) $ids);
        $request_url = add_query_arg($params, $request_url);

        return $this->get($request_url, $headers);
    }

    /**
     * Get Wordpress, WooCommerce, MyParcel version and place theme in a array. Implode the array to get an UserAgent.
     *
     * @return string
     */
    private function getUserAgent(): string
    {
        $userAgents = [
            'Wordpress', get_bloginfo('version').
            'WooCommerce/' . WOOCOMMERCE_VERSION .
            'MyParcelNL-WooCommerce/' . WC_MYPARCEL_NL_VERSION,
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
     * @param array $orderIds
     * @param MyParcelCollection $collection
     * @throws Exception
     */
    private function updateOrderBarcode(array $orderIds, MyParcelCollection $collection) : void
    {
        foreach ($orderIds as $orderId) {
            $order           = WC_Core::get_order($orderId);
            $lastShipmentIds = unserialize($order->get_meta('_myparcel_last_shipment_ids'));

            if (is_bool($lastShipmentIds)) {
                continue;
            }

            $shipmentData = (new WCMP_Export())->getShipmentData($lastShipmentIds, $order);
            $trackTrace   = $shipmentData["track_trace"] ?? null;
            ChannelEngine::updateMetaOnExport($order, $trackTrace);
        }

        WCMP_Export::saveTrackTracesToOrders($collection, $orderIds);
    }
}
