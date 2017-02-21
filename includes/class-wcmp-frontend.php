<?php
/**
 * Frontend views
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( !class_exists( 'WooCommerce_MyParcel_Frontend' ) ) :

class WooCommerce_MyParcel_Frontend {
	
	function __construct()	{
		// Customer Emails
		if (isset(WooCommerce_MyParcel()->general_settings['email_tracktrace'])) {
			add_action( 'woocommerce_email_before_order_table', array( $this, 'track_trace_email' ), 10, 2 );
		}

		// Track & trace in my account
		if (isset(WooCommerce_MyParcel()->general_settings['myaccount_tracktrace'])) {
			add_filter( 'woocommerce_my_account_my_orders_actions', array( $this, 'track_trace_myaccount' ), 10, 2 );
		}

		// pickup address in email
		// woocommerce_email_customer_details:
		// @10 = templates/email-customer-details.php
		// @20 = templates/email-addresses.php
		add_action( 'woocommerce_email_customer_details', array( $this, 'email_pickup_html'), 19, 3 );

		// Delivery options
		if (isset(WooCommerce_MyParcel()->checkout_settings['myparcel_checkout'])) {
			add_action( apply_filters( 'wc_myparcel_delivery_options_location', 'woocommerce_after_checkout_billing_form' ), array( $this, 'output_delivery_options' ), 10, 1 );
		}

		// Save delivery options data
		add_action( 'woocommerce_checkout_update_order_meta', array( $this, 'save_delivery_options' ), 10, 2 );

		// Delivery options fees
		add_action( 'woocommerce_cart_calculate_fees', array( $this, 'delivery_options_fees' ) );

		// Output most expensive shipping class in frontend data
		add_action( 'woocommerce_checkout_after_order_review', array( $this, 'output_shipping_data' ) );
		add_action( 'woocommerce_update_order_review_fragments', array( $this, 'order_review_fragments' ) );
	}

	public function track_trace_email( $order, $sent_to_admin ) {

		if ( $sent_to_admin ) return;

		if ( $order->status != 'completed') return;

		$tracktrace_links = WooCommerce_MyParcel()->admin->get_tracktrace_links ( $order->id );
		if ( !empty($tracktrace_links) ) {
			$email_text = __( 'You can track your order with the following PostNL track&trace code:', 'woocommerce-myparcel' );
			$email_text = apply_filters( 'wcmyparcel_email_text', $email_text, $order );
			?>
			<p><?php echo $email_text.' '.implode(', ', $tracktrace_links); ?></p>
	
			<?php
		}
	}

	public function email_pickup_html( $order, $sent_to_admin = false, $plain_text = false ) {
		WooCommerce_MyParcel()->admin->show_order_delivery_options( $order );
	}

	public function track_trace_myaccount( $actions, $order ) {
		if ( $consignments = WooCommerce_MyParcel()->admin->get_tracktrace_shipments( $order->id ) ) {
			foreach ($consignments as $key => $consignment) {
				$actions['myparcel_tracktrace_'.$consignment['tracktrace']] = array(
					'url'  => $consignment['tracktrace_url'],
					'name' => apply_filters( 'wcmyparcel_myaccount_tracktrace_button', __( 'Track&Trace', 'wooocommerce-myparcel' ) )
				);
			}
		}

		return $actions;
	}

	public function output_delivery_options() {
		// Don't load when cart doesn't need shipping
		if ( false == WC()->cart->needs_shipping() ) {
			return;
		}
		
		/**
		 * load settings etc.
		 */
		// get api url
		$ajax_url = admin_url( 'admin-ajax.php' );
		$request_prefix = strpos($ajax_url, '?') !== false ? '&' : '?';
		$frontend_api_url = wp_nonce_url( $ajax_url . $request_prefix . 'action=wc_myparcel_frontend', 'wc_myparcel_frontend' );

		// delivery types
		$delivery_types = array(
			1	=> 'morning',
			2	=> 'standard', // 'default in JS API'
			3	=> 'night',
			4	=> 'pickup',
			5	=> 'pickup_express',
		);
		// delivery options
		$delivery_options = array(
			'signed',
			'only_recipient',
		);

		// get delivery option fees/prices
		$price_options = array_merge( $delivery_options, $delivery_types );
		$prices = array();
		foreach ($price_options as $key => $option) {
			// JS API correction
			if ($option == 'standard') {
				$option = 'default';
			}

			if ( in_array($option,$delivery_options) && !isset(WooCommerce_MyParcel()->checkout_settings[$option.'_enabled']) ) {
				$prices[$option] = 'disabled';
			} elseif (!empty(WooCommerce_MyParcel()->checkout_settings[$option.'_fee'])) {
				$fee = WooCommerce_MyParcel()->checkout_settings[$option.'_fee'];
				$fee = $this->normalize_price( $fee );
				$fee_including_tax = $fee + array_sum( WC_Tax::calc_shipping_tax( $fee, WC_Tax::get_shipping_tax_rates() ) );
				$formatted_fee = wc_price($fee_including_tax); // this includes price HTML, may need to use custom function, also for &#8364; instead of eur
				$prices[$option] = '+ '.$formatted_fee;
			}
		}

		// exclude delivery types
		$exclude_delivery_types = array();
		foreach ($delivery_types as $key => $delivery_type) {
			// JS API correction
			if ($delivery_type == 'standard') {
				continue;
			}
			if (!isset(WooCommerce_MyParcel()->checkout_settings[$delivery_type.'_enabled'])) {
				$exclude_delivery_types[] = $key;
			}
		}
		$exclude_delivery_types = implode(';', $exclude_delivery_types);

		// combine settings
		$settings = array(
			'base_url'				=> $frontend_api_url,
			'exclude_delivery_type'	=> $exclude_delivery_types,
			'price'					=> $prices,
			'dropoff_delay'			=> isset(WooCommerce_MyParcel()->checkout_settings['dropoff_delay']) ? WooCommerce_MyParcel()->checkout_settings['dropoff_delay'] : '',
			'cutoff_time'			=> isset(WooCommerce_MyParcel()->checkout_settings['cutoff_time']) ? WooCommerce_MyParcel()->checkout_settings['cutoff_time'] : '',
			'deliverydays_window'	=> isset(WooCommerce_MyParcel()->checkout_settings['deliverydays_window']) ? max(1,WooCommerce_MyParcel()->checkout_settings['deliverydays_window']) : '',
			'dropoff_days'			=> isset(WooCommerce_MyParcel()->checkout_settings['dropoff_days']) ? implode(';', WooCommerce_MyParcel()->checkout_settings['dropoff_days'] ): '',
		);
		// remove empty options
		$settings = array_filter($settings);

		// encode settings for JS object
		$settings = json_encode($settings);

		if ( isset( WooCommerce_MyParcel()->checkout_settings['checkout_display'] ) && WooCommerce_MyParcel()->checkout_settings['checkout_display'] == 'all_methods' ) {
			$myparcel_delivery_options_always_display = 'yes';
			$delivery_options_shipping_methods = array();
		} elseif ( isset( WooCommerce_MyParcel()->export_defaults['shipping_methods_package_types'] ) && isset( WooCommerce_MyParcel()->export_defaults['shipping_methods_package_types'][1] ) ) {
			// Shipping methods associated with parcels = enable delivery options
			$delivery_options_shipping_methods = WooCommerce_MyParcel()->export_defaults['shipping_methods_package_types'][1];
		} else {
			$delivery_options_shipping_methods = array();
		}
		$delivery_options_shipping_methods = json_encode($delivery_options_shipping_methods);

		$iframe_url = WooCommerce_MyParcel()->plugin_url() . '/includes/views/wcmp-delivery-options.php';
		?>
		<iframe id="myparcel-iframe" src="<?php echo $iframe_url; ?>" frameborder="0" scrolling="auto" style="width: 100%;" onload="MyPaLoaded();">Bezig met laden...</iframe>
		<script>
		jQuery( function( $ ) {
			window.mypa = {};
			window.mypa.settings = <?php echo $settings; ?>;
			window.myparcel_delivery_options_shipping_methods = <?php echo $delivery_options_shipping_methods; ?>;
			<?php if (!empty($myparcel_delivery_options_always_display)): ?>
			window.myparcel_delivery_options_always_display = 'yes';
			<?php endif ?>

			// set reference to iFrame
			var $MyPaiFrame = $('#myparcel-iframe')[0];
			window.MyPaWindow = $MyPaiFrame.contentWindow ? $MyPaiFrame.contentWindow : $MyPaiFrame.contentDocument.defaultView;
		});
		</script>
		
		<input style="display:none" type="checkbox" name='mypa-options-enabled' id="mypa-options-enabled">
		<div id="mypa-chosen-delivery-options">
			<input style="display:none" name='mypa-post-nl-data' id="mypa-input">
			<input style="display:none" type="checkbox" name='mypa-signed' id="mypa-signed">
			<input style="display:none" type="checkbox" name='mypa-recipient-only' id="mypa-recipient-only">
		</div>
		<?php
	}

	public function output_shipping_data() {
		$shipping_data = $this->get_shipping_data();
		printf('<div class="myparcel-shipping-data">%s</div>', $shipping_data);
	}

	public function get_shipping_data() {
		if ($shipping_class = $this->get_cart_shipping_class()) {
			$shipping_data = sprintf('<input type="hidden" value="%s" id="myparcel_highest_shipping_class" name="myparcel_highest_shipping_class">', $shipping_class);
			return $shipping_data;
		} else {
			return false;
		}
	}

	/**
	 * Save delivery options to order when used
	 *
	 * @param  int   $order_id
	 * @param  array $posted
	 *
	 * @return void
	 */
	public function save_delivery_options( $order_id, $posted ) {
		// mypa-recipient-only - 'on' or not set  
		// mypa-signed         - 'on' or not set  
		// mypa-post-nl-data   - JSON of chosen delivery options
		
		// check if delivery options were used
		if (!isset($_POST['mypa-options-enabled'])) {
			return;
		}

		if (isset($_POST['mypa-signed'])) {
			update_post_meta( $order_id, '_myparcel_signed', 'on' );
		}

		if (isset($_POST['mypa-recipient-only'])) {
			update_post_meta( $order_id, '_myparcel_only_recipient', 'on' );
		}

		if (!empty($_POST['mypa-post-nl-data'])) {
			$delivery_options = json_decode( stripslashes( $_POST['mypa-post-nl-data']), true );
			update_post_meta( $order_id, '_myparcel_delivery_options', $delivery_options );
		}

		if (isset($_POST['myparcel_highest_shipping_class'])) {
			update_post_meta( $order_id, '_myparcel_highest_shipping_class', $_POST['myparcel_highest_shipping_class'] );
		}
	}

	public function delivery_options_fees( $cart ) {
		global $woocommerce;
		if ( ! $_POST || ( is_admin() && ! is_ajax() ) ) {
			return;
		}

		if ( isset( $_POST['post_data'] ) ) {
			// non-default post data for AJAX calls
			parse_str( $_POST['post_data'], $post_data );
		} else {
			// checkout finalization
			$post_data = $_POST;
		}

		// check for delivery options & add fees
		if (!empty($post_data['mypa-post-nl-data'])) {
			$delivery_options = json_decode( stripslashes( $post_data['mypa-post-nl-data']), true );
			// Fees for pickup & pickup express
			if (isset($delivery_options['price_comment'])) {
				switch ($delivery_options['price_comment']) {
					case 'retail':
						if (!empty(WooCommerce_MyParcel()->checkout_settings['pickup_fee'])) {
							$fee = WooCommerce_MyParcel()->checkout_settings['pickup_fee'];
							$fee_name = __( 'PostNL Pickup', 'woocommerce-myparcel' );
						}
						break;
					case 'retailexpress':
						if (!empty(WooCommerce_MyParcel()->checkout_settings['pickup_express_fee'])) {
							$fee = WooCommerce_MyParcel()->checkout_settings['pickup_express_fee'];
							$fee_name = __( 'PostNL Pickup Express', 'woocommerce-myparcel' );
						}
						break;
				}

				if (!empty($fee)) {
					$this->add_fee( $fee_name, $fee );
				}
			}

			// Fees for delivery time options
			if (isset($delivery_options['time'])) {
				$time = array_shift($delivery_options['time']); // take first element in time array
				if (isset($time['price_comment'])) {
					switch ($time['price_comment']) {
						case 'morning':
							$only_recipient_included = true;
							if (!empty(WooCommerce_MyParcel()->checkout_settings['morning_fee'])) {
								$fee = WooCommerce_MyParcel()->checkout_settings['morning_fee'];
								$fee_name = __( 'Morning delivery', 'woocommerce-myparcel' );
							}
							break;
						case 'standard':
							if (!empty(WooCommerce_MyParcel()->checkout_settings['default_fee'])) {
								$fee = WooCommerce_MyParcel()->checkout_settings['default_fee'];
								$fee_name = __( 'Standard delivery', 'woocommerce-myparcel' );
							}
							break;
						case 'night':
							$only_recipient_included = true;
							if (!empty(WooCommerce_MyParcel()->checkout_settings['night_fee'])) {
								$fee = WooCommerce_MyParcel()->checkout_settings['night_fee'];
								$fee_name = __( 'Evening delivery', 'woocommerce-myparcel' );
							}
							break;
					}

					if (!empty($fee)) {
						$this->add_fee( $fee_name, $fee );
					}
				}

			}
		}

		// Fee for "signed" option
		if (isset($post_data['mypa-signed'])) {
			if (!empty(WooCommerce_MyParcel()->checkout_settings['signed_fee'])) {
				$fee = WooCommerce_MyParcel()->checkout_settings['signed_fee'];
				$fee_name = __( 'Signature on delivery', 'woocommerce-myparcel' );
				$this->add_fee( $fee_name, $fee );
			}
		}

		// Fee for "only recipient" option, don't apply fee for morning & night delivery (already included)
		if (isset($post_data['mypa-recipient-only']) && empty($only_recipient_included)) {
			if (!empty(WooCommerce_MyParcel()->checkout_settings['only_recipient_fee'])) {
				$fee = WooCommerce_MyParcel()->checkout_settings['only_recipient_fee'];
				$fee_name = __( 'Home address only delivery', 'woocommerce-myparcel' );
				$this->add_fee( $fee_name, $fee );
			}
		}

	}

	public function add_fee( $fee_name, $fee ) {
		global $woocommerce; // should be rewritten to WC with fallback functions
		$fee = $this->normalize_price( $fee );
		// get shipping tax data
		$shipping_tax_class = $this->get_shipping_tax_class();
		if ( $shipping_tax_class ) {
			if ($shipping_tax_class == 'standard') {
				$shipping_tax_class = '';
			}
			$woocommerce->cart->add_fee( $fee_name, $fee, true, $shipping_tax_class );
		} else {
			$woocommerce->cart->add_fee( $fee_name, $fee );
		}
	}

	/**
	 * Get shipping tax class
	 * adapted from WC_Tax::get_shipping_tax_rates
	 *
	 * assumes per order shipping (per item shipping not supported for MyParcel yet)
	 * @return string tax class
	 */
	public function get_shipping_tax_class() {
		global $woocommerce; // should be rewritten to WC with fallback functions in future WooCommerce versions

		// See if we have an explicitly set shipping tax class
		if ( $shipping_tax_class = get_option( 'woocommerce_shipping_tax_class' ) ) {
			$tax_class = 'standard' === $shipping_tax_class ? '' : $shipping_tax_class;
		}

		$location          = WC_Tax::get_tax_location( '' );

		if ( sizeof( $location ) === 4 ) {
			list( $country, $state, $postcode, $city ) = $location;

			// This will be per order shipping - loop through the order and find the highest tax class rate
			$cart_tax_classes = $woocommerce->cart->get_cart_item_tax_classes();

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

		$shipping_method = WooCommerce_MyParcel()->export->get_shipping_method($chosen_method);
		if (empty($shipping_method)) {
			return false;
		}

		// get shipping classes from package
		$found_shipping_classes = $shipping_method->find_shipping_classes( $package );

		$highest_class = WooCommerce_MyParcel()->export->get_shipping_class( $shipping_method, $found_shipping_classes );
		return $highest_class;
	}



	public function order_review_fragments( $fragments ) {
		$myparcel_shipping_data = $this->get_shipping_data();

		// echo '<pre>';var_dump($myparcel_shipping_data);echo '</pre>';die();
		$fragments['.myparcel-shipping-data'] = $myparcel_shipping_data;
		return $fragments;
	}

	// converts price string to float value, assuming no thousand-separators used
	public function normalize_price( $price ) {
		$price = str_replace(',', '.', $price);
		$price = floatval($price);

		return $price;
	}
}

endif; // class_exists

return new WooCommerce_MyParcel_Frontend();