<?php

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Tests\Unit;

use MyParcelNL\Pdk\Base\PdkBootstrapper;
use MyParcelNL\WooCommerce\Tests\Exception\DieException;
use MyParcelNL\WooCommerce\Tests\Mock\MockWcPdkBootstrapper;
use MyParcelNL\WooCommerce\Tests\Mock\MockWpActions;
use MyParcelNL\WooCommerce\Tests\Mock\WordPressOptions;
use MyParcelNL\WooCommerce\Tests\Uses\UseInstantiatePlugin;
use MyParcelNLWooCommerce;
use function Spatie\Snapshots\assertMatchesJsonSnapshot;

function bootPlugin() {
    if (class_exists(\MyParcelNLWooCommerce::class)) {
        new \MyParcelNLWooCommerce();
        return;
    }
    require __DIR__ . '/../../woocommerce-myparcel.php';
}

/**
 * Testing whether all hooks are added correctly needs a special setup where the api key is available
 * during boot, so the plugin (class) can only be instantiated after the api key is set.
 * Therefore, this is in a separate test file with the needed order of operations that is not necessary elsewhere.
 */
it('adds all hooks on plugin init', function () {
    // namespaced class used somewhere during initialization
    require(__DIR__ . '/../Mock/WoocommerceUtilities.php');
    // add an api key to the settings in wp_options
    $optionKey = sprintf('_%s_account', PdkBootstrapper::PLUGIN_NAMESPACE);
    WordPressOptions::updateOption($optionKey, array(
        'apiKey' => 'fake-api-key',
        'apiKeyIsValid' => true,
    ));
    // set the woocommerce plugin as active (same as all other tests)
    WordPressOptions::updateOption('active_plugins', ['woocommerce/woocommerce.php']);
    // only now you may start the plugin
    bootPlugin();

    MockWpActions::execute('init');

    expect(MockWpActions::get('init'))->toBe([]);
    assertMatchesJsonSnapshot(json_encode(MockWpActions::toArray()));
});

it('throws error if the php version is too low', function () {
    MockWcPdkBootstrapper::addConfig(['isPhpVersionSupported' => false]);
    bootPlugin();

    MockWpActions::execute('activate_woocommerce-myparcel');
})->throws(DieException::class, 'PHP');

