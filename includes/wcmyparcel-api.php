<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( !class_exists( 'WC_MyParcel_API' ) ) :

class WC_MyParcel_API {

	/**
	 * Construct.
	 */
			
	public function __construct() {
		$this->settings = get_option( 'wcmyparcel_settings' );
		$this->log_file = dirname(dirname(__FILE__)).'/myparcel_log.txt';
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

		// decode result
		$result = json_decode($result, true);
		
		$this->log( "API response:\n" . print_r( $result, true ) );
		
		// translate errors
		if (isset($result['error'])) {
			$result['error'] = $this->translate_error($result['error']);
		}

		return $result;
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

return new WC_MyParcel_API();
