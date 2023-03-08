<?php

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Pdk;

use MyParcelNL\Pdk\Base\PdkBootstrapper;
use function DI\value;

class WcPdkBootstrapper extends PdkBootstrapper
{
    /**
     * @param  string $name
     * @param  string $title
     * @param  string $version
     * @param  string $path
     * @param  string $url
     *
     * @return array
     */
    protected function getAdditionalConfig(
        string $name,
        string $title,
        string $version,
        string $path,
        string $url
    ): array {
        return [
            'userAgent' => value([
                'MyParcelNL-WooCommerce' => $version,
                'WooCommerce'            => defined('WOOCOMMERCE_VERSION') ? constant('WOOCOMMERCE_VERSION') : '?',
                'WordPress'              => get_bloginfo('version'),
            ]),

            'routeBackend'        => value("$name/backend/v1"),
            'routeBackendPdk'     => value('pdk'),
            'routeBackendWebhook' => value('webhook'),

            'routeFrontend'    => value("$name/frontend/v1"),
            'routeFrontendPdk' => value('pdk'),
        ];
    }
}
