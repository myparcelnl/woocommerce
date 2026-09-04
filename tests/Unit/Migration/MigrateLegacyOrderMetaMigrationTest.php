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

it('converts a carrier stored as a numeric id', function () {
    // What a 5.x install actually holds once OrdersMigration converted a 4.x shipment.
    $order = makeOrderWithMeta([
        LEGACY_SHIPMENTS_KEY => [
            [
                'id'              => 224628798,
                'barcode'         => '3SMYPA402029013',
                'carrier'         => ['id' => 1],
                'deliveryOptions' => ['carrier' => ['id' => 1]],
            ],
        ],
    ]);

    loadLegacyOrderMetaMigration()->up();

    $migrated = wc_get_order($order->get_id())->get_meta(CURRENT_SHIPMENTS_KEY);

    expect($migrated[0]['carrier'])->toBe('POSTNL')
        ->and($migrated[0]['deliveryOptions']['carrier'])->toBe('POSTNL');
});

it('migrates the belgian namespace too', function () {
    $order = makeOrderWithMeta(['_myparcelbe_order_shipments' => [legacyShipment()]]);

    loadLegacyOrderMetaMigration()->up();

    $migrated = wc_get_order($order->get_id())->get_meta(CURRENT_SHIPMENTS_KEY);

    expect($migrated)->toBeArray()->toHaveCount(1)
        ->and($migrated[0]['carrier'])->toBe('POSTNL');
});

it('normalises the carrier inside order data as well', function () {
    $order = makeOrderWithMeta([
        '_myparcelnl_order_data' => [
            'exported'        => true,
            'deliveryOptions' => ['carrier' => ['externalIdentifier' => 'postnl:1']],
        ],
    ]);

    loadLegacyOrderMetaMigration()->up();

    $migrated = wc_get_order($order->get_id())->get_meta('_myparcelcom_order_data');

    expect($migrated['deliveryOptions']['carrier'])->toBe('POSTNL')
        ->and($migrated['exported'])->toBeTrue();
});

it('keeps the contract id encoded in the legacy identifier', function () {
    $order = makeOrderWithMeta([
        LEGACY_SHIPMENTS_KEY => [
            [
                'id'              => 224628799,
                'barcode'         => '3SMYPA402029014',
                'carrier'         => ['externalIdentifier' => 'postnl:42'],
                'deliveryOptions' => ['carrier' => ['externalIdentifier' => 'postnl:42']],
            ],
        ],
    ]);

    loadLegacyOrderMetaMigration()->up();

    $migrated = wc_get_order($order->get_id())->get_meta(CURRENT_SHIPMENTS_KEY);

    expect($migrated[0]['carrier'])->toBe('POSTNL')
        ->and($migrated[0]['contractId'])->toBe(42)
        ->and($migrated[0]['deliveryOptions']['carrier'])->toBe('POSTNL')
        ->and($migrated[0]['deliveryOptions']['contractId'])->toBe(42);
});

it('keeps the contract id encoded in the carrier-key array shape', function () {
    $order = makeOrderWithMeta([
        LEGACY_SHIPMENTS_KEY => [
            [
                'id'      => 224628800,
                'barcode' => '3SMYPA402029015',
                'carrier' => ['carrier' => 'postnl:42'],
            ],
        ],
    ]);

    loadLegacyOrderMetaMigration()->up();

    $migrated = wc_get_order($order->get_id())->get_meta(CURRENT_SHIPMENTS_KEY);

    expect($migrated[0]['carrier'])->toBe('POSTNL')
        ->and($migrated[0]['contractId'])->toBe(42);
});

it('does not finalise an order whose carrier cannot be normalised', function () {
    $order = makeOrderWithMeta([
        LEGACY_SHIPMENTS_KEY => [
            [
                'id'      => 224628801,
                'barcode' => '3SMYPA402029016',
                'carrier' => ['id' => 999999],
            ],
        ],
    ]);

    loadLegacyOrderMetaMigration()->up();

    $reloaded = wc_get_order($order->get_id());

    expect($reloaded->meta_exists(CURRENT_SHIPMENTS_KEY))->toBeFalse()
        ->and($reloaded->get_meta(LEGACY_SHIPMENTS_KEY))->toBeArray()->toHaveCount(1);
});

it('does not treat false-like invalid carriers as a missing carrier', function ($carrier) {
    $order = makeOrderWithMeta([
        LEGACY_SHIPMENTS_KEY => [
            [
                'id'      => 224628802,
                'barcode' => '3SMYPA402029017',
                'carrier' => $carrier,
            ],
        ],
    ]);

    loadLegacyOrderMetaMigration()->up();

    expect(wc_get_order($order->get_id())->meta_exists(CURRENT_SHIPMENTS_KEY))->toBeFalse();
})->with([0, '0', false]);

it('migrates valid orders that sit behind a full page of unusable ones', function () {
    // Enough broken records to fill more than one page. A run that stopped at the first page it
    // could not migrate would never reach the valid order behind them.
    for ($i = 0; $i < 120; $i++) {
        makeOrderWithMeta([LEGACY_SHIPMENTS_KEY => []]);
    }

    $valid = makeOrderWithMeta([LEGACY_SHIPMENTS_KEY => [legacyShipment()]]);

    loadLegacyOrderMetaMigration()->up();

    expect(wc_get_order($valid->get_id())->get_meta(CURRENT_SHIPMENTS_KEY))->toBeArray()->toHaveCount(1);
});

it('bounds scanning empty records and resumes from the stored cursor', function () {
    for ($i = 0; $i < 260; $i++) {
        makeOrderWithMeta([LEGACY_SHIPMENTS_KEY => []]);
    }

    $valid = makeOrderWithMeta([LEGACY_SHIPMENTS_KEY => [legacyShipment()]]);

    $first = loadLegacyOrderMetaMigration();
    $first->up();

    expect($first->hasFailed())->toBeTrue()
        ->and(wc_get_order($valid->get_id())->meta_exists(CURRENT_SHIPMENTS_KEY))->toBeFalse();

    $second = loadLegacyOrderMetaMigration();
    $second->up();

    expect($second->hasFailed())->toBeFalse()
        ->and(wc_get_order($valid->get_id())->get_meta(CURRENT_SHIPMENTS_KEY))->toBeArray()->toHaveCount(1);
});

it('bounds a single run and resumes on the next one', function () {
    $total = 260;

    for ($i = 0; $i < $total; $i++) {
        makeOrderWithMeta([LEGACY_SHIPMENTS_KEY => [legacyShipment()]]);
    }

    $countMigrated = static function () use ($total): int {
        $migrated = 0;

        foreach (wc_get_orders(['limit' => -1]) as $order) {
            if ($order->meta_exists(CURRENT_SHIPMENTS_KEY)) {
                $migrated++;
            }
        }

        return $migrated;
    };

    $first = loadLegacyOrderMetaMigration();
    $first->up();

    // Stops at the run limit and reports failure, which leaves it unrecorded so it runs again.
    expect($first->hasFailed())->toBeTrue()
        ->and($countMigrated())->toBe(250);

    $second = loadLegacyOrderMetaMigration();
    $second->up();

    expect($countMigrated())->toBe($total)
        ->and($second->hasFailed())->toBeFalse();
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
