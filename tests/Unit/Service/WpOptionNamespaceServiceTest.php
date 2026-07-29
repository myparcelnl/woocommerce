<?php

/** @noinspection PhpUnhandledExceptionInspection */

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Tests\Unit\Service;

use MyParcelNL\Pdk\App\Installer\Contract\InstallerServiceInterface;
use MyParcelNL\Pdk\Facade\Pdk;
use MyParcelNL\Pdk\Storage\MemoryCacheStorage;
use MyParcelNL\WooCommerce\Migration\Migration6_0_0;
use MyParcelNL\WooCommerce\Service\WpOptionNamespaceService;
use MyParcelNL\WooCommerce\Tests\Mock\MockWpCache;
use MyParcelNL\WooCommerce\Tests\Mock\MockWcPdkBootstrapper;
use MyParcelNL\WooCommerce\Tests\Mock\MockWpOptionsDatabase;
use MyParcelNL\WooCommerce\Tests\Mock\WordPressOptions;
use RuntimeException;

beforeEach(function () {
    global $wpdb;

    WordPressOptions::reset();
    $wpdb = new MockWpOptionsDatabase();
});

afterEach(function () {
    global $wpdb;

    WordPressOptions::reset();
    unset($wpdb);
});

function createNamespaceService(?MemoryCacheStorage $storage = null): WpOptionNamespaceService
{
    return new WpOptionNamespaceService($storage ?? new MemoryCacheStorage());
}

function getNamespaceDatabase(): MockWpOptionsDatabase
{
    global $wpdb;

    return $wpdb;
}

function getInstallerService(): InstallerServiceInterface
{
    $pluginRoot = dirname(__DIR__, 3);

    MockWcPdkBootstrapper::boot('6.7.3', "$pluginRoot/", 'https://example.com/plugin/');

    return Pdk::get(InstallerServiceInterface::class);
}

it('restores a deactivated v6 installation and keeps current values in a mixed state', function () {
    $database = getNamespaceDatabase();

    $database->seedOption('_myparcelcom_carrier', ['current']);
    $database->seedOption('_myparcelcom_installed_version', '6.7.3');
    $database->seedOption('_myparcelnl_carrier', ['legacy']);
    $database->seedOption('_myparcelnl_checkout', ['deliveryOptionsEnabled' => true], 'no');
    $database->seedOption('_myparcelnl_applied_migrations', ['migration']);
    $database->seedOption('_myparcelnl_installed_version', '6.7.3');
    $database->seedOption('unrelated_myparcelnl_checkout', ['untouched']);
    $database->seedOption('_myparcelnlx_checkout', ['untouched']);

    expect(createNamespaceService()->restoreDeactivatedV6Installation())->toBeTrue()
        ->and($database->getOptions())->toMatchArray([
            '_myparcelcom_carrier'            => ['current'],
            '_myparcelcom_checkout'           => ['deliveryOptionsEnabled' => true],
            '_myparcelcom_applied_migrations' => ['migration'],
            '_myparcelcom_installed_version'  => '6.7.3',
            'unrelated_myparcelnl_checkout'   => ['untouched'],
            '_myparcelnlx_checkout'           => ['untouched'],
        ])
        ->and(array_values(array_filter(
            $database->getOperations(),
            static function (array $operation): bool {
                return in_array($operation['operation'], ['delete', 'update'], true);
            }
        )))->toBe([
            ['operation' => 'delete', 'option' => '_myparcelnl_carrier'],
            ['operation' => 'update', 'option' => '_myparcelnl_checkout'],
            ['operation' => 'update', 'option' => '_myparcelnl_applied_migrations'],
            ['operation' => 'delete', 'option' => '_myparcelnl_installed_version'],
        ])
        ->and($database->getAutoload('_myparcelcom_checkout'))->toBe('no')
        ->and($database->getLikePatterns())->toContain('\_myparcelnl\_%');
});

it('runs the v6 repair before the installer returns for an equal current version', function () {
    $database = getNamespaceDatabase();

    $database->seedOption('_myparcelcom_carrier', ['current']);
    $database->seedOption('_myparcelcom_installed_version', '6.7.3');
    $database->seedOption('_myparcelnl_carrier', ['legacy']);

    getInstallerService()->install();

    expect($database->getOptions())->toMatchArray([
        '_myparcelcom_carrier'           => ['current'],
        '_myparcelcom_installed_version' => '6.7.3',
    ])->not->toHaveKey('_myparcelnl_carrier');
});

it('stores carrier defaults on a fresh installation without an api key', function () {
    getInstallerService()->install();

    expect(WordPressOptions::$options)
        ->toHaveKey('_myparcelcom_carrier')
        ->and(WordPressOptions::$options['_myparcelcom_carrier'])
        ->toBeArray();
});

it('ignores a malformed current version instead of passing it through the installer return type', function () {
    $database = getNamespaceDatabase();

    $database->seedOption('_myparcelcom_installed_version', ['invalid']);

    getInstallerService()->install();

    expect(WordPressOptions::$options['_myparcelcom_installed_version'])->toBe('6.7.3');
});

it('does not let the installer mark a failed v6 repair as successful', function () {
    $database = getNamespaceDatabase();

    $database->seedOption('_myparcelcom_installed_version', '6.7.3');
    $database->seedOption('_myparcelnl_carrier', ['settings']);
    $database->failOn('update', '_myparcelnl_carrier');

    expect(static function (): void {
        getInstallerService()->install();
    })->toThrow(RuntimeException::class, 'Simulated database failure')
        ->and($database->getOptions())->toMatchArray([
            '_myparcelcom_installed_version' => '6.7.3',
            '_myparcelnl_carrier'            => ['settings'],
        ])->not->toHaveKey('_myparcelcom_carrier');
});

it('restores settings when a failed down migration left the v6 version in the current namespace', function () {
    $database = getNamespaceDatabase();

    $database->seedOption('_myparcelcom_installed_version', '6.7.3');
    $database->seedOption('_myparcelnl_carrier', ['POSTNL' => ['enabled' => true]]);

    expect(createNamespaceService()->restoreDeactivatedV6Installation())->toBeTrue()
        ->and($database->getOptions())->toMatchArray([
            '_myparcelcom_carrier'           => ['POSTNL' => ['enabled' => true]],
            '_myparcelcom_installed_version' => '6.7.3',
        ]);
});

it('uses a valid legacy v6 version when the current version value is invalid', function () {
    $database = getNamespaceDatabase();

    $database->seedOption('_myparcelcom_installed_version', ['invalid']);
    $database->seedOption('_myparcelnl_carrier', ['settings']);
    $database->seedOption('_myparcelnl_installed_version', '6.7.3');

    expect(createNamespaceService()->restoreDeactivatedV6Installation())->toBeTrue()
        ->and($database->getOptions())->toMatchArray([
            '_myparcelcom_carrier'           => ['settings'],
            '_myparcelcom_installed_version' => '6.7.3',
        ]);
});

it('recognises prerelease versions by major version', function (string $version) {
    $database = getNamespaceDatabase();

    $database->seedOption('_myparcelnl_carrier', ['settings']);
    $database->seedOption('_myparcelnl_installed_version', $version);

    expect(createNamespaceService()->restoreDeactivatedV6Installation())->toBeTrue()
        ->and($database->getOptions())
        ->toHaveKey('_myparcelcom_carrier');
})->with(['6.0.0-beta.1', '7.0.0-alpha.2']);

it('leaves genuine and interrupted v5 upgrades to Migration6_0_0', function ($currentVersion, $legacyVersion) {
    $database = getNamespaceDatabase();

    if (null !== $currentVersion) {
        $database->seedOption('_myparcelcom_installed_version', $currentVersion);
    }

    $database->seedOption('_myparcelnl_carrier', ['v5']);
    $database->seedOption('_myparcelnl_installed_version', $legacyVersion);

    expect(createNamespaceService()->restoreDeactivatedV6Installation())->toBeFalse()
        ->and($database->getOptions())->toHaveKey('_myparcelnl_carrier')
        ->not->toHaveKey('_myparcelcom_carrier');
})->with([
    'genuine v5'     => [null, '5.4.2'],
    'interrupted v5' => ['5.4.2', '6.7.3'],
    'invalid value'  => [null, ['6.7.3']],
]);

it('does not treat genuine v5 BE options as a deactivated v6 installation', function () {
    $database = getNamespaceDatabase();

    $database->seedOption('_myparcelbe_carrier', ['v5 BE']);
    $database->seedOption('_myparcelbe_installed_version', '5.4.2');

    expect(createNamespaceService()->restoreDeactivatedV6Installation())->toBeFalse()
        ->and($database->getOptions())->toHaveKey('_myparcelbe_carrier')
        ->not->toHaveKey('_myparcelcom_carrier');
});

it('can safely run the v6 repair repeatedly', function () {
    $database = getNamespaceDatabase();

    $database->seedOption('_myparcelnl_carrier', ['settings']);
    $database->seedOption('_myparcelnl_installed_version', '6.7.3');

    $service = createNamespaceService();

    expect($service->restoreDeactivatedV6Installation())->toBeTrue()
        ->and($service->restoreDeactivatedV6Installation())->toBeFalse()
        ->and($database->getOptions())->toMatchArray([
            '_myparcelcom_carrier'           => ['settings'],
            '_myparcelcom_installed_version' => '6.7.3',
        ]);
});

it('rolls back and invalidates caches when a database write fails', function () {
    $database = getNamespaceDatabase();
    $storage  = new MemoryCacheStorage();

    $database->seedOption('_myparcelnl_carrier', ['settings']);
    $database->seedOption('_myparcelnl_checkout', ['checkout']);
    $database->seedOption('_myparcelnl_installed_version', '6.7.3');
    $database->failOn('update', '_myparcelnl_checkout');

    $storage->set('settings__myparcelnl_carrier', ['cached legacy']);
    $storage->set('settings__myparcelcom_carrier', ['cached current']);
    $storage->set('settings_all', ['cached all']);

    expect(static function () use ($storage): void {
        createNamespaceService($storage)->restoreDeactivatedV6Installation();
    })->toThrow(RuntimeException::class, 'Simulated database failure')
        ->and($database->getOptions())->toMatchArray([
            '_myparcelnl_carrier'           => ['settings'],
            '_myparcelnl_checkout'          => ['checkout'],
            '_myparcelnl_installed_version' => '6.7.3',
        ])
        ->and($storage->has('settings__myparcelnl_carrier'))->toBeFalse()
        ->and($storage->has('settings__myparcelcom_carrier'))->toBeFalse()
        ->and($storage->has('settings__myparcelnl_checkout'))->toBeFalse()
        ->and($storage->has('settings__myparcelcom_checkout'))->toBeFalse()
        ->and($storage->has('settings_all'))->toBeFalse()
        ->and(MockWpCache::$deleted)->toContain(
            ['key' => '_myparcelnl_carrier', 'group' => 'options'],
            ['key' => '_myparcelcom_carrier', 'group' => 'options'],
            ['key' => 'alloptions', 'group' => 'options'],
            ['key' => 'notoptions', 'group' => 'options'],
        );

    expect(createNamespaceService($storage)->restoreDeactivatedV6Installation())->toBeTrue()
        ->and($database->getOptions())->toMatchArray([
            '_myparcelcom_carrier'           => ['settings'],
            '_myparcelcom_checkout'          => ['checkout'],
            '_myparcelcom_installed_version' => '6.7.3',
        ]);
});

it('does not leave changes behind when committing the transaction fails', function () {
    $database = getNamespaceDatabase();

    $database->seedOption('_myparcelnl_carrier', ['settings']);
    $database->seedOption('_myparcelnl_installed_version', '6.7.3');
    $database->failOn('query', 'COMMIT');

    expect(static function (): void {
        createNamespaceService()->restoreDeactivatedV6Installation();
    })->toThrow(RuntimeException::class, 'commit option namespace transaction')
        ->and($database->getOptions())->toMatchArray([
            '_myparcelnl_carrier'           => ['settings'],
            '_myparcelnl_installed_version' => '6.7.3',
        ]);
});

it('lets the active current value replace a stale legacy value during down migration', function () {
    $database = getNamespaceDatabase();

    $database->seedOption('_myparcelcom_carrier', ['current']);
    $database->seedOption('_myparcelcom_installed_version', '6.7.3');
    $database->seedOption('_myparcelnl_carrier', ['stale']);
    $database->seedOption('_myparcelnl_installed_version', '5.4.2');

    (new Migration6_0_0(createNamespaceService()))->down();

    expect($database->getOptions())->toMatchArray([
        '_myparcelnl_carrier'           => ['current'],
        '_myparcelnl_installed_version' => '6.7.3',
    ])->not->toHaveKeys([
        '_myparcelcom_carrier',
        '_myparcelcom_installed_version',
    ]);
});

it('keeps current values while upgrading genuine v5 NL and BE options', function () {
    $database = getNamespaceDatabase();

    $database->seedOption('_myparcelcom_carrier', ['current']);
    $database->seedOption('_myparcelnl_carrier', ['nl']);
    $database->seedOption('_myparcelnl_checkout', ['nl checkout']);
    $database->seedOption('_myparcelnl_installed_version', '5.4.2');
    $database->seedOption('_myparcelbe_carrier', ['be']);
    $database->seedOption('_myparcelbe_order', ['be order']);

    (new Migration6_0_0(createNamespaceService()))->up();

    expect($database->getOptions())->toMatchArray([
        '_myparcelcom_carrier'           => ['current'],
        '_myparcelcom_checkout'          => ['nl checkout'],
        '_myparcelcom_installed_version' => '5.4.2',
        '_myparcelcom_order'             => ['be order'],
    ])->not->toHaveKeys([
        '_myparcelnl_carrier',
        '_myparcelnl_checkout',
        '_myparcelnl_installed_version',
        '_myparcelbe_carrier',
        '_myparcelbe_order',
    ]);
});
