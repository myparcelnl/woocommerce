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
		$this->api = include( 'wcmyparcel-api.php' );
		add_action( 'load-edit.php', array( &$this, 'wcmyparcel_action' ) ); // Export actions (popup & file export)
		$this->settings = get_option( 'wcmyparcel_settings' );
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
				$consignments = stripslashes_deep($_POST['consignments']);

				$api_data = array(
					'process'		=> isset($this->settings['process'])?1:0, // NOTE: process parameter is active, put on 0 to create a consignment without processing it
					'consignments'	=> $consignments
				);

				// Send consignments to MyParcel API
				$result = $this->api->request( 'create-consignments', $api_data);
				// put order_id in key!
				$result = array_combine( array_keys($api_data['consignments']), array_values($result) );
				
				$processed_result = $this->process_export_result( $result );
				extract($processed_result); // $consignment_list, $error

				$pdf_url = wp_nonce_url( admin_url( 'edit.php?&action=wcmyparcel-label&consignment=' . implode('x', $consignment_list) . '&order_ids=' . implode('x', array_keys($consignment_list)) ), 'wcmyparcel-label' );
				
				$this->export_done($pdf_url, $consignment_list, $error);

				exit;
			case 'wcmyparcel-label':
				if ( empty($_GET['consignment']) && empty($_GET['order_ids']) ) {
					die('U heeft geen orders geselecteerd!');
				}

				$order_ids = explode('x',$_GET['order_ids']);

				$consignment_list = array();

				if ( !isset($_GET['consignment']) ) {
					// Bulk export label
					foreach ($order_ids as $order_id) {
						if (get_post_meta($order_id,'_myparcel_consignment_id',true)) {
							$order_consignment_id = get_post_meta($order_id,'_myparcel_consignment_id',true);
							$consignment_list[$order_id] = $order_consignment_id;
						}
					}
				} else {
					// Label request from modal (directly after export)
					// consignments already given!
					$consignment_list = array_combine($order_ids, explode('x',$_GET['consignment']));
				}

				// retrieve pdf for the consignment (this is another api call to retrieve-pdf)
				$array = array(
					'consignment_id' => $consignment_id_encoded = implode(',', $consignment_list),
					'format'		 => 'json',
				);

				// Request labels from MyParcel API
				$result = $this->api->request( 'retrieve-pdf', $array);
				
				$this->process_label_result( $result, $consignment_list );

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

		$bericht = isset($this->settings['bericht'])
			? str_replace('[ORDER_NR]', $order_number, $this->settings['bericht'])
			: '';
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
			'insured_amount'	=> 0, // default to 0 when no user input
			'extra_size'		=> (isset($this->settings['extragroot'])) ? '1' : '0',
			'custom_id'			=> $kenmerk,
			'comments'			=> $bericht,
			'weight'			=> $this->get_parcel_weight( $order ),
		);

		return apply_filters( 'wcmyparcel_order_consignment_data', $consignment, $order );
	}

	public function process_export_result ( $result ) {
		$consignment_list = array();
		$error = array();
		foreach ($result as $order_id => $order_decode ) {
			if ( !isset($order_decode['error']) ) {
				$consignment_id = $order_decode['consignment_id'];
				$consignment_list[$order_id] = $consignment_id; //collect consigment_ids in an array for pdf retreival
				$tracktrace = $order_decode['tracktrace'];

				update_post_meta ( $order_id, '_myparcel_consignment_id', $consignment_id );
				update_post_meta ( $order_id, '_myparcel_tracktrace', $tracktrace );

				// set status to complete
				if ( isset($this->settings['auto_complete']) ) {
					$order = new WC_Order( $order_id );
					$order->update_status( 'completed', 'Order voltooid na MyParcel export' );
				}

			} else {
				//$error[$order_id] = $order_decode['error'];
				$error[$order_id] = implode( ', ', $this->array_flatten($order_decode) );
			}
		}

		return compact('consignment_list', 'error');
	}

	public function process_label_result ( $result, $consignment_list ) {
		if (isset($result['consignment_pdf'])) {
			// We have a PDF!
			$pdf_data = $result['consignment_pdf'];
			$consigments_tracktrace = array_combine( explode(',',$result['consignment_id']), explode(',',$result['tracktrace']) );
			
			// track & trace fallback
			foreach ( $consignment_list as $order_id => $consignment_id ) {
				if ( isset($consigments_tracktrace[$consignment_id]) ) {
					// create array with $order_id => $tracktrace
					$orders_tracktrace[$order_id] = $consigments_tracktrace[$consignment_id];
					
					// put track&trace code in order meta
					update_post_meta ( $order_id, '_myparcel_tracktrace', $consigments_tracktrace[$consignment_id] );
				}
			}
			
			unset($result['consignment_pdf']);
			
			$this->api->log( "PDF data received:\n" . print_r( $orders_tracktrace, true ) );

			do_action( 'wcmyparcel_before_label_print', $consignment_list );

			$filename  = 'MyParcel';
			$filename .= '-' . date('Y-m-d') . '.pdf';
			
			// Get output setting
			$output_mode = isset($this->settings['download_display'])?$this->settings['download_display']:'';

			// Switch headers according to output setting
			if ( $output_mode == 'display' ) {
				header('Content-type: application/pdf');
				header('Content-Disposition: inline; filename="'.$filename.'"');
			} else {
				header('Content-Description: File Transfer');
				header('Content-Type: application/octet-stream');
				header('Content-Disposition: attachment; filename="'.$filename.'"'); 
				header('Content-Transfer-Encoding: binary');
				header('Connection: Keep-Alive');
				header('Expires: 0');
				header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
				header('Pragma: public');
			}

			// stream data
			echo urldecode($pdf_data);
		} elseif (isset($result['error'])) {
			// No PDF, show error
			echo 'Error: ' . $result['error'];
		} else {
			echo 'An unknown error occured<br/>';
			echo 'Server response: ' . print_r($result);
		}

		return;
	}

	public function get_item_display_name ( $item, $order ) {
		// set base name
		$name = $item['name'];

		// add variation name if available
		$product = $order->get_product_from_item( $item );
		if( $product && isset( $item['variation_id'] ) && $item['variation_id'] > 0 ) {
			$name .= woocommerce_get_formatted_variation( $product->get_variation_attributes() );
		}
		
		return $name;
	}

	/**
	 * Get the current order items
	 */
	public function get_order_items( $order ) {
		global $woocommerce;
		$items = $order->get_items();
		$data_list = array();
	
		if( sizeof( $items ) > 0 ) {
			foreach ( $items as $item ) {
				// Array with data for the printing template
				$data = array();
				
				// Create the product
				$product = $order->get_product_from_item( $item );

				// Set the variation
				if( !empty($product) && isset( $item['variation_id'] ) && $item['variation_id'] > 0 ) {
					$data['variation'] = woocommerce_get_formatted_variation( $product->get_variation_attributes() );
				} else {
					$data['variation'] = null;
				}
				
				// Set item name
				$data['name'] = $item['name'];
				
				// Set item quantity
				$data['quantity'] = $item['qty'];

				// Set item weight
				$data['total_weight'] = $this->get_product_weight_kg( $product ) * $item['qty'];				$weight = $product->get_weight();
				
				if ( !empty($product) ) {
					echo '<pre>';var_dump($product);echo '</pre>';die();
					// Set item SKU
					$data['sku'] = $product->get_sku();
					// Set item dimensions
					$data['dimensions'] = $product->get_dimensions();
				} else {
					// no product, set empty values
					$data['sku'] = $data['dimensions'] = '';
				}

				$data_list[] = $data;
			}
		}

		return $data_list;
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


	/**
	 * Get shipping data for current order
	 */
	public function name_length_check($names) {
		$voornaam = $names['voornaam'];
		$achternaam = $names['achternaam'];
		$bedrijfsnaam = $names['bedrijfsnaam'];
		
		if (strlen($voornaam) + strlen($achternaam) + 1 > 30 ) { $voornaam = preg_replace('/(\w)(\w+) *-*/', '\1.', $voornaam);	}							
		$naam = $voornaam . ' ' . $achternaam;
		
		if (!$bedrijfsnaam=="") {
			if (strlen($bedrijfsnaam) > 35 ) { $bedrijfsnaam = substr($bedrijfsnaam, 0, 35); }
			
			if (strlen($bedrijfsnaam) + strlen($naam) > 30) {
				if (strlen($bedrijfsnaam) + strlen($achternaam) <= 30) {$naam = $achternaam;}
				else {$bedrijfsnaam = "";}
			}
		}
		$checked_names['naam'] = $naam;
		$checked_names['bedrijfsnaam'] = $bedrijfsnaam;
		return $checked_names;
	}
	
	/**
	 * Multi-dimensional array flatten
	 */
	public function array_flatten($a,$f=array()){
		if(!$a||!is_array($a))return '';
		foreach($a as $k=>$v){
			if(is_array($v))$f=$this->array_flatten($v,$f);
			else $f[$k]=$v;
		}
		return $f;
	}
	
	/**
	 * Vertaal engelse MyParcel foutmeldingen
	 */
	public function translate_error($error){
		switch ($error) {
			case 'access denied - Signature does not match request - parameters need to be hashed in alphabetical order':
				$error = 'Toegang geweigerd - De API key komt niet overeen met de gebruikersnaam.';
				break;
			case 'access denied - Username \''.$this->settings['api_username'].'\' does not exist':
				$error = 'Toegang geweigerd - De gebruikersnaam <strong>'.$this->settings['api_username'].'</strong> bestaat niet.';
				break;
		}

		return $error;
	}

	/**
	 * Export result page
	 */
	public function export_done ($pdf_url, $consignment_list, $error) {
		?>
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
	<?php
		require_once(ABSPATH . 'wp-admin/admin.php');
		wp_register_style( 'wcmyparcel-admin-styles', dirname(plugin_dir_url(__FILE__)) .  '/css/wcmyparcel-admin-styles.css', array(), '', 'all' );
		wp_enqueue_style( 'wcmyparcel-admin-styles' );		
		wp_enqueue_style( 'colors' );
		wp_enqueue_style( 'media' );
		do_action('admin_print_styles');
	?>
</head>
<body style="padding:10px 20px;">
<h1>Export voltooid</h1>
<?php
		if (!empty($error)) {
			echo '<p>Er hebben zich fouten voorgedaan bij de volgende orders, deze zijn niet verwerkt:<ul style="margin-left:20px;">';
			foreach($error as $order_id => $error_message) {
				$order = new WC_Order($order_id);
				$order_number = $order->get_order_number();
				echo '<li><strong>'.$order_number.'</strong> <i>'.$error_message.'</i></li>';
			}
			echo '</ul></p>';
		}
		if (!empty($consignment_list)) {
			if (!empty($error)) {
				echo '<p>De overige orders zijn succesvol verzonden naar MyParcel.<br />';
			} else {
				echo '<p>De geselecteerde orders zijn succesvol verzonden naar MyParcel.<br />';		
			}
			$target = ( isset($this->settings['download_display']) && $this->settings['download_display'] == 'display') ? 'target="_blank"' : '';

			if ( isset( $this->settings['process'] ) ) :
?>
Hieronder kunt u de labels in PDF formaat downloaden.</p>
<?php printf('<a href="%1$s" %2$s><img src="%3$s"></a>', $pdf_url, $target, dirname(plugin_dir_url(__FILE__)) . '/img/download-pdf.png'); ?>
<p><strong>Let op!</strong><br />
Uw pakket met daarop het verzendetiket dient binnen 9 werkdagen na het aanmaken bij PostNL binnen te zijn. Daarna verliest het zijn geldigheid.
</body></html>
<?php
			endif;
		}
	}



}

endif; // class_exists
