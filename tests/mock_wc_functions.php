<?php
/** @noinspection PhpMissingReturnTypeInspection,PhpUnhandledExceptionInspection */

declare(strict_types=1);

use MyParcelNL\WooCommerce\Tests\Mock\MockWcData;

/** @see \get_woocommerce_currency() */
function get_woocommerce_currency(): string
{
    return 'EUR';
}

/**
 * @return \stdClass[]
 * @see \wc_get_order_notes()
 */
function wc_get_order_notes($args = []): array
{
    $id = $args['order_id'] ?? null;

    if (! $id) {
        return [];
    }

    $item = MockWcData::get($id);

    return $item->getAttributes()['order_notes'] ?? [];
}

/** @see \wc_get_orders() */
function wc_get_orders($args)
{
    // create array of 324 wc_orders
    return array_map(
        static function () {
            return new WC_Order(['id' => random_int(1, 10000)]);
        },
        range(1, 324)
    );
}
