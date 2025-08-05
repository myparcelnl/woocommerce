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
        if (($data = self::$options[$name] ?? null)) {
            return unserialize($data);
        }

        return $default;
    }

    /**
     * @param  string $option
     * @param         $value
     * @param  null   $autoload
     */
    public static function updateOption($option, $value, $autoload = null): void
    {
        self::$options[$option] = serialize($value);
    }
}
