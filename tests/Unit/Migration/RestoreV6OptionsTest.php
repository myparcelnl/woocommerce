<?php

/** @noinspection StaticClosureCanBeUsedInspection */

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Migration\Repair;

use MyParcelNL\Pdk\App\Installer\Contract\InstallerServiceInterface;
use MyParcelNL\Pdk\App\Installer\Migration\AbstractTimestampedMigration;
use MyParcelNL\Pdk\Facade\Pdk;
use MyParcelNL\Pdk\Settings\Contract\PdkSettingsRepositoryInterface;
use MyParcelNL\WooCommerce\Tests\Mock\MockWcPdkBootstrapper;
use MyParcelNL\WooCommerce\Tests\Mock\MockWpdb;
use MyParcelNL\WooCommerce\Tests\Mock\WordPressOptions;
use RuntimeException;

beforeEach(function () {
    global $wpdb;

    WordPressOptions::reset();
    $wpdb = new MockWpdb();

    $pluginRoot = dirname(__DIR__, 3);
    MockWcPdkBootstrapper::boot('6.7.4', "$pluginRoot/", 'https://example.com/plugin/');
});

afterEach(function () {
    global $wpdb;

    WordPressOptions::reset();
    MockWcPdkBootstrapper::reset();
    unset($wpdb);
});

function getRestoreV6OptionsMigration(): AbstractTimestampedMigration
{
    return require __DIR__ . '/../../../src/Migration/2026_08_04_101714_restore_v6_options.php';
}

function getSettingsRepository(): PdkSettingsRepositoryInterface
{
    return Pdk::get(PdkSettingsRepositoryInterface::class);
}

function getInstallerService(): InstallerServiceInterface
{
    return Pdk::get(InstallerServiceInterface::class);
}

function getWpdb(): MockWpdb
{
    global $wpdb;

    return $wpdb;
}

it('restores missing v6 options and keeps current values in a mixed state', function () {
    WordPressOptions::updateOption('_myparcelcom_carrier', ['current']);
    WordPressOptions::updateOption('_myparcelcom_installed_version', '6.7.3');
    WordPressOptions::updateOption('_myparcelnl_carrier', ['legacy']);
    WordPressOptions::updateOption('_myparcelnl_checkout', ['deliveryOptionsEnabled' => true]);
    WordPressOptions::updateOption('_myparcelnl_installed_version', '6.7.2');
    WordPressOptions::updateOption('_transient_myparcelnl_cache', ['untouched']);
    WordPressOptions::updateOption('unrelated_myparcelnl_checkout', ['untouched']);

    $settingsRepository = getSettingsRepository();

    // Warm the repository cache with the missing value before the repair runs.
    expect($settingsRepository->get('_myparcelcom_checkout'))->toBeNull();

    getRestoreV6OptionsMigration()->up();

    expect(WordPressOptions::$options)->toMatchArray([
        '_myparcelcom_carrier'            => ['current'],
        '_myparcelcom_checkout'           => ['deliveryOptionsEnabled' => true],
        '_myparcelcom_installed_version'  => '6.7.3',
        '_transient_myparcelnl_cache'     => ['untouched'],
        'unrelated_myparcelnl_checkout'   => ['untouched'],
    ])->not->toHaveKeys([
        '_myparcelnl_carrier',
        '_myparcelnl_checkout',
        '_myparcelnl_installed_version',
    ])
        ->and($settingsRepository->get('_myparcelcom_checkout'))
        ->toBe(['deliveryOptionsEnabled' => true])
        ->and(getWpdb()->preparedOptionPattern)
        ->toBe('\_myparcelnl\_%');
});

it('does not repair genuine v5 options', function () {
    WordPressOptions::updateOption('_myparcelnl_carrier', ['legacy']);
    WordPressOptions::updateOption('_myparcelnl_installed_version', '5.4.2');

    getRestoreV6OptionsMigration()->up();

    expect(WordPressOptions::$options)->toMatchArray([
        '_myparcelnl_carrier'           => ['legacy'],
        '_myparcelnl_installed_version' => '5.4.2',
    ])->not->toHaveKey('_myparcelcom_carrier')
        ->and(getWpdb()->preparedOptionPattern)
        ->toBeNull();
});

it('recognises v6 prereleases and later major versions', function (string $version) {
    WordPressOptions::updateOption('_myparcelnl_carrier', ['legacy']);
    WordPressOptions::updateOption('_myparcelnl_installed_version', $version);

    getRestoreV6OptionsMigration()->up();

    expect(WordPressOptions::$options)
        ->toHaveKey('_myparcelcom_carrier', ['legacy'])
        ->not->toHaveKey('_myparcelnl_carrier');
})->with(['6.0.0-beta.1', '7.0.0-alpha.1']);

it('can safely run repeatedly', function () {
    WordPressOptions::updateOption('_myparcelnl_carrier', ['legacy']);
    WordPressOptions::updateOption('_myparcelnl_installed_version', '6.7.3');

    $migration = getRestoreV6OptionsMigration();

    $migration->up();
    $migration->up();

    expect(WordPressOptions::$options)->toMatchArray([
        '_myparcelcom_carrier'           => ['legacy'],
        '_myparcelcom_installed_version' => '6.7.3',
    ])->not->toHaveKeys([
        '_myparcelnl_carrier',
        '_myparcelnl_installed_version',
    ]);
});

it('keeps the repair pending when legacy options cannot be read', function () {
    WordPressOptions::updateOption('_myparcelnl_carrier', ['legacy']);
    WordPressOptions::updateOption('_myparcelnl_installed_version', '6.7.3');
    getWpdb()->last_error = 'Simulated database failure';

    expect(static function (): void {
        getRestoreV6OptionsMigration()->up();
    })->toThrow(RuntimeException::class, 'Could not read legacy options')
        ->and(WordPressOptions::$options)
        ->toHaveKeys([
            '_myparcelnl_carrier',
            '_myparcelnl_installed_version',
        ])
        ->not->toHaveKey('_myparcelcom_carrier');
});

it('runs once through the normal upgrade path', function () {
    WordPressOptions::updateOption('_myparcelnl_carrier', ['legacy']);
    WordPressOptions::updateOption('_myparcelnl_installed_version', '6.7.3');

    getInstallerService()->install();
    getInstallerService()->install();

    expect(WordPressOptions::$options)->toMatchArray([
        '_myparcelcom_carrier'           => ['legacy'],
        '_myparcelcom_installed_version' => '6.7.4',
    ])->not->toHaveKeys([
        '_myparcelnl_carrier',
        '_myparcelnl_installed_version',
    ])
        ->and(WordPressOptions::getOption('_myparcelcom_applied_migrations'))
        ->toContain('2026_08_04_101714_restore_v6_options');
});

it('stores carrier settings on a fresh installation', function () {
    getInstallerService()->install();

    expect(WordPressOptions::$options)
        ->toHaveKey('_myparcelcom_carrier')
        ->and(WordPressOptions::getOption('_myparcelcom_installed_version'))
        ->toBe('6.7.4');
});
