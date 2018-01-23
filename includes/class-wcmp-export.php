<?php
use WPO\WC\PostNL\Compatibility\WC_Core as WCX;
use WPO\WC\PostNL\Compatibility\Order as WCX_Order;
use WPO\WC\PostNL\Compatibility\Product as WCX_Product;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( !class_exists( 'WooCommerce_PostNL_Export' ) ) :

    class WooCommerce_PostNL_Export {
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
            add_action( 'wp_ajax_wc_postnl', array($this, 'export' ));
            add_action( 'wp_ajax_wc_postnl_frontend', array($this, 'frontend_api_request' ));
            add_action( 'wp_ajax_nopriv_wc_postnl_frontend', array($this, 'frontend_api_request' ));
        }

        public function admin_notices () {
            if ( isset($_GET['postnl_done']) ) { // only do this when for the user that initiated this
                $action_return = get_option( 'wcpostnl_admin_notices' );
                $print_queue = get_option( 'wcpostnl_print_queue', array() );
                if (!empty($action_return)) {
                    foreach ($action_return as $type => $message) {
                        if (in_array($type, array('success','error'))) {
                            if ( $type == 'success' && !empty($print_queue) ) {
                                $print_queue_store = sprintf('<input type="hidden" value="%s" id="wcmp_printqueue">', json_encode(array_keys($print_queue['order_ids'])));
                                $print_queue_offset_store = sprintf('<input type="hidden" value="%s" id="wcmp_printqueue_offset">', $print_queue['offset']);
                                // dequeue
                                delete_option( 'wcpostnl_print_queue' );
                            }
                            printf('<div class="postnl_notice notice notice-%s"><p>%s</p>%s</div>', $type, $message, isset($print_queue_store)?$print_queue_store.$print_queue_offset_store:'');
                        }
                    }
                    // destroy after reading
                    delete_option( 'wcpostnl_admin_notices' );
                    wp_cache_delete( 'wcpostnl_admin_notices','options' );
                }
            }

            if (isset($_GET['postnl'])) {
                switch ($_GET['postnl']) {
                    case 'no_consignments':
                        $message = __('You have to export the orders to PostNL before you can print the labels!', 'woocommerce-postnl');
                        printf('<div class="postnl_notice notice notice-error"><p>%s</p></div>', $message);
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
            check_ajax_referer( 'wc_postnl', 'security' );

            if( ! is_user_logged_in() ) {
                wp_die( __( 'You do not have sufficient permissions to access this page.', 'woocommerce-postnl' ) );
            }

            $return = array();

            // Check the user privileges (maybe use order ids for filter?)
            if( apply_filters( 'wc_postnl_check_privs', !current_user_can( 'manage_woocommerce_orders' ) && !current_user_can( 'edit_shop_orders' ) ) ) {
                $return['error'] = __( 'You do not have sufficient permissions to access this page.', 'woocommerce-postnl' );
                $json = json_encode( $return );
                echo $json;
                die();
            }

            extract($_REQUEST); // $request, $order_ids, ...
            // make sure $order_ids is a proper array
            $order_ids = !empty($order_ids) ? $this->sanitize_posted_array($order_ids) : array();

            switch($request) {
                case 'add_shipments':

                    // filter out non-postnl destinations
                    $order_ids = $this->filter_postnl_destination_orders( $order_ids );

                    if ( empty($order_ids) ) {
                        $this->errors[] = __( 'You have not selected any orders!', 'woocommerce-postnl' );
                        break;
                    }

                    // if we're going to print directly, we need to process the orders first, regardless of the settings
                    $process = (isset($print) && $print == 'yes') ? true : false;
                    $return = $this->add_shipments( $order_ids );
                    break;

                case 'get_labels':

                    $offset = !empty($offset) && is_numeric($offset) ? $offset % 4 : 0;

                    if ( empty($order_ids) && empty($shipment_ids)) {
                        $this->errors[] = __( 'You have not selected any orders!', 'woocommerce-postnl' );
                        break;
                    }
                    $label_response_type = isset($label_response_type) ? $label_response_type : NULL;

                    if (!empty($shipment_ids)) {
                        $order_ids = !empty($order_ids) ? $this->sanitize_posted_array($order_ids) : array();
                        $shipment_ids = $this->sanitize_posted_array($shipment_ids);
                        $return = $this->get_shipment_labels( $shipment_ids, $order_ids, $label_response_type, $offset );
                    } else {
                        $order_ids = $this->filter_postnl_destination_orders( $order_ids );
                        $return = $this->get_labels( $order_ids, $label_response_type, $offset );

                    }
                    break;
                case 'modal_dialog':
                    if ( empty($order_ids) ) {
                        $errors[] = __( 'You have not selected any orders!', 'woocommerce-postnl' );
                        break;
                    }
                    $order_ids = $this->filter_postnl_destination_orders( $order_ids );
                    $this->modal_dialog( $order_ids, $dialog );
                    break;
            }

            // display errors directly if PDF requested or modal
            if ( in_array($request, array('get_labels','modal_dialog')) && !empty($this->errors) ) {
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
                update_option( 'wcpostnl_admin_notices', $return );
                if ($print == 'after_reload') {
                    $print_queue = array(
                        'order_ids'	=> $return['success_ids'],
                        'offset'	=> isset($offset) && is_numeric($offset) ? $offset % 4 : 0,
                    );
                    update_option( 'wcpostnl_print_queue', $print_queue );
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

        public function sanitize_posted_array($array) {
            // check for JSON
            if (is_string($array) && strpos($array, '[') !== false ) {
                $array = json_decode(stripslashes($array));
            }

            // cast as array for single exports
            $array = (array) $array;

            return $array;
        }

        public function add_shipments( $order_ids, $process = false ) {
            $return = array();

            $this->log("*** Creating shipments started ***");

            foreach ($order_ids as $order_id) {
                $created_shipments = array();
                $order = WCX::get_order( $order_id );
                $shipments = $this->get_order_shipment_data( (array) $order_id );
                $shipments = $this->validate_shipments( $shipments );
                if (empty($shipments)) {
                    $this->log("Export for order {$order_id} skipped (missing or invalidated shipment data)");
                    continue;
                }

                $this->log("Shipment data for order {$order_id}:\n".var_export($shipments, true));

                // check colli amount
                $extra_params = WCX_Order::get_meta( $order, '_postnl_shipment_options_extra' );
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

                            $created_shipments[] = $shipment_id;
                            $shipment = array (
                                'shipment_id' => $shipment_id,
                            );

                            // save shipment data in order meta
                            $this->save_shipment_data( $order, $shipment );

                            // process directly setting
                            if ( isset(WooCommerce_PostNL()->general_settings['process_directly']) || $process === true ) {
                                // flush cache until WC issue #13439 is fixed https://github.com/woocommerce/woocommerce/issues/13439
                                if (method_exists($order, 'save')) {
                                    $order->save();
                                }
                                $this->get_labels( (array) $order_id, 'url' );
                                $this->get_shipment_data( $shipment_id, $order );
                            }

                            // status automation
                            if ( isset(WooCommerce_PostNL()->general_settings['order_status_automation']) && !empty(WooCommerce_PostNL()->general_settings['automatic_order_status']) ) {
                                $order->update_status( WooCommerce_PostNL()->general_settings['automatic_order_status'], __( 'PostNL shipment created:', 'woocommerce-postnl' ) );
                            }
                        } else {
                            $this->errors[$order_id] = __( 'Unknown error', 'woocommerce-postnl' );
                        }
                    } catch (Exception $e) {
                        $this->errors[$order_id] = $e->getMessage();
                    }
                }

                // store shipment ids from this export
                if (!empty($created_shipments)) {
                    WCX_Order::update_meta_data( $order, '_postnl_last_shipment_ids', $created_shipments );
                }
            }
            // echo '<pre>';var_dump($this->success);echo '</pre>';die();
            if (!empty($this->success)) {
                $return['success'] = sprintf(__( '%s shipments successfully exported to PostNL', 'woocommerce-postnl' ), count($this->success));
                $return['success_ids'] = $this->success;
            }

            return $return;
        }

        public function get_shipment_labels( $shipment_ids, $order_ids = array(), $label_response_type = NULL, $offset = 0 ) {
            $return = array();
            $this->log("*** Label request started ***");
            $this->log("Shipment ID's: ".implode(', ', $shipment_ids));
            try {
                $api = $this->init_api();
                $params = array();

				if(WooCommerce_PostNL()->general_settings['label_format'] == 'A4'){

					 if (!empty($offset) && is_numeric ($offset)) {
						$portrait_positions = array( 2, 4, 1, 3 ); // positions are defined on landscape, but paper is filled portrait-wise
						$params['positions'] = implode( ';', array_slice($portrait_positions,$offset) );
                	}
					$params['format'] = 'A4';
				}else{

					$params['format'] = 'A6';
				}

                if (isset($label_response_type) && $label_response_type == 'url') {
                    $response = $api->get_shipment_labels( $shipment_ids, $params, 'link' );
                    $this->log("API response:\n".var_export($response, true));
                    // var_dump( $response );
                    if (isset($response['body']['data']['pdfs']['url'])) {
                        $url = untrailingslashit( $api->APIURL ) . $response['body']['data']['pdfs']['url'];
                        $return['url'] = $url;
                    } else {
                        $this->errors[] = __( 'Unknown error', 'woocommerce-postnl' );
                    }
                } else {
                    $response = $api->get_shipment_labels( $shipment_ids, $params, 'pdf' );

                    if (isset($response['body'])) {
                        $this->log("PDF data received");
                        $pdf_data = $response['body'];
                        $output_mode = isset(WooCommerce_PostNL()->general_settings['download_display'])?WooCommerce_PostNL()->general_settings['download_display']:'';
                        if ( $output_mode == 'display' ) {
                            $this->stream_pdf( $pdf_data, $order_ids );
                        } else {
                            $this->download_pdf( $pdf_data, $order_ids );
                        }
                    } else {
                        $this->log("Unknown error, API response:\n".var_export($response, true));
                        $this->errors[] = __( 'Unknown error', 'woocommerce-postnl' );
                    }

                    // echo '<pre>';var_dump($response);echo '</pre>';die();
                }
            } catch (Exception $e) {
                $this->errors[] = $e->getMessage();
            }

            return $return;
        }

        public function get_labels( $order_ids, $label_response_type = NULL, $offset = 0 ) {
            $shipment_ids = $this->get_shipment_ids( $order_ids, array( 'only_last' => true ) );

            if ( empty($shipment_ids) ) {
                $this->log("*** Failed label request (not exported yet) ***");
                $this->errors[] = __( 'The selected orders have not been exported to PostNL yet!', 'woocommerce-postnl' );
                return array();
            }

            return $this->get_shipment_labels( $shipment_ids, $order_ids, $label_response_type, $offset );
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
            $api_params = array(
                'cc'					=> '',
                'postal_code'			=> '',
                'number'				=> '',
                'carrier'				=> '',
                'delivery_time'			=> '',
                'delivery_date'			=> '',
                'cutoff_time'			=> '',
                'dropoff_days'			=> '',
                'dropoff_delay'			=> '',
                'deliverydays_window'	=> '',
                'exclude_delivery_type'	=> '',
            );
            $params = array_intersect_key($params, $api_params);

            $api = $this->init_api();

            try {
                $response = $api->get_delivery_options( $params, true );

                @header('Content-type: application/json; charset=utf-8');

                echo $response['body'];
            } catch (Exception $e) {
                @header("HTTP/1.1 503 service unavailable");
            }
            die();
        }

        public function init_api () {
            // $user = WooCommerce_PostNL()->general_settings['api_username'];
            if ( !isset(WooCommerce_PostNL()->general_settings['api_key']) ) {
                return false;
            }

            $key = WooCommerce_PostNL()->general_settings['api_key'];
            $api = new WC_PostNL_API( $key );

            return $api;
        }

        public function get_order_shipment_data( $order_ids, $type = 'standard' ) {
            foreach( $order_ids as $order_id ) {
                // get order
                $order = WCX::get_order( $order_id );

                $shipment = array(
                    'recipient' => $this->get_recipient( $order ),
                    'options'	=> $this->get_options( $order ),
                    'carrier'	=> 1, // default to POSTNL for now
                );

                if ( $pickup = $this->is_pickup( $order ) ) {
                    // $pickup_time = array_shift($pickup['time']); // take first element in time array
                    $shipment['pickup'] = array(
                        'postal_code'	=> $pickup['postal_code'],
                        'street'		=> $pickup['street'],
                        'city'			=> $pickup['city'],
                        'number'		=> $pickup['number'],
                        'location_name'	=> $pickup['location'],
                    );
                }

                $shipping_country = WCX_Order::get_prop( $order, 'shipping_country' );
                if ( $this->is_world_shipment_country( $shipping_country ) ) {
                    $customs_declaration = $this->get_customs_declaration( $order );
                    $shipment['customs_declaration'] = $customs_declaration;
                    $shipment['physical_properties'] = array(
                        'weight' => $customs_declaration['weight'],
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

        public function get_recipient( $order ) {
            $shipping_name = method_exists($order, 'get_formatted_shipping_full_name') ? $order->get_formatted_shipping_full_name() : trim( $order->shipping_first_name . ' ' . $order->shipping_last_name );
            $address = array(
                'cc'			=> (string) WCX_Order::get_prop( $order, 'shipping_country' ),
                'city'			=> (string) WCX_Order::get_prop( $order, 'shipping_city' ),
                'person'		=> $shipping_name,
                'company'		=> (string) WCX_Order::get_prop( $order, 'shipping_company' ),
                'email'			=> (string) WCX_Order::get_prop( $order, 'billing_email' ),
                'phone'			=> isset(WooCommerce_PostNL()->export_defaults['connect_phone']) ? WCX_Order::get_prop( $order, 'billing_phone' ) : '',
            );


            $shipping_country = WCX_Order::get_prop( $order, 'shipping_country' );
            if ( $shipping_country == 'NL' ) {
                // use billing address if old 'pakjegemak' (1.5.6 and older)
                if ( $pgaddress = WCX_Order::get_meta( $order, '_postnl_pgaddress' ) ) {
                    $billing_name = method_exists($order, 'get_formatted_billing_full_name') ? $order->get_formatted_billing_full_name() : trim( $order->billing_first_name . ' ' . $order->billing_last_name );
                    $address_intl = array(
                        'city'			=> (string) WCX_Order::get_prop( $order, 'billing_city' ),
                        'person'		=> $billing_name,
                        'company'		=> (string) WCX_Order::get_prop( $order, 'billing_company' ),
                        'street'		=> (string) WCX_Order::get_meta( $order, '_billing_street_name' ),
                        'number'		=> (string) WCX_Order::get_meta( $order, '_billing_house_number' ),
                        'number_suffix' => (string) WCX_Order::get_meta( $order, '_billing_house_number_suffix' ),
                        'postal_code'	=> (string) WCX_Order::get_prop( $order, 'billing_postcode' ),
                    );
                } else {
                    $address_intl = array(
                        'street'		=> (string) WCX_Order::get_meta( $order, '_shipping_street_name' ),
                        'number'		=> (string) WCX_Order::get_meta( $order, '_shipping_house_number' ),
                        'number_suffix' => (string) WCX_Order::get_meta( $order, '_shipping_house_number_suffix' ),
                        'postal_code'	=> (string) WCX_Order::get_prop( $order, 'shipping_postcode' ),
                    );
                }
            } else {
                $address_intl = array(
                    'postal_code'				=> (string) WCX_Order::get_prop( $order, 'shipping_postcode' ),
                    'street'					=> (string) WCX_Order::get_prop( $order, 'shipping_address_1' ),
                    'street_additional_info'	=> (string) WCX_Order::get_prop( $order, 'shipping_address_2' ),
                    'region'					=> (string) WCX_Order::get_prop( $order, 'shipping_state' ),
                );
            }

            $address = array_merge( $address, $address_intl);

            return apply_filters( 'wc_postnl_recipient', $address, $order );
        }

        public function get_options( $order ) {
            // parse description
            if (isset(WooCommerce_PostNL()->export_defaults['label_description'])) {
                $description = $this->replace_shortcodes( WooCommerce_PostNL()->export_defaults['label_description'], $order );
            } else {
                $description = '';
            }

            // use shipment options from order when available
            $shipment_options = WCX_Order::get_meta( $order, '_postnl_shipment_options' );
            if (!empty($shipment_options)) {
                $emty_defaults = array(
                    'package_type'		=> 1,
                    'only_recipient'	=> 0,
                    'signature'			=> 0,
                    'return'			=> 0,
                    'label_description'	=> '',
                    'insured_amount'	=> 0,
                );
                $options = array_merge($emty_defaults, $shipment_options);
            } else {
                if (isset(WooCommerce_PostNL()->export_defaults['insured']) && WooCommerce_PostNL()->export_defaults['insured_amount'] == '' && isset(WooCommerce_PostNL()->export_defaults['insured_amount_custom'])) {
                    $insured_amount = WooCommerce_PostNL()->export_defaults['insured_amount_custom'];
                } elseif (isset(WooCommerce_PostNL()->export_defaults['insured']) && isset(WooCommerce_PostNL()->export_defaults['insured_amount'])) {
                    $insured_amount = WooCommerce_PostNL()->export_defaults['insured_amount'];
                } else {
                    $insured_amount = 0;
                }

                $options = array(
                    'package_type'		=> $this->get_package_type_for_order( $order ),
                    'only_recipient'	=> (isset(WooCommerce_PostNL()->export_defaults['only_recipient'])) ? 1 : 0,
                    'signature'			=> (isset(WooCommerce_PostNL()->export_defaults['signature'])) ? 1 : 0,
                    'return'			=> (isset(WooCommerce_PostNL()->export_defaults['return'])) ? 1 : 0,
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
            $postnl_delivery_options = WCX_Order::get_meta( $order, '_postnl_delivery_options' );

            // set delivery type
            $options['delivery_type'] = $this->get_delivery_type( $order, $postnl_delivery_options );

            // Options for Pickup and Pickup express delivery types:
            // always enable signature on receipt
            if ( $this->is_pickup( $order, $postnl_delivery_options ) ) {
                $options['signature'] = 1;
            }

		// delivery date (postponed delivery & pickup)
		if ($delivery_date = $this->get_delivery_date( $order, $myparcel_delivery_options ) ) {
			$date_time = explode(' ', $delivery_date); // split date and time
			// only add if date is in the future
			$timestamp = strtotime($date_time[0]);

			if ( $timestamp < time() ) {
                $new_timestamp= $this->get_next_delivery_day($timestamp);
                $delivery_date = date( 'Y-m-d h:i:s', $new_timestamp );
			}

			$options['delivery_date'] = $delivery_date;
		}

        // options signed & recipient only
		$myparcel_signed = WCX_Order::get_meta( $order, '_postnl_signed' );
		if (!empty($myparcel_signed)) {
			$options['signature'] = 1;
		}
		$myparcel_only_recipient = WCX_Order::get_meta( $order, '_postnl_only_recipient' );
		if (!empty($myparcel_only_recipient)) {
			$options['only_recipient'] = 1;
		}

            // allow prefiltering consignment data
            $options = apply_filters( 'wc_postnl_order_shipment_options', $options, $order );

            // PREVENT ILLEGAL SETTINGS
            // convert numeric strings to int
            $int_options = array( 'package_type', 'delivery_type', 'only_recipient', 'signature', 'return' );
            foreach ($options as $key => &$value) {
                if ( in_array($key, $int_options) ) {
                    $value = (int) $value;
                }
            }

            // disable options for mailbox package and unpaid letter
            // echo '<pre>';var_dump($package_type);echo '</pre>';die();
            if ( $options['package_type'] != 1 ) {
                $illegal_options = array( 'delivery_type', 'only_recipient', 'signature', 'return', 'insurance', 'delivery_date' );
                foreach ($options as $key => $option) {
                    if (in_array($key, $illegal_options)) {
                        unset($options[$key]);
                    }
                }
            }

            return $options;

        }

        /**
         * @param int $timestamp
         *
         * @return false|string
         */
        private function get_next_delivery_day($timestamp) {
            $weekDay = date('w', $timestamp);
            $new_timestamp = strtotime( '+1 day', $timestamp );

            if ($weekDay == 0 || $weekDay == 1 || $new_timestamp < time() ) {
                $new_timestamp = $this->get_next_delivery_day( $new_timestamp );
            }

            return $new_timestamp;
        }

        public function get_customs_declaration( $order ) {
            $weight = (int) round( $this->get_parcel_weight( $order ) * 1000 );
            $invoice = $this->get_invoice_number( $order );
            $contents = (int) ( (isset(WooCommerce_PostNL()->export_defaults['package_contents'])) ? WooCommerce_PostNL()->export_defaults['package_contents'] : 1 );

            // Item defaults:
            // Classification
            $default_hs_code = (isset(WooCommerce_PostNL()->export_defaults['hs_code'])) ? WooCommerce_PostNL()->export_defaults['hs_code'] : '';
            // Country (=shop base)
            $country = WC()->countries->get_base_country();

            $items = array();
            foreach ( $order->get_items() as $item_id => $item ) {
                $product = $order->get_product_from_item( $item );
                if ( !empty( $product )) {
                    // Description
                    $description = $item['name'];
                    // Amount
                    $amount = (int) ( isset($item['qty']) ? $item['qty'] : 1 );
                    // Weight (total item weight in grams)
                    $weight = (int) round( $this->get_item_weight_kg ( $item, $order ) * 1000 );
                    // Item value (in cents)
                    $item_value = array(
                        'amount'	=> (int) round( ( $item['line_total'] + $item['line_tax'] ) * 100 ),
                        'currency'	=> WCX_Order::get_prop( $order, 'currency' ),
                    );
                    // Classification / HS Code
                    $classification = WCX_Product::get_meta( $product, '_postnl_hs_code', true );
                    if (empty($classification)) {
                        $classification = $default_hs_code;
                    }

                    // add item to item list
                    $items[] = compact( 'description', 'amount', 'weight', 'item_value', 'classification', 'country' );
                }
            }

            return compact( 'weight', 'invoice', 'contents', 'items' );
        }

        public function validate_shipments( $shipments, $output_errors = true ) {
            $missing_hs_codes = 0;
            foreach ($shipments as $key => $shipment) {
                // check customs declaration for HS codes
                if (isset($shipment['customs_declaration']) && !empty($shipment['customs_declaration']['items'])) {
                    foreach ($shipment['customs_declaration']['items'] as $key => $item) {
                        if (empty($item['classification'])) {
                            unset($shipments[$key]);
                            $missing_hs_codes++;
                            break;
                        }
                    }
                }
                if ($output_errors === true && $missing_hs_codes > 0) {
                    $this->errors[] = sprintf( __( '%d shipments missing HS codes - not exported.', 'woocommerce-postnl' ), $missing_hs_codes);
                }
            }

            return $shipments;
        }

        public function get_shipment_ids( $order_ids, $args ) {
            $shipment_ids = array();
            foreach ($order_ids as $order_id) {
                $order = WCX::get_order( $order_id );
                $order_shipments = WCX_Order::get_meta( $order, '_postnl_shipments' );
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
                        $last_shipment_ids = WCX_Order::get_meta( $order, '_postnl_last_shipment_ids' );
                        if ( !empty( $last_shipment_ids ) && is_array( $last_shipment_ids ) ) {
                            foreach ($order_shipment_ids as $order_shipment_id) {
                                if ( in_array($order_shipment_id, $last_shipment_ids ) ) {
                                    $shipment_ids[] = $order_shipment_id;
                                }
                            }
                        } else {
                            $shipment_ids[] = array_pop( $order_shipment_ids );
                        }
                    } else {
                        $shipment_ids[] = array_merge( $shipment_ids, $order_shipment_ids );
                    }
                }
            }

            return $shipment_ids;
        }

        public function save_shipment_data ( $order, $shipment ) {
            if ( empty($shipment) ) {
                return false;
            }

            $new_shipments = array();
            $new_shipments[$shipment['shipment_id']] = $shipment;
            // don't store full shipment data
            // if (isset($shipment['shipment'])) {
            // unset($shipment['shipment']);
            // }

            if ( isset(WooCommerce_PostNL()->general_settings['keep_shipments']) ) {
                if ( $old_shipments = WCX_Order::get_meta( $order, '_postnl_shipments' ) ) {
                    $shipments = $old_shipments;
                    foreach ($new_shipments as $shipment_id => $shipment) {
                        $shipments[$shipment_id] = $shipment;
                    }
                }
            }

            $shipments = isset($shipments) ? $shipments : $new_shipments;

            WCX_Order::update_meta_data( $order, '_postnl_shipments', $shipments );

            return;
        }

        public function get_package_type_from_shipping_method( $shipping_method, $shipping_class, $shipping_country ) {
            $package_type = 1;
            if (isset(WooCommerce_PostNL()->export_defaults['shipping_methods_package_types'])) {
                if ( strpos($shipping_method, "table_rate:") === 0 && class_exists('WC_Table_Rate_Shipping') ) {
                    // Automattic / WooCommerce table rate
                    // use full method = method_id:instance_id:rate_id
                    $shipping_method_id = $shipping_method;
                } else { // non table rates

                    if ( strpos($shipping_method, ':') !== false ) {
                        // means we have method_id:instance_id
                        $shipping_method = explode(':', $shipping_method);
                        $shipping_method_id = $shipping_method[0];
                        $shipping_method_instance = $shipping_method[1];
                    } else {
                        $shipping_method_id = $shipping_method;
                    }

                    // add class if we have one
                    if (!empty($shipping_class)) {
                        $shipping_method_id_class = "{$shipping_method_id}:{$shipping_class}";
                    }
                }

                foreach (WooCommerce_PostNL()->export_defaults['shipping_methods_package_types'] as $package_type_key => $package_type_shipping_methods ) {
                    // check if we have a match with the predefined methods
                    // fallback to bare method (without class) (if bare method also defined in settings)
                    if (in_array($shipping_method_id, $package_type_shipping_methods) || (!empty($shipping_method_id_class) && in_array($shipping_method_id_class, $package_type_shipping_methods))) {
                        $package_type = $package_type_key;
                        break;
                    }
                }
            }

            // disable mailbox package outside NL
            if ($shipping_country != 'NL' && $package_type == 2 ) {
                $package_type = 1;
            }

            return $package_type;
        }

        // determine appropriate package type for this order
        public function get_package_type_for_order( $order ) {
            $shipping_country = WCX_Order::get_prop( $order, 'shipping_country' );

            // get shipping methods from order
            $order_shipping_methods = $order->get_items('shipping');

            if ( !empty( $order_shipping_methods ) ) {
                // we're taking the first (we're not handling multiple shipping methods as of yet)
                $order_shipping_method = array_shift($order_shipping_methods);
                $order_shipping_method = $order_shipping_method['method_id'];

                $order_shipping_class = WCX_Order::get_meta( $order, '_postnl_highest_shipping_class' );
                if (empty($order_shipping_class)) {
                    $order_shipping_class = $this->get_order_shipping_class( $order, $order_shipping_method );
                }

                $package_type = $this->get_package_type_from_shipping_method( $order_shipping_method, $order_shipping_class, $shipping_country );
            }

            // fallbacks if no match from previous
            if (!isset($package_type)) {
                if ((isset(WooCommerce_PostNL()->export_defaults['package_type']))) {
                    $package_type = WooCommerce_PostNL()->export_defaults['package_type'];
                } else {
                    $package_type = 1; // 1. package | 2. mailbox package | 3. letter
                }
            }

            // always parcel for Pickup and Pickup express delivery types.
            if ( $this->is_pickup( $order ) ) {
                $package_type = 1;
            }

            return $package_type;
        }

        public function get_package_types( $shipment_type = 'shipment' ) {
            $package_types = array(
                1	=> __( 'Parcel' , 'woocommerce-postnl' ),
                2	=> __( 'Mailbox package' , 'woocommerce-postnl' ),
                3	=> __( 'Unpaid letter' , 'woocommerce-postnl' ),
            );
            if ( $shipment_type == 'return' ) {
                unset($package_types[2]);
                unset($package_types[3]);
            }

            return $package_types;
        }

        public function get_package_name( $package_type ) {
            $package_types = $this->get_package_types();
            $package_name = isset($package_types[$package_type]) ? $package_types[$package_type] : __( 'Unknown' , 'woocommerce-postnl' );
            return $package_name;
        }

        public function parse_errors( $errors ) {
            $parsed_errors = array();
            foreach ($errors as $key => $error) {
                // check if we have an order_id
                if ($key > 10) {
                    $parsed_errors[] = sprintf("<strong>%s %s:</strong> %s", __( 'Order', 'woocommerce-postnl' ), $key, $error );
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
            $filename  = 'PostNL';
            $filename .= '-' . date('Y-m-d') . '.pdf';

            return apply_filters( 'wcpostnl_filename', $filename, $order_ids );
        }

        public function get_shipment_status_name( $status_code ) {
            $shipment_statuses = array(
                1	=> __('pending - concept', 'woocommerce-postnl'),
                2	=> __('pending - registered', 'woocommerce-postnl'),
                3	=> __('enroute - handed to carrier', 'woocommerce-postnl'),
                4	=> __('enroute - sorting', 'woocommerce-postnl'),
                5	=> __('enroute - distribution', 'woocommerce-postnl'),
                6	=> __('enroute - customs', 'woocommerce-postnl'),
                7	=> __('delivered - at recipient', 'woocommerce-postnl'),
                8	=> __('delivered - ready for pickup', 'woocommerce-postnl'),
                9	=> __('delivered - package picked up', 'woocommerce-postnl'),
                30	=> __('inactive - concept', 'woocommerce-postnl'),
                31	=> __('inactive - registered', 'woocommerce-postnl'),
                32	=> __('inactive - enroute - handed to carrier', 'woocommerce-postnl'),
                33	=> __('inactive - enroute - sorting', 'woocommerce-postnl'),
                34	=> __('inactive - enroute - distribution', 'woocommerce-postnl'),
                35	=> __('inactive - enroute - customs', 'woocommerce-postnl'),
                36	=> __('inactive - delivered - at recipient', 'woocommerce-postnl'),
                37	=> __('inactive - delivered - ready for pickup', 'woocommerce-postnl'),
                38	=> __('inactive - delivered - package picked up', 'woocommerce-postnl'),
                99	=> __('inactive - unknown', 'woocommerce-postnl'),
            );

            if (isset($shipment_statuses[$status_code])) {
                return $shipment_statuses[$status_code];
            } else {
                return __('Unknown status', 'woocommerce-postnl');
            }
        }

        public function get_shipment_data( $id, $order ) {
        	try {
                $api = $this->init_api();
                $response = $api->get_shipments( $id );

                if (!empty($response['body']['data']['shipments'])) {
                    $shipments = $response['body']['data']['shipments'];
                    $shipment = array_shift($shipments);

                    // if shipment id matches and status is not concept, get tracktrace barcode and status name
                    if ( isset($shipment['id']) && $shipment['id'] == $id && $shipment['status'] >= 2 )  {
                        $status = $this->get_shipment_status_name( $shipment['status']);
                        $tracktrace = $shipment['barcode'];
                        $shipment_id = $id;
                        $shipment_data = compact( 'shipment_id', 'status', 'tracktrace', 'shipment');
                        $this->save_shipment_data( $order, $shipment_data );
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

        public function replace_shortcodes( $description, $order ) {
            $postnl_delivery_options = WCX_Order::get_meta( $order, '_postnl_delivery_options' );
            $replacements = array(
                '[ORDER_NR]'		=> $order->get_order_number(),
                '[DELIVERY_DATE]'	=> isset($postnl_delivery_options) && isset($postnl_delivery_options['date']) ? $postnl_delivery_options['date'] : '',
            );

            $description = str_replace(array_keys($replacements), array_values($replacements), $description);

            return $description;
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
            $parcel_weight = (isset(WooCommerce_PostNL()->general_settings['empty_parcel_weight'])) ? preg_replace("/\D/","",WooCommerce_PostNL()->general_settings['empty_parcel_weight'])/1000 : 0;

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

            $item_weight = (float) $product_weight * (int) $item['qty'];

            return $item_weight;
        }

        public function is_pickup( $order, $postnl_delivery_options = '' ) {
            if (empty($postnl_delivery_options)) {
                $postnl_delivery_options = WCX_Order::get_meta( $order, '_postnl_delivery_options' );
            }

            $pickup_types = array( 'retail', 'retailexpress' );
            if ( !empty($postnl_delivery_options['price_comment']) && in_array($postnl_delivery_options['price_comment'], $pickup_types) ) {
                return $postnl_delivery_options;
            }

            // Backwards compatibility for pakjegemak data
            $pgaddress = WCX_Order::get_meta( $order, '_postnl_pgaddress' );
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

        public function get_delivery_type( $order, $postnl_delivery_options = '' ) {
            // delivery types
            $delivery_types = array(
                'morning'		=> 1,
                'standard'		=> 2, // 'default in JS API'
                'night'			=> 3,
                'retail'		=> 4, // 'pickup'
                'retailexpress'	=> 5, // 'pickup_express'
            );

            if (empty($postnl_delivery_options)) {
                $postnl_delivery_options = WCX_Order::get_meta( $order, '_postnl_delivery_options' );
            }

            // standard = default, overwrite if otpions found
            $delivery_type = 'standard';
            if (!empty($postnl_delivery_options)) {
                // pickup & pickupexpress store the delivery type in the delivery options,
                // morning & night store it in the time data (...)
                if ( empty($postnl_delivery_options['price_comment']) && !empty($postnl_delivery_options['time']) ) {
                    // check if we have a price_comment in the time option
                    $delivery_time = array_shift($postnl_delivery_options['time']); // take first element in time array
                    if (isset($delivery_time['price_comment'])) {
                        $delivery_type = $delivery_time['price_comment'];
                    }
                } else {
                    $delivery_type = $postnl_delivery_options['price_comment'];
                }
            }

            // backwards compatibility for pakjegemak
            if ( $pgaddress = WCX_Order::get_meta( $order, '_postnl_pgaddress' ) ) {
                $delivery_type = 'retail';
            }

            // convert to int (default to 2 = standard for unknown types)
            $delivery_type = isset($delivery_types[$delivery_type]) ? $delivery_types[$delivery_type] : 2;

            return $delivery_type;
        }

        public function get_delivery_date( $order, $postnl_delivery_options = '' ) {
            if (empty($postnl_delivery_options)) {
                $postnl_delivery_options = WCX_Order::get_meta( $order, '_postnl_delivery_options' );
            }


            if ( !empty($postnl_delivery_options) && !empty($postnl_delivery_options['date']) ) {
                $delivery_date = $postnl_delivery_options['date'];

                $delivery_type = $this->get_delivery_type( $order, $postnl_delivery_options );
                if ( in_array($delivery_type, array(1,3)) && !empty($postnl_delivery_options['time']) ) {
                    $delivery_time_options = array_shift($postnl_delivery_options['time']); // take first element in time array
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
                if ( empty( $chosen_method[1] ) ) {
                    return false; // no instance known (=probably manual order)
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
                if ( $class_cost > $highest_class_cost && !empty($shipping_class_term->term_id) ) {
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
                    // normally this should pass the $product object, but only in the checkout this contains
                    // quantity & line_total (which is all we need), so we pass data from the $item instead
                    $item_product = new stdClass;
                    $item_product->quantity = $item['qty'];
                    $item_product->line_total = $item['line_total'];
                    $found_shipping_classes[ $found_class ][ $item_id ] = $item_product;
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

        public function filter_postnl_destination_orders( $order_ids ) {
            foreach ($order_ids as $key => $order_id) {
                $order = WCX::get_order( $order_id );
                $shipping_country = WCX_Order::get_prop( $order, 'shipping_country' );
                // skip non-postnl destination orders
                if ( !$this->is_postnl_destination( $shipping_country ) ) {
                    unset($order_ids[$key]);
                }
            }
            return $order_ids;
        }

        public function is_postnl_destination( $country_code ) {
            return ( $country_code == 'NL' || $this->is_eu_country( $country_code ) || $this->is_world_shipment_country( $country_code ) );
        }

        public function is_eu_country($country_code) {
            // $eu_countries = array( 'GB', 'AT', 'BE', 'BG', 'HR', 'CY', 'CZ', 'DK', 'EE', 'FI', 'FR', 'DE', 'EL', 'HU', 'IE', 'IT', 'LV', 'LT', 'LU', 'MT', 'NL', 'PL', 'PT', 'RO', 'SK', 'SI', 'ES', 'SE' );
            $euro_countries = array( 'AT','BE','BG','CZ','DK','EE','FI','FR','DE','GB','GR','HU','IE','IT','LV','LT','LU','PL','PT','RO','SK','SI','ES','SE','MC','AL','AD','BA','IC','FO','GI','GL','GG','IS','JE','HR','LI','MK','MD','ME','NO','UA','SM','RS','TR','VA','BY','CH' );
            return in_array( $country_code, $euro_countries);
        }

        public function is_world_shipment_country( $country_code ) {
            $world_shipment_countries = array( 'AF','AQ','DZ','VI','AO','AG','AR','AM','AW','AU','AZ','BS','BH','BD','BB','BZ','BJ','BM','BT','BO','BW','BR','VG','BN','BF','BI','KH','CA','KY','CF','CL','CN','CO','KM','CG','CD','CR','CU','DJ','DM','DO','EC','EG','SV','GQ','ER','ET','FK','FJ','PH','GF','PF','GA','GM','GE','GH','GD','GP','GT','GN','GW','GY','HT','HN','HK','IN','ID','IQ','IR','IL','CI','JM','JP','YE','JO','CV','CM','KZ','KE','KG','KI','KW','LA','LS','LB','LR','LY','MO','MG','MW','MV','MY','ML','MA','MQ','MR','MU','MX','MN','MS','MZ','MM','NA','NR','NP','NI','NC','NZ','NE','NG','KP','UZ','OM','TL','PK','PA','PG','PY','PE','PN','PR','QA','RE','RU','RW','KN','LC','VC','PM','WS','ST','SA','SN','SC','SL','SG','SO','LK','SD','SR','SZ','SY','TJ','TW','TZ','TH','TG','TO','TT','TD','TN','TM','TC','TV','UG','UY','VU','VE','AE','US','VN','ZM','ZW','ZA','KR','AN','BQ','CW','SX','XK','IM','MT','CY' );
            return in_array( $country_code, $world_shipment_countries);
        }

        public function get_invoice_number( $order ) {
            return (string) apply_filters( 'wc_postnl_invoice_number', $order->get_order_number() );
        }

        public function log( $message ) {
            if (isset(WooCommerce_PostNL()->general_settings['error_logging'])) {
                if( class_exists('WC_Logger') ) {
                    $wc_logger = function_exists('wc_get_logger') ? wc_get_logger() : new WC_Logger();
                    $wc_logger->add('wc-postnl', $message );
                } else {
                    // Old WC versions didn't have a logger
                    // log file in upload folder - wp-content/uploads
                    $upload_dir = wp_upload_dir();
                    $upload_base = trailingslashit( $upload_dir['basedir'] );
                    $log_file = $upload_base.'postnl_log.txt';

                    $current_date_time = date("Y-m-d H:i:s");
                    $message = $current_date_time .' ' .$message ."\n";

                    file_put_contents($log_file, $message, FILE_APPEND);
                }
            }
        }
    }

endif; // class_exists

return new WooCommerce_PostNL_Export();
