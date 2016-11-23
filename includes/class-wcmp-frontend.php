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
			'deliverydays_window'	=> isset(WooCommerce_MyParcel()->checkout_settings['deliverydays_window']) ? WooCommerce_MyParcel()->checkout_settings['deliverydays_window'] : '',
			'dropoff_days'			=> isset(WooCommerce_MyParcel()->checkout_settings['dropoff_days']) ? implode(';', WooCommerce_MyParcel()->checkout_settings['dropoff_days'] ): '',
		);
		// remove empty options
		$settings = array_filter($settings);

		// encode settings for JS object
		$settings = json_encode($settings);

		// Shipping methods associated with delivery options
		if ( isset( WooCommerce_MyParcel()->export_defaults['shipping_methods_package_types'] ) && isset( WooCommerce_MyParcel()->export_defaults['shipping_methods_package_types'][1] ) ) {
			$delivery_options_shipping_methods = WooCommerce_MyParcel()->export_defaults['shipping_methods_package_types'][1];
		} else {
			$delivery_options_shipping_methods = array();
		}
		$delivery_options_shipping_methods = json_encode($delivery_options_shipping_methods);

		$iframe_url = WooCommerce_MyParcel()->plugin_url() . '/includes/views/wcmp-delivery-options.php';
		?>
		<iframe id="myparcel-iframe" src="<?php echo $iframe_url; ?>" frameborder="0" scrolling="auto" style="width: 100%;" onload="MyPaLoaded()">Bezig met laden...</iframe>
		<script>
		jQuery( function( $ ) {
			window.mypa = {};
			window.mypa.settings = <?php echo $settings; ?>;
			window.myparcel_delivery_options_shipping_methods = <?php echo $delivery_options_shipping_methods; ?>;
			
			// set reference to iFrame
			var $MyPaiFrame = $('#myparcel-iframe')[0];
			var MyPaWindow = $MyPaiFrame.contentWindow ? $MyPaiFrame.contentWindow : $MyPaiFrame.contentDocument.defaultView;
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
			$shipping_data = sprintf('<input type="hidden" value="%s" id="myparcel_highest_shipping_class">', $shipping_class);
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

		// Fee for "signed" option
		if (isset($post_data['mypa-signed'])) {
			if (!empty(WooCommerce_MyParcel()->checkout_settings['signed_fee'])) {
				$fee = WooCommerce_MyParcel()->checkout_settings['signed_fee'];
				$fee_name = __( 'Signature on delivery', 'woocommerce-myparcel' );
				$this->add_fee( $fee_name, $fee );
			}
		}

		// Fee for "only recipient" option
		if (isset($post_data['mypa-recipient-only'])) {
			if (!empty(WooCommerce_MyParcel()->checkout_settings['only_recipient_fee'])) {
				$fee = WooCommerce_MyParcel()->checkout_settings['only_recipient_fee'];
				$fee_name = __( 'Home address only delivery', 'woocommerce-myparcel' );
				$this->add_fee( $fee_name, $fee );
			}
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
	}

	public function add_fee( $fee_name, $fee ) {
		global $woocommerce; // should be rewritten to WC with fallback functions
		$fee = $this->normalize_price( $fee );
		// get shipping tax data
		$shipping_tax_class = $this->get_shipping_tax_class();
		if ( $shipping_tax_class ) {
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
		}

		return $tax_class;
	}

	/**
	 * Get the most expensive shipping class in the cart
	 * Requires WC2.4+
	 *
	 * Only supports 1 packages, takes the first
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

		if (!isset($shipping_method)) {
			return false;
		}

		// get shipping classes from cart
		$found_shipping_classes = $shipping_method->find_shipping_classes( $package );

		
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