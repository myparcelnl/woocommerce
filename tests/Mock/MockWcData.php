<?php

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Tests\Mock;

use MyParcelNL\Pdk\Base\Support\Arr;
use RuntimeException;

final class MockWcData implements StaticMockInterface
{
    /**
     * @var array<int, \WC_Data>
     */
    private static $items = [];

    /**
     * @template T of \MyParcelNL\WooCommerce\Tests\Mock\MockWcClass
     * @param  T $data
     *
     * @return T
     */
    public static function create(MockWcClass $data): MockWcClass
    {
        if (! $data->get_id()) {
            $data->set_id(count(self::$items) + 1);
        }

        return self::save($data);
    }

    /**
     * @param  string|int $id
     *
     * @return \MyParcelNL\WooCommerce\Tests\Mock\MockWcClass
     */
    public static function get($id): MockWcClass
    {
        if (! self::has($id)) {
            throw new RuntimeException("Post $id not found");
        }

        return Arr::get(self::$items, (string) $id);
    }

    /**
     * @param  class-string<\WC_Data> $class
     *
     * @return array
     */
    public static function getByClass(string $class): array
    {
        return array_filter(self::$items, static function (MockWcClass $data) use ($class) {
            return get_class($data) === $class;
        });
    }

    /**
     * @param  string|int $id
     *
     * @return bool
     */
    public static function has($id): bool
    {
        return Arr::has(self::$items, (string) $id);
    }

    public static function reset(): void
    {
        self::$items = [];
    }

    /**
     * @template T of \MyParcelNL\WooCommerce\Tests\Mock\MockWcClass
     * @param  T $data
     *
     * @return T
     */
    public static function save(MockWcClass $data): MockWcClass
    {
        $id = (string) $data->get_id();

        if (! $id) {
            throw new RuntimeException('Cannot save data without id');
        }

        self::$items[$id] = $data;

        return $data;
    }
}
