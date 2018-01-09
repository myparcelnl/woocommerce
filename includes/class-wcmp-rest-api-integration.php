<?php

use WPO\WC\MyParcel\Compatibility\WC_Core as WCX;
use WPO\WC\MyParcel\Compatibility\Order as WCX_Order;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( ! class_exists( 'WC_REST_API_Integration' ) ) :

	final class WC_REST_API_Integration {

		public function __construct() {
			add_action( 'woocommerce_rest_insert_shop_order_object', array( $this, 'rest_insert_shop_order' ), 10, 2 );
		}

		/**
		 * @param \WC_Data $object
		 * @param \WP_REST_Request $request
		 */
		public function rest_insert_shop_order($object, $request) {
			$order = null;
			if ($object instanceof WC_Order) {
				$order = $object;
			} else {
				$order = WCX::get_order($object->get_id());
			}

			if (!$order) {
				throw new Exception("Invalid WC_Data object.");
			}

			// Billing.
			$billing = $request->get_param('billing');
			$billing_street_name = $billing['street_name'] ?: '';
			$billing_house_number = $billing['house_number'] ?: '';
			$billing_house_number_suffix = $billing['house_number_suffix'] ?: '';
			WCX_Order::update_meta_data( $order, '_billing_street_name', $billing_street_name );
			WCX_Order::update_meta_data( $order, '_billing_house_number', $billing_house_number );
			WCX_Order::update_meta_data( $order, '_billing_house_number_suffix', $billing_house_number_suffix );

			// Shipping.
			$shipping = $request->get_param('shipping');
			$shipping_street_name = $shipping['street_name'] ?: '';
			$shipping_house_number = $shipping['house_number'] ?: '';
			$shipping_house_number_suffix = $shipping['house_number_suffix'] ?: '';
			WCX_Order::update_meta_data( $order, '_shipping_street_name', $shipping_street_name );
			WCX_Order::update_meta_data( $order, '_shipping_house_number', $shipping_house_number );
			WCX_Order::update_meta_data( $order, '_shipping_house_number_suffix', $shipping_house_number_suffix );
		}

	}

endif;

return new WC_REST_API_Integration();