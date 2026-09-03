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

const LEGACY_SHIPMENTS_KEY  = '_myparcelnl_order_shipments';
const CURRENT_SHIPMENTS_KEY = '_myparcelcom_order_shipments';

/**
 * Loads the migration the same way the installer does.
 */
function loadLegacyOrderMetaMigration(): TimestampedMigrationInterface
{
    return require __DIR__ . '/../../../src/Migration/2026_09_03_101500_migrate_legacy_order_meta.php';
}

/**
 * A shipment as stored before 6.0.0: the carrier is an object holding the legacy identifier.
 */
function legacyShipment(): array
{
    return [
        'id'                 => 224628797,
        'barcode'            => '3SMYPA402029012',
        'carrier'            => ['externalIdentifier' => 'postnl:1'],
        'linkConsumerPortal' => 'https://myparcel.me/track-trace/3SMYPA402029012/2132JE/NL',
        'deliveryOptions'    => ['carrier' => ['externalIdentifier' => 'postnl:1']],
    ];
}

function makeOrderWithMeta(array $meta): WC_Order
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
    $migration = loadLegacyOrderMetaMigration();

    $migration->setIdentity('2026_09_03_101500_migrate_legacy_order_meta');

    expect($migration)->toBeInstanceOf(TimestampedMigrationInterface::class)
        ->and($migration->getId())->toBe('2026_09_03_101500_migrate_legacy_order_meta');
});

it('moves legacy shipments to the current key and converts the carrier', function () {
    $order = makeOrderWithMeta([LEGACY_SHIPMENTS_KEY => [legacyShipment()]]);

    loadLegacyOrderMetaMigration()->up();

    $migrated = wc_get_order($order->get_id())->get_meta(CURRENT_SHIPMENTS_KEY);

    expect($migrated)->toBeArray()->toHaveCount(1)
        ->and($migrated[0]['carrier'])->toBe('POSTNL')
        ->and($migrated[0]['deliveryOptions']['carrier'])->toBe('POSTNL')
        ->and($migrated[0]['barcode'])->toBe('3SMYPA402029012');
});

it('leaves an order alone when the current key already holds shipments', function () {
    $existing = [['id' => 1, 'barcode' => 'EXISTING', 'carrier' => 'POSTNL']];
    $order    = makeOrderWithMeta([
        LEGACY_SHIPMENTS_KEY  => [legacyShipment()],
        CURRENT_SHIPMENTS_KEY => $existing,
    ]);

    loadLegacyOrderMetaMigration()->up();

    expect(wc_get_order($order->get_id())->get_meta(CURRENT_SHIPMENTS_KEY))->toBe($existing);
});

it('does not resurrect shipments that were deliberately removed', function () {
    // An empty array is a decision, not an absence: the shipments were deleted.
    $order = makeOrderWithMeta([
        LEGACY_SHIPMENTS_KEY  => [legacyShipment()],
        CURRENT_SHIPMENTS_KEY => [],
    ]);

    loadLegacyOrderMetaMigration()->up();

    expect(wc_get_order($order->get_id())->get_meta(CURRENT_SHIPMENTS_KEY))->toBe([]);
});

it('leaves the legacy meta in place so a repeated run is harmless', function () {
    $order = makeOrderWithMeta([LEGACY_SHIPMENTS_KEY => [legacyShipment()]]);

    $migration = loadLegacyOrderMetaMigration();
    $migration->up();
    $migration->up();

    $reloaded = wc_get_order($order->get_id());

    expect($reloaded->get_meta(LEGACY_SHIPMENTS_KEY))->toBeArray()->toHaveCount(1)
        ->and($reloaded->get_meta(CURRENT_SHIPMENTS_KEY))->toHaveCount(1);
});
