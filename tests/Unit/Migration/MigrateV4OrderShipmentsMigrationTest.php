<?php

/** @noinspection StaticClosureCanBeUsedInspection */

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Migration;

use MyParcelNL\Pdk\App\Installer\Contract\TimestampedMigrationInterface;
use MyParcelNL\WooCommerce\Tests\Uses\UsesMockWcPdkInstance;
use WC_Order;
use function MyParcelNL\Pdk\Tests\usesShared;
use function MyParcelNL\WooCommerce\Tests\wpFactory;

usesShared(new UsesMockWcPdkInstance());

const V4_SHIPMENTS_KEY   = '_myparcel_shipments';
const PDK_SHIPMENTS_KEY  = '_myparcelcom_order_shipments';

function loadV4ShipmentsMigration(): TimestampedMigrationInterface
{
    return require __DIR__ . '/../../../src/Migration/2026_09_03_101600_migrate_v4_order_shipments.php';
}

/**
 * Shipments as a 4.x plugin stored them: entries wrapping a snake_case API shipment.
 */
function v4Shipments(): array
{
    return [
        [
            'shipment' => [
                'id'                   => 224628797,
                'barcode'              => '3SMYPA402029012',
                'carrier_id'           => 1,
                'reference_identifier' => '59',
                'external_identifier'  => null,
                'customs_declaration'  => null,
            ],
        ],
    ];
}

function makeV4Order(array $meta): WC_Order
{
    $factory = wpFactory(WC_Order::class);
    $order   = $factory->make();

    foreach ($meta as $key => $value) {
        $order->update_meta_data($key, $value);
    }

    $order->save();
    $factory->store();

    return $order;
}

it('is a timestamped migration the installer can discover', function () {
    $migration = loadV4ShipmentsMigration();

    $migration->setIdentity('2026_09_03_101600_migrate_v4_order_shipments');

    expect($migration)->toBeInstanceOf(TimestampedMigrationInterface::class)
        ->and($migration->getId())->toBe('2026_09_03_101600_migrate_v4_order_shipments');
});

it('converts 4.x shipments into the current key', function () {
    $order = makeV4Order([V4_SHIPMENTS_KEY => v4Shipments()]);

    loadV4ShipmentsMigration()->up();

    $shipments = wc_get_order($order->get_id())->get_meta(PDK_SHIPMENTS_KEY);

    expect($shipments)->toBeArray()->toHaveCount(1)
        ->and($shipments[0]['barcode'])->toBe('3SMYPA402029012')
        ->and($shipments[0]['id'])->toBe(224628797);
});

it('does not rewrite an order that already has current shipments', function () {
    $existing = [['id' => 1, 'barcode' => 'EXISTING', 'carrier' => 'POSTNL']];
    $order    = makeV4Order([
        V4_SHIPMENTS_KEY  => v4Shipments(),
        PDK_SHIPMENTS_KEY => $existing,
    ]);

    loadV4ShipmentsMigration()->up();

    expect(wc_get_order($order->get_id())->get_meta(PDK_SHIPMENTS_KEY))->toBe($existing);
});

it('does not rewrite an order whose shipments were deliberately cleared', function () {
    $order = makeV4Order([
        V4_SHIPMENTS_KEY  => v4Shipments(),
        PDK_SHIPMENTS_KEY => [],
    ]);

    loadV4ShipmentsMigration()->up();

    expect(wc_get_order($order->get_id())->get_meta(PDK_SHIPMENTS_KEY))->toBe([]);
});
