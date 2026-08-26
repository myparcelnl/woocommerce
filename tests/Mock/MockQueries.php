<?php

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Tests\Mock;

/**
 * Records the arguments the record-query functions were called with.
 *
 * The mocked wc_get_products() and wc_get_orders() ignore their arguments and return everything, so
 * without this a test cannot tell what was actually asked for. A migration that pages over records
 * cares about exactly that.
 */
final class MockQueries implements StaticMockInterface
{
    /**
     * @var array<string, array[]>
     */
    private static $calls = [];

    public static function record(string $function, array $args): void
    {
        self::$calls[$function][] = $args;
    }

    /**
     * @return array The arguments of the first call to $function, or an empty array if never called.
     */
    public static function first(string $function): array
    {
        return self::$calls[$function][0] ?? [];
    }

    public static function reset(): void
    {
        self::$calls = [];
    }
}
