<?php

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Pdk\Plugin\Action;

use MyParcelNL\Pdk\Plugin\Api\PdkEndpointActions;
use MyParcelNL\WooCommerce\Hooks\RestApiHooks;

class WcEndpointActions extends PdkEndpointActions
{
    /**
     * Add a nonce to the request to authenticate the user.
     */
    public function __construct()
    {
        $this->headers['X-WP-Nonce'] = wp_create_nonce('wp_rest');
    }

    /**
     * @return string
     */
    public function getBaseUrl(): string
    {
        return get_rest_url(null, sprintf('%s/%s', RestApiHooks::NAMESPACE, RestApiHooks::ROUTE_PDK));
    }
}
