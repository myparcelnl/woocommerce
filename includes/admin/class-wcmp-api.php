<?php

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
    private $user_agent;

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

        $this->user_agent = $this->getUserAgent();

        $this->key = (string) $key;
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
            "user-agent"    => $this->user_agent,
        ];

        $request_url = $this->apiUrl . $endpoint;

        return $this->post($request_url, $json, $headers);
    }

    /**
     * Delete Shipment
     *
     * @param array $ids shipment ids
     *
     * @return array       response
     * @throws Exception
     */
    public function delete_shipments(array $ids): array
    {
        $endpoint = "shipments";

        $headers = [
            "headers" => [
                "Accept"        => "application/json; charset=UTF-8",
                "Authorization" => "basic " . base64_encode("{$this->key}"),
                "user-agent"    => $this->user_agent,
            ],
        ];

        $request_url = $this->apiUrl . $endpoint . "/" . implode(";", $ids);
        return $this->delete($request_url, $headers);
    }

    /**
     * Unrelated return shipments
     *
     * @return array       response
     * @throws Exception
     */
    public function unrelated_return_shipments(): array
    {
        $endpoint = "return_shipments";

        $headers = [
            "Authorization: basic " . base64_encode("{$this->key}"),
        ];

        $request_url = $this->apiUrl . $endpoint;
        return $this->post($request_url, "", $headers);
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
                "user-agent"    => $this->user_agent,
            ],
        ];

        $request_url = $this->apiUrl . $endpoint . "/" . implode(";", (array) $ids);
        $request_url = add_query_arg($params, $request_url);

        return $this->get($request_url, $headers);
    }

    /**
     * Get shipment labels
     *
     * @param array  $ids    shipment ids
     * @param array  $params request parameters
     * @param string $return pdf or json
     *
     * @return array          response
     * @throws Exception
     */
    public function get_shipment_labels(array $ids, array $params = [], string $return = "pdf"): array
    {
        $endpoint = "shipment_labels";

        if ($return == "pdf") {
            $accept = "application/pdf"; // (For the PDF binary. This is the default.)
            $raw    = true;
        } else {
            $accept = "application/json; charset=UTF-8"; // (For shipment download link)
            $raw    = false;
        }

        $headers = [
            "headers" => [
                "Accept"        => $accept,
                "Authorization" => "basic " . base64_encode("{$this->key}"),
                "user-agent"    => $this->user_agent,
            ],
        ];

        $positions = isset($params["positions"]) ? $params["positions"] : null;

        $label_format_url = $this->get_label_format_url_parameters($positions);
        $request_url      = $this->apiUrl . $endpoint . "/" . implode(";", $ids) . "?" . $label_format_url;

        return $this->get($request_url, $headers, $raw);
    }

    /**
     * Track shipments
     *
     * @param array $ids    shipment ids
     * @param array $params request parameters
     *
     * @return array          response
     * @throws Exception
     */
    public function get_tracktraces(array $ids, array $params = []): array
    {
        $endpoint = "tracktraces";

        $headers = [
            "headers" => [
                "Authorization" => "basic " . base64_encode($this->key),
                "user-agent"    => $this->user_agent,
            ],
        ];

        $request_url = add_query_arg($params, $this->apiUrl . $endpoint . "/" . implode(";", $ids));
        return $this->get($request_url, $headers, false);
    }

    /**
     * Get delivery options
     *
     * @param array $params
     * @param bool  $raw
     *
     * @return array          response
     * @throws Exception
     */
    public function get_delivery_options(array $params = [], bool $raw = false): array
    {
        $endpoint = "delivery_options";

        if (WCMP()->setting_collection->isEnabled(WCMP_Settings::SETTING_CARRIER_SATURDAY_DELIVERY_ENABLED)) {
            $params["saturday_delivery"] = 1;
        }

        $request_url = add_query_arg($params, $this->apiUrl . $endpoint);

        return $this->get($request_url, null, $raw);
    }

    /**
     * Get Wordpress, WooCommerce, MyParcel version and place theme in a array. Implode the array to get an UserAgent.
     *
     * @return string
     */
    private function getUserAgent(): string
    {
        $userAgents = [
            "Wordpress/" . get_bloginfo("version"),
            "WooCommerce/" . WOOCOMMERCE_VERSION,
            "MyParcelBE-WooCommerce/" . WC_MYPARCEL_BE_VERSION,
        ];

        // Place white space between the array elements
        return implode(" ", $userAgents);
    }

    /**
     * @param $positions
     *
     * @return string
     */
    private function get_label_format_url_parameters($positions): string
    {
        $labelFormat = WCMP()->setting_collection->getByName(WCMP_Settings::SETTING_LABEL_FORMAT);

        switch ($labelFormat) {
            case "A4":
                $value = "format=A4&positions=" . $positions;
                break;
            case "A6":
                $value = "format=A6";
                break;
            default:
                $value = "";
                break;
        }

        return $value;
    }
}
