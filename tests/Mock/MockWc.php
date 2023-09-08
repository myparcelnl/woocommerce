<?php

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Tests\Mock;

class MockWc extends MockWcClass implements StaticMockInterface
{
    /**
     * @var null|self
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
     * @var string
     */
    public $version = '5.0.0';

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

    public static function reset(): void
    {
        self::$instance = null;
    }
}
