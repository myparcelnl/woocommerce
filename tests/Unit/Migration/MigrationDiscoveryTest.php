<?php

/** @noinspection StaticClosureCanBeUsedInspection */

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Migration;

use MyParcelNL\Pdk\Facade\Pdk;
use MyParcelNL\WooCommerce\Tests\Uses\UsesMockWcPdkInstance;
use function MyParcelNL\Pdk\Tests\usesShared;

usesShared(new UsesMockWcPdkInstance());

/**
 * The installer only runs timestamped migrations it finds on disk, and it looks in
 * migrationDirectory. The PDK default resolves against the PDK package instead of the plugin, so
 * without the plugin's own value none of its migrations are ever picked up.
 */
it('derives the migration directory from the plugin path', function () {
    // The test bootstrap mocks appInfo, so this asserts the relationship rather than a fixed
    // path: the directory has to follow the plugin, wherever it is installed.
    $expected = rtrim(Pdk::getAppInfo()->path, '/') . '/src/Migration';

    expect(Pdk::get('migrationDirectory'))->toBe($expected);
});

it('keeps the timestamped migrations where the installer looks for them', function () {
    // Same filename pattern the installer filters on, checked against the real directory: a file
    // that fails this is a file the installer silently skips.
    $found = array_map('basename', array_filter(
        glob(__DIR__ . '/../../../src/Migration/*.php') ?: [],
        function (string $path): bool {
            return (bool) preg_match('/^\d{4}_\d{2}_\d{2}_\d{6}_/', pathinfo($path, PATHINFO_FILENAME));
        }
    ));

    expect($found)->toContain(
        '2026_07_29_092726_refresh_carrier_capabilities.php',
        '2026_09_03_130123_restore_account_data.php'
    );
});
