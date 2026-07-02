<?php

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Tests\Mock;

/**
 * @extends \WC_Session
 */
class MockWcSession implements StaticMockInterface
{
    private static $session = [
        'rates' => [
            'flat_rate:0' => [],
        ],
    ];

    /**
     * Mirrors WC_Session::get(): returns the stored value or the given default (null) when unset.
     *
     * @param  mixed $key
     * @param  mixed $default
     *
     * @return mixed
     */
    public function get($key, $default = null)
    {
        return self::$session[$key] ?? $default;
    }

    /**
     * @param $key
     * @param $value
     *
     * @return void
     */
    public function set($key, $value): void
    {
        self::$session[$key] = $value;
    }

    public static function reset(): void
    {
        self::$session = [];
    }
}
