<?php

use MyParcelNL\Sdk\src\Support\Arr;

if (! defined('ABSPATH')) {
    exit;
} // Exit if accessed directly

if (class_exists('WCMPBE_Rest')) {
    return;
}

/**
 * A simple JSON REST request abstraction layer
 */
class WCMPBE_Rest
{
    /**
     * Handle for the current cURL session
     *
     * @var CurlHandle|resource
     */
    private $curl = null;

    /**
     * Default cURL settings
     *
     * @var
     */
    protected $curlDefaults = [
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
        CURLOPT_SSL_VERIFYPEER => false,    // if all else fails :)
    ];

    /**
     * Basic constructor
     * Checks for cURL and initialize options
     *
     * @return void
     * @throws Exception
     */
    public function __construct()
    {
        if (! function_exists("curl_init")) {
            throw new Exception("cURL is not installed on this system");
        }

        $this->curl = curl_init();
        if ((! is_resource($this->curl) && ! is_a($this->curl, 'CurlHandle')) || ! isset($this->curl)) {
            throw new Exception("Unable to create cURL session");
        }

        $options                 = $this->curlDefaults;
        $options[CURLOPT_CAINFO] =
            dirname(__FILE__) . 'lib/ca-bundle.pem'; // Use bundled PEM file to avoid issues with Windows servers

        if ((ini_get('open_basedir') == '') AND (! ini_get('safe_mode'))) {
            $options[CURLOPT_FOLLOWLOCATION] = true;
        }

        $success = curl_setopt_array($this->curl, $options);
        if ($success !== true) {
            throw new Exception("cURL Error: " . curl_error($this->curl));
        }
    }

    /**
     * Closes the current cURL connection
     */
    public function close()
    {
        curl_close($this->curl);
        unset($this->curl);
    }

    public function __destruct()
    {
        $this->close();
    }

    /**
     * Returns last error message
     *
     * @return string  Error message
     */
    public function error()
    {
        return curl_error($this->curl);
    }

    /**
     * Returns last error code
     *
     * @return int
     */
    public function errno()
    {
        return curl_errno($this->curl);
    }

    /**
     * @param       $url
     * @param array $headers
     * @param bool  $raw
     *
     * @return array
     * @throws Exception
     */
    public function get($url, $headers = [], $raw = false)
    {
        return $this->request($url, "GET", $headers, null, null, $raw);
    }

    /**
     * @param       $url
     * @param       $post
     * @param array $headers
     * @param bool  $raw
     *
     * @return array
     * @throws Exception
     */
    public function post($url, $post, $headers = [], $raw = false)
    {
        return $this->request($url, "POST", $headers, $post, null, $raw);
    }

    /**
     * @param       $url
     * @param       $body
     * @param array $headers
     * @param bool  $raw
     *
     * @return array
     * @throws Exception
     */
    public function put($url, $body, $headers = [], $raw = false)
    {
        return $this->request($url, "PUT", $headers, null, $body, $raw);
    }

    /**
     * @param       $url
     * @param array $headers
     * @param bool  $raw
     *
     * @return array
     * @throws Exception
     */
    public function delete($url, $headers = [], $raw = false)
    {
        return $this->request($url, "GET", $headers, null, null, $raw);
    }

    /**
     * @param        $url
     * @param string $method
     * @param array  $headers
     * @param        $post
     * @param null   $body
     * @param bool   $raw
     *
     * @return array
     * @throws Exception
     */
    public function request($url, $method = "GET", $headers = [], $post = '', $body = null, $raw = false)
    {
        // Set the method and related options
        switch ($method) {
            case "PUT":
                throw new Exception('Can not put MyParcel BE shipment', 500);
                break;

            case "POST":
                $response = wp_remote_post($url, ['body' => $post, 'headers' => $headers]);
                break;

            case "DELETE":
                throw new Exception('Can not delete MyParcel BE shipment', 500);
                break;

            case "GET":
            default:
                $response = wp_remote_get($url, $headers);
                break;
        }

        // Close any open resource handle
        if (isset($f) && is_resource($f)) {
            @fclose($f);
        }

        $status = Arr::get($response, "response.code");
        $body   = Arr::get($response, "body");

        if ($raw !== true) {
            $body = json_decode($body, true); // The second parameter set to true returns objects as associative arrays
        }

        if ($status > 400) {
            if ($raw === true) {
                $body = json_decode($body, true);
            }

            if (! empty($body["errors"])) {
                $error = $this->parse_errors($body);
            } elseif (! empty($body["message"])) {
                $error = $body["message"];
            } else {
                $error = "Unknown error";
            }
            throw new Exception($error, $status);
        }

        return ["code" => $status, "body" => $body, "headers" => Arr::get($response, "headers")];
    }

    /**
     * @param $body
     *
     * @return mixed|string
     */
    public function parse_errors($body)
    {
        $errors  = $body['errors'];
        $message = isset($body['message']) ? $body['message'] : '';

        $parsed_errors = [];
        foreach ($errors as $error) {
            $code = isset($error['code']) ? $error['code'] : '';

            if (isset($error['human']) && is_array($error['human'])) {
                foreach ($error['human'] as $key => $human_error) {
                    $parsed_errors[$code] = "{$human_error} (<strong>Code {$code}</strong>)";
                }
            }     elseif (isset($error['message'])) {
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
            $html = sprintf("<ul>%s</ul>", implode("\n", $parsed_errors));
        }

        return $html;
    }
}
