<?php
/** @noinspection StaticClosureCanBeUsedInspection */

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Service;

use InvalidArgumentException;
use MyParcelNL\WooCommerce\Facade\Filter;
use MyParcelNL\WooCommerce\Tests\Uses\UsesMockWcPdkInstance;
use function MyParcelNL\Pdk\Tests\mockPdkProperties;
use function MyParcelNL\Pdk\Tests\usesShared;

usesShared(new UsesMockWcPdkInstance());

function mockFilters(): callable
{
    return mockPdkProperties([
        'filters'        => [
            'filterAlias' => 'mpwc_some_filter',
        ],
        'filterDefaults' => [
            'filterAlias' => 'fallback',
        ],
    ]);
}

it('returns value', function () {
    $reset = mockFilters();

    expect(Filter::apply('filterAlias', 'my_value'))->toBe('my_value');

    $reset();
});

it('returns default value from config if none is passed', function () {
    $reset = mockFilters();

    expect(Filter::apply('filterAlias'))->toBe('fallback');

    $reset();
});

it('returns custom value if add_filter was used', function () {
    $reset = mockFilters();

    add_filter('mpwc_some_filter', function ($value) {
        return "{$value}_custom";
    });

    $value = Filter::apply('filterAlias', 'my_value');

    expect($value)->toBe('my_value_custom');

    $reset();
});

it('throws error when unknown filter is used', function () {
    Filter::apply('unknown_filter');
})->throws(InvalidArgumentException::class);