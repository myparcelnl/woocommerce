<?php

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Tests\Mock;

use WC_Shipping;

/**
 * @extends \WC_Shipping
 */
class MockWcShipping implements StaticMockInterface
{
    /**
     * The single instance of the class
     *
     * @var WC_Shipping
     * @since 2.1
     */
    protected static $_instance;

    /**
     * True if shipping is enabled.
     *
     * @var bool
     */
    public $enabled = false;

    /**
     * Stores packages to ship and to get quotes for.
     *
     * @var array
     */
    public $packages = [];

    /**
     * Stores the shipping classes.
     *
     * @var array
     */
    public $shipping_classes = [];

    /**
     * Stores methods loaded into woocommerce.
     *
     * @var array|null
     */
    public $shipping_methods;

    /**
     * Main WC_Shipping Instance.
     * Ensures only one instance of WC_Shipping is loaded or can be loaded.
     *
     * @return \MyParcelNL\WooCommerce\Tests\Mock\MockWcShipping Main instance
     * @since 2.1
     */
    public static function instance()
    {
        if (null === self::$_instance) {
            self::$_instance = new self();
        }

        return self::$_instance;
    }

    /**
     * @return void
     */
    public static function reset(): void
    {
        self::$_instance = null;
    }

    public function get_shipping_classes(): array
    {
        return $this->shipping_classes;
    }
}