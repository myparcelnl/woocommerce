<?php

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Hooks\Contract;

interface WooCommerceInitHookInterface
{
    /**
     * Code to run whenever "woocommerce_init" is called.
     * Most relevant to add new fields to the blocks checkout.
     * @return void
     */
    public function onWoocommerceInit(): void;
}
