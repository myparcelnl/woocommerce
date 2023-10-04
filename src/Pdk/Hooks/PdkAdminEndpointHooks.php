<?php

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Pdk\Hooks;

use MyParcelNL\Pdk\App\Api\PdkEndpoint;
use MyParcelNL\Pdk\Facade\Pdk;
use WP_REST_Request;
use WP_REST_Response;
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
     * @return \WP_REST_Response
     */
    public function processBackendRequest(WP_REST_Request $request): WP_REST_Response
    {
        return $this->processRequest(PdkEndpoint::CONTEXT_BACKEND, $request);
    }

    /**
     * @return void
     */
    public function registerPdkRoutes(): void
    {
        register_rest_route(Pdk::get('routeBackend'), Pdk::get('routeBackendPdk'), [
            'methods'             => WP_REST_Server::ALLMETHODS,
            'callback'            => [$this, 'processBackendRequest'],
            'permission_callback' => function () {
                if (! is_user_logged_in()) {
                    return false;
                }
                
                if ('shop_manager' === (wp_get_current_user()->roles[0] ?? '')) {
                    return true;
                }

                return current_user_can('manage_options');
            },
        ]);
    }
}
