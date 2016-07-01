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
		// if (isset(WooCommerce_MyParcel()->checkout_settings['delivery_options'])) {
			add_action( apply_filters( 'wc_myparcel_delivery_options_location', 'woocommerce_after_checkout_billing_form' ), array( $this, 'output_delivery_options' ), 10, 1 );
		// }

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
	public function output_delivery_options() {
		// get api url
		$ajax_url = admin_url( 'admin-ajax.php' );
		$request_prefix = strpos($ajax_url, '?') !== false ? '&' : '?';
		$frontend_api_url = wp_nonce_url( $ajax_url . $request_prefix . 'action=wc_myparcel_frontend', 'wc_myparcel_frontend' );

		// get delivery option fees/prices
		$prices = array(
			'morning'			=> isset(WooCommerce_MyParcel()->checkout_settings['morning_delivery_fee']) ? WooCommerce_MyParcel()->checkout_settings['morning_delivery_fee'] : '',
			'default'			=> isset(WooCommerce_MyParcel()->checkout_settings['default_fee']) ? WooCommerce_MyParcel()->checkout_settings['default_fee'] : '',
			'night'				=> isset(WooCommerce_MyParcel()->checkout_settings['night_delivery_fee']) ? WooCommerce_MyParcel()->checkout_settings['night_delivery_fee'] : '',
			'pickup'			=> isset(WooCommerce_MyParcel()->checkout_settings['postnl_pickup_fee']) ? WooCommerce_MyParcel()->checkout_settings['postnl_pickup_fee'] : '',
			'pickup_express'	=> isset(WooCommerce_MyParcel()->checkout_settings['postnl_pickup_early_fee']) ? WooCommerce_MyParcel()->checkout_settings['postnl_pickup_early_fee'] : '',
			'signed'			=> isset(WooCommerce_MyParcel()->checkout_settings['signed_fee']) ? WooCommerce_MyParcel()->checkout_settings['signed_fee'] : '',
			'only_recipient'	=> isset(WooCommerce_MyParcel()->checkout_settings['only_recipient_fee']) ? WooCommerce_MyParcel()->checkout_settings['only_recipient_fee'] : '',
		);

		// exclude delivery types
		$exclude_delivery_types = array(1,4,5);
		$exclude_delivery_types = implode(';', $exclude_delivery_types);

		// combine settings
		$settings = array(
			'base_url'				=> $frontend_api_url,
			'exclude_delivery_type'	=> $exclude_delivery_types,
			'price'					=> $prices,
		);
		
		// encode settings for JS object
		$settings = json_encode($settings);

		include('views/wcmp-delivery-options.php');
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
		// mypa-onoffswitch   - 'on' or not set  
		// mypa-delivery-type - always 'on'
		// mypa-delivery-time - 'on' or delivery data (json object)
		// mypa-pickup-option - pickup data (json object) or not set
		
		// echo '<pre>';var_dump($_POST);echo '</pre>';die();

		if (isset($_POST['mypa-delivery-time']) && $_POST['mypa-delivery-time'] != 'on') {
			$delivery_time = json_decode( stripslashes( $_POST['mypa-delivery-time']), true );
			update_post_meta( $order_id, '_myparcel_delivery_time', $delivery_time );
		}

		if (isset($_POST['mypa-pickup-option'])) {
			$pickup_option = json_decode( stripslashes( $_POST['mypa-pickup-option']), true );
			update_post_meta( $order_id, '_myparcel_pickup_option', $pickup_option );
		}
	}

	public function delivery_options_fees( $cart ) {
		global $woocommerce;
		if ( ! $_POST || ( is_admin() && ! is_ajax() ) ) {
			return;
		}

		if ( isset( $_POST['post_data'] ) ) {
			parse_str( $_POST['post_data'], $post_data );
		}

		if (isset($post_data['mypa-pickup-option'])) {
			$pickup_option = json_decode( stripslashes( $post_data['mypa-pickup-option']), true );
			$cost = 10;
			$woocommerce->cart->add_fee( 'Pickup', $cost );
			// echo '<pre>';var_dump($pickup_option);echo '</pre>';die();
		}
		if (isset($post_data['mypa-delivery-time']) && $post_data['mypa-delivery-time'] != 'on') {
			$pickup_option = json_decode( stripslashes( $post_data['mypa-delivery-time']), true );
			$cost = 5;
			$woocommerce->cart->add_fee( 'Delivery', $cost );
			// echo '<pre>';var_dump($pickup_option);echo '</pre>';die();
		}
	}
}

endif; // class_exists

return new WooCommerce_MyParcel_Frontend();