<?php

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Pdk\Plugin\Action;

use MyParcelNL;
use MyParcelNL\Pdk\Plugin\Api\Frontend\AbstractFrontendEndpointService;

class WcFrontendEndpointService extends AbstractFrontendEndpointService
{
    public const ROUTE = 'myparcelnl';

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
        return get_rest_url(null, sprintf('%s/%s', MyParcelNL::FRONTEND_REST_ROUTE, self::ROUTE));
    }
}
