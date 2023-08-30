<?php
/** @noinspection StaticClosureCanBeUsedInspection */

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Tests\Unit\Hooks;

use MyParcelNL\Pdk\Facade\Pdk;
use MyParcelNL\Pdk\Settings\Contract\SettingsRepositoryInterface;
use MyParcelNL\Pdk\Settings\Model\GeneralSettings;
use MyParcelNL\Pdk\Tests\Bootstrap\MockSettingsRepository;
use MyParcelNL\WooCommerce\Hooks\TrackTraceHooks;
use MyParcelNL\WooCommerce\Tests\Uses\UsesMockWcPdkInstance;
use function DI\autowire;
use function MyParcelNL\Pdk\Tests\usesShared;
use function MyParcelNL\WooCommerce\Tests\createWcOrder;
use function Spatie\Snapshots\assertMatchesHtmlSnapshot;

usesShared(
    new UsesMockWcPdkInstance([
        SettingsRepositoryInterface::class => autowire(MockSettingsRepository::class)->constructor([
            GeneralSettings::ID => [
                GeneralSettings::TRACK_TRACE_IN_ACCOUNT => true,
                GeneralSettings::TRACK_TRACE_IN_EMAIL   => true,
            ],
        ]),
    ])
);

it('renders order details in account', function (array $input) {
    /** @var TrackTraceHooks $class */
    $class = Pdk::get(TrackTraceHooks::class);

    ob_start();
    $class->renderTrackTraceInAccountOrderDetails(createWcOrder($input));
    assertMatchesHtmlSnapshot(ob_get_clean());
})->with('orders');
