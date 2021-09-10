<?php

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\includes\Concerns;

defined('ABSPATH') or die();

trait HasInstance
{
    /**
     * @var static
     */
    private static $instance;

    /**
     * Get the one instance of this class that is loaded or can be loaded.
     *
     * @return static
     * @TODO         add return type "static" when we've dropped support for php < 8
     * @noinspection PhpMissingReturnTypeInspection
     */
    public static function getInstance()
    {
        if (null === static::$instance) {
            static::$instance = new static();
        }

        return static::$instance;
    }
}
