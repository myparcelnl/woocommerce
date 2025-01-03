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
        if (! is_scalar($data->get_id()) && ! is_scalar($data->get_instance_id())) {
            $data->set_id(count(self::$items) + 1);
        }

        return self::save($data);
    }

    /**
     * @param  string|int $id
     *
     * @return null|\MyParcelNL\WooCommerce\Tests\Mock\MockWcClass
     */
    public static function get($id): ?MockWcClass
    {
        if (! self::has($id)) {
            return null;
        }

        return Arr::get(self::$items, (string) $id);
    }

    /**
     * @template T of \WC_Data
     * @param  class-string<T> $class
     *
     * @return array<int, T>
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
        $id = $data->get_instance_id() ?? $data->get_id();

        if (! is_scalar($id)) {
            throw new RuntimeException('Cannot save data without id');
        }

        self::$items[(string) $id] = $data;

        return $data;
    }
}
