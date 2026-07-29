<?php

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Tests\Mock;

use Exception;

final class MockWpCache implements StaticMockInterface
{
    public static $cache = [];

    public static $deleted = [];

    public static function add(string $key, $data, string $group = '', int $expire = 0): bool
    {
        if (! self::$cache[$group]) {
            self::$cache[$group] = [];
        }

        if (! self::$cache[$group][$key]) {
            self::$cache[$group][$key]['data']   = $data;
            self::$cache[$group][$key]['expire'] = $expire;

            return true;
        }

        return false;
    }

    public static function get($key, $group = '', $force = false, &$found = null)
    {
        if (! self::$cache[$group][$key]['data']) {
            return false;
        }

        return self::$cache[$group][$key]['data'];
    }

    public static function reset(): void
    {
        self::$cache   = [];
        self::$deleted = [];
    }

    public static function set(string $key, $data, string $group = '', int $expire = 0): bool
    {
        try {
            if (! self::$cache[$group]) {
                self::$cache[$group] = [];
            }

            self::$cache[$group][$key]['data']   = $data;
            self::$cache[$group][$key]['expire'] = $expire;

            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    public static function delete(string $key, string $group = ''): bool
    {
        self::$deleted[] = compact('key', 'group');
        unset(self::$cache[$group][$key]);

        return true;
    }
}
