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
        register_rest_route(Pdk::get('routeFrontend'), 'address/list', [
            'methods'             => 'GET',
            'callback'           => [$this, 'processAddressListRequest'],
            'permission_callback' => '__return_true',
        ]);

        register_rest_route(Pdk::get('routeFrontend'), 'address/validate', [
            'methods'             => 'GET',
            'callback'           => [$this, 'processAddressValidateRequest'],
            'permission_callback' => '__return_true',
        ]);
    }
} 
