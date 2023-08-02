<?php
/** @noinspection PhpIllegalPsrClassPathInspection */

declare(strict_types=1);

use MyParcelNL\Pdk\Base\Support\Collection;
use MyParcelNL\WooCommerce\Tests\Mock\MockWcCart;
use MyParcelNL\WooCommerce\Tests\Mock\MockWcClass;
use MyParcelNL\WooCommerce\Tests\Mock\MockWcCustomer;
use MyParcelNL\WooCommerce\Tests\Mock\MockWcDateTime;
use MyParcelNL\WooCommerce\Tests\Mock\MockWcOrder;
use MyParcelNL\WooCommerce\Tests\Mock\MockWcProduct;
use MyParcelNL\WooCommerce\Tests\Mock\MockWpMeta;

class WC_Data {}

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
 * Data container for WordPress options.
 */
final class WordPressOptions
{
    public static $options = [
        'woocommerce_weight_unit' => 'kg',
    ];

    public static function getOption(string $name, $default = false)
    {
        return self::$options[$name] ?? $default;
    }

    public static function updateOption($option, $value, $autoload = null): void
    {
        self::$options[$option] = $value;
    }
}

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

const WP_DEBUG = true;
