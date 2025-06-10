<?php

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Hooks\Contract;

interface WooCommerceInitCallbacksInterface
{
    /**
     * Run the specific code directly when the woocommerce_init action fires.
     *
     * @return void
     */
    public function onWoocommerceInit(): void;
}
