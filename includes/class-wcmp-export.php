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

				foreach ($order_ids as $order_id) {
					$shipments = $this->get_order_shipment_data( (array) $order_id );

					// check colli amount
					$extra_params = $order->myparcel_shipment_options_extra;
					$colli_amount = isset($extra_params['colli_amount']) ? $extra_params['colli_amount'] : 1;

					for ($i=0; $i < intval($colli_amount); $i++) {
						try {
							$api = $this->init_api();
							$response = $api->add_shipments( $shipments );
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

				foreach ($myparcel_options as $order_id => $options) {

					$return_shipment = $this->prepare_return_shipment_data( $order_id, $options );
					// echo '<pre>';var_dump($return_shipment);echo '</pre>';die();

					try {
						$api = $this->init_api();
						$response = $api->add_shipments( $return_shipment, 'return' );
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

				try {
					$api = $this->init_api();
					$params = array();
					

					if (isset($label_response_type) && $label_response_type == 'url') {
						$response = $api->get_shipment_labels( $shipment_ids, $params, 'link' );
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
							$pdf_data = $response['body'];
							$output_mode = isset(WooCommerce_MyParcel()->general_settings['download_display'])?WooCommerce_MyParcel()->general_settings['download_display']:'';
							if ( $output_mode == 'display' ) {
								$this->stream_pdf( $pdf_data, $order_ids );
							} else {
								$this->download_pdf( $pdf_data, $order_ids );
							}
						} else {
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

				// echo 'bla';
				// include( WooCommerce_MyParcel()->plugin_path() . 'includes/views/wcmp-bulk-options-form.php' );
				error_reporting( E_ALL );
				ini_set( 'display_errors', 1 );

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
		if (isset($options['insured_amount'])) {
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

		return array_merge( $address, $address_intl);
	}

	public function get_options( $order ) {
		$order_number = $order->get_order_number();

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

		$options = array(
			'package_type'		=> $package_type,
			'only_recipient'	=> (isset(WooCommerce_MyParcel()->export_defaults['only_recipient'])) ? 1 : 0,
			'signature'			=> (isset(WooCommerce_MyParcel()->export_defaults['signature'])) ? 1 : 0,
			'return'			=> (isset(WooCommerce_MyParcel()->export_defaults['return'])) ? 1 : 0,
			'large_format'		=> (isset(WooCommerce_MyParcel()->export_defaults['large_format'])) ? 1 : 0,
			'label_description'	=> $description,
			'insured_amount'	=> (isset(WooCommerce_MyParcel()->export_defaults['insured_amount'])) ? WooCommerce_MyParcel()->export_defaults['insured_amount'] : 0,
		);


		// use shipment options from order when available
		$shipment_options = $order->myparcel_shipment_options;
		if (!empty($shipment_options)) {
			$options = array_merge($options, $shipment_options);
		}

		// convert insurance option
		if (isset($options['insured_amount'])) {
			$options['insurance'] = array(
				'amount'	=> (int) $options['insured_amount'],
				'currency'	=> 'EUR',
			);
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

		// disable letterbox outside NL
		if ($order->shipping_country != 'NL' && $options['package_type'] == 2 ) {
			$options['package_type'] == 1;
		}
		// always parcel for Pickup and Pickup express delivery types.
		if ( $this->is_pickup( $order ) ) {
			$options['package_type'] == 1;
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

		if ( isset(WooCommerce_MyParcel()->general_settings['keep_shipments']) ) {
			if ( $old_shipments = get_post_meta($order_id,'_myparcel_shipments',true) ) {
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

	public function get_shipment_data( $id ) {
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
					return compact('status', 'tracktrace', 'shipment');
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
	}












	public function get_export_form_data ( $order_ids ) {
		foreach( $order_ids as $order_id ) {
			if ( version_compare( WOOCOMMERCE_VERSION, '2.2', '<' ) ) {
				$order = new WC_Order( $order_id );
			} else {
				$order = wc_get_order( $order_id );
			}
			$data[ $order_id ] = array (
				'consignment'	=> $this->get_consignment_data_from_order( $order ),
				'order'			=> $order,
			);
		}

		return $data;
	}

	public function get_consignment_data_from_order ( $order ) {
		$items = $order->get_items();
		$order_number = $order->get_order_number();

		$kenmerk = isset($this->settings['kenmerk'])
			? str_replace('[ORDER_NR]', $order_number, $this->settings['kenmerk'])
			: '';

		$address = array(
			'name'			=> trim( $order->shipping_first_name . ' ' . $order->shipping_last_name ),
			'business'		=> $order->shipping_company,
			'town'			=> $order->shipping_city,
			'email'			=> isset($this->settings['email']) ? $order->billing_email : '',
			'phone_number'	=> isset($this->settings['telefoon']) ? $order->billing_phone : '',
		);

		if ( $order->shipping_country == 'NL' ) {
			$address_intl = array(
				'postcode'		=> preg_replace('/[^a-zA-Z0-9]/', '', $order->shipping_postcode),
				'house_number'	=> $order->shipping_house_number,
				'number_addition' => $order->shipping_house_number_suffix,
				'street'		  => $order->shipping_street_name,
			);
		} else {
			$address_intl = array(
				'country_code'	=> $order->shipping_country,
				'eps_postcode'	=> $order->shipping_postcode,
				'street'		=> trim( $order->shipping_address_1.' '.$order->shipping_address_2 ),
			);
		}		

		$consignment = array(
			'shipment_type'	=> (isset($this->settings['shipment_type'])) ? $this->settings['shipment_type'] : 'standard', // standard | letterbox | unpaid_letter
			'ToAddress'		=> array_merge( $address, $address_intl),
			'ProductCode'	=> array(
				'signature_on_receipt'	=> (isset($this->settings['handtekening'])) ? '1' : '0',
				'return_if_no_answer'	=> (isset($this->settings['retourbgg'])) ? '1' : '0',
				'home_address_only'		=> (isset($this->settings['huisadres'])) ? '1' : '0',
				'home_address_signature'=> (isset($this->settings['huishand'])) ? '1' : '0',
				'mypa_insured'			=> (isset($this->settings['huishandverzekerd'])) ? '1' : '0',
				'insured'				=> (isset($this->settings['verzekerd'])) ? '1' : '0',
				'extra_size'			=> (isset($this->settings['extragroot'])) ? '1' : '0',
			),
			'insured_amount'	=> (isset($this->settings['verzekerdbedrag'])) ? $this->settings['verzekerdbedrag'] : '0',
			'custom_id'			=> $kenmerk,
			'weight'			=> $this->get_parcel_weight( $order ),
		);

		// use shipment options from order when available
		$shipment_options = $order->myparcel_shipment_options;
		if (!empty($shipment_options)) {
			// recursively merge (!== array_merge_recursive)
			if (!empty($shipment_options['ProductCode'])) {
				$consignment['ProductCode'] = array_merge($consignment['ProductCode'], $shipment_options['ProductCode']);
				unset($shipment_options['ProductCode']);
			}
			$consignment = array_merge($consignment, $shipment_options);
		}

		// always enable signature on receipt for Pickup and Pickup express delivery types.
		if ( $this->is_pickup( $order ) ) {
			$consignment['ProductCode']['signature_on_receipt'] = '1';
		}

		// allow prefiltering consignment data
		$consignment = apply_filters( 'wcmyparcel_order_consignment_data', $consignment, $order );

		// PREVENT ILLEGAL SETTINGS

		// disable letterbox outside NL
		if ($order->shipping_country != 'NL' && $consignment['shipment_type'] == 'letterbox' ) {
			$consignment['shipment_type'] == 'standard';
		}
		// always parcel for Pickup and Pickup express delivery types.
		if ( $this->is_pickup( $order ) ) {
			$consignment['shipment_type'] == 'standard';
		}

		return $consignment;
	}

	/**
	 * Process consignment data after it has been reviewed for submit
	 */
	public function process_consignment_data ( $consignment_data ) {
		foreach ($consignment_data as $order_id => $consignment) {
			// Pakjegemak: Use billing address as ToAddress and send PgAddress separately
			if ( $pgaddress = $this->is_pickup( $order_id ) ) {
				// load order
				if ( version_compare( WOOCOMMERCE_VERSION, '2.2', '<' ) ) {
					$order = new WC_Order( $order_id );
				} else {
					$order = wc_get_order( $order_id );
				}

				$consignment['PgAddress'] = $pgaddress;
				$consignment['ToAddress'] = array(
					'name'			=> trim( $order->billing_first_name . ' ' . $order->billing_last_name ),
					'business'		=> $order->billing_company,
					'town'			=> $order->billing_city,
					'email'			=> isset($this->settings['email']) ? $order->billing_email : '',
					'phone_number'	=> isset($this->settings['telefoon']) ? $order->billing_phone : '',
					'postcode'		=> preg_replace('/[^a-zA-Z0-9]/', '', $order->billing_postcode),
					'house_number'	=> $order->billing_house_number,
					'number_addition' => $order->billing_house_number_suffix,
					'street'		  => $order->billing_street_name,
				);

				$consignment['ProductCode']['signature_on_receipt'] = '0';
			}

			$colli_amount = isset($consignment['colli_amount']) ? $consignment['colli_amount'] : 1;

			// multiply consignments by colli_amount
			for ($i=0; $i < intval($colli_amount); $i++) {
				unset($consignment['colli_amount']);
				$consignment['order_id'] = $order_id;
				$consignment_data_processed[] = $consignment;
			}
		}

		return apply_filters('wcmyparcel_process_consignment_data', $consignment_data_processed );
	}

}

endif; // class_exists

return new WooCommerce_MyParcel_Export();