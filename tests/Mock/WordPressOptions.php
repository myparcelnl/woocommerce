<?php

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Tests\Mock;

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
