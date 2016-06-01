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
			add_action( apply_filters( 'wc_myparcel_delivery_options_location', 'woocommerce_checkout_before_customer_details' ), array( $this, 'output_delivery_options' ), 10, 1 );
		// }

		// Save delivery options data
		add_action( 'woocommerce_checkout_update_order_meta', array( $this, 'save_delivery_options' ), 10, 2 );

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
		#stub
	}
}

endif; // class_exists

return new WooCommerce_MyParcel_Frontend();