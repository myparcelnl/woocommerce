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
 * Captures registered Store API update callbacks keyed by namespace so tests can invoke them.
 *
 * @see \Automattic\WooCommerce\StoreApi\Schemas\ExtendSchema::register_update_callback()
 */
function woocommerce_store_api_register_update_callback($args): void
{
    $GLOBALS['__mpwc_store_api_update_callbacks'][$args['namespace']] = $args['callback'];
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

/**
 * Honours the arguments the plugin actually relies on: EXISTS/NOT EXISTS meta queries, a result
 * limit and 'ids' as return type. Without them a paging migration cannot be tested, because every
 * run would keep receiving the orders it just handled.
 *
 * @see \wc_get_orders()
 */
function wc_get_orders($args)
{
    $orders = MockWcData::getByClass(WC_Order::class);

    foreach ($args['meta_query'] ?? [] as $clause) {
        // Skip the 'relation' => 'AND' entry; every clause is applied conjunctively anyway.
        if (! is_array($clause) || ! isset($clause['key'])) {
            continue;
        }

        $orders = array_values(array_filter($orders, static function ($order) use ($clause): bool {
            $exists = $order->meta_exists($clause['key']);

            return 'NOT EXISTS' === ($clause['compare'] ?? 'EXISTS') ? ! $exists : $exists;
        }));
    }

    usort($orders, static function ($a, $b): int {
        return (int) $a->get_id() <=> (int) $b->get_id();
    });

    $limit = (int) ($args['limit'] ?? -1);

    if ($limit > 0) {
        $orders = array_slice($orders, 0, $limit);
    }

    if ('ids' === ($args['return'] ?? null)) {
        return array_map(static function ($order) {
            return $order->get_id();
        }, $orders);
    }

    return $orders;
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

    $allPages = MockWpCache::$cache['pages'] ?? [];

    foreach ($allPages as $pageId => $singlePage) {
        if ($singlePage['data']['pageName'] === $page) {
            return $pageId;
        }
    }

    return -1;
}
