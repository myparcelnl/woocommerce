<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( !class_exists( 'WC_MyParcel_API' ) ) :

class WC_MyParcel_API extends WC_MyParcel_REST_Client {
	/** @var API URL */
	public $APIURL = "https://api.myparcel.nl/";

	/* @var API Key */
	private $key;

	/**
	 * Default constructor
	 *
	 * @param  string  $key     API Key provided by MyParcel
	 * @return void
	 */
	function __construct( $key ) {
		parent::__construct();

		$this->user_agent = $this->getUserAgent();

		$this->key = $key;
	}

	/**
	 * Add shipment
	 * @param array  $shipments array of shipments
	 * @param string $type      shipment type: standard/return/unrelated_return
	 */
	public function add_shipments ( $shipments, $type = 'standard' ) {
		$endpoint = 'shipments';

		// define content type
		switch ($type) {
			case 'standard': default:
				$content_type = 'application/vnd.shipment+json';
				$data_key = 'shipments';
				break;
			case 'return':
				$content_type = 'application/vnd.return_shipment+json';
				$data_key = 'return_shipments';
				break;
			case 'unrelated_return':
				$content_type = 'application/vnd.unrelated_return_shipment+json';
				$data_key = 'unrelated_return_shipments';
				break;
		}

		$data = array(
			'data' => array (
				$data_key => $shipments,
			),
		);

		$json = json_encode( $data );

		$headers = array(
			'Content-type' => $content_type . '; charset=UTF-8',
			'Authorization' => 'basic '. base64_encode("{$this->key}"),
			'user-agent' => $this->user_agent
		);

		$request_url = $this->APIURL . $endpoint;
		$response = $this->post($request_url, $json, $headers);
		return $response;
	}

	/**
	 * Delete Shipment
	 * @param  array  $ids shipment ids
	 * @return array       response
	 */
	public function delete_shipments ( $ids ) {
		$endpoint = 'shipments';

		$headers = array (
			'headers' => array(
				'Accept' => 'application/json; charset=UTF-8',
				'Authorization' => 'basic '. base64_encode("{$this->key}"),
				'user-agent' => $this->user_agent
			)
		);

		$request_url = $this->APIURL . $endpoint . '/' . implode(';', $ids);
		$response = $this->delete($request_url, $headers );

		return $response;
	}

	/**
	 * Unrelated return shipments
	 * @return array       response
	 */
	public function unrelated_return_shipments () {
		$endpoint = 'return_shipments';

		$headers = array (
			'Authorization: basic '. base64_encode("{$this->key}"),
		);

		$request_url = $this->APIURL . $endpoint;
		$response = $this->post($request_url, '', $headers );

		return $response;
	}

	/**
	 * Get shipments
	 * @param  array  $params request parameters
	 * @return array          response
	 */
	public function get_shipments ( $ids, $params = array() ) {
		$endpoint = 'shipments';

		$headers = array (
			'headers' => array(
				'Accept' => 'application/json; charset=UTF-8',
				'Authorization' => 'basic '. base64_encode("{$this->key}"),
				'user-agent' => $this->user_agent
			)
		);

		$request_url = $this->APIURL . $endpoint . '/' . implode(';', (array) $ids);
		$request_url = add_query_arg( $params, $request_url );
		$response = $this->get($request_url, $headers);

		return $response;
	}

	/**
	 * Get shipment labels
	 * @param  array  $ids    shipment ids
	 * @param  array  $params request parameters
	 * @param  string $return pdf or json
	 * @return array          response
	 */
	public function get_shipment_labels ( $ids, $params = array(), $return = 'pdf' ) {
		$endpoint = 'shipment_labels';

		if ( $return == 'pdf' ) {
			$accept = 'application/pdf'; // (For the PDF binary. This is the default.)
			$raw = true;
		} else {
			$accept = 'application/json; charset=UTF-8'; // (For shipment download link)
			$raw = false;
		}

		$headers = array(
			'headers' => array(
				'Accept' => $accept,
				'Authorization' => 'basic '. base64_encode("{$this->key}"),
				'user-agent' => $this->user_agent
			)
		);

		$request_url = add_query_arg( $params, $this->APIURL . $endpoint . '/' . implode(';', $ids) );
		$response = $this->get($request_url, $headers, $raw);
		
		return $response;
	}

	/**
	 * Track shipments
	 * @param  array  $ids    shipment ids
	 * @param  array  $params request parameters
	 * @return array          response
	 */
	public function get_tracktraces ( $ids, $params = array() ) {
		$endpoint = 'tracktraces';

		$headers = array (
			'headers' => array(
				'Authorization' => 'basic '. base64_encode("{$this->key}"),
				'user-agent' => $this->user_agent
			)
		);

		$request_url = add_query_arg( $params, $this->APIURL . $endpoint . '/' . implode(';', $ids) );
		$response = $this->get($request_url, $headers, false);

		return $response;
	}


	/**
	 * Get delivery options
	 * @return array          response
	 */
	public function get_delivery_options ( $params = array(), $raw = false ) {
		$endpoint = 'delivery_options';
		$checkout_settings = WooCommerce_MyParcel()->checkout_settings;
		if (isset(WooCommerce_MyParcel()->checkout_settings['monday_delivery']) ) {
			$params['monday_delivery'] = 1;
		}

		$request_url = add_query_arg( $params, $this->APIURL . $endpoint );
		$response = $this->get($request_url, null, $raw);

		return $response;
	}

	/**
	 * Get Wordpress, Woocommerce, Myparcel version and place theme in a array. Implode the array to get an UserAgent.
	 * @return string
	 */
	private function getUserAgent() {

		$userAgents = array(
			'Wordpress/'.get_bloginfo( 'version' ),
			'WooCommerce/'.WOOCOMMERCE_VERSION,
			'MyParcelNL-WooCommerce/'.WC_MYPARCEL_VERSION,
		);

		//Place white space between the array elements
		$userAgent = implode(' ', $userAgents);

		return $userAgent;
	}


}

endif; // class_exists
