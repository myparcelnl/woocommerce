<?php

/** @noinspection StaticClosureCanBeUsedInspection,PhpUnhandledExceptionInspection */

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Pdk\Service;

use MyParcelNL\Pdk\Facade\Pdk;
use MyParcelNL\WooCommerce\Tests\Mock\MockWpScreen;
use MyParcelNL\WooCommerce\Tests\Mock\MockWpUser;
use MyParcelNL\WooCommerce\Tests\Uses\UsesMockWcPdkInstance;

use function MyParcelNL\Pdk\Tests\mockPdkProperties;
use function MyParcelNL\Pdk\Tests\usesShared;

usesShared(new UsesMockWcPdkInstance());

it('detects the plugins overview', function (?string $screenId, array $roles, bool $expected) {
    MockWpScreen::set($screenId);

    foreach ($roles as $role) {
        MockWpUser::addRole($role);
    }

    /** @var \MyParcelNL\WooCommerce\Pdk\Service\WcViewService $service */
    $service = Pdk::get(WcViewService::class);

    expect($service->isPluginsOverviewPage())
        ->toBe($expected)
        ->and($service->isAnyPdkPage())
        ->toBe($expected);
})->with([
    'plugins overview'               => ['plugins', ['administrator'], true],
    'network plugins overview'       => ['plugins-network', ['administrator'], true],
    'user cannot manage plugins'     => ['plugins', ['shop_manager'], false],
    'other admin page'               => ['dashboard', ['administrator'], false],
    'no screen, e.g. the storefront' => [null, ['administrator'], false],
]);

it('does not render notifications in the plugins overview', function () {
    MockWpScreen::set('plugins');
    MockWpUser::addRole('administrator');

    /** @var \MyParcelNL\WooCommerce\Pdk\Service\WcViewService $service */
    $service = Pdk::get(WcViewService::class);

    expect($service->hasNotifications())->toBeFalse();
});

it('selects the deactivate link of this plugin', function (string $pluginBasename) {
    $reset    = mockPdkProperties(['pluginBasename' => $pluginBasename]);
    $selector = Pdk::get('deactivationFeedbackLinkSelector');
    $reset();

    expect($selector)->toBe('tr[data-plugin^="woocommerce-myparcel/"] .deactivate a');
})->with([
    'directory, as WordPress returns it for the plugin path' => ['woocommerce-myparcel'],
    'main plugin file'                                       => ['woocommerce-myparcel/woocommerce-myparcel.php'],
]);
