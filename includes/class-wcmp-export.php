<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( !class_exists( 'WooCommerce_MyParcel_Export' ) ) :

class WooCommerce_MyParcel_Export {
	public $order_id;

	/**
	 * Construct.
	 */
			
	public function __construct() {
		include( 'class-wcmp-rest.php' );
		include( 'class-wcmp-api.php' );

		add_action( 'admin_notices', array( $this, 'admin_notices' ) );
		add_action( 'wp_ajax_wc_myparcel', array($this, 'export' ));
		add_action( 'wp_ajax_wc_myparcel_frontend', array($this, 'frontend_api_request' ));
		add_action( 'wp_ajax_nopriv_wc_myparcel_frontend', array($this, 'frontend_api_request' ));
	}

	public function admin_notices () {
		if (!isset($_GET['myparcel'])) {
			return;
		}

		switch ($_GET['myparcel']) {
			case 'no_consignments':
				$message = __('U dient de orders eerst naar MyParcel te exporteren voordat u de labels kunt printen!');
				printf('<div class="error"><p>%s</p></div>', $message);
				break;
			default:
				break;
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

		// Check the user privileges (maybe use order ids for filter?)
		if( apply_filters( 'wc_myparcel_check_privs', !current_user_can( 'manage_woocommerce_orders' ) && !current_user_can( 'edit_shop_orders' ) ) ) {
			$return['error'] = __( 'You do not have sufficient permissions to access this page.', 'woocommerce-myparcel' );
			$json = json_encode( $return );
			echo $json;
			die();
		}

		extract($_REQUEST); // $request, $order_ids, ...

		$return = array();
		$success = array();
		$errors = array();
		switch($request) {
			case 'add_shipments':
				if ( empty($order_ids) ) {
					$errors[] = __( 'You have not selected any orders!', 'woocommerce-myparcel' );
					break;
				}

				$this->log("*** Creating shipments started ***");

				foreach ($order_ids as $order_id) {
					$shipments = $this->get_order_shipment_data( (array) $order_id );
	
					$this->log("Shipment data for order {$order_id}:\n".var_export($shipments, true));

					// check colli amount
					$extra_params = $order->myparcel_shipment_options_extra;
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
								$success[$order_id] = $shipment_id;

								$shipment = array (
									'shipment_id' => $shipment_id,
								);

								// save shipment data in order meta
								$this->save_shipment_data( $order_id, $shipment );

								// status automation
								if ( isset(WooCommerce_MyParcel()->general_settings['order_status_automation']) && !empty(WooCommerce_MyParcel()->general_settings['automatic_order_status']) ) {
									$order = $this->get_order( $order_id );
									$order->update_status( WooCommerce_MyParcel()->general_settings['automatic_order_status'], __( 'MyParcel shipment created:', 'woocommerce-myparcel' ) );
								}

							} else {
								$errors[$order_id] = __( 'Unknown error', 'woocommerce-myparcel' );
							}
						} catch (Exception $e) {
							$errors[$order_id] = $e->getMessage();
						}
					}					
				}
				// echo '<pre>';var_dump($success);echo '</pre>';die();
				if (!empty($success)) {
					$return['success'] = sprintf(__( '%s shipments successfully exported to Myparcel', 'woocommerce-myparcel' ), count($success));
				}
			break;
			case 'add_return':
				if ( empty($myparcel_options) ) {
					$errors[] = __( 'You have not selected any orders!', 'woocommerce-myparcel' );
					break;
				}

				$this->log("*** Creating return shipments started ***");

				foreach ($myparcel_options as $order_id => $options) {

					$return_shipment = $this->prepare_return_shipment_data( $order_id, $options );
					$this->log("Return shipment data for order {$order_id}:\n".var_export($return_shipment, true));
					// echo '<pre>';var_dump($return_shipment);echo '</pre>';die();

					try {
						$api = $this->init_api();
						$response = $api->add_shipments( $return_shipment, 'return' );
						$this->log("API response (order {$order_id}):\n".var_export($response, true));
						// echo '<pre>';var_dump($response);echo '</pre>';die();
						if (isset($response['body']['data']['ids'])) {
							$ids = array_shift($response['body']['data']['ids']);
							$shipment_id = $ids['id'];
							$success[$order_id] = $shipment_id;

							$shipment = array (
								'shipment_id' => $shipment_id,
							);

							// save shipment data in order meta
							$this->save_shipment_data( $order_id, $shipment );

						} else {
							$errors[$order_id] = __( 'Unknown error', 'woocommerce-myparcel' );
						}
					} catch (Exception $e) {
						$errors[$order_id] = $e->getMessage();
					}
					
				}
				// echo '<pre>';var_dump($success);echo '</pre>';die();

			break;
			case 'get_labels':
				if ( empty($order_ids) ) {
					$errors[] = __( 'You have not selected any orders!', 'woocommerce-myparcel' );
					break;
				}

				// check for JSON
				if (is_string($order_ids) && strpos($order_ids, '[') !== false ) {
					$order_ids = json_decode(stripslashes($order_ids));
				}

				// cast as array for single exports
				$order_ids = (array) $order_ids;

				$shipment_ids = $this->get_shipment_ids( $order_ids );

				if ( empty($shipment_ids) ) {
					$errors[] = __( 'The selected orders have not been exported to MyParcel yet!', 'woocommerce-myparcel' );
					break;
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
							$errors[] = __( 'Unknown error', 'woocommerce-myparcel' );
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
							$errors[] = __( 'Unknown error', 'woocommerce-myparcel' );
						}

						// echo '<pre>';var_dump($response);echo '</pre>';die();
					}
				} catch (Exception $e) {
					$errors[] = $e->getMessage();
				}
			break;
			case 'modal_dialog':
				if ( empty($order_ids) ) {
					$errors[] = __( 'You have not selected any orders!', 'woocommerce-myparcel' );
					break;
				}

				// check for JSON
				if (is_string($order_ids) && strpos($order_ids, '[') !== false ) {
					$order_ids = json_decode(stripslashes($order_ids));
				}

				// cast as array for single exports
				$order_ids = (array) $order_ids;

				include('views/wcmp-bulk-options-form.php');
				die();
				break;
		}

		// display errors directly if PDF requested or modal
		if ( in_array($request, array('get_labels','modal_dialog')) && !empty($errors) ) {
			echo $this->parse_errors( $errors );
			die();
		}		

		// format errors for html output
		if (!empty($errors)) {
			$return['error'] = $this->parse_errors( $errors );
		}

		// return JSON response
		echo json_encode( $return );
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

			// echo '<pre>';var_dump($shipment);echo '</pre>';die();

			$shipments[] = $shipment;
		}

		return $shipments;
	}

	public function prepare_return_shipment_data( $order_id, $options ) {
		$order = $this->get_order( $order_id );

		// convert insurance option
		if ( isset($options['insured_amount']) && $options['insured_amount'] != 0 ) {
			$options['insurance'] = array(
				'amount'	=> (int) $options['insured_amount'],
				'currency'	=> 'EUR',
			);
			unset($options['insured_amount']);
			unset($options['insured']);
		}

		// set name & email
		$return_shipment_data = array(
			'name'			=> trim( $order->shipping_first_name . ' ' . $order->shipping_last_name ),
			'email'			=> isset(WooCommerce_MyParcel()->export_defaults['connect_email']) ? $order->billing_email : '',
			'carrier'		=> 1, // default to POSTNL for now
			'options'		=> $options,
		);

		// get parent
		$shipment_ids = $this->get_shipment_ids( (array) $order_id );
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
			$address_intl = array(
				'street'		=> $order->shipping_street_name,
				'number'		=> $order->shipping_house_number,
				'number_suffix' => $order->shipping_house_number_suffix,
				'postal_code'	=> $order->shipping_postcode,
			);
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

				foreach (WooCommerce_MyParcel()->export_defaults['shipping_methods_package_types'] as $package_type_key => $package_type_shipping_methods ) {
					if (in_array($order_shipping_method, $package_type_shipping_methods)) {
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
			$package_type == 1;
		}

		// always parcel for Pickup and Pickup express delivery types.
		if ( $this->is_pickup( $order ) ) {
			$package_type == 1;
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
			$options = array(
				'package_type'		=> $package_type,
				'only_recipient'	=> (isset(WooCommerce_MyParcel()->export_defaults['only_recipient'])) ? 1 : 0,
				'signature'			=> (isset(WooCommerce_MyParcel()->export_defaults['signature'])) ? 1 : 0,
				'return'			=> (isset(WooCommerce_MyParcel()->export_defaults['return'])) ? 1 : 0,
				'large_format'		=> (isset(WooCommerce_MyParcel()->export_defaults['large_format'])) ? 1 : 0,
				'label_description'	=> $description,
				'insured_amount'	=> (isset(WooCommerce_MyParcel()->export_defaults['insured_amount'])) ? WooCommerce_MyParcel()->export_defaults['insured_amount'] : 0,
			);
		}

		// convert insurance option
		if (isset($options['insured_amount'])) {
			if ($options['insured_amount'] > 0) {
				$options['insurance'] = array(
					'amount'	=> (int) $options['insured_amount'],
					'currency'	=> 'EUR',
				);
			}

			unset($options['insured_amount']);
			unset($options['insured']);
		}

		// always enable signature on receipt for Pickup and Pickup express delivery types.
		if ( $this->is_pickup( $order ) ) {
			$options['signature'] = 1;
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
			$illegal_options = array( 'delivery_type', 'only_recipient', 'signature', 'return', 'large_format', 'insurance' );
			foreach ($options as $key => $option) {
				if (in_array($key, $illegal_options)) {
					unset($options[$key]);
				}
			}
		}

		return $options;

	}

	public function get_shipment_ids( $order_ids ) {
		$shipment_ids = array();
		foreach ($order_ids as $order_id) {
			$shipments = get_post_meta($order_id,'_myparcel_shipments',true);
			if (!empty($shipments)) {
				$last_shipment = array_pop( $shipments );
				$shipment_ids[] = $last_shipment['shipment_id'];
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
		if (isset($shipment['shipment'])) {
			unset($shipment['shipment']);
		}

		if ( isset(WooCommerce_MyParcel()->general_settings['keep_shipments']) ) {
			if ( $old_shipments = get_post_meta($order_id,'_myparcel_shipments',true) ) {
				$shipments = $shipments + $old_shipments;
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

	public function is_pickup( $order ) {
		// load order_id if order object passed
		if (is_object($order)) {
			$order_id = $order->id;
		} else {
			$order_id = $order;
		}

		$myparcel_pickup_option = get_post_meta( $order_id, '_myparcel_pickup_option', true );
		if (!empty($myparcel_pickup_option)) {
			return $myparcel_pickup_option;
		} else {
			return false;
		}

		/* old pakjegemak code
		// load meta data
		$pakjegemak = get_post_meta( $order_id, '_myparcel_is_pickup', true );
		$pgaddress = get_post_meta( $order_id, '_myparcel_pgaddress', true );

		// make sure pakjegemak address is present and contains an address
		// (cancelled pg popups still save pgaddress)
		if ( !empty( $pgaddress ) && !empty( $pgaddress['postcode'] ) ) {
			return $pgaddress;
		} else {
			return false;
		}
		*/
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