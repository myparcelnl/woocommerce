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
    public static $options = [];

    /**
     * @param  string $name
     * @param  bool   $default
     *
     * @return bool|mixed
     */
    public static function getOption(string $name, $default = false)
    {
        return array_key_exists($name, self::$options)
            ? self::$options[$name]
            : $default;
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

    public static function deleteOption(string $option): bool
    {
        if (! array_key_exists($option, self::$options)) {
            return false;
        }

        unset(self::$options[$option]);

        return true;
    }

    public static function reset(): void
    {
        self::$options = [];
    }
}
