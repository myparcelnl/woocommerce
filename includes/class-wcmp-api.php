<?php

if (! defined('ABSPATH')) {
    exit;
} // Exit if accessed directly

if (class_exists('WCMP_Api')) {
    return;
}

class WCMP_Api extends WCMP_Rest
{
    public  $apiUrl = "https://api.myparcel.nl/";
    private $key;

    /**
     * Default constructor
     *
     * @param string $key API Key provided by MyParcel
     *
     * @throws Exception
     */
    function __construct($key)
    {
        parent::__construct();

        $this->user_agent = $this->getUserAgent();

        $this->key = $key;
    }

    /**
     * Add shipment
     *
     * @param array  $shipments array of shipments
     * @param string $type      shipment type: standard/return/unrelated_return
     *
     * @return array
     * @throws Exception
     */
    public function add_shipments($shipments, $type = 'standard')
    {
        $endpoint = 'shipments';

        // define content type
        switch ($type) {
            case 'standard':
            default:
                $content_type = 'application/vnd.shipment+json';
                $data_key     = 'shipments';
                break;
            case 'return':
                $content_type = 'application/vnd.return_shipment+json';
                $data_key     = 'return_shipments';
                break;
            case 'unrelated_return':
                $content_type = 'application/vnd.unrelated_return_shipment+json';
                $data_key     = 'unrelated_return_shipments';
                break;
        }

        $data = [
            'data' => [
                $data_key => $shipments,
            ],
        ];

        $json = json_encode($data);

        $headers = [
            'Content-type'  => $content_type . '; charset=UTF-8',
            'Authorization' => 'basic ' . base64_encode("{$this->key}"),
            'user-agent'    => $this->user_agent,
        ];

        $request_url = $this->apiUrl . $endpoint;
        $response    = $this->post($request_url, $json, $headers);

        return $response;
    }

    /**
     * Delete Shipment
     *
     * @param array $ids shipment ids
     *
     * @return array       response
     * @throws Exception
     */
    public function delete_shipments($ids)
    {
        $endpoint = 'shipments';

        $headers = [
            'headers' => [
                'Accept'        => 'application/json; charset=UTF-8',
                'Authorization' => 'basic ' . base64_encode("{$this->key}"),
                'user-agent'    => $this->user_agent,
            ],
        ];

        $request_url = $this->apiUrl . $endpoint . '/' . implode(';', $ids);
        $response    = $this->delete($request_url, $headers);

        return $response;
    }

    /**
     * Unrelated return shipments
     *
     * @return array       response
     * @throws Exception
     */
    public function unrelated_return_shipments()
    {
        $endpoint = 'return_shipments';

        $headers = [
            'Authorization: basic ' . base64_encode("{$this->key}"),
        ];

        $request_url = $this->apiUrl . $endpoint;
        $response    = $this->post($request_url, '', $headers);

        return $response;
    }

    /**
     * Get shipments
     *
     * @param       $ids
     * @param array $params request parameters
     *
     * @return array          response
     * @throws Exception
     */
    public function get_shipments($ids, $params = [])
    {
        $endpoint = 'shipments';

        $headers = [
            'headers' => [
                'Accept'        => 'application/json; charset=UTF-8',
                'Authorization' => 'basic ' . base64_encode("{$this->key}"),
                'user-agent'    => $this->user_agent,
            ],
        ];

        $request_url = $this->apiUrl . $endpoint . '/' . implode(';', (array) $ids);
        $request_url = add_query_arg($params, $request_url);
        $response    = $this->get($request_url, $headers);

        return $response;
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
    public function get_shipment_labels($ids, $params = [], $return = 'pdf')
    {
        $endpoint = 'shipment_labels';

        if ($return == 'pdf') {
            $accept = 'application/pdf'; // (For the PDF binary. This is the default.)
            $raw    = true;
        } else {
            $accept = 'application/json; charset=UTF-8'; // (For shipment download link)
            $raw    = false;
        }

        $headers = [
            'headers' => [
                'Accept'        => $accept,
                'Authorization' => 'basic ' . base64_encode("{$this->key}"),
                'user-agent'    => $this->user_agent,
            ],
        ];

        $positions = isset($params['positions']) ? $params['positions'] : null;

        $label_format_url = $this->get_label_format_url($positions);
        $request_url      = $this->apiUrl . $endpoint . '/' . implode(';', $ids) . '?' . $label_format_url;

        $response = $this->get($request_url, $headers, $raw);

        return $response;
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
    public function get_tracktraces($ids, $params = [])
    {
        $endpoint = 'tracktraces';

        $headers = [
            'headers' => [
                'Authorization' => 'basic ' . base64_encode("{$this->key}"),
                'user-agent'    => $this->user_agent,
            ],
        ];

        $request_url = add_query_arg($params, $this->apiUrl . $endpoint . '/' . implode(';', $ids));
        $response    = $this->get($request_url, $headers, false);

        return $response;
    }

    /**
     * Get delivery options
     *
     * @return array          response
     * @throws Exception
     */
    public function get_delivery_options($params = [], $raw = false)
    {
        $endpoint = 'delivery_options';
        if (isset(WooCommerce_MyParcelBE()->bpost_settings['saturday_delivery'])) {
            $params['saturday_delivery'] = 1;
        }

        $request_url = add_query_arg($params, $this->apiUrl . $endpoint);
        $response    = $this->get($request_url, null, $raw);

        return $response;
    }

    /**
     * Get Wordpress, Woocommerce, Myparcel version and place theme in a array. Implode the array to get an UserAgent.
     *
     * @return string
     */
    private function getUserAgent()
    {
        $userAgents = [
            'Wordpress/' . get_bloginfo('version'),
            'WooCommerce/' . WOOCOMMERCE_VERSION,
            'MyParcelBE-WooCommerce/' . WC_MYPARCEL_BE_VERSION,
        ];

        //Place white space between the array elements
        $userAgent = implode(' ', $userAgents);

        return $userAgent;
    }

    private function get_label_format_url($positions)
    {
        $generalSettings = WooCommerce_MyParcelBE()->setting_collection;

        if ($generalSettings['label_format'] == 'A4') {
            return 'format=A4&positions=' . $positions;
        }

        if ($generalSettings['label_format'] == 'A6') {
            return 'format=A6';
        }

        return '';
    }
}
