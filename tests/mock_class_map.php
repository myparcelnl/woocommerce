<?php
/** @noinspection PhpIllegalPsrClassPathInspection */

declare(strict_types=1);

use MyParcelNL\WooCommerce\Tests\Mock\MockWcCart;
use MyParcelNL\WooCommerce\Tests\Mock\MockWcCustomer;
use MyParcelNL\WooCommerce\Tests\Mock\MockWcOrder;

class WC_Order extends MockWcOrder { }

class WC_Customer extends MockWcCustomer { }

class WC_Cart extends MockWcCart { }

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

const WP_DEBUG = true;
