<?php

declare(strict_types=1);

namespace MyParcelNL\WooCommerce;

use MyParcelNL\Pdk\Base\Pdk;
use MyParcelNL\WooCommerce\Pdk\WcPdkBootstrapper;
use MyParcelNL\WooCommerce\Tests\Mock\MockWcPdkBootstrapper;

if (! function_exists('\MyParcelNL\WooCommerce\bootPdk')) {
    /**
     * @param  string $version
     * @param  string $path
     * @param  string $url
     * @param  string $mode
     *
     * @return void
     * @throws \Exception
     */
    function bootPdk(
        string $version,
        string $path,
        string $url,
        string $mode = Pdk::MODE_PRODUCTION
    ): void {
        // TODO: find a way to make this work without having this in production code
        if (! defined('PEST')) {
            WcPdkBootstrapper::boot(...func_get_args());

            return;
        }

        MockWcPdkBootstrapper::boot(...func_get_args());
    }
}
