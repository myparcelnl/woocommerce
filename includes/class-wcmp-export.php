<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( !class_exists( 'WooCommerce_MyParcel_Export' ) ) :

class WooCommerce_MyParcel_Export {
	public $order_id;
	public $success;
	public $errors;

	/**
	 * Construct.
	 */
			
	public function __construct() {
		$this->success = array();
		$this->errors = array();

		include( 'class-wcmp-rest.php' );
		include( 'class-wcmp-api.php' );

		add_action( 'admin_notices', array( $this, 'admin_notices' ) );
		add_action( 'wp_ajax_wc_myparcel', array($this, 'export' ));
		add_action( 'wp_ajax_wc_myparcel_frontend', array($this, 'frontend_api_request' ));
		add_action( 'wp_ajax_nopriv_wc_myparcel_frontend', array($this, 'frontend_api_request' ));
	}

	public function admin_notices () {
		if ( isset($_GET['myparcel_done']) ) { // only do this when for the user that initiated this
			$action_return = get_option( 'wcmyparcel_admin_notices' );
			$success_ids = get_option( 'wcmyparcel_print_queue', array() );
			if (!empty($action_return)) {
				foreach ($action_return as $type => $message) {
					if (in_array($type, array('success','error'))) {
						if ( $type == 'success' && !empty($success_ids) ) {
							$print_queue = sprintf('<input type="hidden" value="%s" id="wcmp_printqueue">', json_encode(array_keys($success_ids)));
							// dequeue
							delete_option( 'wcmyparcel_print_queue' );
						}
						printf('<div class="myparcel_notice notice notice-%s"><p>%s</p>%s</div>', $type, $message, isset($print_queue)?$print_queue:'');
					}
				}
				// destroy after reading
				delete_option( 'wcmyparcel_admin_notices' );
			}
		}

		if (isset($_GET['myparcel'])) {
			switch ($_GET['myparcel']) {
				case 'no_consignments':
					$message = __('You have to export the orders to MyParcel before you can print the labels!', 'woocommerce-myparcel');
					printf('<div class="myparcel_notice notice notice-error"><p>%s</p></div>', $message);
					break;
				default:
					break;
			}
		}
	}

	/**
	 * Export selected orders
	 *
	 * @access public
	 * @return void
	 */
	public function export() {
		// Check the nonce
		check_ajax_referer( 'wc_myparcel', 'security' );

		if( ! is_user_logged_in() ) {
			wp_die( __( 'You do not have sufficient permissions to access this page.', 'woocommerce-myparcel' ) );
		}

		$return = array();

		// Check the user privileges (maybe use order ids for filter?)
		if( apply_filters( 'wc_myparcel_check_privs', !current_user_can( 'manage_woocommerce_orders' ) && !current_user_can( 'edit_shop_orders' ) ) ) {
			$return['error'] = __( 'You do not have sufficient permissions to access this page.', 'woocommerce-myparcel' );
			$json = json_encode( $return );
			echo $json;
			die();
		}

		extract($_REQUEST); // $request, $order_ids, ...
		// make sure $order_ids is a proper array
		$order_ids = !empty($order_ids) ? $this->sanitize_order_ids($order_ids) : array();

		switch($request) {
			case 'add_shipments':
				if ( empty($order_ids) ) {
					$this->errors[] = __( 'You have not selected any orders!', 'woocommerce-myparcel' );
					break;
				}
				$order_ids = $this->filter_eu_orders( $order_ids );
				// if we're going to print directly, we need to process the orders first, regardless of the settings
				$process = (isset($print) && $print == 'yes') ? true : false;
				$return = $this->add_shipments( $order_ids );
				break;
			case 'add_return':
				if ( empty($myparcel_options) ) {
					$this->errors[] = __( 'You have not selected any orders!', 'woocommerce-myparcel' );
					break;
				}
				$return = $this->add_return( $myparcel_options );
				break;
			case 'get_labels':
				if ( empty($order_ids) ) {
					$this->errors[] = __( 'You have not selected any orders!', 'woocommerce-myparcel' );
					break;
				}
				$order_ids = $this->filter_eu_orders( $order_ids );
				$label_response_type = isset($label_response_type) ? $label_response_type : NULL;
				$return = $this->get_labels( $order_ids, $label_response_type );
				break;
			case 'modal_dialog':
				if ( empty($order_ids) ) {
					$errors[] = __( 'You have not selected any orders!', 'woocommerce-myparcel' );
					break;
				}
				$order_ids = $this->filter_eu_orders( $order_ids );
				$this->modal_dialog( $order_ids, $dialog );
				break;
		}

		// display errors directly if PDF requested or modal
		if ( in_array($request, array('add_return','get_labels','modal_dialog')) && !empty($this->errors) ) {
			echo $this->parse_errors( $this->errors );
			die();
		}		

		// format errors for html output
		if (!empty($this->errors)) {
			$return['error'] = $this->parse_errors( $this->errors );
		}

		// When adding shipments, store $return for use in admin_notice
		// This way we can refresh the page (JS) to show all new buttons
		if ($request == 'add_shipments' && !empty($print) && ($print == 'no'|| $print == 'after_reload')) {
			update_option( 'wcmyparcel_admin_notices', $return );
			if ($print == 'after_reload') {
				update_option( 'wcmyparcel_print_queue', $return['success_ids'] );
			}
		}

		// if we're directed here from modal, show proper result page
		if (isset($modal)) {
			$this->modal_success_page( $request, $return );
		} else {
			// return JSON response
			echo json_encode( $return );
		}

		die();
	}

	public function sanitize_order_ids($order_ids) {
		// check for JSON
		if (is_string($order_ids) && strpos($order_ids, '[') !== false ) {
			$order_ids = json_decode(stripslashes($order_ids));
		}

		// cast as array for single exports
		$order_ids = (array) $order_ids;

		return $order_ids;
	}

	public function add_shipments( $order_ids, $process = false ) {
		$return = array();

		$this->log("*** Creating shipments started ***");

		foreach ($order_ids as $order_id) {
			$shipments = $this->get_order_shipment_data( (array) $order_id );

			$this->log("Shipment data for order {$order_id}:\n".var_export($shipments, true));

			// check colli amount
			$extra_params = get_post_meta( $order_id, '_myparcel_shipment_options_extra', true );
			$colli_amount = isset($extra_params['colli_amount']) ? $extra_params['colli_amount'] : 1;

			for ($i=0; $i < intval($colli_amount); $i++) {
				try {
					$api = $this->init_api();
					$response = $api->add_shipments( $shipments );
					$this->log("API response (order {$order_id}):\n".var_export($response, true));
					// echo '<pre>';var_dump($response);echo '</pre>';die();
					if (isset($response['body']['data']['ids'])) {
						$ids = array_shift($response['body']['data']['ids']);
						$shipment_id = $ids['id'];
						$this->success[$order_id] = $shipment_id;

						$shipment = array (
							'shipment_id' => $shipment_id,
						);

						// save shipment data in order meta
						$this->save_shipment_data( $order_id, $shipment );

						// process directly setting
						if ( isset(WooCommerce_MyParcel()->general_settings['process_directly']) || $process === true ) {
							$order = $this->get_order( $order_id );
							$this->get_labels( (array) $order_id, 'url' );
							$this->get_shipment_data( $shipment_id, $order );
						}

						// status automation
						if ( isset(WooCommerce_MyParcel()->general_settings['order_status_automation']) && !empty(WooCommerce_MyParcel()->general_settings['automatic_order_status']) ) {
							$order = $this->get_order( $order_id );
							$order->update_status( WooCommerce_MyParcel()->general_settings['automatic_order_status'], __( 'MyParcel shipment created:', 'woocommerce-myparcel' ) );
						}
					} else {
						$this->errors[$order_id] = __( 'Unknown error', 'woocommerce-myparcel' );
					}
				} catch (Exception $e) {
					$this->errors[$order_id] = $e->getMessage();
				}
			}					
		}
		// echo '<pre>';var_dump($this->success);echo '</pre>';die();
		if (!empty($this->success)) {
			$return['success'] = sprintf(__( '%s shipments successfully exported to Myparcel', 'woocommerce-myparcel' ), count($this->success));
			$return['success_ids'] = $this->success;
		}

		return $return;
	}

	public function add_return( $myparcel_options ) {
		$return = array();

		$this->log("*** Creating return shipments started ***");

		foreach ($myparcel_options as $order_id => $options) {
			$return_shipments = array( $this->prepare_return_shipment_data( $order_id, $options ) );
			$this->log("Return shipment data for order {$order_id}:\n".var_export($return_shipments, true));
			// echo '<pre>';var_dump($return_shipment);echo '</pre>';die();

			try {
				$api = $this->init_api();
				$response = $api->add_shipments( $return_shipments, 'return' );
				$this->log("API response (order {$order_id}):\n".var_export($response, true));
				// echo '<pre>';var_dump($response);echo '</pre>';die();
				if (isset($response['body']['data']['ids'])) {
					$ids = array_shift($response['body']['data']['ids']);
					$shipment_id = $ids['id'];
					$this->success[$order_id] = $shipment_id;

					$shipment = array (
						'shipment_id' => $shipment_id,
					);

					// save shipment data in order meta
					$this->save_shipment_data( $order_id, $shipment );

				} else {
					$this->errors[$order_id] = __( 'Unknown error', 'woocommerce-myparcel' );
				}
			} catch (Exception $e) {
				$this->errors[$order_id] = $e->getMessage();
			}
			
		}
		// echo '<pre>';var_dump($success);echo '</pre>';die();

		return $return;
	}

	public function get_labels( $order_ids, $label_response_type = NULL ) {
		$return = array();

		$shipment_ids = $this->get_shipment_ids( $order_ids, array( 'only_last' => true ) );

		if ( empty($shipment_ids) ) {
			$this->log("*** Failed label request (not exported yet) ***");
			$this->errors[] = __( 'The selected orders have not been exported to MyParcel yet!', 'woocommerce-myparcel' );
			return $return;
		}

		$this->log("*** Label request started ***");
		$this->log("Shipment ID's: ".implode(', ', $shipment_ids));

		try {
			$api = $this->init_api();
			$params = array();
			

			if (isset($label_response_type) && $label_response_type == 'url') {
				$response = $api->get_shipment_labels( $shipment_ids, $params, 'link' );
				$this->log("API response:\n".var_export($response, true));
				// var_dump( $response );
				if (isset($response['body']['data']['pdfs']['url'])) {
					$url = untrailingslashit( $api->APIURL ) . $response['body']['data']['pdfs']['url'];
					$return['url'] = $url;
				} else {
					$this->errors[] = __( 'Unknown error', 'woocommerce-myparcel' );
				}
			} else {
				$response = $api->get_shipment_labels( $shipment_ids, $params, 'pdf' );

				if (isset($response['body'])) {
					$this->log("PDF data received");
					$pdf_data = $response['body'];
					$output_mode = isset(WooCommerce_MyParcel()->general_settings['download_display'])?WooCommerce_MyParcel()->general_settings['download_display']:'';
					if ( $output_mode == 'display' ) {
						$this->stream_pdf( $pdf_data, $order_ids );
					} else {
						$this->download_pdf( $pdf_data, $order_ids );
					}
				} else {
					$this->log("Unknown error, API response:\n".var_export($response, true));
					$this->errors[] = __( 'Unknown error', 'woocommerce-myparcel' );
				}

				// echo '<pre>';var_dump($response);echo '</pre>';die();
			}
		} catch (Exception $e) {
			$this->errors[] = $e->getMessage();
		}

		return $return;
	}

	public function modal_dialog( $order_ids, $dialog ) {
		// check for JSON
		if (is_string($order_ids) && strpos($order_ids, '[') !== false ) {
			$order_ids = json_decode(stripslashes($order_ids));
		}

		// cast as array for single exports
		$order_ids = (array) $order_ids;

		include('views/wcmp-bulk-options-form.php');
		die();
	}

	public function modal_success_page( $request, $result ) {
		include('views/wcmp-modal-result-page.php');
		die();
	}

	public function frontend_api_request() {
		// TODO: check nonce
		$params = $_REQUEST;

		// filter non API params
		$api_param_keys = array(
			'cc',
			'postal_code',
			'number',
			'carrier',
			'delivery_time',
			'delivery_date',
			'cutoff_time',
			'dropoff_days',
			'dropoff_delay',
			'deliverydays_window',
			'exclude_delivery_type',
		);
		foreach ($params as $key => $value) {
			if (!in_array($key, $api_param_keys)) {
				unset($params[$key]);
			}
		}

		$api = $this->init_api();
		$response = $api->get_delivery_options( $params, true );

		@header('Content-type: application/json; charset=utf-8');

		echo $response['body'];
		die();
	}

	public function init_api () {
		// $user = WooCommerce_MyParcel()->general_settings['api_username'];
		if ( !isset(WooCommerce_MyParcel()->general_settings['api_key']) ) {
			return false;
		}

		$key = WooCommerce_MyParcel()->general_settings['api_key'];
		$api = new WC_MyParcel_API( $key );

		return $api;
	}

	public function get_order_shipment_data( $order_ids, $type = 'standard' ) {
		foreach( $order_ids as $order_id ) {
			// get order
			$order = $this->get_order( $order_id );

			$shipment = array(
				'recipient' => $this->get_recipient( $order ),
				'options'	=> $this->get_options( $order ),
				'carrier'	=> 1, // default to POSTNL for now
			);

			if ( $pickup = $this->is_pickup( $order_id ) ) {
				// $pickup_time = array_shift($pickup['time']); // take first element in time array
				$shipment['pickup'] = array(
					'postal_code'	=> $pickup['postal_code'],
					'street'		=> $pickup['street'],
					'city'			=> $pickup['city'],
					'number'		=> $pickup['number'],
					'location_name'	=> $pickup['location'],
				);
			}

			/* disabled for now
			$concept_shipments = $this->get_shipment_ids( (array) $order_id, array( 'only_concepts' => true, 'only_last' => true ) );
			if ( !empty($concept_shipments) ) {
				$shipment['id'] = array_pop($concept_shipments);
			}
			*/

			$shipments[] = $shipment;
		}

		return $shipments;
	}

	public function prepare_return_shipment_data( $order_id, $options ) {
		$order = $this->get_order( $order_id );

		// set name & email
		$return_shipment_data = array(
			'name'			=> trim( $order->shipping_first_name . ' ' . $order->shipping_last_name ),
			'email'			=> isset(WooCommerce_MyParcel()->export_defaults['connect_email']) ? $order->billing_email : '',
			'carrier'		=> 1, // default to POSTNL for now
		);

		// add options if available
		if (!empty($options)) {
			// convert insurance option
			if ( isset($options['insured_amount']) && $options['insured_amount'] != 0 ) {
				$options['insurance'] = array(
					'amount'	=> (int) $options['insured_amount'] * 100,
					'currency'	=> 'EUR',
				);
				unset($options['insured_amount']);
				unset($options['insured']);
			}

			// PREVENT ILLEGAL SETTINGS
			// convert numeric strings to int
			$int_options = array( 'package_type', 'delivery_type', 'only_recipient', 'signature', 'return', 'large_format' );
			foreach ($options as $key => &$value) {
				if ( in_array($key, $int_options) ) {
					$value = (int) $value;
				}
			}

			$return_shipment_data['options'] = $options;
		}

		// get parent
		$shipment_ids = $this->get_shipment_ids( (array) $order_id, array( 'exclude_concepts' => true, 'only_last' => true ) );
		if ( !empty($shipment_ids) ) {
			$return_shipment_data['parent'] = array_pop( $shipment_ids);
		}

		return $return_shipment_data;
	}

	public function get_recipient( $order ) {
		$address = array(
			'cc'			=> $order->shipping_country,
			'city'			=> $order->shipping_city,
			'person'		=> trim( $order->shipping_first_name . ' ' . $order->shipping_last_name ),
			'company'		=> $order->shipping_company,
			'email'			=> isset(WooCommerce_MyParcel()->export_defaults['connect_email']) ? $order->billing_email : '',
			'phone'			=> isset(WooCommerce_MyParcel()->export_defaults['connect_phone']) ? $order->billing_phone : '',
		);


		if ( $order->shipping_country == 'NL' ) {
			// use billing address if old 'pakjegemak' (1.5.6 and older)
			if ( $pgaddress = get_post_meta( $order->id, '_myparcel_pgaddress', true ) ) {
				$address_intl = array(
					'city'			=> $order->billing_city,
					'person'		=> trim( $order->billing_first_name . ' ' . $order->billing_last_name ),
					'company'		=> $order->billing_company,
					'street'		=> $order->billing_street_name,
					'number'		=> $order->billing_house_number,
					'number_suffix' => $order->billing_house_number_suffix,
					'postal_code'	=> $order->billing_postcode,
				);
			} else {
				$address_intl = array(
					'street'		=> $order->shipping_street_name,
					'number'		=> $order->shipping_house_number,
					'number_suffix' => $order->shipping_house_number_suffix,
					'postal_code'	=> $order->shipping_postcode,
				);
			}
		} else {
			$address_intl = array(
				'postal_code'				=> $order->shipping_postcode,
				'street'					=> $order->shipping_address_1,
				'street_additional_info'	=> $order->shipping_address_2,
			);
		}

		$address = array_merge( $address, $address_intl);

		return apply_filters( 'wc_myparcel_recipient', $address, $order );
	}

	public function get_options( $order ) {
		$order_number = $order->get_order_number();

		// parse description
		if (isset(WooCommerce_MyParcel()->export_defaults['label_description'])) {
			$description = str_replace('[ORDER_NR]', $order_number, WooCommerce_MyParcel()->export_defaults['label_description']);
		} else {
			$description = '';
		}

		// determine appropriate package type for this order
		if (isset(WooCommerce_MyParcel()->export_defaults['shipping_methods_package_types'])) {
			// get shipping methods from order
			$order_shipping_methods = $order->get_items('shipping');

			if ( !empty( $order_shipping_methods ) ) {
				// we're taking the first (we're not handling multiple shipping methods as of yet)
				$order_shipping_method = array_shift($order_shipping_methods);
				$order_shipping_method = $order_shipping_method['method_id'];
				$order_shipping_class = $order->myparcel_highest_shipping_class;
				if (empty($order_shipping_class)) {
					$order_shipping_class = $this->get_order_shipping_class( $order, $order_shipping_method );
				}

				if ( strpos($order_shipping_method, ':') !== false ) {
					// means we have method_id:instance_id
					$order_shipping_method = explode(':', $order_shipping_method);
					$order_shipping_method_id = $order_shipping_method[0];
					$order_shipping_method_instance = $order_shipping_method[1];
				} else {
					$order_shipping_method_id = $order_shipping_method;
				}

				// add class if we have one
				if (!empty($order_shipping_class)) {
					$order_shipping_method_id_class = "{$order_shipping_method_id}:{$order_shipping_class}";
				}

				foreach (WooCommerce_MyParcel()->export_defaults['shipping_methods_package_types'] as $package_type_key => $package_type_shipping_methods ) {
					// check if we have a match with the predefined methods
					// fallback to bare method (without class) (if bare method also defined in settings)
					if (in_array($order_shipping_method_id, $package_type_shipping_methods) || (!empty($order_shipping_method_id_class) && in_array($order_shipping_method_id_class, $package_type_shipping_methods))) {
						$package_type = $package_type_key;
						break;
					}
				}
			}
		}
		// fallbacks if no match from previous
		if (!isset($package_type)) {
			if ((isset(WooCommerce_MyParcel()->export_defaults['package_type']))) {
				$package_type = WooCommerce_MyParcel()->export_defaults['package_type'];
			} else {
				$package_type = 1; // 1. package | 2. mailbox package | 3. letter
			}
		}

		// disable mailbox package outside NL
		if ($order->shipping_country != 'NL' && $package_type == 2 ) {
			$package_type = 1;
		}

		// always parcel for Pickup and Pickup express delivery types.
		if ( $this->is_pickup( $order ) ) {
			$package_type = 1;
		}

		// use shipment options from order when available
		$shipment_options = $order->myparcel_shipment_options;
		if (!empty($shipment_options)) {
			$emty_defaults = array(
				'package_type'		=> 1,
				'only_recipient'	=> 0,
				'signature'			=> 0,
				'return'			=> 0,
				'large_format'		=> 0,
				'label_description'	=> '',
				'insured_amount'	=> 0,
			);
			$options = array_merge($emty_defaults, $shipment_options);
		} else {
			if (isset(WooCommerce_MyParcel()->export_defaults['insured']) && WooCommerce_MyParcel()->export_defaults['insured_amount'] == '' && isset(WooCommerce_MyParcel()->export_defaults['insured_amount_custom'])) {
				$insured_amount = WooCommerce_MyParcel()->export_defaults['insured_amount_custom'];
			} elseif (isset(WooCommerce_MyParcel()->export_defaults['insured']) && isset(WooCommerce_MyParcel()->export_defaults['insured_amount'])) {
				$insured_amount = WooCommerce_MyParcel()->export_defaults['insured_amount'];
			} else {
				$insured_amount = 0;
			}

			$options = array(
				'package_type'		=> $package_type,
				'only_recipient'	=> (isset(WooCommerce_MyParcel()->export_defaults['only_recipient'])) ? 1 : 0,
				'signature'			=> (isset(WooCommerce_MyParcel()->export_defaults['signature'])) ? 1 : 0,
				'return'			=> (isset(WooCommerce_MyParcel()->export_defaults['return'])) ? 1 : 0,
				'large_format'		=> (isset(WooCommerce_MyParcel()->export_defaults['large_format'])) ? 1 : 0,
				'label_description'	=> $description,
				'insured_amount'	=> $insured_amount,
			);
		}

		// convert insurance option
		if ( !isset($options['insurance']) && isset($options['insured_amount']) ) {
			if ($options['insured_amount'] > 0) {
				$options['insurance'] = array(
					'amount'	=> (int) $options['insured_amount'] * 100,
					'currency'	=> 'EUR',
				);
			}

			unset($options['insured_amount']);
			unset($options['insured']);
		}

		// set insurance amount to int if already set
		if (isset($options['insurance'])) {
			$options['insurance']['amount'] = (int) $options['insurance']['amount'];
		}

		// remove frontend insurance option values
		if (isset($options['insured_amount'])) {
			unset($options['insured_amount']);
		}
		if (isset($options['insured'])) {
			unset($options['insured']);
		}

		// load delivery options
		$myparcel_delivery_options = $order->myparcel_delivery_options;

		// set delivery type
		$options['delivery_type'] = $this->get_delivery_type( $order, $myparcel_delivery_options );

		// Options for Pickup and Pickup express delivery types:
		// always enable signature on receipt
		if ( $this->is_pickup( $order, $myparcel_delivery_options ) ) {
			$options['signature'] = 1;
		}

		// delivery date (postponed delivery & pickup)
		if ($delivery_date = $this->get_delivery_date( $order, $myparcel_delivery_options ) ) {
			$date_time = explode(' ', $delivery_date); // split date and time
			// only add if date is in the future
			$timestamp = strtotime($date_time[0]);
			if (time() < $timestamp) {
				$options['delivery_date'] = $delivery_date;
			}
		}

		// options signed & recipient only
		if (isset($order->myparcel_signed)) {
			$options['signature'] = 1;
		}
		if (isset($order->myparcel_only_recipient)) {
			$options['only_recipient'] = 1;
		}

		// allow prefiltering consignment data
		$options = apply_filters( 'wc_myparcel_order_shipment_options', $options, $order );

		// PREVENT ILLEGAL SETTINGS
		// convert numeric strings to int
		$int_options = array( 'package_type', 'delivery_type', 'only_recipient', 'signature', 'return', 'large_format' );
		foreach ($options as $key => &$value) {
			if ( in_array($key, $int_options) ) {
				$value = (int) $value;
			}
		}

		// disable options for mailbox package and unpaid letter
		// echo '<pre>';var_dump($package_type);echo '</pre>';die();
		if ( $options['package_type'] != 1 ) {
			$illegal_options = array( 'delivery_type', 'only_recipient', 'signature', 'return', 'large_format', 'insurance', 'delivery_date' );
			foreach ($options as $key => $option) {
				if (in_array($key, $illegal_options)) {
					unset($options[$key]);
				}
			}
		}

		return $options;

	}

	public function get_shipment_ids( $order_ids, $args ) {
		$shipment_ids = array();
		foreach ($order_ids as $order_id) {
			$order_shipments = get_post_meta($order_id,'_myparcel_shipments',true);
			if (!empty($order_shipments)) {
				$order_shipment_ids = array();
				// exclude concepts or only concepts
				foreach ( $order_shipments as $key => $shipment) {
					if (isset($args['exclude_concepts']) && empty($shipment['tracktrace'])) {
						continue;
					}
					if (isset($args['only_concepts']) && !empty($shipment['tracktrace'])) {
						continue;
					}

					$order_shipment_ids[] = $shipment['shipment_id'];
				}

				if (isset($args['only_last'])) {
					$shipment_ids[] = array_pop( $order_shipment_ids );
				} else {
					$shipment_ids[] = array_merge( $shipment_ids, $order_shipment_ids );
				}
			}
		}

		return $shipment_ids;
	}

	public function save_shipment_data ( $order_id, $shipment ) {
		if ( empty($shipment) ) {
			return false;
		}

		$shipments = array();
		$shipments[$shipment['shipment_id']] = $shipment;
		// don't store full shipment data
		// if (isset($shipment['shipment'])) {
			// unset($shipment['shipment']);
		// }

		if ( isset(WooCommerce_MyParcel()->general_settings['keep_shipments']) ) {
			if ( $old_shipments = get_post_meta($order_id,'_myparcel_shipments',true) ) {
				// merging the arrays with the union operator (+) preserves the left hand version
				// when the key exists in both arrays, but we also want to preserve keys and put
				// new shipments AFTER old shipments, so we remove doubles first
				// More intelligent sorting (created/modified date) would be a better solution
				foreach ($shipments as $shipment_id => $shipment) {
					if (isset($old_shipments[$shipment_id])) {
						unset($old_shipments[$shipment_id]);
					}
				}
				$shipments = $old_shipments + $shipments;
			}
		}

		update_post_meta ( $order_id, '_myparcel_shipments', $shipments );

		return;
	}

	public function get_package_types() {
		$package_types = array(
			1	=> __( 'Parcel' , 'woocommerce-myparcel' ),
			2	=> __( 'Mailbox package' , 'woocommerce-myparcel' ),
			3	=> __( 'Unpaid letter' , 'woocommerce-myparcel' ),
		);

		return $package_types;
	}

	public function parse_errors( $errors ) {
		$parsed_errors = array();
		foreach ($errors as $key => $error) {
			// check if we have an order_id
			if ($key > 10) {
				$parsed_errors[] = sprintf("<strong>%s %s:</strong> %s", __( 'Order', 'woocommerce-myparcel' ), $key, $error );
			} else {
				$parsed_errors[] = $error;
			}
		}

		if (count($parsed_errors) == 1) {
			$html = array_shift($parsed_errors);
		} else {
			foreach ($parsed_errors as &$parsed_error) {
				$parsed_error = "<li>{$parsed_error}</li>";
			}
			$html = sprintf("<ul>%s</ul>", implode("\n",$parsed_errors));
		}

		return $html;
	}

	public function stream_pdf ( $pdf_data, $order_ids ) {
		header('Content-type: application/pdf');
		header('Content-Disposition: inline; filename="'.$this->get_filename( $order_ids ).'"');
		echo $pdf_data;
		die();
	}
	public function download_pdf ( $pdf_data, $order_ids ) {
		header('Content-Description: File Transfer');
		header('Content-Type: application/octet-stream');
		header('Content-Disposition: attachment; filename="'.$this->get_filename( $order_ids ).'"'); 
		header('Content-Transfer-Encoding: binary');
		header('Connection: Keep-Alive');
		header('Expires: 0');
		header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
		header('Pragma: public');
		echo $pdf_data;
		die();
	}

	public function get_filename ( $order_ids ) {
		$filename  = 'MyParcel';
		$filename .= '-' . date('Y-m-d') . '.pdf';

		return apply_filters( 'wcmyparcel_filename', $filename, $order_ids );
	}

	public function get_shipment_status_name( $status_code ) {
		$shipment_statuses = array(
			1	=> __('pending - concept', 'woocommerce-myparcel'),
			2	=> __('pending - registered', 'woocommerce-myparcel'),
			3	=> __('enroute - handed to carrier', 'woocommerce-myparcel'),
			4	=> __('enroute - sorting', 'woocommerce-myparcel'),
			5	=> __('enroute - distribution', 'woocommerce-myparcel'),
			6	=> __('enroute - customs', 'woocommerce-myparcel'),
			7	=> __('delivered - at recipient', 'woocommerce-myparcel'),
			8	=> __('delivered - ready for pickup', 'woocommerce-myparcel'),
			9	=> __('delivered - package picked up', 'woocommerce-myparcel'),
			30	=> __('inactive - concept', 'woocommerce-myparcel'),
			31	=> __('inactive - registered', 'woocommerce-myparcel'),
			32	=> __('inactive - enroute - handed to carrier', 'woocommerce-myparcel'),
			33	=> __('inactive - enroute - sorting', 'woocommerce-myparcel'),
			34	=> __('inactive - enroute - distribution', 'woocommerce-myparcel'),
			35	=> __('inactive - enroute - customs', 'woocommerce-myparcel'),
			36	=> __('inactive - delivered - at recipient', 'woocommerce-myparcel'),
			37	=> __('inactive - delivered - ready for pickup', 'woocommerce-myparcel'),
			38	=> __('inactive - delivered - package picked up', 'woocommerce-myparcel'),
			99	=> __('inactive - unknown', 'woocommerce-myparcel'),
		);

		if (isset($shipment_statuses[$status_code])) {
			return $shipment_statuses[$status_code];
		} else {
			return __('Unknown status', 'woocommerce-myparcel');
		}
	}

	public function get_shipment_data( $id, $order ) {
		try {
			$api = $this->init_api();
			$response = $api->get_shipments( $id );
			// echo '<pre>';var_dump($response);echo '</pre>';die();

			if (!empty($response['body']['data']['shipments'])) {
				$shipments = $response['body']['data']['shipments'];
				$shipment = array_shift($shipments);
				// echo '<pre>';var_export($shipment);echo '</pre>';die();

				// if shipment id matches and status is not concept, get tracktrace barcode and status name
				if ( isset($shipment['id']) && $shipment['id'] == $id && $shipment['status'] >= 2 )  {
					$status = $this->get_shipment_status_name( $shipment['status']);
					$tracktrace = $shipment['barcode'];
					$shipment_id = $id;
					$shipment_data = compact( 'shipment_id', 'status', 'tracktrace', 'shipment');
					$this->save_shipment_data( $order->id, $shipment_data );
					return $shipment_data;
				} else {
					return false;
				}

			} else {
				// No shipments found with this ID
				return false;
			}


		} catch (Exception $e) {
			// echo $e->getMessage();
			return false;
		}
	}

	public function get_order( $order_id ) {
		if ( version_compare( WOOCOMMERCE_VERSION, '2.2', '<' ) ) {
			$order = new WC_Order( $order_id );
		} else {
			$order = wc_get_order( $order_id );
		}

		return $order;
	}

	public function get_order_id( $order ) {
		// load order_id if order object passed
		if (is_object($order)) {
			$order_id = $order->id;
		} else {
			$order_id = $order;
		}

		return $order_id;
	}


	public function get_item_display_name ( $item, $order ) {
		// set base name
		$name = $item['name'];

		// add variation name if available
		$product = $order->get_product_from_item( $item );
		if( $product && isset( $item['variation_id'] ) && $item['variation_id'] > 0 && method_exists($product, 'get_variation_attributes')) {
			$name .= woocommerce_get_formatted_variation( $product->get_variation_attributes() );
		}
		
		return $name;
	}

	public function get_parcel_weight ( $order ) {
		$parcel_weight = (isset(WooCommerce_MyParcel()->general_settings['empty_parcel_weight'])) ? preg_replace("/\D/","",WooCommerce_MyParcel()->general_settings['empty_parcel_weight'])/1000 : 0;

		$items = $order->get_items();
		foreach ( $items as $item_id => $item ) {
			$parcel_weight += $this->get_item_weight_kg( $item, $order );
		}

		return $parcel_weight;
	}

	public function get_item_weight_kg ( $item, $order ) {
		$product = $order->get_product_from_item( $item );

		if (empty($product)) {
			return 0;
		}

		$weight = $product->get_weight();
		$weight_unit = get_option( 'woocommerce_weight_unit' );
		switch ($weight_unit) {
			case 'kg':
				$product_weight = $weight;
				break;
			case 'g':
				$product_weight = $weight / 1000;
				break;
			case 'lbs':
				$product_weight = $weight * 0.45359237;
				break;
			case 'oz':
				$product_weight = $weight * 0.0283495231;
				break;
			default:
				$product_weight = $weight;
				break;
		}
	
		$item_weight = $product_weight * $item['qty'];

		return $item_weight;
	}

	public function is_pickup( $order, $myparcel_delivery_options = '' ) {
		$order_id = $this->get_order_id( $order );

		if (empty($myparcel_delivery_options)) {
			$myparcel_delivery_options = get_post_meta( $order_id, '_myparcel_delivery_options', true );
		}
		
		$pickup_types = array( 'retail', 'retailexpress' );
		if ( !empty($myparcel_delivery_options['price_comment']) && in_array($myparcel_delivery_options['price_comment'], $pickup_types) ) {
			return $myparcel_delivery_options;
		}

		// Backwards compatibility for pakjegemak data
		$pgaddress = get_post_meta( $order_id, '_myparcel_pgaddress', true );
		if ( !empty( $pgaddress ) && !empty( $pgaddress['postcode'] ) ) {
			$pickup = array(
				'postal_code'	=> $pgaddress['postcode'],
				'street'		=> $pgaddress['street'],
				'city'			=> $pgaddress['town'],
				'number'		=> $pgaddress['house_number'],
				'location'		=> $pgaddress['name'],
				'price_comment'	=> 'retail',
			);

			return $pickup;
		}

		// no pickup
		return false;
	}

	public function get_delivery_type( $order, $myparcel_delivery_options = '' ) {
		// delivery types
		$delivery_types = array(
			'morning'		=> 1,
			'standard'		=> 2, // 'default in JS API'
			'night'			=> 3,
			'retail'		=> 4, // 'pickup'
			'retailexpress'	=> 5, // 'pickup_express'
		);

		$order_id = $this->get_order_id( $order );

		if (empty($myparcel_delivery_options)) {
			$myparcel_delivery_options = get_post_meta( $order_id, '_myparcel_delivery_options', true );
		}

		// standard = default, overwrite if otpions found
		$delivery_type = 'standard';
		if (!empty($myparcel_delivery_options)) {
			// pickup & pickupexpress store the delivery type in the delivery options,
			// morning & night store it in the time data (...)
			if ( empty($myparcel_delivery_options['price_comment']) && !empty($myparcel_delivery_options['time']) ) {
				// check if we have a price_comment in the time option
				$delivery_time = array_shift($myparcel_delivery_options['time']); // take first element in time array
				if (isset($delivery_time['price_comment'])) {
					$delivery_type = $delivery_time['price_comment'];
				}
			} else {
				$delivery_type = $myparcel_delivery_options['price_comment'];
			}
		}

		// backwards compatibility for pakjegemak
		if ( $pgaddress = get_post_meta( $order_id, '_myparcel_pgaddress', true ) ) {
			$delivery_type = 'retail';
		}

		// convert to int (default to 2 = standard for unknown types)
		$delivery_type = isset($delivery_types[$delivery_type]) ? $delivery_types[$delivery_type] : 2;

		return $delivery_type;
	}

	public function get_delivery_date( $order, $myparcel_delivery_options = '' ) {
		$order_id = $this->get_order_id( $order );

		if (empty($myparcel_delivery_options)) {
			$myparcel_delivery_options = get_post_meta( $order_id, '_myparcel_delivery_options', true );
		}


		if ( !empty($myparcel_delivery_options) && !empty($myparcel_delivery_options['date']) ) {
			$delivery_date = $myparcel_delivery_options['date'];

			$delivery_type = $this->get_delivery_type( $order, $myparcel_delivery_options );
			if ( in_array($delivery_type, array(1,3)) && !empty($myparcel_delivery_options['time']) ) {
				$delivery_time_options = array_shift($myparcel_delivery_options['time']); // take first element in time array
				$delivery_time = $delivery_time_options['start'];
			} else {
				$delivery_time = '00:00:00';
			}
			$delivery_date = "{$delivery_date} {$delivery_time}";
			return $delivery_date;
		} else {
			return false;
		}

	}

	public function get_order_shipping_class($order, $shipping_method_id = '') {
		if (empty($shipping_method_id)) {
			$order_shipping_methods = $order->get_items('shipping');

			if ( !empty( $order_shipping_methods ) ) {
				// we're taking the first (we're not handling multiple shipping methods as of yet)
				$order_shipping_method = array_shift($order_shipping_methods);
				$shipping_method_id = $order_shipping_method['method_id'];
			} else {
				return false;
			}
		}

		$shipping_method = $this->get_shipping_method( $shipping_method_id );
		if (empty($shipping_method)) {
			return false;
		}

		// get shipping classes from order
		$found_shipping_classes = $this->find_order_shipping_classes( $order );

		$highest_class = $this->get_shipping_class( $shipping_method, $found_shipping_classes );
		return $highest_class;

	}

	public function get_shipping_method($chosen_method) {
		if ( version_compare( WOOCOMMERCE_VERSION, '2.6', '>=' ) && $chosen_method !== 'legacy_flat_rate' ) {
			$chosen_method = explode( ':', $chosen_method ); // slug:instance
			// only for flat rate
			if ( $chosen_method[0] !== 'flat_rate' ) {
				return false;
			}
			$method_slug = $chosen_method[0];
			$method_instance = $chosen_method[1];

			$shipping_method = WC_Shipping_Zones::get_shipping_method( $method_instance );
		} else {
			// only for flat rate or legacy flat rate
			if ( !in_array($chosen_method, array('flat_rate','legacy_flat_rate') ) ) {
				return false;
			}
			$shipping_methods = WC()->shipping->load_shipping_methods( $package );

			if (!isset($shipping_methods[$chosen_method])) {
				return false;
			}
			$shipping_method = $shipping_methods[$chosen_method];
		}

		return $shipping_method;
	}

	public function get_shipping_class($shipping_method, $found_shipping_classes) {
		// get most expensive class
		// adapted from $shipping_method->calculate_shipping()
		$highest_class_cost = 0;
		$highest_class = false;
		foreach ( $found_shipping_classes as $shipping_class => $products ) {
			// Also handles BW compatibility when slugs were used instead of ids
			$shipping_class_term = get_term_by( 'slug', $shipping_class, 'product_shipping_class' );
			$class_cost_string   = $shipping_class_term && $shipping_class_term->term_id ? $shipping_method->get_option( 'class_cost_' . $shipping_class_term->term_id, $shipping_method->get_option( 'class_cost_' . $shipping_class, '' ) ) : $shipping_method->get_option( 'no_class_cost', '' );

			if ( $class_cost_string === '' ) {
				continue;
			}


			$has_costs  = true;
			$class_cost = $this->wc_flat_rate_evaluate_cost( $class_cost_string, array(
				'qty'  => array_sum( wp_list_pluck( $products, 'quantity' ) ),
				'cost' => array_sum( wp_list_pluck( $products, 'line_total' ) )
			), $shipping_method );

			if ( $class_cost > $highest_class_cost && $shipping_class_term->term_id) {
				$highest_class_cost = $class_cost;
				$highest_class = $shipping_class_term->term_id;
			}
		}

		return $highest_class;
	}

	public function find_order_shipping_classes($order) {
		$found_shipping_classes = array();
		$order_items = $order->get_items();
		foreach ( $order_items as $item_id => $item ) {
			$product = $order->get_product_from_item( $item );
			if ( $product && $product->needs_shipping() ) {
				$found_class = $product->get_shipping_class();

				if ( ! isset( $found_shipping_classes[ $found_class ] ) ) {
					$found_shipping_classes[ $found_class ] = array();
				}

				$found_shipping_classes[ $found_class ][ $item_id ] = $product;
			}
		}

		return $found_shipping_classes;
	}

	/**
	 * Adapted from WC_Shipping_Flat_Rate - Protected method
	 * Evaluate a cost from a sum/string.
	 * @param  string $sum
	 * @param  array  $args
	 * @return string
	 */
	public function wc_flat_rate_evaluate_cost($sum, $args = array(), $flat_rate_method) {
		if ( version_compare( WOOCOMMERCE_VERSION, '2.6', '>=' ) ) {
			include_once( WC()->plugin_path() . '/includes/libraries/class-wc-eval-math.php' );
		} else {
			include_once( WC()->plugin_path() . '/includes/shipping/flat-rate/includes/class-wc-eval-math.php' );
		}

		// Allow 3rd parties to process shipping cost arguments
		$args           = apply_filters( 'woocommerce_evaluate_shipping_cost_args', $args, $sum, $flat_rate_method );
		$locale         = localeconv();
		$decimals       = array( wc_get_price_decimal_separator(), $locale['decimal_point'], $locale['mon_decimal_point'], ',' );
		$this->fee_cost = $args['cost'];

		// Expand shortcodes
		add_shortcode( 'fee', array( $this, 'wc_flat_rate_fee' ) );

		$sum = do_shortcode( str_replace(
			array(
				'[qty]',
				'[cost]'
			),
			array(
				$args['qty'],
				$args['cost']
			),
			$sum
		) );

		remove_shortcode( 'fee', array( $this, 'wc_flat_rate_fee' ) );

		// Remove whitespace from string
		$sum = preg_replace( '/\s+/', '', $sum );

		// Remove locale from string
		$sum = str_replace( $decimals, '.', $sum );

		// Trim invalid start/end characters
		$sum = rtrim( ltrim( $sum, "\t\n\r\0\x0B+*/" ), "\t\n\r\0\x0B+-*/" );

		// Do the math
		return $sum ? WC_Eval_Math::evaluate( $sum ) : 0;
	}

	/**
	 * Adapted from WC_Shipping_Flat_Rate - Protected method
	 * Work out fee (shortcode).
	 * @param  array $atts
	 * @return string
	 */
	public function wc_flat_rate_fee( $atts ) {
		$atts = shortcode_atts( array(
			'percent' => '',
			'min_fee' => '',
			'max_fee' => '',
		), $atts );

		$calculated_fee = 0;

		if ( $atts['percent'] ) {
			$calculated_fee = $this->fee_cost * ( floatval( $atts['percent'] ) / 100 );
		}

		if ( $atts['min_fee'] && $calculated_fee < $atts['min_fee'] ) {
			$calculated_fee = $atts['min_fee'];
		}

		if ( $atts['max_fee'] && $calculated_fee > $atts['max_fee'] ) {
			$calculated_fee = $atts['max_fee'];
		}

		return $calculated_fee;
	}

	public function filter_eu_orders( $order_ids ) {
		foreach ($order_ids as $key => $order_id) {
			$shipping_country = get_post_meta( $order_id, '_shipping_country', true );
			// skip non-eu orders
			if ( !$this->is_eu_country( $shipping_country ) ) {
				unset($order_ids[$key]);
			}
		}
		return $order_ids;
	}

	public function is_eu_country($country_code) {
		$eu_countries = array( 'GB', 'AT', 'BE', 'BG', 'HR', 'CY', 'CZ', 'DK', 'EE', 'FI', 'FR', 'DE', 'EL', 'HU', 'IE', 'IT', 'LV', 'LT', 'LU', 'MT', 'NL', 'PL', 'PT', 'RO', 'SK', 'SI', 'ES', 'SE' );
		return in_array( $country_code, $eu_countries);
	}

	public function log( $message ) {
		if (isset(WooCommerce_MyParcel()->general_settings['error_logging'])) {
			// log file in upload folder - wp-content/uploads
			$upload_dir = wp_upload_dir();
			$upload_base = trailingslashit( $upload_dir['basedir'] );
			$log_file = $upload_base.'myparcel_log.txt';

			$current_date_time = date("Y-m-d H:i:s");
			$message = $current_date_time .' ' .$message ."\n";

			file_put_contents($log_file, $message, FILE_APPEND);
		}
	}
}

endif; // class_exists

return new WooCommerce_MyParcel_Export();