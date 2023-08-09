<?php
/** @noinspection PhpIllegalPsrClassPathInspection */

declare(strict_types=1);

use MyParcelNL\Pdk\Base\Support\Collection;
use MyParcelNL\Pdk\Facade\Pdk;
use MyParcelNL\WooCommerce\Tests\Mock\MockWcCart;
use MyParcelNL\WooCommerce\Tests\Mock\MockWcClass;
use MyParcelNL\WooCommerce\Tests\Mock\MockWcCustomer;
use MyParcelNL\WooCommerce\Tests\Mock\MockWcDateTime;
use MyParcelNL\WooCommerce\Tests\Mock\MockWcOrder;
use MyParcelNL\WooCommerce\Tests\Mock\MockWcProduct;
use MyParcelNL\WooCommerce\Tests\Mock\MockWpMeta;
use MyParcelNL\WooCommerce\Tests\Mock\WordPressOptions;
use MyParcelNL\WooCommerce\Tests\Mock\WordPressScheduledTasks;

class WC_Data { }

/** @see \WC_Cart */
class WC_Cart extends MockWcCart { }

/** @see \WC_Customer */
class WC_Customer extends MockWcCustomer { }

/** @see \WC_Order */
class WC_Order extends MockWcOrder { }

/** @see \WC_Order_Item */
class WC_Order_Item extends MockWcClass { }

/** @see \WC_Order_Item_Product */
class WC_Order_Item_Product extends WC_Order_Item { }

/** @see \WC_Product */
class WC_Product extends MockWcProduct { }

/**  @see \WC_DateTime */
class WC_DateTime extends MockWcDateTime { }

/**
 * @see \update_post_meta()
 */
function update_post_meta($postId, $metaKey, $metaValue): bool
{
    MockWpMeta::update($postId, $metaKey, $metaValue);

    return true;
}

/**
 * @see \get_post_meta()
 */
function get_post_meta($postId, $metaKey)
{
    return MockWpMeta::get($postId, $metaKey);
}

/**
 * @see \wc_get_product()
 */
function wc_get_product($postId)
{
    return new WC_Product($postId);
}

/**
 * @see \get_bloginfo()
 */
function get_bloginfo(string $name): string
{
    return '';
}

/**
 * @see \get_option()
 */
function get_option(string $name, $default = false)
{
    return WordPressOptions::getOption($name, $default);
}

/**
 * @see \update_option()
 */
function update_option($option, $value, $autoload = null)
{
    WordPressOptions::updateOption($option, $value, $autoload);
}

/**
 * @see \apply_filters()
 */
function apply_filters($tag, $value)
{
    return $value;
}

function get_woocommerce_currency()
{
    return 'EUR';
}

/**
 * @return \stdClass[]
 * @see \wc_get_order_notes()
 */
function wc_get_order_notes($args = []): array
{
    if (! isset($args['order_id'])) {
        return [];
    }

    $date = new WC_DateTime('2023-01-01 00:00:00');

    $orderNotes = new Collection([
        '1' => [
            (object) [
                'id'           => 33,
                'added_by'     => 'admin',
                'content'      => 'test admin',
                'date_created' => $date,

            ],
            (object) [
                'id'           => 34,
                'added_by'     => 'system',
                'content'      => 'test system',
                'date_created' => $date,
            ],
        ],
    ]);

    return $orderNotes->get($args['order_id'], []);
}

/**
 * @see \wp_schedule_single_event()
 */
function wp_schedule_single_event($timestamp, $callback, $args)
{
    /** @var \MyParcelNL\WooCommerce\Tests\Mock\WordPressScheduledTasks $tasks */
    $tasks = Pdk::get(WordPressScheduledTasks::class);

    $tasks->add($callback, $timestamp, $args);
}

/**
 * @see \wc_get_orders()
 */
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

const WP_DEBUG = true;
