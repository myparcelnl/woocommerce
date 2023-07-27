<?php

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Tests\Mock;

use MyParcelNL\Pdk\Base\Support\Arr;

final class MockWpMeta
{
    public static $meta = [];

    public static function clear(): void
    {
        self::$meta = [];
    }

    public static function delete($postId, $key): void
    {
        Arr::forget(self::$meta, "$postId.$key");
    }

    public static function get($postId, $key, $default = null)
    {
        return Arr::get(self::$meta, "$postId.$key", $default);
    }

    public static function update($postId, $key, $value): void
    {
        Arr::set(self::$meta, "$postId.$key", $value);
    }
}
