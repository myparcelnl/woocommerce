<?php

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Tests\Mock;

class MockCallableClass
{
    public static function mockStatic(): string
    {
        return 'mocked static';
    }

    public function mock(): string
    {
        return 'mocked';
    }

    public function updateOption(string $name, string $value): void
    {
        update_option($name, $value);
    }
}
