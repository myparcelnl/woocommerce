<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( !class_exists( 'WC_MyParcel_API' ) ) :

class WC_MyParcel_API {
	public $errors = array();
	public $consignments = array();

	/**
	 * Construct.
	 */
			
	public function __construct() {
		$this->settings = get_option( 'wcmyparcel_settings' );
		$this->log_file = dirname(dirname(__FILE__)).'/myparcel_log.txt';
	}

	public function create_consignments ( $consignment_data ) {
		$api_data = array(
			'process'		=> isset($this->settings['process'])?1:0, // NOTE: process parameter is active, put on 0 to create a consignment without processing it
			'consignments'	=> $consignment_data
		);

		$result = $this->request( 'create-consignments', $api_data);
		$timestamp = current_time('mysql');

		// check for general errors
		if (isset($result['error'])) {
			$this->errors['general'] = $result['error'];
		} else {
			// add order_id back to result - this assumes result array always matches input array!
			foreach ($result as $key => $consignment) {
				$result[$key]['order_id'] = $consignment_data[$key]['order_id'];
			}
			// separate consignment errors from successful consignments
			foreach ($result as $consignment ) {
				$order_id = $consignment['order_id'];
				if ( !isset($consignment['error']) ) {
					$consignment['timestamp'] = $timestamp;
					$this->consignments[$order_id][$consignment['consignment_id']] = $consignment;
				} else {
					//$error[$order_id] = $order_decode['error'];
					$this->errors[$order_id][] = implode( ', ', $this->array_flatten($consignment) );
				}
			}			
		}


		$this->save_consignment_data();

		if ( empty($this->consignments) ) {
			return false;
		} else {
			return $this->consignments;
		}
	}

	public function get_labels ( $consignments = '' ) {
		// get consignments from result if already available
		if ( empty($consignments) ) {
			if ( !empty($this->consignments) ) {
				foreach ($this->consignments as $order_id => $order_consignments) {
					foreach ($order_consignments as $order_consignment) {
						$consignments[$order_consignment['consignment_id']] = $order_id;
					}
				}
			} else {
				return false;
			}
		}

		// retrieve pdf for the consignment
		$api_data = array(
			'consignment_id' => $consignment_id_encoded = implode(',', array_keys( $consignments ) ),
			'format'		 => 'json',
		);

		// Request labels from MyParcel API
		$result = $this->request( 'retrieve-pdf', $api_data);
		$timestamp = current_time('mysql');

		if (!empty($this->pdf)) {
			// Create proper consignment array, put order_id as key
			$this->consignments = array();
			
			$consignment_ids = explode(',',$result['consignment_id']);
			$tracktrace      = explode(',',$result['tracktrace']);
			$consignments_tracktrace = array_combine( $consignment_ids, $tracktrace );
			// $downpartner     = explode(',',$result['downpartner']);
			foreach ( $consignments as $consignment_id => $order_id ) {
				if ( isset( $consignments_tracktrace[$consignment_id] ) ) {
					// add track&trace to consignments
					$this->consignments[$order_id][$consignment_id] =  array(
						'consignment_id'	=> $consignment_id,
						'tracktrace'		=> $consignments_tracktrace[$consignment_id],
						'timestamp'			=> $timestamp,
					);
				}
			}

			// fallback saving consignment data
			$this->save_consignment_data();
		} elseif (isset($result['error'])) {
			// No PDF, show error
			echo 'Error: ' . $result['error'];
		} else {
			echo 'An unknown error occured<br/>';
			echo 'Server response: ' . print_r($result);
		}

		return;
	}

	public function save_consignment_data () {
		if ( empty($this->consignments) ) {
			return false;
		}

		foreach ($this->consignments as $order_id => $order_consignments ) {
			// check if we need to keep old consignments
			if ( isset($this->settings['keep_consignments']) ) {
				if ( $old_consignments = get_post_meta($order_id,'_myparcel_consignments',true) ) {
					$order_consignments = $old_consignments + $order_consignments;
				}
			}

			update_post_meta ( $order_id, '_myparcel_consignments', $order_consignments );

			// set status to complete (if setting enabled)
			if ( isset($this->settings['auto_complete']) ) {
				$order = new WC_Order( $order_id );
				$order->update_status( 'completed', 'Order voltooid na MyParcel export' );
			}
		}

		return;
	}

	public function get_pdf_data () {
		return $this->pdf;
	}

	public function save_pdf ( $path, $filename = '' ) {
		if (empty($filename)) {
			$filename = $this->get_filename();
		}

		file_put_contents(trailingslashit( $path ) . $filename, $this->pdf);

		return;
	}

	public function stream_pdf () {
		header('Content-type: application/pdf');
		header('Content-Disposition: inline; filename="'.$this->get_filename().'"');

		echo $this->pdf;
	}

	public function download_pdf () {
		header('Content-Description: File Transfer');
		header('Content-Type: application/octet-stream');
		header('Content-Disposition: attachment; filename="'.$this->get_filename().'"'); 
		header('Content-Transfer-Encoding: binary');
		header('Connection: Keep-Alive');
		header('Expires: 0');
		header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
		header('Pragma: public');

		echo $this->pdf;
	}

	public function get_filename () {
		$filename  = 'MyParcel';
		$filename .= '-' . date('Y-m-d') . '.pdf';

		return apply_filters( 'wcmyparce_filename', $filename, $this->consignments );
	}

	public function request( $request_type, $data, $method = 'POST' ) {
		// echo '<pre>';var_dump($data);echo '</pre>';die();
		$this->log( "API request: ".$request_type );
		$this->log( "consignment data:\n". var_export( $data, true ) );

		// collect API credentials/settings
		$target_site_api = 'http://www.myparcel.nl/api/';
		$username = $this->settings['api_username'];
		$api_key = $this->settings['api_key'];
		$timestamp = time();
		$nonce = rand(0,255); // note: this should be incremented in case 2 requests occur within the same timestamp (second)

		// JSON encode data
		$json = urlencode(json_encode($data));

		// create GET/POST string (keys in alphabetical order)
		$string = implode('&', array(
			'json=' . $json,
			'nonce=' . $nonce,
			'test=' . (isset( $this->settings['testmode'] ) ? '1' : '0'),
			'timestamp=' . $timestamp,
			'username=' . $username,
		));	

		// $this->log( "Post content:\n" . $string );

		// create hash
		$signature = hash_hmac('sha1', $method . '&' . urlencode($string), $api_key);

		if($method == 'POST')
		{
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, $target_site_api . $request_type . '/');
			curl_setopt($ch, CURLOPT_POST, true);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $string . '&signature=' . $signature);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_TIMEOUT, 60);
			$result = curl_exec($ch);
			curl_close ($ch);
		}
		else // GET
		{
			// depricated, long urls for batch processing gives issues
			$request = $target_site_api . $request_type . '/?' . $string . '&signature=' . $signature;
			$result = file_get_contents($request);
		}
		// $this->log( "RAW api response:\n" . print_r( $result, true ) );

		// decode result
		$result = json_decode($result, true);
		
		// check if we have a pdf - store & unset so that logfile stays small
		if (isset($result['consignment_pdf'])) {
			$this->pdf = urldecode( $result['consignment_pdf'] );
			unset($result['consignment_pdf']);
			$this->log( "PDF received" );
		}

		$this->log( "API response:\n" . print_r( $result, true ) );
		
		// translate errors
		if (isset($result['error'])) {
			$result['error'] = $this->translate_error($result['error']);
		}

		return $result;
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
	 * Error logging
	 */
	public function log ( $message ) {
		if (isset($this->settings['error_logging'])) {
			$current_date_time = date("Y-m-d H:i:s");
			$message = $current_date_time .' ' .$message ."\n";

			file_put_contents($this->log_file, $message, FILE_APPEND);
		}

	}
}

endif; // class_exists
