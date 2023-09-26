<?php
/** @noinspection StaticClosureCanBeUsedInspection */

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Hooks;

use MyParcelNL\Pdk\Facade\Pdk;
use MyParcelNL\Pdk\Settings\Contract\SettingsRepositoryInterface;
use MyParcelNL\Pdk\Settings\Model\OrderSettings;
use MyParcelNL\Pdk\Tests\Bootstrap\MockSettingsRepository;
use MyParcelNL\WooCommerce\Tests\Uses\UsesMockWcPdkInstance;
use WC_Order_Factory;
use function DI\autowire;
use function MyParcelNL\Pdk\Tests\usesShared;
use function Spatie\Snapshots\assertMatchesHtmlSnapshot;

usesShared(
    new UsesMockWcPdkInstance([
        SettingsRepositoryInterface::class => autowire(MockSettingsRepository::class)->constructor([
            OrderSettings::ID => [
                OrderSettings::TRACK_TRACE_IN_ACCOUNT => true,
                OrderSettings::TRACK_TRACE_IN_EMAIL   => true,
            ],
        ]),
    ])
);

it('renders order details in account', function (WC_Order_Factory $factory) {
    /** @var TrackTraceHooks $class */
    $class = Pdk::get(TrackTraceHooks::class);

    ob_start();
    $class->renderTrackTraceInAccountOrderDetails($factory->make());
    assertMatchesHtmlSnapshot(ob_get_clean());
})->with('orders');
