<?php

use MyParcelNL\Sdk\src\Support\Arr;

if (! defined('ABSPATH')) {
    exit;
} // Exit if accessed directly

if (class_exists('WCMP_Rest')) {
    return;
}

/**
 * A simple JSON REST request abstraction layer
 */
class WCMP_Rest
{
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
     * @param null   $deprecated
     * @param bool   $raw
     *
     * @return array
     * @throws Exception
     */
    public function request($url, $method = 'GET', $headers = [], $post = '', $deprecated = null, $raw = false)
    {
        // Set the method and related options
        switch ($method) {
            case 'PUT':
                throw new Exception('Can not put MyParcel shipment', 500);
                break;

            case 'POST':
                $response = wp_remote_post($url, ['body' => $post, 'headers' => $headers]);
                break;

            case 'DELETE':
                throw new Exception('Can not delete MyParcel shipment', 500);
                break;

            case 'GET':
            default:
                $response = wp_remote_get($url, $headers);
                break;
        }

        $status = Arr::get($response, 'response.code');
        $body   = Arr::get($response, 'body');

        if ($raw !== true) {
            $body = json_decode($body, true); // The second parameter set to true returns objects as associative arrays
        }

        if ($status > 400) {
            if ($raw === true) {
                $body = json_decode($body, true);
            }

            if (! empty($body['errors'])) {
                $error = $this->parse_errors($body);
            } elseif (! empty($body['message'])) {
                $error = $body['message'];
            } else {
                $error = 'Unknown error';
            }
            throw new Exception(esc_html($error), esc_html($status));
        }

        return ['code' => $status, 'body' => $body, 'headers' => Arr::get($response, 'headers')];
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
                $parsed_error = "<li>$parsed_error</li>";
            }
            $html = sprintf("<ul>%s</ul>", implode("\n", $parsed_errors));
        }

        return $html;
    }
}
