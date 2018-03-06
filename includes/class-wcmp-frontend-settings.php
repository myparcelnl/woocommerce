<?php
use WPO\WC\MyParcelBE\Compatibility\WC_Core as WCX;
use WPO\WC\MyParcelBE\Compatibility\Order   as WCX_Order;
use WPO\WC\MyParcelBE\Compatibility\Product as WCX_Product;

/**
 * Frontend views
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( !class_exists( 'WooCommerce_MyParcelBE_Frontend_Settings' ) ) :

	class WooCommerce_MyParcelBE_Frontend_Settings {

		const DAYS_SATURDAY = 6;

		const CARRIER_CODE = 2;
		const CARRIER_NAME = "Bpost";

		private $settings;

		function __construct() {

			$this->settings = WooCommerce_MyParcelBE()->checkout_settings;
//			add_action( 'woocommerce_myparcelbe_frontend_settings', array($this, 'get_default_settings' ));
			//add_action( 'woocommerce_update_order_review_fragments', array( $this, 'order_review_fragments' ) );
		}

		/**
		 * @return mixed
		 */
		public function get_cutoff_time() {
			if (
				date_i18n('w') == self::DAYS_SATURDAY &&
				isset($this->settings['saturday_cutoff_time'])
			) {
				return $this->settings['saturday_cutoff_time'];
			}

			return $this->settings['cutoff_time'];
		}

		/**
		 * @return mixed
		 */
		public function get_saturday_cutoff_time() {
			return $this->settings['saturday_cutoff_time'];
		}

		public function get_dropoff_delay() {
			return $this->settings['dropoff_delay'];
		}

		public function get_deliverydays_window() {
			return $this->settings['deliverydays_window'];
		}
		/**
		 * @return string
		 */
		public function get_dropoff_days() {
			return implode(";", $this->settings['dropoff_days']);
		}
		/**
		 * @return string
		 */
		public function get_api_url() {
			$wpAjaxUrl        = admin_url('admin-ajax.php');
			$requestPrefix    = strpos($wpAjaxUrl, '?') !== false ? '&' : '?';

			return wp_nonce_url($wpAjaxUrl . $requestPrefix . 'action=wc_myparcelbe_frontend', 'wc_myparcelbe_frontend');
		}

		public function get_country_code(){
		}

		public function get_price_saterday_delivery() {
		}

		public function get_price_signature() {
			return $this->settings['signed_fee'];
		}

		public function is_active_saterday_delivery() {
		}

	}

endif; // class_exists

return new WooCommerce_MyParcelBE_Frontend_Settings();
