<?php

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Pdk\Hooks;

use MyParcelNL\Pdk\App\Api\PdkEndpoint;
use MyParcelNL\WooCommerce\Hooks\Concern\UsesPdkRequestConverter;
use MyParcelNL\WooCommerce\Hooks\Contract\WordPressHooksInterface;
use WP_REST_Request;
use WP_REST_Response;

abstract class AbstractPdkEndpointHooks implements WordPressHooksInterface
{
    use UsesPdkRequestConverter;

    /**
     * @var \MyParcelNL\Pdk\App\Api\PdkEndpoint
     */
    private $endpoint;

    /**
     * @param  \MyParcelNL\Pdk\App\Api\PdkEndpoint $endpoint
     */
    public function __construct(PdkEndpoint $endpoint)
    {
        $this->endpoint = $endpoint;
    }

    /**
     * Convert the request to a format that the PDK understands, calls the endpoint and converts the response back to
     * a format that WordPress understands.
     * Disables notices to prevent random plugin notices from breaking the response.
     *
     * @param  string           $context
     * @param  \WP_REST_Request $request
     *
     * @return \WP_REST_Response
     */
    protected function processRequest(string $context, WP_REST_Request $request): WP_REST_Response
    {
        error_reporting(error_reporting() & ~E_NOTICE);

        $convertedRequest = $this->convertRequest($request);

        $response = $this->endpoint->call($convertedRequest, $context);

        return $this->convertResponse($response);
    }
}
