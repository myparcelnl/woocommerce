<?php
use WPO\WC\MyParcelBE\Compatibility\WC_Core as WCX;
use WPO\WC\MyParcelBE\Compatibility\Order   as WCX_Order;
use WPO\WC\MyParcelBE\Compatibility\Product as WCX_Product;

/**
 * Frontend views
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( !class_exists( 'WooCommerce_MyParcelBE_Frontend' ) ) :

	class WooCommerce_MyParcelBE_Frontend {

        /*
         * @var WooCommerce_MyParcelBE_Frontend_Settings
         */
		private $frontend_settings;

		function __construct()	{
			// Customer Emails
			if (isset(WooCommerce_MyParcelBE()->general_settings['email_tracktrace'])) {
				add_action( 'woocommerce_email_before_order_table', array( $this, 'track_trace_email' ), 10, 2 );
			}

			// Track & trace in my account
			if (isset(WooCommerce_MyParcelBE()->general_settings['myaccount_tracktrace'])) {
				add_filter( 'woocommerce_my_account_my_orders_actions', array( $this, 'track_trace_myaccount' ), 10, 2 );
			}

			// pickup address in email
			// woocommerce_email_customer_details:
			// @10 = templates/email-customer-details.php
			// @20 = templates/email-addresses.php
			add_action( 'woocommerce_email_customer_details', array( $this, 'email_pickup_html'), 19, 3 );

			// pickup address on thank you page
			add_action( 'woocommerce_thankyou', array( $this, 'thankyou_pickup_html'), 10, 1 );

			// WooCommerce PDF Invoices & Packing Slips Premium Templates compatibility
			add_filter( 'wpo_wcpdf_templates_replace_myparcelbe_delivery_date', array( $this, 'wpo_wcpdf_delivery_date' ), 10, 2 );
			add_filter( 'wpo_wcpdf_templates_replace_myparcelbe_tracktrace', array( $this, 'wpo_wcpdf_tracktrace' ), 10, 2 );
			add_filter( 'wpo_wcpdf_templates_replace_myparcelbe_tracktrace_link', array( $this, 'wpo_wcpdf_tracktrace_link' ), 10, 2 );
			add_filter( 'wpo_wcpdf_templates_replace_myparcelbe_delivery_options', array( $this, 'wpo_wcpdf_delivery_options' ), 10, 2 );

			// Delivery options
			if (isset(WooCommerce_MyParcelBE()->checkout_settings['myparcelbe_checkout'])) {
				add_action( apply_filters( 'wc_myparcelbe_delivery_options_location', 'woocommerce_after_checkout_billing_form' ), array( $this, 'output_delivery_options' ), 10, 1 );
			}

			// Save delivery options data
			add_action( 'woocommerce_checkout_update_order_meta', array( $this, 'save_delivery_options' ), 10, 2 );

			// Delivery options fees
			add_action( 'woocommerce_cart_calculate_fees', array( $this, 'delivery_options_fees' ) );

			// Output most expensive shipping class in frontend data
			add_action( 'woocommerce_checkout_after_order_review', array( $this, 'output_shipping_data' ) );
			add_action( 'woocommerce_update_order_review_fragments', array( $this, 'order_review_fragments' ) );

			/* @todo remove require_once() */
			require_once( WooCommerce_MyParcelBE()->plugin_path() . '/includes/class-wcmp-frontend-settings.php' );

			$this->frontend_settings = new WooCommerce_MyParcelBE_Frontend_Settings();
		}

		public function track_trace_email( $order, $sent_to_admin ) {

			if ( $sent_to_admin ) return;

			if ( WCX_Order::get_status( $order ) != 'completed') return;

			$order_id = WCX_Order::get_id( $order );

			$tracktrace_links = WooCommerce_MyParcelBE()->admin->get_tracktrace_links ( $order_id );
			if ( !empty($tracktrace_links) ) {
				$email_text = __( 'You can track your order with the following PostNL track&trace code:', 'woocommerce-myparcelbe' );
				$email_text = apply_filters( 'wcmyparcelbe_email_text', $email_text, $order );
				?>
                <p><?php echo $email_text.' '.implode(', ', $tracktrace_links); ?></p>

				<?php
			}
		}

		public function email_pickup_html( $order, $sent_to_admin = false, $plain_text = false ) {
			WooCommerce_MyParcelBE()->admin->show_order_delivery_options( $order );
		}

		public function thankyou_pickup_html( $order_id ) {
			$order = wc_get_order( $order_id );
			WooCommerce_MyParcelBE()->admin->show_order_delivery_options( $order );
		}

		public function track_trace_myaccount( $actions, $order ) {
			$order_id = WCX_Order::get_id( $order );
			if ( $consignments = WooCommerce_MyParcelBE()->admin->get_tracktrace_shipments( $order_id ) ) {
				foreach ($consignments as $key => $consignment) {
					$actions['myparcelbe_tracktrace_'.$consignment['tracktrace']] = array(
						'url'  => $consignment['tracktrace_url'],
						'name' => apply_filters( 'wcmyparcelbe_myaccount_tracktrace_button', __( 'Track&Trace', 'wooocommerce-myparcelbe' ) )
					);
				}
			}

			return $actions;
		}

		//  @deprecated ?
		public function wpo_wcpdf_delivery_options( $replacement, $order ) {
			ob_start();
			WooCommerce_MyParcelBE()->admin->show_order_delivery_options( $order );
			return ob_get_clean();
		}


		// @deprecated
		public function wpo_wcpdf_delivery_date( $replacement, $order ) {
			if ($delivery_date = WooCommerce_MyParcelBE()->export->get_delivery_date( $order ) ) {
				$formatted_date = date_i18n( apply_filters( 'wcmyparcelbe_delivery_date_format', wc_date_format() ), strtotime( $delivery_date ) );
				return $formatted_date;
			}
			return $replacement;
		}

		public function wpo_wcpdf_tracktrace( $replacement, $order ) {
			if ( $shipments = WooCommerce_MyParcelBE()->admin->get_tracktrace_shipments( WCX_Order::get_id( $order ) ) ) {
				$tracktrace = array();
				foreach ($shipments as $shipment) {
					if (!empty($shipment['tracktrace'])) {
						$tracktrace[] = $shipment['tracktrace'];
					}
				}
				$replacement = implode(', ', $tracktrace);
			}
			return $replacement;
		}

		public function wpo_wcpdf_tracktrace_link( $replacement, $order ) {
			$tracktrace_links = WooCommerce_MyParcelBE()->admin->get_tracktrace_links ( WCX_Order::get_id( $order ) );
			if ( !empty($tracktrace_links) ) {
				$replacement = implode(', ', $tracktrace_links);
			}
			return $replacement;
		}

		/**
		 *
		 * Output some stuff.
		 *
		 */

		public function output_delivery_options() {
			// Don't load when cart doesn't need shipping
			if ( false == WC()->cart->needs_shipping() ) {
				return;
			}

			$urlJsConfig = WooCommerce_MyParcelBE()->plugin_url() . "/assets/delivery-options/js/myparcel.config.js";
			$urlJs       = WooCommerce_MyParcelBE()->plugin_url() . "/assets/delivery-options/js/myparcelbe.js";

			$jsonConfig  = $this->get_checkout_config();
			$myparcelBeShippingMethods = json_encode($this->get_shipping_methods());

			echo "<script> myParcelConfig = {$jsonConfig}; myparcelbe_delivery_options_shipping_methods = {$myparcelBeShippingMethods} </script>";
			require_once(WooCommerce_MyParcelBE()->plugin_path().'/includes/views/wcmp-checkout-template.php');

			return;
		}


		// XXX Move to Jquery ?
		public function output_shipping_data() {
			$shipping_data = $this->get_shipping_data();
			printf('<div class="myparcelbe-shipping-data">%s</div>', $shipping_data);
		}

		// XXX Move to Jquery ?
		public function get_shipping_data() {


			if ($shipping_class = $this->get_cart_shipping_class()) {
				$shipping_data = sprintf('<input type="hidden" value="%s" id="myparcelbe_highest_shipping_class" name="myparcelbe_highest_shipping_class">', $shipping_class);
				return $shipping_data;
			}
			return false;
		}

		/**
		 * Save delivery options to order when used
		 *
		 * @param  int   $order_id
		 * @param  array $posted
		 *
		 * @return void
		 */

		// XXX adapt this to new situation
		public function save_delivery_options( $order_id, $posted ) {
			$order = WCX::get_order( $order_id );


			if (isset($_POST['myparcelbe_highest_shipping_class'])) {
				WCX_Order::update_meta_data( $order, '_myparcelbe_highest_shipping_class', $_POST['myparcelbe_highest_shipping_class'] );
			}

			// mypa-recipient-only - 'on' or not set
			// mypa-signed         - 'on' or not set
			// mypa-post-be-data   - JSON of chosen delivery options

			// check if delivery options were used
			if (!isset($_POST['mypa-options-enabled'])) {
				return;
			}


			if (isset($_POST['mypa-signed'])) {
				WCX_Order::update_meta_data( $order, '_myparcelbe_signed', 'on' );
			}

			if (!empty($_POST['mypa-post-be-data'])) {
				$delivery_options = json_decode( stripslashes( $_POST['mypa-post-be-data']), true );
				WCX_Order::update_meta_data( $order, '_myparcelbe_delivery_options', $delivery_options );
			}
		}

		public function delivery_options_fees( $cart ) {
			$post_data = $this->get_post_data();

            /* @todo pickup no signed */

            if ($this->pickup_fee($post_data)) {
	            return;
            }

            $this->signed_fee($post_data);

            return;
		}

		public function add_fee( $fee_name, $fee ) {
			$fee = $this->normalize_price( $fee );
			// get shipping tax data
			$shipping_tax_class = $this->get_shipping_tax_class();
			if ( $shipping_tax_class ) {
				if ($shipping_tax_class == 'standard') {
					$shipping_tax_class = '';
				}
				WC()->cart->add_fee( $fee_name, $fee, true, $shipping_tax_class );
			} else {
				WC()->cart->add_fee( $fee_name, $fee );
			}
		}

		/**
		 * Get shipping tax class
		 * adapted from WC_Tax::get_shipping_tax_rates
		 *
		 * assumes per order shipping (per item shipping not supported for MyParcelbe yet)
		 * @return string tax class
		 */
		public function get_shipping_tax_class() {
			$shipping_tax_class = get_option( 'woocommerce_shipping_tax_class' );
			// WC3.0+ sets 'inherit' for taxes based on items, empty for 'standard'
			if ( version_compare( WOOCOMMERCE_VERSION, '3.0', '>=' ) && 'inherit' !== $shipping_tax_class ) {
				$shipping_tax_class = '' === $shipping_tax_class ? 'standard' : $shipping_tax_class;
				return $shipping_tax_class;
			} elseif ( !empty( $shipping_tax_class ) && 'inherit' !== $shipping_tax_class ) {
				return $shipping_tax_class;
			}

			if ( $shipping_tax_class == 'inherit' ) {
				$shipping_tax_class = '';
			}

			// See if we have an explicitly set shipping tax class
			if ( $shipping_tax_class ) {
				$tax_class = 'standard' === $shipping_tax_class ? '' : $shipping_tax_class;
			}

			$location          = WC_Tax::get_tax_location( '' );


			// XXX refactor completely
			if ( sizeof( $location ) === 4 ) {
				list( $country, $state, $postcode, $city ) = $location;

				// This will be per order shipping - loop through the order and find the highest tax class rate
				$cart_tax_classes = WC()->cart->get_cart_item_tax_classes();
				// If multiple classes are found, use the first one. Don't bother with standard rate, we can get that later.
				if ( sizeof( $cart_tax_classes ) > 1 && ! in_array( '', $cart_tax_classes ) ) {
					$tax_classes = WC_Tax::get_tax_classes();

					foreach ( $tax_classes as $tax_class ) {
						$tax_class = sanitize_title( $tax_class );
						if ( in_array( $tax_class, $cart_tax_classes ) ) {
							// correct $tax_class is now set
							break;
						}
					}

					// If a single tax class is found, use it
				} elseif ( sizeof( $cart_tax_classes ) == 1 ) {
					$tax_class = array_pop( $cart_tax_classes );
				}

				// no rate = standard rate
				if (empty($tax_class)) {
					$tax_class = 'standard';
				}
			}

			return $tax_class;
		}

		/**
		 * Get the most expensive shipping class in the cart
		 * Requires WC2.4+
		 *
		 * Only supports 1 package, takes the first
		 * @return [type] [description]
		 */
		public function get_cart_shipping_class() {
			if ( version_compare( WOOCOMMERCE_VERSION, '2.4', '<' ) ) {
				return false;
			}

			$chosen_method = isset( WC()->session->chosen_shipping_methods[ 0 ] ) ? WC()->session->chosen_shipping_methods[ 0 ] : '';

			// get package
			$packages = WC()->shipping->get_packages();
			$package = current($packages);

			$shipping_method = WooCommerce_MyParcelBE()->export->get_shipping_method($chosen_method);
			if (empty($shipping_method)) {
				return false;
			}

			// get shipping classes from package
			$found_shipping_classes = $shipping_method->find_shipping_classes( $package );
			// return print_r( $found_shipping_classes, true );

			$highest_class = WooCommerce_MyParcelBE()->export->get_shipping_class( $shipping_method, $found_shipping_classes );
			return $highest_class;
		}



		public function order_review_fragments( $fragments ) {
			$myparcelbe_shipping_data = $this->get_shipping_data();

			// echo '<pre>';var_dump($myparcelbe_shipping_data);echo '</pre>';die();
			$fragments['.myparcelbe-shipping-data'] = $myparcelbe_shipping_data;
			return $fragments;
		}

		// converts price string to float value, assuming no thousand-separators used
		// XXX money format
		public function normalize_price( $price ) {
			$price = str_replace(',', '.', $price);
			$price = floatval($price);

			return $price;
		}

		private function get_checkout_config()
		{

			$config = [
				'apiBaseUrl'                 => $this->frontend_settings->get_api_url(),
				'cutoffTime'                 => $this->frontend_settings->get_cutoff_time(),
				'saturdayCutoffTime'         => $this->frontend_settings->get_saturday_cutoff_time(),
				'dropoffDelay'               => $this->frontend_settings->get_dropoff_delay(),
				'deliverydaysWindow'         => $this->frontend_settings->get_deliverydays_window(),
				'dropoffDays'                => $this->frontend_settings->get_dropoff_days(),
				"countryCode"                => $this->frontend_settings->get_country_code(),
				"carrierCode"                => WooCommerce_MyParcelBE_Frontend_Settings::CARRIER_CODE,
				"carrierName"                => WooCommerce_MyParcelBE_Frontend_Settings::CARRIER_NAME,
				"allowBpostAutograph"        => $this->frontend_settings->is_signed_enabled(),
				"priceBpostAutograph"        => $this->frontend_settings->get_price_signature(),
				"allowBpostSaturdayDelivery" => $this->frontend_settings->is_saterday_delivery_enabled(),
				"priceBpostSaturdayDelivery" => $this->frontend_settings->get_price_saterday_delivery(),
			];

			return json_encode( $config );

			// Use cutoff_time and saturday_cutoff_time on saturdays
		}

		/**
         * Get shipping methods associated with parcels to enable delivery options
		 */
		private function get_shipping_methods() {
			
			if (
                $this->frontend_settings->get_checkout_display() != 'all_methods' &&
                isset( WooCommerce_MyParcelBE()->export_defaults['shipping_methods_package_types'][1] )
            ) {
				return WooCommerce_MyParcelBE()->export_defaults['shipping_methods_package_types'][1];
			}

            return array();
		}

		/**
		 * check if delivery method must hide
		 */
		private function is_hide_delivery_method() {
			if ($this->frontend_settings->get_checkout_display() == 'all_methods' ) {
				return false;
			}

			// determine whether to pre-hide iframe (prevents flashing)
			$chosen_shipping_methods = WC()->session->chosen_shipping_methods;
			if ( empty($chosen_shipping_methods) || !is_array($chosen_shipping_methods) ) {
				return false;
			}

            $shipping_country = WC()->customer->get_shipping_country();
            if ($shipping_country != 'BE') {
                return true;
            }

			return false;
		}

		/**
		 * @return null|array
		 */
		private function get_post_data() {
			if ( ! $_POST || ( is_admin() && ! is_ajax() ) ) {
				return null;
			}

			if ( isset( $_POST['post_data'] ) ) {
				// non-default post data for AJAX calls
				parse_str( $_POST['post_data'], $post_data );

				return $post_data;
			}

            // checkout finalization
            return $_POST;
		}

		/**
		 * @param $post_data
		 *
		 * @return bool
		 */
		private function pickup_fee( $post_data ) {
			// Fee for "pickup" option
			if (isset($post_data['test']) && $post_data['test'] == 'on') {
				if (!empty(WooCommerce_MyParcelBE()->checkout_settings['pickup_fee'])) {
					$fee = WooCommerce_MyParcelBE()->checkout_settings['pickup_fee'];
					$fee_name = __( 'Bpost pickup', 'woocommerce-myparcel' );
					$this->add_fee( $fee_name, $fee );

					return true;

				}
			}

			return false;
		}

		/**
		 * @param $post_data
		 *
		 * @return bool
		 */
		private function signed_fee($post_data) {
			// Fee for "signed" option
			if (isset($post_data['mypa-method-signature-selector-be']) && $post_data['mypa-method-signature-selector-be'] == 'on') {
				if (!empty(WooCommerce_MyParcelBE()->checkout_settings['signed_fee'])) {
					$fee = WooCommerce_MyParcelBE()->checkout_settings['signed_fee'];
					$fee_name = __( 'Signature on delivery', 'woocommerce-myparcelbe' );
					$this->add_fee( $fee_name, $fee );

					return true;
				}
			}

			return false;
		}
	}

endif; // class_exists

return new WooCommerce_MyParcelBE_Frontend();
