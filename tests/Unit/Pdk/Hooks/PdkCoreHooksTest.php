<?php

/** @noinspection StaticClosureCanBeUsedInspection,PhpUnhandledExceptionInspection */

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Pdk\Hooks;

use MyParcelNL\Pdk\Facade\Pdk;
use MyParcelNL\Pdk\Frontend\Contract\ViewServiceInterface;
use MyParcelNL\WooCommerce\Pdk\Service\WcViewService;
use MyParcelNL\WooCommerce\Service\WpScriptService;
use MyParcelNL\WooCommerce\Tests\Mock\MockWpEnqueue;
use MyParcelNL\WooCommerce\Tests\Mock\MockWpScreen;
use MyParcelNL\WooCommerce\Tests\Mock\MockWpUser;
use MyParcelNL\WooCommerce\Tests\Uses\UsesMockWcPdkInstance;

use function DI\get;
use function DI\value;
use function MyParcelNL\Pdk\Tests\usesShared;

usesShared(new UsesMockWcPdkInstance([
    ViewServiceInterface::class => get(WcViewService::class),
    // The real values depend on HPOS, which the tests do not load.
    'orderListPageId'           => value('edit-shop_order'),
    'orderPageId'               => value('shop_order'),
]));

beforeEach(function () {
    MockWpEnqueue::reset();
    MockWpScreen::reset();
    MockWpUser::reset();
});

it('loads the admin script without the WooCommerce admin script in the plugins overview', function () {
    MockWpScreen::set('plugins');
    MockWpUser::addRole('administrator');

    /** @var \MyParcelNL\WooCommerce\Pdk\Hooks\PdkCoreHooks $hooks */
    $hooks = Pdk::get(PdkCoreHooks::class);
    $hooks->registerPdkScripts();

    $deps = MockWpEnqueue::get(WpScriptService::HANDLE_PDK_ADMIN)[0]['deps'];

    expect($deps)
        ->toContain(WpScriptService::HANDLE_VUE)
        ->not->toContain(WpScriptService::HANDLE_WOOCOMMERCE_ADMIN);
});

it('loads the admin script with the WooCommerce admin script on WooCommerce screens', function () {
    MockWpScreen::set('product');

    /** @var \MyParcelNL\WooCommerce\Pdk\Hooks\PdkCoreHooks $hooks */
    $hooks = Pdk::get(PdkCoreHooks::class);
    $hooks->registerPdkScripts();

    $deps = MockWpEnqueue::get(WpScriptService::HANDLE_PDK_ADMIN)[0]['deps'];

    expect($deps)->toContain(WpScriptService::HANDLE_WOOCOMMERCE_ADMIN);
});

it('does not load the admin script outside PDK pages', function () {
    MockWpScreen::set('dashboard');

    /** @var \MyParcelNL\WooCommerce\Pdk\Hooks\PdkCoreHooks $hooks */
    $hooks = Pdk::get(PdkCoreHooks::class);
    $hooks->registerPdkScripts();

    expect(MockWpEnqueue::get(WpScriptService::HANDLE_PDK_ADMIN))->toBe([]);
});
