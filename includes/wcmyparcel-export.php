<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( !class_exists( 'WC_MyParcel_Export' ) ) :

class WC_MyParcel_Export {
	public $order_id;

	/**
	 * Construct.
	 */
			
	public function __construct() {
		include( 'wcmyparcel-api.php' );

		add_action( 'admin_notices', array( $this, 'admin_notices' ) );
		add_action( 'load-edit.php', array( $this, 'wcmyparcel_action' ) ); // Export actions (popup & file export)
		$this->settings = get_option( 'wcmyparcel_settings' );
	}

	public function admin_notices () {
		if (!isset($_GET['myparcel'])) {
			return;
		}

		switch ($_GET['myparcel']) {
			case 'no_consignments':
				$message = __('U dient de orders eerst naar MyParcel te exporteren voordat u de labels kunt printen!');
				printf('<div class="error"><p>%s</p></div>', $message);
				break;
			default:
				break;
		}
	}

	/**
	 * Export selected orders
	 *
	 * @access public
	 * @return void
	 */
	public function wcmyparcel_action() {
		if ( !isset($_REQUEST['action']) ) {
			return false;
		}

		$action = $_REQUEST['action'];

		switch($action) {
			case 'wcmyparcel':
				if ( empty($_GET['order_ids']) ) {
					die('U heeft geen orders geselecteerd!');
				}
				
				$order_ids = explode('x',$_GET['order_ids']);
				$form_data = $this->get_export_form_data( $order_ids );
				
				// Include HTML for export page/iframe
				include('wcmyparcel-export-html.php');

				die();
			break;
			case 'wcmyparcel-export':
				// Get the data
				if (!isset($_POST['consignments'])) {
					die('Er zijn geen orders om te exporteren!');
				}

				// stripslashes! Wordpress always slashes POST data, regardless of magic quotes settings... http://stackoverflow.com/q/8949768/1446634
				$consignment_data = $this->process_consignment_data( stripslashes_deep($_POST['consignments']) );

				// echo '<pre>';print_r($consignment_data);echo '</pre>';die();

				$api = new WC_MyParcel_API();

				// Send consignment data to MyParcel API
				$api->create_consignments( $consignment_data );

				// Include HTML for export done page/iframe
				include('wcmyparcel-export-done-html.php');

				exit;
			case 'wcmyparcel-label':
				if ( empty($_GET['consignment']) && empty($_GET['order_ids']) ) {
					die('U heeft geen orders geselecteerd!');
				}

				$order_ids = explode('x',$_GET['order_ids']);

				$consignments = array();

				if ( !isset($_GET['consignment']) ) {
					// Bulk export label
					foreach ($order_ids as $order_id) {
						if ( $order_consignment_id = get_post_meta($order_id,'_myparcel_consignment_id',true) ) {
							$consignments[$order_consignment_id] = $order_id;
						} elseif ( $order_consignments = get_post_meta($order_id,'_myparcel_consignments',true) ) {
							foreach ($order_consignments as $key => $order_consignment) {
								$consignments[$order_consignment['consignment_id']] = $order_id;
							}
						}
					}
				} else {
					// Label request from modal (directly after export)
					// consignments already given!
					$consignments = array_combine(explode('x',$_GET['consignment']), $order_ids);
				}

				if (empty($consignments)) {
					wp_redirect( admin_url( 'edit.php?post_type=shop_order&myparcel=no_consignments' ) );
					exit();
				}

				$api = new WC_MyParcel_API();

				// Request labels from MyParcel API
				$api->get_labels( $consignments );

				if (!empty($api->pdf)) {
					// Get output setting
					$output_mode = isset($this->settings['download_display'])?$this->settings['download_display']:'';
					if ( $output_mode == 'display' ) {
						$api->stream_pdf();
					} else {
						$api->download_pdf();
					}
				}
				
				exit;
			default: return;
		}
	}

	public function get_export_form_data ( $order_ids ) {
		foreach( $order_ids as $order_id ) {
			$order = new WC_Order( $order_id );
			$data[ $order_id ] = array (
				'consignment'	=> $this->get_consignment_data_from_order( $order ),
				'order'			=> $order,
			);
		}

		return $data;
	}

	public function get_consignment_data_from_order ( $order ) {
		$items = $order->get_items();
		$order_number = $order->get_order_number();

		$kenmerk = isset($this->settings['kenmerk'])
			? str_replace('[ORDER_NR]', $order_number, $this->settings['kenmerk'])
			: '';

		$address = array(
			'name'			=> trim( $order->shipping_first_name . ' ' . $order->shipping_last_name ),
			'business'		=> $order->shipping_company,
			'town'			=> $order->shipping_city,
			'email'			=> isset($this->settings['email']) ? $order->billing_email : '',
			'phone_number'	=> isset($this->settings['telefoon']) ? $order->billing_phone : '',
		);

		if ( $order->shipping_country == 'NL' ) {
			$address_intl = array(
				'postcode'		=> $order->shipping_postcode,
				'house_number'	=> $order->shipping_house_number,
				'number_addition' => $order->shipping_house_number_suffix,
				'street'		  => $order->shipping_street_name,
			);
		} else {
			$address_intl = array(
				'country_code'	=> $order->shipping_country,
				'eps_postcode'	=> $order->shipping_postcode,
				'street'		=> trim( $order->shipping_address_1.' '.$order->shipping_address_2 ),
			);
		}		

		$consignment = array(
			'shipment_type'	=> (isset($this->settings['shipment_type'])) ? $this->settings['shipment_type'] : 'standard', // standard | letterbox | unpaid_letter
			'ToAddress'		=> array_merge( $address, $address_intl),
			'ProductCode'	=> array(
				'signature_on_receipt'	=> (isset($this->settings['handtekening'])) ? '1' : '0',
				'return_if_no_answer'	=> (isset($this->settings['retourbgg'])) ? '1' : '0',
				'home_address_only'		=> (isset($this->settings['huisadres'])) ? '1' : '0',
				'home_address_signature'=> (isset($this->settings['huishand'])) ? '1' : '0',
				'mypa_insured'			=> (isset($this->settings['huishandverzekerd'])) ? '1' : '0',
				'insured'				=> (isset($this->settings['verzekerd'])) ? '1' : '0',
			),
			'insured_amount'	=> (isset($this->settings['verzekerdbedrag'])) ? $this->settings['verzekerdbedrag'] : '0',
			'extra_size'		=> (isset($this->settings['extragroot'])) ? '1' : '0',
			'custom_id'			=> $kenmerk,
			'weight'			=> $this->get_parcel_weight( $order ),
		);

		return apply_filters( 'wcmyparcel_order_consignment_data', $consignment, $order );
	}

	/**
	 * Process consignment data after it has been reviewed for submit
	 */
	public function process_consignment_data ( $consignment_data ) {
		foreach ($consignment_data as $order_id => $consignment) {
			$colli_amount = isset($consignment['colli_amount']) ? $consignment['colli_amount'] : 1;

			// multiply consignments by colli_amount
			for ($i=0; $i < intval($colli_amount); $i++) {
				unset($consignment['colli_amount']);
				$consignment['order_id'] = $order_id;
				$consignment_data_processed[] = $consignment;
			}
		}
		return apply_filters('wcmyparcel_process_consignment_data', $consignment_data_processed );
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
		$parcel_weight = (isset($this->settings['verpakkingsgewicht'])) ? preg_replace("/\D/","",$this->settings['verpakkingsgewicht'])/1000 : 0;

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
	
		$item_weight = $product_weight * $item['qty'];

		return $item_weight;
	}

}

endif; // class_exists
