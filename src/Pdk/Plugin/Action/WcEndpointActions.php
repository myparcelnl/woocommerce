<?php

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Pdk\Plugin\Action;

use MyParcelNL;
use MyParcelNL\Pdk\Plugin\Api\PdkEndpointActions;

class WcEndpointActions extends PdkEndpointActions
{
    public const ROUTE = 'pdk';

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
        return get_rest_url(null, sprintf('%s/%s', MyParcelNL::REST_ROUTE, self::ROUTE));
    }
}
