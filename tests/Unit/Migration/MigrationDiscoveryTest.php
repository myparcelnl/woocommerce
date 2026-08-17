<?php

/** @noinspection StaticClosureCanBeUsedInspection */

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Migration;

use MyParcelNL\Pdk\Facade\Pdk;
use MyParcelNL\WooCommerce\Tests\Uses\UsesMockWcPdkInstance;
use function MyParcelNL\Pdk\Tests\usesShared;

usesShared(new UsesMockWcPdkInstance());

it('scans the plugin for timestamped migrations', function () {
    // The pdk defaults migrationDirectory to its own package root, which holds no plugin
    // migration. Without an override the installer finds no timestamped migration file at all.
    expect(realpath((string) Pdk::get('migrationDirectory')))
        ->toBe(realpath(__DIR__ . '/../../../src/Migration'));
});
