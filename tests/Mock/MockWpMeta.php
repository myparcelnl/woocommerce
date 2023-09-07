<?php

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Tests\Mock;

use MyParcelNL\Pdk\Base\Support\Arr;
use WC_Meta_Data;

final class MockWpMeta implements StaticMockInterface
{
    public static $meta = [];

    /**
     * @param  int $postId
     *
     * @return array
     */
    public static function all(int $postId): array
    {
        return self::$meta[$postId] ?? [];
    }

    /**
     * @param  int    $postId
     * @param  string $key
     *
     * @return void
     */
    public static function delete(int $postId, string $key): void
    {
        Arr::forget(self::$meta, "$postId.$key");
    }

    /**
     * @param  string|int  $postId
     * @param  string|null $key
     * @param  mixed       $default
     *
     * @return array|\ArrayAccess|mixed|string
     */
    public static function get($postId, string $key = null, $default = null)
    {
        $data = Arr::get(self::$meta, implode('.', array_filter([$postId, $key])), $default);

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

    public static function reset(): void
    {
        self::$meta = [];
    }

    /**
     * @param  int $postId
     *
     * @return WC_Meta_Data[]
     */
    public static function toWcMetaData(int $postId): array
    {
        $all = self::all($postId);

        return array_map(static function ($key, $value) {
            return new WC_Meta_Data([
                'current_data' => [
                    'id'    => 0,
                    'key'   => $key,
                    'value' => $value,
                ],
                'data'         => [
                    'id'    => 0,
                    'key'   => $key,
                    'value' => $value,
                ],
            ]);
        }, array_keys($all), array_values($all));
    }

    /**
     * @param  int    $postId
     * @param  string $key
     * @param  mixed  $value
     *
     * @return void
     */
    public static function update(int $postId, string $key, $value): void
    {
        Arr::set(self::$meta, "$postId.$key", $value);
    }
}
