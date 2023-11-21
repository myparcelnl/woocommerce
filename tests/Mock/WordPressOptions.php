<?php

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Tests\Mock;

/**
 * Data container for WordPress options.
 */
final class WordPressOptions
{
    /**
     * @var array
     */
    public static $options = [
        'woocommerce_weight_unit' => 'kg',
    ];

    /**
     * @param  string $name
     * @param  bool   $default
     *
     * @return bool|mixed
     */
    public static function getOption(string $name, $default = false)
    {
        return self::$options[$name] ?? $default;
    }

    /**
     * @param  string $option
     * @param         $value
     * @param  null   $autoload
     */
    public static function updateOption($option, $value, $autoload = null): void
    {
        self::$options[$option] = $value;
    }
}
