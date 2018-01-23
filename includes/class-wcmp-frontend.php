<?php
use WPO\WC\PostNL\Compatibility\WC_Core as WCX;
use WPO\WC\PostNL\Compatibility\Order as WCX_Order;
use WPO\WC\PostNL\Compatibility\Product as WCX_Product;

/**
 * Frontend views
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( !class_exists( 'WooCommerce_PostNL_Frontend' ) ) :

class WooCommerce_PostNL_Frontend {
	
	function __construct()	{
		// Customer Emails
		if (isset(WooCommerce_PostNL()->general_settings['email_tracktrace'])) {
			add_action( 'woocommerce_email_before_order_table', array( $this, 'track_trace_email' ), 10, 2 );
		}

		// Track & trace in my account
		if (isset(WooCommerce_PostNL()->general_settings['myaccount_tracktrace'])) {
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
		add_filter( 'wpo_wcpdf_templates_replace_postnl_delivery_date', array( $this, 'wpo_wcpdf_delivery_date' ), 10, 2 );
		add_filter( 'wpo_wcpdf_templates_replace_postnl_tracktrace', array( $this, 'wpo_wcpdf_tracktrace' ), 10, 2 );
		add_filter( 'wpo_wcpdf_templates_replace_postnl_tracktrace_link', array( $this, 'wpo_wcpdf_tracktrace_link' ), 10, 2 );
		add_filter( 'wpo_wcpdf_templates_replace_postnl_delivery_options', array( $this, 'wpo_wcpdf_delivery_options' ), 10, 2 );

		// Delivery options
		if (isset(WooCommerce_PostNL()->checkout_settings['postnl_checkout'])) {
			add_action( apply_filters( 'wc_postnl_delivery_options_location', 'woocommerce_after_checkout_billing_form' ), array( $this, 'output_delivery_options' ), 10, 1 );
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

		if ( WCX_Order::get_status( $order ) != 'completed') return;

		$order_id = WCX_Order::get_id( $order );

		$tracktrace_links = WooCommerce_PostNL()->admin->get_tracktrace_links ( $order_id );
		if ( !empty($tracktrace_links) ) {
			$email_text = __( 'You can track your order with the following PostNL track&trace code:', 'woocommerce-postnl' );
			$email_text = apply_filters( 'wcpostnl_email_text', $email_text, $order );
			?>
			<p><?php echo $email_text.' '.implode(', ', $tracktrace_links); ?></p>
	
			<?php
		}
	}

	public function email_pickup_html( $order, $sent_to_admin = false, $plain_text = false ) {
		WooCommerce_PostNL()->admin->show_order_delivery_options( $order );
	}

	public function thankyou_pickup_html( $order_id ) {
		$order = wc_get_order( $order_id );
		WooCommerce_PostNL()->admin->show_order_delivery_options( $order );
	}

	public function track_trace_myaccount( $actions, $order ) {
		$order_id = WCX_Order::get_id( $order );
		if ( $consignments = WooCommerce_PostNL()->admin->get_tracktrace_shipments( $order_id ) ) {
			foreach ($consignments as $key => $consignment) {
				$actions['postnl_tracktrace_'.$consignment['tracktrace']] = array(
					'url'  => $consignment['tracktrace_url'],
					'name' => apply_filters( 'wcpostnl_myaccount_tracktrace_button', __( 'Track&Trace', 'wooocommerce-postnl' ) )
				);
			}
		}

		return $actions;
	}

	public function wpo_wcpdf_delivery_options( $replacement, $order ) {
		ob_start();
		WooCommerce_PostNL()->admin->show_order_delivery_options( $order );
		return ob_get_clean();
	}

	public function wpo_wcpdf_delivery_date( $replacement, $order ) {
		if ($delivery_date = WooCommerce_PostNL()->export->get_delivery_date( $order ) ) {
			$formatted_date = date_i18n( apply_filters( 'wcpostnl_delivery_date_format', wc_date_format() ), strtotime( $delivery_date ) );
			return $formatted_date;
		}
		return $replacement;
	}

	public function wpo_wcpdf_tracktrace( $replacement, $order ) {
		if ( $shipments = WooCommerce_PostNL()->admin->get_tracktrace_shipments( WCX_Order::get_id( $order ) ) ) {
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
		$tracktrace_links = WooCommerce_PostNL()->admin->get_tracktrace_links ( WCX_Order::get_id( $order ) );
		if ( !empty($tracktrace_links) ) {
			$replacement = implode(', ', $tracktrace_links);
		}
		return $replacement;
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
		$frontend_api_url = wp_nonce_url( $ajax_url . $request_prefix . 'action=wc_postnl_frontend', 'wc_postnl_frontend' );

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

			if ( in_array($option,$delivery_options) && !isset(WooCommerce_PostNL()->checkout_settings[$option.'_enabled']) ) {
				$prices[$option] = 'disabled';
			} elseif (!empty(WooCommerce_PostNL()->checkout_settings[$option.'_fee'])) {
				$fee = WooCommerce_PostNL()->checkout_settings[$option.'_fee'];
				$fee = $this->normalize_price( $fee );

				// get WC Tax display setting for cart
				$tax_display_cart = get_option( 'woocommerce_tax_display_cart' );
				if ($tax_display_cart == 'incl') {
					$display_fee = $fee + array_sum( WC_Tax::calc_shipping_tax( $fee, WC_Tax::get_shipping_tax_rates() ) );
				} else {
					$display_fee = $fee;
				}

				// determine whether fee is cost or discount
				if ($display_fee < 0) {
					$prices[$option] = '- '.wc_price(abs($display_fee)); // this includes price HTML, may need to use custom function, also for &#8364; instead of eur
				} else {
					$prices[$option] = '+ '.wc_price($display_fee);
				}
			}
		}

		// exclude delivery types
		$exclude_delivery_types = array();
		foreach ($delivery_types as $key => $delivery_type) {
			// JS API correction
			if ($delivery_type == 'standard') {
				continue;
			}
			if (!isset(WooCommerce_PostNL()->checkout_settings[$delivery_type.'_enabled'])) {
				$exclude_delivery_types[] = $key;
			}
		}
		$exclude_delivery_types = implode(';', $exclude_delivery_types);

		// Use saturday_cutoff_time on saturdays
		if ( date_i18n('w') == 6 && isset(WooCommerce_PostNL()->checkout_settings['saturday_cutoff_time']) ) {
			$cutoff_time = WooCommerce_PostNL()->checkout_settings['saturday_cutoff_time'];
		} else {
			$cutoff_time = isset(WooCommerce_PostNL()->checkout_settings['cutoff_time']) ? WooCommerce_PostNL()->checkout_settings['cutoff_time'] : '';
		}

		// combine settings
		$settings = array(
			'base_url'				=> $frontend_api_url,
			'exclude_delivery_type'	=> $exclude_delivery_types,
			'price'					=> $prices,
			'dropoff_delay'			=> isset(WooCommerce_PostNL()->checkout_settings['dropoff_delay']) ? WooCommerce_PostNL()->checkout_settings['dropoff_delay'] : '',
			'cutoff_time'			=> $cutoff_time,
			'deliverydays_window'	=> isset(WooCommerce_PostNL()->checkout_settings['deliverydays_window']) ? max(1,WooCommerce_PostNL()->checkout_settings['deliverydays_window']) : '',
			'dropoff_days'			=> isset(WooCommerce_PostNL()->checkout_settings['dropoff_days']) ? implode(';', WooCommerce_PostNL()->checkout_settings['dropoff_days'] ): '',
		);
		// remove empty options
		$settings = array_filter($settings);

		// encode settings for JS object
		$settings = json_encode($settings);

		if ( isset( WooCommerce_PostNL()->checkout_settings['checkout_display'] ) && WooCommerce_PostNL()->checkout_settings['checkout_display'] == 'all_methods' ) {
			$postnl_delivery_options_always_display = 'yes';
			$delivery_options_shipping_methods = array();
		} elseif ( isset( WooCommerce_PostNL()->export_defaults['shipping_methods_package_types'] ) && isset( WooCommerce_PostNL()->export_defaults['shipping_methods_package_types'][1] ) ) {
			// Shipping methods associated with parcels = enable delivery options
			$delivery_options_shipping_methods = WooCommerce_PostNL()->export_defaults['shipping_methods_package_types'][1];
		} else {
			$delivery_options_shipping_methods = array();
		}
		$delivery_options_shipping_methods = json_encode($delivery_options_shipping_methods);

		$iframe_url = WooCommerce_PostNL()->plugin_url() . '/includes/views/wcmp-delivery-options.php';

		// determine whether to pre-hide iframe (prevents flashing)
		$hide_delivery_options = false;
		$chosen_shipping_methods = WC()->session->chosen_shipping_methods;
		if ( empty($postnl_delivery_options_always_display) && !empty($chosen_shipping_methods) && is_array($chosen_shipping_methods) ) {
			$shipping_country = WC()->customer->get_shipping_country();
			if ($shipping_country != 'NL') {
				$hide_delivery_options = true;
			} else {
				$chosen_shipping_method = array_shift($chosen_shipping_methods);
				$shipping_class = $this->get_cart_shipping_class();
				$package_type = WooCommerce_PostNL()->export->get_package_type_from_shipping_method( $chosen_shipping_method, $shipping_class, $shipping_country );
				if ($package_type != 1) { // parcel
					$hide_delivery_options = true;
				}
			}
		}
		// We're using placeholder for the iframe to prevent race condition:
		// IE/Edge loads in a different order than Chrome/Firefox and plugin conflicts could cause the same,
		// so we never know whether the iframe is loaded before the main page scripts
		// This way we gain control by setting the iframe + src via the main page script 
		?>
		<div class="postnl-iframe-placeholder" style="display:none;"></div>
		<script>
			window.post = {};
			window.post.settings = <?php echo $settings; ?>;
			window.postnl_delivery_options_shipping_methods = <?php echo $delivery_options_shipping_methods; ?>;
			<?php if (!empty($postnl_delivery_options_always_display)): ?>
			window.postnl_delivery_options_always_display = 'yes';
			<?php endif ?>
			window.postnl_initial_hide = <?php echo ($hide_delivery_options) ? 'true' : 'false'; ?>;
		</script>
		
		<input style="display:none !important;" type="checkbox" name='post-options-enabled' id="post-options-enabled">
		<div id="post-chosen-delivery-options" style="display:none !important;">
			<input style="display:none !important;" name='post-post-nl-data' id="post-input">
			<input style="display:none !important;" type="checkbox" name='post-signed' id="post-signed">
			<input style="display:none !important;" type="checkbox" name='post-recipient-only' id="post-recipient-only">
		</div>
		<?php
	}

	public function output_shipping_data() {
		$shipping_data = $this->get_shipping_data();
		printf('<div class="postnl-shipping-data">%s</div>', $shipping_data);
	}

	public function get_shipping_data() {
		if ($shipping_class = $this->get_cart_shipping_class()) {
			$shipping_data = sprintf('<input type="hidden" value="%s" id="postnl_highest_shipping_class" name="postnl_highest_shipping_class">', $shipping_class);
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
		$order = WCX::get_order( $order_id );

		if (isset($_POST['postnl_highest_shipping_class'])) {
			WCX_Order::update_meta_data( $order, '_postnl_highest_shipping_class', $_POST['postnl_highest_shipping_class'] );
		}

		// post-recipient-only - 'on' or not set
		// post-signed         - 'on' or not set
		// post-post-nl-data   - JSON of chosen delivery options
		
		// check if delivery options were used
		if (!isset($_POST['post-options-enabled'])) {
			return;
		}


		if (isset($_POST['post-signed'])) {
			WCX_Order::update_meta_data( $order, '_postnl_signed', 'on' );
		}

		if (isset($_POST['post-recipient-only'])) {
			WCX_Order::update_meta_data( $order, '_postnl_only_recipient', 'on' );
		}

		if (!empty($_POST['post-post-nl-data'])) {
			$delivery_options = json_decode( stripslashes( $_POST['post-post-nl-data']), true );
			WCX_Order::update_meta_data( $order, '_postnl_delivery_options', $delivery_options );
		}
	}

	public function delivery_options_fees( $cart ) {
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
		if (!empty($post_data['post-post-nl-data'])) {
			$delivery_options = json_decode( stripslashes( $post_data['post-post-nl-data']), true );
			// Fees for pickup & pickup express
			if (isset($delivery_options['price_comment'])) {
				switch ($delivery_options['price_comment']) {
					case 'retail':
						if (!empty(WooCommerce_PostNL()->checkout_settings['pickup_fee'])) {
							$fee = WooCommerce_PostNL()->checkout_settings['pickup_fee'];
							$fee_name = __( 'PostNL Pickup', 'woocommerce-postnl' );
						}
						break;
					case 'retailexpress':
						if (!empty(WooCommerce_PostNL()->checkout_settings['pickup_express_fee'])) {
							$fee = WooCommerce_PostNL()->checkout_settings['pickup_express_fee'];
							$fee_name = __( 'PostNL Pickup Express', 'woocommerce-postnl' );
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
							if (!empty(WooCommerce_PostNL()->checkout_settings['morning_fee'])) {
								$fee = WooCommerce_PostNL()->checkout_settings['morning_fee'];
								$fee_name = __( 'Morning delivery', 'woocommerce-postnl' );
							}
							break;
						case 'standard':
							if (!empty(WooCommerce_PostNL()->checkout_settings['default_fee'])) {
								$fee = WooCommerce_PostNL()->checkout_settings['default_fee'];
								$fee_name = __( 'Standard delivery', 'woocommerce-postnl' );
							}
							break;
						case 'night':
							$only_recipient_included = true;
							if (!empty(WooCommerce_PostNL()->checkout_settings['night_fee'])) {
								$fee = WooCommerce_PostNL()->checkout_settings['night_fee'];
								$fee_name = __( 'Evening delivery', 'woocommerce-postnl' );
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
		if (isset($post_data['post-signed'])) {
			if (!empty(WooCommerce_PostNL()->checkout_settings['signed_fee'])) {
				$fee = WooCommerce_PostNL()->checkout_settings['signed_fee'];
				$fee_name = __( 'Signature on delivery', 'woocommerce-postnl' );
				$this->add_fee( $fee_name, $fee );
			}
		}

		// Fee for "only recipient" option, don't apply fee for morning & night delivery (already included)
		if (isset($post_data['post-recipient-only']) && empty($only_recipient_included)) {
			if (!empty(WooCommerce_PostNL()->checkout_settings['only_recipient_fee'])) {
				$fee = WooCommerce_PostNL()->checkout_settings['only_recipient_fee'];
				$fee_name = __( 'Home address only delivery', 'woocommerce-postnl' );
				$this->add_fee( $fee_name, $fee );
			}
		}

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
	 * assumes per order shipping (per item shipping not supported for PostNL yet)
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

		$shipping_method = WooCommerce_PostNL()->export->get_shipping_method($chosen_method);
		if (empty($shipping_method)) {
			return false;
		}

		// get shipping classes from package
		$found_shipping_classes = $shipping_method->find_shipping_classes( $package );
		// return print_r( $found_shipping_classes, true );

		$highest_class = WooCommerce_PostNL()->export->get_shipping_class( $shipping_method, $found_shipping_classes );
		return $highest_class;
	}



	public function order_review_fragments( $fragments ) {
		$postnl_shipping_data = $this->get_shipping_data();

		// echo '<pre>';var_dump($postnl_shipping_data);echo '</pre>';die();
		$fragments['.postnl-shipping-data'] = $postnl_shipping_data;
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

return new WooCommerce_PostNL_Frontend();