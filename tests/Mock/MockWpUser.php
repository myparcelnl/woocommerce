<?php

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Tests\Mock;

use stdClass;

final class MockWpUser implements StaticMockInterface
{
    public static $roles = [];

    public static function addRole(string $role): void
    {
        self::$roles[] = $role;
    }

    public static function get(): stdClass
    {
        $user = new stdClass();

        $user->roles = self::$roles;

        return $user;
    }

    public static function isLoggedIn(): bool
    {
        return (bool) self::$roles;
    }

    public static function reset(): void
    {
        self::$roles = [];
    }
}
