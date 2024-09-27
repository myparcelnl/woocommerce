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
     * @param $key
     *
     * @return array
     */
    public function get($key): array
    {
        return self::$session[$key] ?? [
            'rates' => [
                'flat_rate:0' => [],
            ],
        ];
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
