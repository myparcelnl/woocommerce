<?php

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Pdk\Hooks;

use MyParcelNL\Pdk\App\Api\PdkEndpoint;
use MyParcelNL\Pdk\Facade\Pdk;
use WP_REST_Request;
use WP_REST_Response;

final class PdkFrontendEndpointHooks extends AbstractPdkEndpointHooks
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
    public function processFrontendRequest(WP_REST_Request $request): WP_REST_Response
    {
        return $this->processRequest(PdkEndpoint::CONTEXT_FRONTEND, $request);
    }

    /**
     * @return void
     */
    public function registerPdkRoutes(): void
    {
        if (empty(WC()->cart)) {
            WC()->frontend_includes();
            wc_load_cart();
            WC()->cart->get_cart_from_session();
        }

        register_rest_route(Pdk::get('routeFrontend'), Pdk::get('routeFrontendMyParcel'), [
            'methods'             => 'GET',
            'callback'            => [$this, 'processFrontendRequest'],
            'permission_callback' => '__return_true',
        ]);
    }
}
