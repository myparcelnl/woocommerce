<?php

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Tests\Mock;

/**
 * Data container for WordPress options.
 */
final class WordPressPlugins
{
    /**
     * @var array default holds WooCommerce plugin data as being installed
     */
    public static $plugins = [
        'woocommerce/woocommerce.php' => [
            'Name' => 'WooCommerce',
            'PluginURI' => 'https://woocommerce.com/',
            'Version' => '10.0.0',
            'Description' => 'An eCommerce toolkit that helps you sell anything.',
            'Author' => 'Automattic',
            'AuthorURI' => 'https://woocommerce.com/',
            'TextDomain' => 'woocommerce',
            'DomainPath' => '/languages',
        ],
    ];

    /**
     * @return array
     */
    public static function getPlugins(): array
    {
        return self::$plugins;
    }

    /**
     * @param  string $path
     * @param  array  $value
     */
    public static function updatePlugin(string $path, array $value): void
    {
        self::$plugins[$path] = $value;
    }

    public static function clear(): void
    {
        self::$plugins = [];
    }
}
