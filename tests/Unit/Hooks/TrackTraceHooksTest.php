<?php
/** @noinspection StaticClosureCanBeUsedInspection */

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Hooks;

use MyParcelNL\Pdk\Facade\Pdk;
use MyParcelNL\Pdk\Settings\Model\OrderSettings;
use MyParcelNL\Pdk\Tests\Bootstrap\TestBootstrapper;
use MyParcelNL\WooCommerce\Tests\Uses\UsesMockWcPdkInstance;
use WC_Order;
use WC_Order_Factory;
use WC_Order_Item_Product;
use WC_Product;
use function MyParcelNL\Pdk\Tests\factory;
use function MyParcelNL\Pdk\Tests\usesShared;
use function MyParcelNL\WooCommerce\Tests\wpFactory;
use function Spatie\Snapshots\assertMatchesHtmlSnapshot;

usesShared(new UsesMockWcPdkInstance());

beforeEach(function () {
    TestBootstrapper::hasAccount();
});

it('renders order details in account', function (WC_Order_Factory $factory) {
    factory(OrderSettings::class)
        ->withTrackTraceInAccount(true)
        ->withTrackTraceInEmail(true)
        ->store();

    /** @var TrackTraceHooks $class */
    $class = Pdk::get(TrackTraceHooks::class);

    ob_start();
    $class->renderTrackTraceInAccountOrderDetails($factory->make());
    assertMatchesHtmlSnapshot(ob_get_clean());
})->with('orders');

it('only renders order details in account for orders with a product that needs shipping', function (array $needsShipping, bool $renders) {
    factory(OrderSettings::class)
        ->withTrackTraceInAccount(true)
        ->store();

    $order = wpFactory(WC_Order::class)->make();
    $order->set_items(array_map(function (bool $physical) {
        return wpFactory(WC_Order_Item_Product::class)
            ->withProduct(wpFactory(WC_Product::class)->withNeedsShipping($physical))
            ->make();
    }, $needsShipping));

    /** @var TrackTraceHooks $class */
    $class = Pdk::get(TrackTraceHooks::class);

    ob_start();
    $class->renderTrackTraceInAccountOrderDetails($order);
    expect(ob_get_clean() !== '')->toBe($renders);
})->with([
    'virtual only' => [[false, false], false],
    'physical'     => [[true], true],
    'mixed'        => [[false, true], true],
]);
