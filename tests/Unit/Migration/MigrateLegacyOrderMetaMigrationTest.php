<?php

/** @noinspection StaticClosureCanBeUsedInspection */

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Migration;

use MyParcelNL\Pdk\App\Installer\Contract\TimestampedMigrationInterface;
use MyParcelNL\Pdk\Base\Contract\CronServiceInterface;
use MyParcelNL\Pdk\Facade\Pdk;
use MyParcelNL\WooCommerce\Hooks\ScheduledMigrationHooks;
use MyParcelNL\WooCommerce\Tests\Mock\MockWpActions;
use MyParcelNL\WooCommerce\Tests\Mock\WordPressScheduledTasks;
use MyParcelNL\WooCommerce\Tests\Uses\UsesMockWcPdkInstance;
use WC_Order;
use function MyParcelNL\Pdk\Tests\usesShared;
use function MyParcelNL\WooCommerce\Tests\wpFactory;

usesShared(new UsesMockWcPdkInstance());

beforeEach(function () {
    (new ScheduledMigrationHooks())->apply();
});

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
 * Execute the scheduled WordPress actions, including their registered callbacks.
 */
function runLegacyOrderMetaTask(array $task): void
{
    // MockWpActions::execute consumes hooks after one call. Dispatch their registered
    // callbacks directly so multiple chunks behave like separate cron executions.
    foreach (MockWpActions::get($task['callback']) as $action) {
        Pdk::get(CronServiceInterface::class)->dispatch($action['function'], ...$task['args']);
    }
}

function runLegacyOrderMetaTasks(): void
{
    foreach (Pdk::get(WordPressScheduledTasks::class)->all() as $task) {
        runLegacyOrderMetaTask($task);
    }
}

function runLegacyOrderMetaMigration(): void
{
    loadLegacyOrderMetaMigration()->up();
    runLegacyOrderMetaTasks();
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

    runLegacyOrderMetaMigration();

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

    runLegacyOrderMetaMigration();

    expect(wc_get_order($order->get_id())->get_meta(CURRENT_SHIPMENTS_KEY))->toBe($existing);
});

it('does not resurrect shipments that were deliberately removed', function () {
    // An empty array is a decision, not an absence: the shipments were deleted.
    $order = makeOrderWithMeta([
        LEGACY_SHIPMENTS_KEY  => [legacyShipment()],
        CURRENT_SHIPMENTS_KEY => [],
    ]);

    runLegacyOrderMetaMigration();

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

    runLegacyOrderMetaMigration();

    $migrated = wc_get_order($order->get_id())->get_meta(CURRENT_SHIPMENTS_KEY);

    expect($migrated[0]['carrier'])->toBe('POSTNL')
        ->and($migrated[0]['deliveryOptions']['carrier'])->toBe('POSTNL');
});

it('migrates the belgian namespace too', function () {
    $order = makeOrderWithMeta(['_myparcelbe_order_shipments' => [legacyShipment()]]);

    runLegacyOrderMetaMigration();

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

    runLegacyOrderMetaMigration();

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

    runLegacyOrderMetaMigration();

    $migrated = wc_get_order($order->get_id())->get_meta(CURRENT_SHIPMENTS_KEY);

    expect($migrated[0]['carrier'])->toBe('POSTNL')
        ->and($migrated[0]['contractId'])->toBe('42')
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

    runLegacyOrderMetaMigration();

    $migrated = wc_get_order($order->get_id())->get_meta(CURRENT_SHIPMENTS_KEY);

    expect($migrated[0]['carrier'])->toBe('POSTNL')
        ->and($migrated[0]['contractId'])->toBe('42');
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

    runLegacyOrderMetaMigration();

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

    runLegacyOrderMetaMigration();

    expect(wc_get_order($order->get_id())->meta_exists(CURRENT_SHIPMENTS_KEY))->toBeFalse();
})->with([0, '0', false]);

it('schedules bounded cron chunks without writing order meta during the upgrade', function () {
    for ($i = 0; $i < 260; $i++) {
        makeOrderWithMeta([LEGACY_SHIPMENTS_KEY => [legacyShipment()]]);
    }

    // An unrelated order must never enter the chunks, even when it predates this migration.
    $unrelated = makeOrderWithMeta([]);
    $migration = loadLegacyOrderMetaMigration();
    $migration->up();

    $tasks = Pdk::get(WordPressScheduledTasks::class)->all();
    $chunks = $tasks->map(static function (array $task): array {
        return $task['args'][0]['orderIds'];
    })->all();
    $countMigrated = static function (): int {
        return count(wc_get_orders([
            'limit'        => -1,
            'meta_key'     => CURRENT_SHIPMENTS_KEY,
            'meta_compare' => 'EXISTS',
        ]));
    };

    expect($migration->hasFailed())->toBeFalse()
        ->and(array_map('count', $chunks))->toBe([100, 100, 60])
        ->and(array_unique(array_merge(...$chunks)))->toHaveCount(260)
        ->and($countMigrated())->toBe(0);

    $first = $tasks->first();
    runLegacyOrderMetaTask($first);

    expect($countMigrated())->toBe(100);

    foreach ($tasks->slice(1) as $task) {
        runLegacyOrderMetaTask($task);
    }

    expect($countMigrated())->toBe(260)
        ->and(wc_get_order($unrelated->get_id())->meta_exists(CURRENT_SHIPMENTS_KEY))->toBeFalse();
});

it('processes valid data after multiple chunks of empty legacy values', function () {
    for ($i = 0; $i < 260; $i++) {
        makeOrderWithMeta([LEGACY_SHIPMENTS_KEY => []]);
    }

    $valid = makeOrderWithMeta([LEGACY_SHIPMENTS_KEY => [legacyShipment()]]);

    runLegacyOrderMetaMigration();

    expect(wc_get_order($valid->get_id())->get_meta(CURRENT_SHIPMENTS_KEY))->toHaveCount(1);
});

it('preserves current meta written after the migration was scheduled', function (array $current) {
    $order = makeOrderWithMeta([LEGACY_SHIPMENTS_KEY => [legacyShipment()]]);

    loadLegacyOrderMetaMigration()->up();

    $order->update_meta_data(CURRENT_SHIPMENTS_KEY, $current);
    $order->save();
    runLegacyOrderMetaTasks();

    expect(wc_get_order($order->get_id())->get_meta(CURRENT_SHIPMENTS_KEY))->toBe($current);
})->with([
    'new export' => [[['id' => 1, 'carrier' => 'POSTNL', 'barcode' => 'NEW']]],
    'removed shipments' => [[]],
]);

it('leaves the entire legacy value intact when one nested carrier is unsupported', function () {
    $legacy = [legacyShipment(), [
        'carrier' => 'postnl',
        'deliveryOptions' => ['carrier' => ['id' => 999999]],
    ]];
    $order = makeOrderWithMeta([LEGACY_SHIPMENTS_KEY => $legacy]);
    $valid = makeOrderWithMeta([LEGACY_SHIPMENTS_KEY => [legacyShipment()]]);

    runLegacyOrderMetaMigration();

    $reloaded = wc_get_order($order->get_id());
    expect($reloaded->meta_exists(CURRENT_SHIPMENTS_KEY))->toBeFalse()
        ->and($reloaded->get_meta(LEGACY_SHIPMENTS_KEY))->toBe($legacy)
        ->and(wc_get_order($valid->get_id())->get_meta(CURRENT_SHIPMENTS_KEY))->toHaveCount(1);
});

it('does not copy malformed shipment records into the current namespace', function (array $legacy) {
    $order = makeOrderWithMeta([LEGACY_SHIPMENTS_KEY => $legacy]);

    runLegacyOrderMetaMigration();

    $reloaded = wc_get_order($order->get_id());
    expect($reloaded->meta_exists(CURRENT_SHIPMENTS_KEY))->toBeFalse()
        ->and($reloaded->get_meta(LEGACY_SHIPMENTS_KEY))->toBe($legacy);
})->with([
    'scalar shipment' => [[legacyShipment(), 'broken']],
    'scalar delivery options' => [[['carrier' => 'postnl', 'deliveryOptions' => 'broken']]],
]);

it('preserves existing contract ids and track and trace data', function () {
    $shipment = legacyShipment();
    $shipment['contractId'] = 42;
    $shipment['deliveryOptions']['contractId'] = 43;
    $order = makeOrderWithMeta([LEGACY_SHIPMENTS_KEY => [$shipment]]);

    runLegacyOrderMetaMigration();

    $migrated = wc_get_order($order->get_id())->get_meta(CURRENT_SHIPMENTS_KEY)[0];
    expect($migrated['contractId'])->toBe(42)
        ->and($migrated['deliveryOptions']['contractId'])->toBe(43)
        ->and($migrated['id'])->toBe($shipment['id'])
        ->and($migrated['barcode'])->toBe($shipment['barcode'])
        ->and($migrated['linkConsumerPortal'])->toBe($shipment['linkConsumerPortal']);
});

it('leaves the legacy meta in place so a repeated run is harmless', function () {
    $order = makeOrderWithMeta([LEGACY_SHIPMENTS_KEY => [legacyShipment()]]);

    runLegacyOrderMetaMigration();
    runLegacyOrderMetaMigration();

    $reloaded = wc_get_order($order->get_id());

    expect($reloaded->get_meta(LEGACY_SHIPMENTS_KEY))->toBeArray()->toHaveCount(1)
        ->and($reloaded->get_meta(CURRENT_SHIPMENTS_KEY))->toHaveCount(1);
});

it('keeps contract ids from all stored carrier object formats', function (array $carrier, int $contractId) {
    $order = makeOrderWithMeta([LEGACY_SHIPMENTS_KEY => [['carrier' => $carrier]]]);

    runLegacyOrderMetaMigration();

    expect(wc_get_order($order->get_id())->get_meta(CURRENT_SHIPMENTS_KEY)[0])->toBe([
        'carrier' => 'POSTNL',
        'contractId' => (string) $contractId,
    ]);
})->with([
    'id with identifier suffix' => [['id' => 1, 'externalIdentifier' => 'postnl:42'], 42],
    'explicit contract wins' => [['id' => 1, 'externalIdentifier' => 'postnl:42', 'contractId' => 43], 43],
    'snake case contract' => [['carrier' => 'postnl:42', 'contract_id' => 44], 44],
]);

it('accepts legacy and V2 names in both carrier fields', function (string $shipmentCarrier, string $deliveryCarrier) {
    $order = makeOrderWithMeta([LEGACY_SHIPMENTS_KEY => [[
        'carrier'         => $shipmentCarrier,
        'deliveryOptions' => ['carrier' => $deliveryCarrier],
    ]]]);

    runLegacyOrderMetaMigration();

    $migrated = wc_get_order($order->get_id())->get_meta(CURRENT_SHIPMENTS_KEY)[0];

    expect($migrated['carrier'])->toBe('POSTNL')
        ->and($migrated['deliveryOptions']['carrier'])->toBe('POSTNL');
})->with([
    'both legacy'       => ['postnl', 'postnl'],
    'both V2'           => ['POSTNL', 'POSTNL'],
    'legacy shipment'   => ['postnl', 'POSTNL'],
    'legacy options'    => ['POSTNL', 'postnl'],
]);

it('preserves missing carriers when it copies legacy metadata', function (array $record) {
    $order = makeOrderWithMeta([LEGACY_SHIPMENTS_KEY => [$record]]);

    runLegacyOrderMetaMigration();

    expect(wc_get_order($order->get_id())->get_meta(CURRENT_SHIPMENTS_KEY))->toBe([$record]);
})->with([
    'absent' => [['id' => 1]],
    'null'   => [['carrier' => null]],
    'string' => [['carrier' => '']],
    'array'  => [['carrier' => []]],
]);
