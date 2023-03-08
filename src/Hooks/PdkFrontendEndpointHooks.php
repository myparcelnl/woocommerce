<?php

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Hooks;

use MyParcelNL;
use MyParcelNL\Pdk\Facade\Pdk;
use MyParcelNL\Pdk\Plugin\Api\PdkEndpoint;
use MyParcelNL\WooCommerce\Hooks\Concern\UsesPdkRequestConverter;
use MyParcelNL\WooCommerce\Pdk\Plugin\Action\WcBackendEndpointService;
use WP_REST_Request;
use WP_REST_Response;

final class PdkFrontendEndpointHooks implements WordPressHooksInterface
{
    use UsesPdkRequestConverter;

    public function apply(): void
    {
        add_action('rest_api_init', [$this, 'registerPdkRoutes']);
    }

    /**
     * @param  \WP_REST_Request $request
     *
     * @return \WP_REST_Response
     */
    public function processFrontendRequest(WP_REST_Request $request): WP_REST_Response
    {
        /** @var \MyParcelNL\Pdk\Plugin\Api\PdkEndpoint $endpoint */
        $endpoint = Pdk::get(PdkEndpoint::class);

        $response = $endpoint->call($this->convertRequest($request), PdkEndpoint::CONTEXT_FRONTEND);

        return $this->convertResponse($response);
    }

    /**
     * @return void
     */
    public function registerPdkRoutes(): void
    {
        register_rest_route(Pdk::get('routeFrontend'), Pdk::get('routeFrontendPdk'), [
            'methods'             => 'GET',
            'callback'            => [$this, 'processFrontendRequest'],
            'permission_callback' => '__return_true',
        ]);
    }
}
