<?php

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Pdk\Hooks;

use MyParcelNL\Pdk\App\Api\PdkEndpoint;
use MyParcelNL\Pdk\Facade\Pdk;
use WP_REST_Request;

final class PdkFrontendEndpointHooks extends AbstractPdkEndpointHooks
{
    public function apply(): void
    {
        add_action('rest_api_init', [$this, 'registerPdkRoutes']);
    }

    /**
     * @param  \WP_REST_Request $request
     */
    public function processFrontendRequest(WP_REST_Request $request): void
    {
        $this->processRequest(PdkEndpoint::CONTEXT_FRONTEND, $request);
    }

    /**
     * Restrict the public frontend route to same-origin browser requests.
     *
     * Same-origin GET requests and non-browser callers send no Origin header and pass
     * through; browser requests carrying a foreign Origin are rejected. The allowed
     * list is filterable via the WordPress `allowed_http_origins` hook.
     *
     * @return bool
     */
    public function checkOrigin(): bool
    {
        $origin = get_http_origin();

        if (! $origin) {
            return true;
        }

        return in_array($origin, get_allowed_http_origins(), true);
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
            'methods'             => ['GET', 'POST'],
            'callback'            => [$this, 'processFrontendRequest'],
            'permission_callback' => [$this, 'checkOrigin'],
        ]);
    }
}
