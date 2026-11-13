<?php

/** @noinspection PhpUnhandledExceptionInspection,StaticClosureCanBeUsedInspection */

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Migration\V4OrdersChunk;

use MyParcelNL\Pdk\Facade\Pdk;
use MyParcelNL\Pdk\Tests\Bootstrap\TestBootstrapper;
use MyParcelNL\WooCommerce\Migration\Pdk\OrdersMigration;
use MyParcelNL\WooCommerce\Migration\V4OrdersChunkMigrator;
use MyParcelNL\WooCommerce\Tests\Mock\MockWpMeta;
use MyParcelNL\WooCommerce\Tests\Uses\UsesMockWcPdkInstance;
use function MyParcelNL\Pdk\Tests\usesShared;
use function MyParcelNL\WooCommerce\Tests\createWcOrder;

usesShared(new UsesMockWcPdkInstance());

beforeEach(function () {
    TestBootstrapper::hasAccount();
});

/**
 * Reads the raw meta, not the PDK models: the models also accept the format of 5.0.0.
 */
function expectStoredCarriersToBeV2Names(int $orderId, array $expected): void
{
    $postMeta  = MockWpMeta::all($orderId);
    $orderData = $postMeta[Pdk::get('metaKeyOrderData')];

    expect($orderData['deliveryOptions']['carrier'] ?? null)->toBe($expected['carrier']);

    foreach ($postMeta[Pdk::get('metaKeyOrderShipments')] as $index => $shipment) {
        // A shipment that 4.x stored without a carrier has none here. The PDK model then falls back
        // to the default carrier.
        expect($shipment['carrier'] ?? $expected['shipmentCarriers'][$index])
            ->toBe($expected['shipmentCarriers'][$index]);
    }
}

it('migrates a 4.x order and stores its carriers as V2 names', function (array $oldMeta, array $expected) {
    createWcOrder(['id' => 1, 'meta' => $oldMeta]);

    Pdk::get(V4OrdersChunkMigrator::class)->migrateChunk(['orderIds' => [1]]);

    expectStoredCarriersToBeV2Names(1, $expected);
})->with('legacy meta data');

it('stores V2 names on an order that the 5.0.0 chunk migrated first', function (array $oldMeta, array $expected) {
    createWcOrder(['id' => 1, 'meta' => $oldMeta]);

    // A shop that upgrades from 4.x for the first time schedules both chunks for the same order.
    Pdk::get(OrdersMigration::class)->migrateOrder(['orderIds' => [1]]);
    Pdk::get(V4OrdersChunkMigrator::class)->migrateChunk(['orderIds' => [1]]);

    expectStoredCarriersToBeV2Names(1, $expected);
})->with('legacy meta data');

it('does nothing for a chunk without orders', function () {
    Pdk::get(V4OrdersChunkMigrator::class)->migrateChunk([]);

    expect(MockWpMeta::all(1))->toBeEmpty();
});
