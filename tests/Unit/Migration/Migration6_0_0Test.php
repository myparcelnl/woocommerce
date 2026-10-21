<?php

/** @noinspection StaticClosureCanBeUsedInspection */

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Migration;

use MyParcelNL\Pdk\Base\Config;
use MyParcelNL\Pdk\Facade\Pdk;
use MyParcelNL\Pdk\SdkApi\Service\CoreApi\Shipment\CapabilitiesService;
use MyParcelNL\Pdk\Settings\Contract\PdkSettingsRepositoryInterface;
use MyParcelNL\WooCommerce\Tests\Mock\MockWcPdkBootstrapper;
use MyParcelNL\WooCommerce\Tests\Mock\MockWpdb;
use MyParcelNL\WooCommerce\Tests\Mock\WordPressOptions;

beforeEach(function () {
    global $wpdb;

    WordPressOptions::reset();
    $wpdb = new MockWpdb();

    $pluginRoot = dirname(__DIR__, 3);
    MockWcPdkBootstrapper::boot('6.0.0', "$pluginRoot/", 'https://example.com/plugin/');
});

afterEach(function () {
    global $wpdb;

    WordPressOptions::reset();
    MockWcPdkBootstrapper::reset();
    unset($wpdb);
});

it('shows the renamed settings to later migrations in the same request', function () {
    WordPressOptions::updateOption('_myparcelnl_account', ['apiKey' => 'test-api-key', 'apiKeyValid' => true]);

    /** @var PdkSettingsRepositoryInterface $settingsRepository */
    $settingsRepository = Pdk::get(PdkSettingsRepositoryInterface::class);

    // Warm the repository cache before the rename, like the constructor of Migration6_5_1 does.
    expect($settingsRepository->all()->account->apiKey)->toBeNull();

    Pdk::get(Migration6_0_0::class)->up();

    expect(WordPressOptions::$options)->toHaveKey('_myparcelcom_account')
        ->and($settingsRepository->all()->account->apiKey)->toBe('test-api-key');
});

it('refreshes the API services that were built before the rename', function () {
    WordPressOptions::updateOption('_myparcelnl_account', [
        'apiKey'      => 'test-api-key',
        'apiKeyValid' => true,
        'environment' => Config::ENVIRONMENT_ACCEPTANCE,
    ]);

    // The installer builds Migration6_5_1, and with it this service, before any migration runs.
    $capabilitiesService = Pdk::get(CapabilitiesService::class);

    Pdk::get(Migration6_0_0::class)->up();

    $config = (function () {
        return $this->shipmentApi->getConfig();
    })->call($capabilitiesService);

    expect($config->getAccessToken())->toBe(base64_encode('test-api-key'))
        ->and($config->getHost())->toBe(Config::API_URL_ACCEPTANCE);
});