<?php

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Hooks\Concern;

use Symfony\Component\HttpFoundation\Request;
use WP_REST_Request;

trait UsesPdkRequestConverter
{
    /**
     * Convert a WP_REST_Request to a Symfony Request.
     *
     * @param  \WP_REST_Request $wpRestRequest
     *
     * @return \Symfony\Component\HttpFoundation\Request
     */
    protected function convertRequest(WP_REST_Request $wpRestRequest): Request
    {
        $request = Request::create(
            $wpRestRequest->get_route(),
            $wpRestRequest->get_method(),
            $wpRestRequest->get_params(),
            $_COOKIE,
            $wpRestRequest->get_file_params(),
            $_SERVER,
            $wpRestRequest->get_body()
        );

        $request->setMethod($wpRestRequest->get_method());
        $request->headers->replace($wpRestRequest->get_headers());

        return $request;
    }
}
