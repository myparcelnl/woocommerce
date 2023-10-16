<?php
/** @noinspection StaticClosureCanBeUsedInspection */

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Hooks;

use MyParcelNL\Pdk\Facade\Pdk;
use MyParcelNL\Pdk\Settings\Model\OrderSettings;
use MyParcelNL\WooCommerce\Tests\Uses\UsesMockWcPdkInstance;
use WC_Order_Factory;
use function MyParcelNL\Pdk\Tests\factory;
use function MyParcelNL\Pdk\Tests\usesShared;
use function Spatie\Snapshots\assertMatchesHtmlSnapshot;

usesShared(new UsesMockWcPdkInstance());

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
