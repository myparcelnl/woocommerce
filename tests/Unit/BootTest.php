<?php

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Tests\Unit;

use MyParcelNL\Pdk\App\Installer\Contract\MigrationServiceInterface;
use MyParcelNL\Pdk\Base\PdkBootstrapper;
use MyParcelNL\Pdk\Facade\Pdk;
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
 * The identities the installer records in applied_migrations: the class name of each registered
 * migration and the file name of each timestamped one.
 */
function allUpgradeMigrationIds(): array
{
    $timestamped = array_map(static function (string $path): string {
        return pathinfo($path, PATHINFO_FILENAME);
    }, glob(Pdk::get('migrationDirectory') . '/[0-9]*.php') ?: []);

    return array_merge(Pdk::get(MigrationServiceInterface::class)->getUpgradeMigrations(), $timestamped);
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

    // A normal request already has the current version stored and every migration recorded as
    // applied, so the installer returns early. The upgrade runs on 'init' now, so without this,
    // executing 'init' would run a full install and the carrier refresh migrations would call the API.
    WordPressOptions::updateOption(Pdk::get('settingKeyInstalledVersion'), Pdk::getAppInfo()->version);
    WordPressOptions::updateOption(Pdk::get('settingKeyAppliedMigrations'), allUpgradeMigrationIds());

    MockWpActions::execute('init');

    expect(MockWpActions::get('init'))->toBe([]);
    assertMatchesJsonSnapshot(json_encode(MockWpActions::toArray()));
});

it('throws error if the php version is too low', function () {
    MockWcPdkBootstrapper::addConfig(['isPhpVersionSupported' => false]);
    bootPlugin();

    MockWpActions::execute('activate_woocommerce-myparcel');
})->throws(DieException::class, 'PHP');


it('runs the upgrade after woocommerce has registered its post types and order statuses', function () {
    // WooCommerce registers its post types and taxonomies on 'init' priority 5 and its order
    // statuses on priority 9. The migrations query orders and products, so the upgrade has to run
    // later than both or those queries find nothing.
    WordPressOptions::updateOption('active_plugins', ['woocommerce/woocommerce.php']);
    bootPlugin();

    $callbacks = array_map(
        static function (array $action) {
            return is_array($action['function']) ? $action['function'][1] : $action['function'];
        },
        MockWpActions::get('init')
    );

    $upgrade = array_values(array_filter(MockWpActions::get('init'), static function (array $action): bool {
        return is_array($action['function']) && 'upgrade' === $action['function'][1];
    }));

    expect($upgrade)->toHaveCount(1)
        ->and($upgrade[0]['priority'])->toBeGreaterThan(9)
        // And still before our own hooks, which read settings a migration may have just rewritten.
        ->and(array_search('upgrade', $callbacks, true))
        ->toBeLessThan(array_search('initialize', $callbacks, true));
});
