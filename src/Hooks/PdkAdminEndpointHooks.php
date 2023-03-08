<?php

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Hooks;

use MyParcelNL\Pdk\Facade\Pdk;
use MyParcelNL\Pdk\Plugin\Api\PdkEndpoint;
use MyParcelNL\WooCommerce\Hooks\Concern\UsesPdkRequestConverter;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

final class PdkAdminEndpointHooks implements WordPressHooksInterface
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
    public function processPdkRequest(WP_REST_Request $request): WP_REST_Response
    {
        /** @var \MyParcelNL\Pdk\Plugin\Api\PdkEndpoint $endpoint */
        $endpoint = Pdk::get(PdkEndpoint::class);

        $response = $endpoint->call($this->convertRequest($request), PdkEndpoint::CONTEXT_BACKEND);

        return $this->convertResponse($response);
    }

    /**
     * @return void
     */
    public function registerPdkRoutes(): void
    {
        register_rest_route(Pdk::get('routeBackend'), Pdk::get('routeBackendPdk'), [
            'methods'             => WP_REST_Server::ALLMETHODS,
            'callback'            => [$this, 'processPdkRequest'],
            'permission_callback' => function () {
                return current_user_can('manage_options');
            },
        ]);
    }
}
