<?php
/** @noinspection StaticClosureCanBeUsedInspection */

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Tests\Unit;

use MyParcelNL\WooCommerce\Tests\Mock\MockWpActions;
use function Spatie\Snapshots\assertMatchesJsonSnapshot;

it('instantiates the plugin', function () {
    require __DIR__ . '/../../woocommerce-myparcel.php';

    assertMatchesJsonSnapshot(json_encode(MockWpActions::toArray()));
});
