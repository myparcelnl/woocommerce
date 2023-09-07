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
    return MockWcData::getByClass(WC_Order::class);
}

/**  @see \wc_get_product() */
function wc_get_product($postId)
{
    return MockWcData::get($postId);
}
