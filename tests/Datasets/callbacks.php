<?php
/** @noinspection StaticClosureCanBeUsedInspection */

declare(strict_types=1);

use MyParcelNL\WooCommerce\Tests\Mock\MockCallableClass;

dataset('callbacks', function () {
    $mockClass = new MockCallableClass();

    return [
        'instantiated class callback' => [[$mockClass, 'mock']],
        'static class callback'       => ['MyParcelNL\WooCommerce\Tests\Mock\MockCallableClass::mock'],
        'function callback'           => '\MyParcelNL\WooCommerce\Tests\mockFunction',
    ];
});
