<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( !class_exists( 'WC_PostNL_API' ) ) :

class WC_PostNL_API extends WC_PostNL_REST_Client {
	/** @var API URL */
	public $APIURL = "https://api.myparcel.nl/";

	/* @var API Key */
	private $key;

	/**
	 * Default constructor
	 *
	 * @param  string  $key     API Key provided by PostNL
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
		$checkout_settings = WooCommerce_PostNL()->checkout_settings;
		if (isset(WooCommerce_PostNL()->checkout_settings['monday_delivery']) ) {
			$params['monday_delivery'] = 1;
		}

		$request_url = add_query_arg( $params, $this->APIURL . $endpoint );
		$response = $this->get($request_url, null, $raw);

		return $response;
	}

	/**
	 * Get Wordpress, Woocommerce, PostNL version and place theme in a array
	 * @return string
	 */
	private function getUserAgent() {
		$userAgent = [];

		$userAgent[] = 'Wordpress/'.get_bloginfo( 'version' );
		$userAgent[] = 'WooCommerce/'.WOOCOMMERCE_VERSION;
		$userAgent[] = 'PostNL-WooCommerce/'.WC_POSTNL_VERSION;

		//Place white space between the array elements
		$totalUserAgent = (implode(' ', $userAgent));

		return $totalUserAgent;
	}


}

endif; // class_exists
