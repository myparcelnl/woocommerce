<?php

declare(strict_types=1);

namespace MyParcelNL\WooCommerce;

use MyParcelNL\Pdk\Base\Pdk;
use MyParcelNL\WooCommerce\Pdk\WcPdkBootstrapper;

if (! function_exists('\MyParcelNL\WooCommerce\bootPdk')) {
    /**
     * @param  string $name
     * @param  string $title
     * @param  string $version
     * @param  string $path
     * @param  string $url
     * @param  string $mode
     *
     * @return void
     * @throws \Exception
     */
    function bootPdk(
        string $name,
        string $title,
        string $version,
        string $path,
        string $url,
        string $mode = Pdk::MODE_PRODUCTION
    ): void {
        WcPdkBootstrapper::boot($name, $title, $version, $path, $url, $mode);
    }
}
