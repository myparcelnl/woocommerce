<?php

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Pdk\Hooks;

use MyParcelNL\Pdk\App\Api\PdkEndpoint;
use MyParcelNL\Pdk\Facade\Pdk;
use WP_REST_Request;
use WP_REST_Server;

final class PdkAdminEndpointHooks extends AbstractPdkEndpointHooks
{
    public function apply(): void
    {
        add_action('rest_api_init', [$this, 'registerPdkRoutes']);
    }

    /**
     * @param  \WP_REST_Request $request
     *
     * @return void
     */
    public function processBackendRequest(WP_REST_Request $request): void
    {
        $this->processRequest(PdkEndpoint::CONTEXT_BACKEND, $request);
    }

    /**
     * @return void
     */
    public function registerPdkRoutes(): void
    {
        register_rest_route(Pdk::get('routeBackend'), Pdk::get('routeBackendPdk'), [
            'methods'             => WP_REST_Server::ALLMETHODS,
            'callback'            => [$this, 'processBackendRequest'],
            'permission_callback' => Pdk::get('routeBackendPermissionCallback'),
        ]);
    }
}
