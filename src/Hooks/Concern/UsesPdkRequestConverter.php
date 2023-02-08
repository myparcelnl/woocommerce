<?php

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Hooks\Concern;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use WP_REST_Request;
use WP_REST_Response;

trait
UsesPdkRequestConverter
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
            $wpRestRequest->get_query_params(),
            [],
            $wpRestRequest->get_file_params(),
            [],
            $wpRestRequest->get_body()
        );

        $request->setMethod($wpRestRequest->get_method());
        $request->headers->replace($wpRestRequest->get_headers());

        return $request;
    }

    /**
     * Convert a WP_REST_Response to a Symfony Response.
     *
     * @param  \Symfony\Component\HttpFoundation\Response $response
     *
     * @return \WP_REST_Response
     */
    protected function convertResponse(Response $response): WP_REST_Response
    {
        if ($response->headers->has('Content-Type') && $response->headers->get('Content-Type') === 'application/json') {
            $content = json_decode($response->getContent(), true);
        } else {
            $content = $response->getContent();
        }

        $wpResponse = new WP_REST_Response($content, $response->getStatusCode());
        $wpResponse->header('Content-Type', $response->headers->get('Content-Type'));

        return $wpResponse;
    }
}

