<?php

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\WooCommerce\Contract;

use MyParcelNL\Pdk\Base\Support\Collection;
use WC_Order;

interface WcOrderRepositoryInterface
{
    /**
     * @param  int|string|WC_Order|\WP_Post $input
     *
     * @return \WC_Order
     */
    public function get($input): WC_Order;

    /**
     * @param  int|string|WC_Order|\WP_Post $input
     *
     * @return \MyParcelNL\Pdk\Base\Support\Collection<\WC_Order_Item>
     */
    public function getItems($input): Collection;

    /**
     * @param $input
     *
     * @return bool
     */
    public function hasLocalPickup($input): bool;
}
