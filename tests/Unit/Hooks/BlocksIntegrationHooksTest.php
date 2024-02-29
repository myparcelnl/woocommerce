<?php
/** @noinspection StaticClosureCanBeUsedInspection */

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Hooks;

use MyParcelNL\Pdk\Facade\Pdk;
use MyParcelNL\WooCommerce\Tests\Uses\UsesMockWcPdkInstance;
use function MyParcelNL\Pdk\Tests\usesShared;

usesShared(new UsesMockWcPdkInstance());

it('loads integrated blocks', function () {
    $hook = Pdk::get(BlocksIntegrationHooks::class);

    $hook->apply();

    // Apply second time for coverage
    $hook->apply();

    expect($hook)->toBeInstanceOf(BlocksIntegrationHooks::class);
});
