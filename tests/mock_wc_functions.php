<?php

/** @noinspection PhpMissingReturnTypeInspection,PhpUnhandledExceptionInspection,PhpUnusedParameterInspection */

declare(strict_types=1);

use MyParcelNL\WooCommerce\Tests\Mock\MockWc;
use MyParcelNL\WooCommerce\Tests\Mock\MockWcData;
use MyParcelNL\WooCommerce\Tests\Mock\MockWpCache;

/** @see \get_woocommerce_currency() */
function get_woocommerce_currency(): string
{
    return 'EUR';
}

/** @see \register_block_type() */
function register_block_type($blockType, $args = []): void {}

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

    return $item ? $item->getAttributes()['order_notes'] ?? [] : [];
}

/**
 * @param  int $id
 *
 * @return \MyParcelNL\WooCommerce\Tests\Mock\MockWcClass
 */
function wc_get_order(int $id)
{
    return MockWcData::get($id);
}

function wc_get_order_statuses()
{
    return [
        'wc-pending'    => 'Pending payment',
        'wc-processing' => 'Processing',
        'wc-on-hold'    => 'On hold',
        'wc-completed'  => 'Completed',
        'wc-cancelled'  => 'Cancelled',
        'wc-refunded'   => 'Refunded',
        'wc-failed'     => 'Failed',
    ];
}

/** @see \wc_get_orders() */
function wc_get_orders($args)
{
    return MockWcData::getByClass(WC_Order::class);
}

/** @see \wc_get_product() */
function wc_get_product($postId): ?WC_Product
{
    /** @var \WC_Product $product */
    $product = MockWcData::get((int) $postId);

    return $product;
}

/**
 * @return \WC_Product[]
 * @see \wc_get_products()
 */
function wc_get_products($args): array
{
    return MockWcData::getByClass(WC_Product::class);
}

/** @see \WC */
function WC()
{
    return MockWc::getInstance();
}

/**
 * @param  string $page Page slug.
 *
 * @return int
 * @see \wc_get_page_id()
 *      Retrieve page ids - used for myaccount, edit_address, shop, cart, checkout, pay, view_order, terms. returns -1
 *      if no page is found.
 */
function wc_get_page_id(string $page)
{
    if ('pay' === $page || 'thanks' === $page) {
        $page = 'checkout';
    }
    if ('change_password' === $page || 'edit_address' === $page || 'lost_password' === $page) {
        $page = 'myaccount';
    }

    $allPages = MockWpCache::$cache['pages'];

    foreach ($allPages as $pageId => $singlePage) {
        if ($singlePage['data']['pageName'] === $page) {
            return $pageId;
        }
    }

    return -1;
}
