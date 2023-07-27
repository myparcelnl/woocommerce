<?php

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Tests\Mock;

use MyParcelNL\Pdk\Base\Support\Arr;

final class MockWpMeta
{
    public static $meta = [];

    /**
     * @param $postId
     *
     * @return array
     */
    public static function all($postId): array
    {
        return Arr::get(self::$meta, $postId, []);
    }

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
        $data = Arr::get(self::$meta, "$postId.$key", $default);

        if (is_string($data)) {
            $decoded = json_decode($data, true);

            if (is_array($decoded) && JSON_ERROR_NONE === json_last_error()) {
                $data = $decoded;
            } else {
                $unserialized = @unserialize($data);

                if (false !== $unserialized) {
                    $data = $unserialized;
                }
            }
        }

        return $data;
    }

    public static function update($postId, $key, $value): void
    {
        Arr::set(self::$meta, "$postId.$key", $value);
    }
}
