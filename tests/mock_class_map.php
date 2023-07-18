<?php
/** @noinspection PhpIllegalPsrClassPathInspection */

declare(strict_types=1);

use MyParcelNL\Pdk\Base\Support\Collection;
use MyParcelNL\WooCommerce\Tests\Mock\MockWcCart;
use MyParcelNL\WooCommerce\Tests\Mock\MockWcCustomer;
use MyParcelNL\WooCommerce\Tests\Mock\MockWcOrder;

/** @see \WC_Cart */
class WC_Cart extends MockWcCart
{
    public function needs_shipping()
    {
        return $this->get_needs_shipping();
    }
}

/** @see \WC_Customer */
class WC_Customer extends MockWcCustomer { }

/** @see \WC_Order */
class WC_Order extends MockWcOrder { }

/** @see \WC_Order_Item */
class WC_Order_Item extends MockWcOrder { }

/** @see \WC_Order_Item_Product */
class WC_Order_Item_Product extends MockWcOrder { }

/** @see \WC_Product */
class WC_Product extends MockWcOrder
{
    public function needs_shipping()
    {
        return $this->get_needs_shipping();
    }
}

/**  @see \WC_DateTime */
class WC_DateTime extends DateTime
{
    public function date($args)
    {
        return $this->format($args);
    }
}

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
                'note'         => 'test admin',
                'date_created' => $date,

            ],
            (object) [
                'id'           => 34,
                'added_by'     => 'system',
                'note'         => 'test system',
                'date_created' => $date,
            ],
        ],
    ]);

    return $orderNotes->get($args['order_id'], []);
}

const WP_DEBUG = true;
