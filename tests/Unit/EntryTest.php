<?php
/** @noinspection StaticClosureCanBeUsedInspection,PhpUnhandledExceptionInspection */

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Tests\Unit;

use MyParcelNL\WooCommerce\Tests\Exception\DieException;
use MyParcelNL\WooCommerce\Tests\Mock\MockWcPdkBootstrapper;
use MyParcelNL\WooCommerce\Tests\Mock\MockWpActions;
use MyParcelNL\WooCommerce\Tests\Uses\UseInstantiatePlugin;
use function MyParcelNL\Pdk\Tests\usesShared;
use function Spatie\Snapshots\assertMatchesJsonSnapshot;

usesShared(new UseInstantiatePlugin());

it('instantiates the plugin', function () {
    assertMatchesJsonSnapshot(json_encode(MockWpActions::toArray()));
});

it('throws error if the php version is too low', function () {
    MockWcPdkBootstrapper::addConfig(['isPhpVersionSupported' => false]);

    MockWpActions::execute('activate_woocommerce-myparcel');
})->throws(DieException::class, 'PHP');

it('throws error if woocommerce is not enabled', function () {
    WC()->version = '';

    MockWpActions::execute('activate_woocommerce-myparcel');
})->throws(DieException::class, 'woocommerce');

it('activates plugin if prerequisites are met', function () {
    MockWcPdkBootstrapper::addConfig([
        'wooCommerceVersion'  => '999.0.0',
        'wooCommerceIsActive' => true,
    ]);

    MockWpActions::execute('activate_woocommerce-myparcel');

    expect(MockWpActions::get('activate_woocommerce-myparcel'))
        ->toBe([])
        ->and(constant('MYPARCELNL_WC_VERSION'))
        ->toBeString();
});

it('runs uninstall on deactivate', function () {
    MockWpActions::execute('deactivate_woocommerce-myparcel');

    expect(MockWpActions::get('deactivate_woocommerce-myparcel'))->toBe([]);
});

it('adds all hooks on plugin init', function () {
    MockWpActions::execute('init');

    expect(MockWpActions::get('init'))->toBe([]);
    assertMatchesJsonSnapshot(json_encode(MockWpActions::toArray()));
});
