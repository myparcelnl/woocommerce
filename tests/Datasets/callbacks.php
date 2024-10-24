<?php
/** @noinspection StaticClosureCanBeUsedInspection */

declare(strict_types=1);

dataset('callbacks', function () {
    return [
        'static class callback'       => ['MyParcelNL\WooCommerce\Tests\Mock\MockCallableClass::mock'],
        'function callback'           => '\MyParcelNL\WooCommerce\Tests\mockFunction',
    ];
});
