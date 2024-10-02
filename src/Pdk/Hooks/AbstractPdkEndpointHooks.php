<?php

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Pdk\Hooks;

use MyParcelNL\Pdk\App\Api\PdkEndpoint;
use MyParcelNL\WooCommerce\Hooks\Concern\UsesPdkRequestConverter;
use MyParcelNL\WooCommerce\Hooks\Contract\WordPressHooksInterface;
use WP_REST_Request;

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
     * Convert the request to a format that the PDK understands, calls the endpoint and sends the response.
     * Disables notices to prevent random plugin notices from breaking the response.
     *
     * @param  string           $context
     * @param  \WP_REST_Request $request
     *
     * @return void
     */
    protected function processRequest(string $context, WP_REST_Request $request): void
    {
        error_reporting(error_reporting() & ~E_NOTICE);

        $convertedRequest = $this->convertRequest($request);
        $response         = $this->endpoint->call($convertedRequest, $context);

        $response->send();

        exit;
    }
}
