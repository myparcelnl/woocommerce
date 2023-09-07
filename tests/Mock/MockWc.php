<?php

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Tests\Mock;

/**
 * @extends \WC_Cart
 */
class MockWc extends MockWcClass
{
    /**
     * @var self
     */
    private static $instance;

    /**
     * @var \MyParcelNL\WooCommerce\Tests\Mock\MockWcCart
     */
    public $cart;

    /**
     * @var \MyParcelNL\WooCommerce\Tests\Mock\MockWcSession
     */
    public $session;

    /**
     * @throws \Throwable
     */
    public function __construct()
    {
        parent::__construct();
        $this->cart    = new MockWcCart();
        $this->session = new MockWcSession();
    }

    public static function getInstance(): self
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }

        return self::$instance;
    }
}
