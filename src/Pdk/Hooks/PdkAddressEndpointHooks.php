<?php

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Pdk\Hooks;

use MyParcelNL\Pdk\App\Api\PdkEndpoint;
use MyParcelNL\Pdk\Facade\Pdk;
use MyParcelNL\Pdk\Api\PdkAddressActions;
use WP_REST_Request;

final class PdkAddressEndpointHooks extends AbstractPdkEndpointHooks
{
    public function apply(): void
    {
        add_action('rest_api_init', [$this, 'registerPdkRoutes']);
    }

    /**
     * @param  \WP_REST_Request $request
     */
    public function processAddressListRequest(WP_REST_Request $request): void
    {
        $request->set_param('action', PdkAddressActions::PROXY_ADDRESSES_LIST);
        $this->processRequest(PdkEndpoint::CONTEXT_FRONTEND, $request);
    }

    /**
     * @param  \WP_REST_Request $request
     */
    public function processAddressValidateRequest(WP_REST_Request $request): void
    {
        $request->set_param('action', PdkAddressActions::PROXY_ADDRESSES_VALIDATE);
        $this->processRequest(PdkEndpoint::CONTEXT_FRONTEND, $request);
    }

    /**
     * @return void
     */
    public function registerPdkRoutes(): void
    {
        error_log('MyParcel: Registering address routes');
        
        $namespace = 'myparcel/v2';
        error_log('MyParcel: Using namespace: ' . $namespace);

        $route = '/addresses';
        error_log('MyParcel: Registering route: ' . $namespace . $route);

        register_rest_route($namespace, $route, [
            'methods'             => 'GET',
            'callback'           => [$this, 'processAddressListRequest'],
            'permission_callback' => '__return_true',
        ]);

        error_log('MyParcel: Route registered: ' . $namespace . $route);

        $validate_route = '/validate';
        error_log('MyParcel: Registering route: ' . $namespace . $validate_route);

        register_rest_route($namespace, $validate_route, [
            'methods'             => 'GET',
            'callback'           => [$this, 'processAddressValidateRequest'],
            'permission_callback' => '__return_true',
        ]);

        error_log('MyParcel: Route registered: ' . $namespace . $validate_route);
        
        // Debug: Check if our specific route is registered
        global $wp_rest_server;
        if ($wp_rest_server) {
            $routes = $wp_rest_server->get_routes();
            $our_route = $namespace . $route;
            error_log('MyParcel: Is our route registered? ' . (isset($routes[$our_route]) ? 'yes' : 'no'));
        }
        
        error_log('MyParcel: Address routes registration completed');
    }
} 