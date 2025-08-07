<?php
/** @noinspection StaticClosureCanBeUsedInspection,PhpUnhandledExceptionInspection */

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Tests\Unit;

use Automattic\WooCommerce\Blocks\Integrations\IntegrationRegistry;
use MyParcelNL\Pdk\Base\PdkBootstrapper;
use MyParcelNL\WooCommerce\Hooks\RanWebhookActions;
use MyParcelNL\WooCommerce\Tests\Exception\DieException;
use MyParcelNL\WooCommerce\Tests\Mock\MockWcPdkBootstrapper;
use MyParcelNL\WooCommerce\Tests\Mock\MockWpActions;
use MyParcelNL\WooCommerce\Tests\Mock\WordPressOptions;
use MyParcelNL\WooCommerce\Tests\Mock\WordPressPlugins;
use MyParcelNL\WooCommerce\Tests\Uses\UseInstantiatePlugin;
use MyParcelNLWooCommerce;
use function _MyParcelNL\DI\get;
use function MyParcelNL\Pdk\Tests\usesShared;
use function Spatie\Snapshots\assertMatchesJsonSnapshot;

usesShared(new UseInstantiatePlugin());

beforeEach(function () {
    WordPressOptions::updateOption('active_plugins', ['woocommerce/woocommerce.php']);
    var_dump(get_option('active_plugins'));
    die('Before each test');
});

it('instantiates the plugin', function () {
    assertMatchesJsonSnapshot(json_encode(MockWpActions::toArray()));
});

it('throws error if the php version is too low', function () {
    MockWcPdkBootstrapper::addConfig(['isPhpVersionSupported' => false]);

    MockWpActions::execute('activate_woocommerce-myparcel');
})->throws(DieException::class, 'PHP');

it('throws error if woocommerce is not enabled', function () {
    //WordPressPlugins::clear();

    MockWpActions::execute('activate_woocommerce-myparcel');
})->throws(DieException::class, 'woocommerce');

it('activates plugin if prerequisites are met', function () {
    MockWpActions::execute('activate_woocommerce-myparcel');

    expect(MockWpActions::get('activate_woocommerce-myparcel'))
        ->toBe([])
        ->and(constant('MYPARCEL_WC_VERSION'))
        ->toBeString();
});

it('runs uninstall on deactivate', function () {
    MockWpActions::execute('deactivate_woocommerce-myparcel');

    expect(MockWpActions::get('deactivate_woocommerce-myparcel'))->toBe([]);
});

it('adds necessary hooks on plugin init', function () {
    MockWpActions::execute('init');

    expect(MockWpActions::get('init'))->toBe([]);
    assertMatchesJsonSnapshot(json_encode(MockWpActions::toArray()));
});

it('adds all hooks on plugin init', function () {
    require(__DIR__ . '/../Mock/WoocommerceUtilities.php');
    // add an api key to the settings in wp_options
    $optionKey = sprintf('_%s_account', PdkBootstrapper::PLUGIN_NAMESPACE);
    update_option($optionKey, array(
        'apiKey' => 'fake-api-key',
        'apiKeyIsValid' => true,
    ));

    MockWpActions::execute('init');

    expect(MockWpActions::get('init'))->toBe([]);
    assertMatchesJsonSnapshot(json_encode(MockWpActions::toArray()));
});

it('registers checkout blocks', function () {
    $plugin              = new MyParcelNLWooCommerce();
    $integrationRegistry = new IntegrationRegistry();

    $plugin->registerCheckoutBlocks($integrationRegistry);
})->expectNotToPerformAssertions();
