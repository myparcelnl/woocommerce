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

		// Delivery options
		if (isset(WooCommerce_MyParcel()->checkout_settings['myparcel_checkout'])) {
			// template
			add_action( 'wp_head', array( $this, 'delivery_options_template' ) );
			// actual output
			add_action( apply_filters( 'wc_myparcel_delivery_options_location', 'woocommerce_after_checkout_billing_form' ), array( $this, 'output_delivery_options' ), 10, 1 );
		}

		// Save delivery options data
		add_action( 'woocommerce_checkout_update_order_meta', array( $this, 'save_delivery_options' ), 10, 2 );

		// Delivery options fees
		add_action( 'woocommerce_cart_calculate_fees', array( $this, 'delivery_options_fees' ) );
	}

	public function track_trace_email( $order, $sent_to_admin ) {

		if ( $sent_to_admin ) return;

		if ( $order->status != 'completed') return;

		$tracktrace_links = WooCommerce_MyParcel()->admin->get_tracktrace_links ( $order->id );
		if ( !empty($tracktrace_links) ) {
			$email_text = __( 'You can track your order with the following PostNL track&trace code:', 'woocommerce-myparcel' );
			$email_text = apply_filters( 'wcmyparcel_email_text', $email_text );
			?>
			<p><?php echo $email_text.' '.implode(', ', $tracktrace_links); ?></p>
	
			<?php
		}
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
	/**
	 * Add delivery options to checkout
	 */
	public function delivery_options_template() {
		include('views/wcmp-delivery-options.php');
	}

	public function output_delivery_options() {
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
				$prices[$option] = '+ &#8364;'.WooCommerce_MyParcel()->checkout_settings[$option.'_fee'];
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
		?>
		<script type="text/javascript">
		window.mypa = {}
		window.mypa.settings = <?php echo $settings; ?>;
		window.myparcel_delivery_options_shipping_methods = <?php echo $delivery_options_shipping_methods; ?>;
		</script>
		<myparcel id="myparcel"></myparcel>
		<div id="mypa-chosen-delivery-options">
			<input style="display:none" name='mypa-post-nl-data' id="mypa-input">
			<input style="display:none" type="checkbox" name='mypa-signed' id="mypa-signed">
			<input style="display:none" type="checkbox" name='mypa-recipient-only' id="mypa-recipient-only">
		</div>
		<?php
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
		// mypa-post-nl-data          - JSON of chosen delivery options
		
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
				$fee = $this->normalize_price( $fee );
				$woocommerce->cart->add_fee( $fee_name, $fee );
			}
		}

		// Fee for "only recipient" option
		if (isset($post_data['mypa-recipient-only'])) {
			if (!empty(WooCommerce_MyParcel()->checkout_settings['only_recipient_fee'])) {
				$fee = WooCommerce_MyParcel()->checkout_settings['only_recipient_fee'];
				$fee_name = __( 'Home address only delivery', 'woocommerce-myparcel' );
				$fee = $this->normalize_price( $fee );
				$woocommerce->cart->add_fee( $fee_name, $fee );
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
					$fee = $this->normalize_price( $fee );
					$woocommerce->cart->add_fee( $fee_name, $fee );
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
						$fee = $this->normalize_price( $fee );
						$woocommerce->cart->add_fee( $fee_name, $fee );
					}
				}

			}
		}


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