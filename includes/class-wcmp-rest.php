<?php
/**
 * A simple JSON REST request abstraction layer
 */
class WC_MyParcel_REST_Client
{
	/**
	 * Handle for the current cURL session
	 * @var
	 */
	private $curl = null;

	/**
	 * Default cURL settings
	 * @var
	 */
	protected $curlDefaults = array(
		// BOOLEANS
		CURLOPT_AUTOREFERER    => true,     // Update referer on redirects
		CURLOPT_FAILONERROR    => false,    // Return false on HTTP code > 400
		CURLOPT_FOLLOWLOCATION => false,    // DON'T Follow redirects
		CURLOPT_RETURNTRANSFER => true,
		CURLOPT_FRESH_CONNECT  => true,     // Don't use cached connection
		CURLOPT_FORBID_REUSE   => true,     // Close connection

		// INTEGERS
		CURLOPT_TIMEOUT        => 10,       // cURL timeout
		CURLOPT_CONNECTTIMEOUT => 10,       // Connection timeout

		// STRINGS
		CURLOPT_ENCODING       => "",       // "identity", "deflate", and "gzip"
		CURLOPT_USERAGENT      => "MyParcel REST PHP Client/1.0",
		CURLOPT_SSL_VERIFYPEER => false,    // if all else fails :)
	);

	/**
	 * Basic constructor
	 *
	 * Checks for cURL and initialize options
	 * @return void
	 */
	function __construct() {
		if (!function_exists("curl_init")) {
			throw new Exception("cURL is not installed on this system");
		}

		$this->curl = curl_init();
		if (!is_resource($this->curl) || !isset($this->curl)) {
			throw new Exception("Unable to create cURL session");
		}

		$options = $this->curlDefaults;
		$options[CURLOPT_CAINFO] = dirname(__FILE__) . 'lib/ca-bundle.pem'; // Use bundled PEM file to avoid issues with Windows servers

		if ((ini_get('open_basedir') == '') AND (!ini_get('safe_mode'))) {
			$options[CURLOPT_FOLLOWLOCATION] = true;
		}

		$success = curl_setopt_array( $this->curl, $options );
		if ($success !== true) {
			throw new Exception("cURL Error: " . curl_error($this->curl));
		}
	}

	/**
	 * Closes the current cURL connection
	 */
	public function close() {
		@curl_close($this->curl);
	}

	function __destruct() {
		$this->close();
	}

	/**
	 * Returns last error message
	 * @return string  Error message
	 */
	public function error() {
		 return curl_error($this->curl);
	}

	/**
	 * Returns last error code
	 * @return int
	 */
	public function errno() {
		 return curl_errno($this->curl);
	} // end function

	public function get($url, $headers = array(), $raw = false) {
		return $this->request($url, "GET", $headers, null, null, $raw);
	}

	public function post($url, $post, $headers = array(), $raw = false) {
		return $this->request($url, "POST", $headers, $post, null, $raw);
	}

	public function put($url, $body, $headers = array(), $raw = false) {
		return $this->request($url, "PUT", $headers, null, $body, $raw);
	}

	public function delete($url, $headers = array(), $raw = false) {
		return $this->request($url, "GET", $headers, null, null, $raw);
	}

	public function request($url, $method = "GET", $headers = array(), $post, $body = null, $raw = false) {
		// Set the URL
		curl_setopt($this->curl, CURLOPT_URL, $url);
		// echo '<pre>';var_dump($post);echo '</pre>';die();

		// Set the method and related options
		switch ($method) {
			case "PUT":
				curl_setopt($this->curl, CURLOPT_PUT, true);
			break;

			case "POST":
				curl_setopt($this->curl, CURLOPT_POST, true);
			break;

			case "DELETE":
			    curl_setopt($this->curl, CURLOPT_CUSTOMREQUEST, "DELETE");
				break;

			case "GET":
			default:
			break;
		}

		// Set the headers
		if (!empty($headers) && is_array($headers)) {
			// An array of HTTP header fields to set, in the format
			//array("Content-type: text/plain", "Content-length: 100")
			curl_setopt($this->curl, CURLOPT_HTTPHEADER, $headers);
		}

		if (!empty($post)) {
			curl_setopt($this->curl, CURLOPT_POSTFIELDS, $post);
		}

		// Retrieve HTTP response headers
		curl_setopt($this->curl, CURLOPT_HEADER, true);

		$response = curl_exec($this->curl);
		$info = curl_getinfo($this->curl);

		// echo '<pre>';var_dump($response);echo '</pre>';die();

		// Close any open resource handle
		if (isset($f) && is_resource($f)) {
			@fclose($f);
		}

		$status = $info["http_code"];
		$header = substr($response, 0, $info["header_size"]);
		$body = substr( $response, $info["header_size"]);

		if ($raw !== true) {
			$body = json_decode($body, true); // The second parameter set to true returns objects as associative arrays
		}

		if ($status > 400) {

			if ($raw === true) {
				$body = json_decode($body, true);
			}

			if ( !empty($body["errors"])) {
				$error = $this->parse_errors( $body );
			} elseif ( !empty($body["message"] ) ) {
				$error = $body["message"];
			} else {
				$error = "Unknown error";
			}
			throw new Exception($error, $status);
		}

		// Parse response headers
		$response_headers = array();
		$lines = explode("\r\n", $header);
		array_shift($lines);
		foreach ($lines as $line) {
			// Skip empty lines
			if ("" == trim($line)) {
				continue;
			}
			@list($k, $v) = explode(": ", $line, 2);
			$response_headers[strtolower($k)] = $v;
		}

		return array("code" => $status, "body" => $body, "headers" => $response_headers);
	}

	public function parse_errors( $body ) {
		$errors = $body['errors'];
		$message = isset( $body['message'] ) ? $body['message'] : '';
		// echo '<pre>';var_dump($errors);echo '</pre>';die();

		$parsed_errors = array();
		foreach ($errors as $error) {
			$code = isset($error['code']) ? $error['code'] : '';

			if ( isset($error['human']) && is_array($error['human']) ) {
				foreach ($error['human'] as $key => $human_error) {
					$parsed_errors[$code] = "{$human_error} (<strong>Code {$code}</strong>)";
				}
			} elseif ( isset($error['message']) ) {
				$parsed_errors[$code] = "{$error['message']} (<strong>Code {$code}</strong>)";
			} else {
				$parsed_errors[$code] = "{$message} (<strong>Code {$code}</strong>)";
			}
		}

		if (count($parsed_errors) == 1) {
			$html = array_shift($parsed_errors);
		} else {
			foreach ($parsed_errors as &$parsed_error) {
				$parsed_error = "<li>{$parsed_error}</li>";
			}
			$html = sprintf("<ul>%s</ul>", implode("\n",$parsed_errors));
		}

		return $html;
	}
}
