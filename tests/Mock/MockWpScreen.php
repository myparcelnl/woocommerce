<?php

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Tests\Mock;

use stdClass;

final class MockWpScreen implements StaticMockInterface
{
    /**
     * @var null|string
     */
    private static $id;

    public static function get(): ?stdClass
    {
        if (null === self::$id) {
            return null;
        }

        $screen     = new stdClass();
        $screen->id = self::$id;

        return $screen;
    }

    public static function reset(): void
    {
        self::$id = null;
    }

    public static function set(?string $id): void
    {
        self::$id = $id;
    }
}
