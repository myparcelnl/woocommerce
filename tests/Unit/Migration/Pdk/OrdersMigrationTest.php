<?php
/** @noinspection PhpUnhandledExceptionInspection,StaticClosureCanBeUsedInspection */

declare(strict_types=1);

namespace MyParcelNL\Pdk\Tests\Unit\Migration\Pdk;

use MyParcelNL\Pdk\App\Order\Contract\PdkOrderRepositoryInterface;
use MyParcelNL\Pdk\Base\Support\Arr;
use MyParcelNL\Pdk\Base\Support\Collection;
use MyParcelNL\Pdk\Facade\Pdk;
use MyParcelNL\WooCommerce\Migration\Pdk\OrdersMigration;
use MyParcelNL\WooCommerce\Tests\Mock\MockWpMeta;
use MyParcelNL\WooCommerce\Tests\Mock\WordPressScheduledTasks;
use MyParcelNL\WooCommerce\Tests\Uses\UsesMockWcPdkInstance;
use function MyParcelNL\Pdk\Tests\usesShared;
use function MyParcelNL\WooCommerce\Tests\createWcOrder;
use function Spatie\Snapshots\assertMatchesJsonSnapshot;

usesShared(new UsesMockWcPdkInstance());

it('schedules order migration in chunks', function () {
    /** @var \MyParcelNL\WooCommerce\Tests\Mock\WordPressScheduledTasks $tasks */
    $tasks = Pdk::get(WordPressScheduledTasks::class);
    /** @var \MyParcelNL\WooCommerce\Migration\Pdk\OrdersMigration $orderMigration */
    $orderMigration = Pdk::get(OrdersMigration::class);

    // create 324 orders
    for ($i = 92000; $i < 92324; $i++) {
        createWcOrder(['id' => $i]);
    }

    $orderMigration->up();

    $allTasks = $tasks->all();

    expect($allTasks->count())
        ->toBe(4)
        // Expect migration callback to be the order migration
        ->and($allTasks->pluck('callback'))->each->toBe(Pdk::get('migrateAction_5_0_0_Orders'))
        // expect the first 3 chunks to each have 100 items
        ->and(
            $allTasks->take(3)
                ->pluck('args.0.orderIds')
        )
        ->each->toHaveCount(100)
        // Expect the last chunk to have 24 items
        ->and(
            $allTasks->take(-1)
                ->pluck('args.0.orderIds')
        )
        ->each->toHaveCount(24);

    $timestamps    = $allTasks->pluck('time');
    $chunkArgs     = $allTasks->pluck('args.0.chunk');
    $lastChunkArgs = $allTasks->pluck('args.0.lastChunk');

    foreach ($allTasks as $index => $task) {
        // Expect the chunk counts to be 1, 2, 3, 4 and the "lastChunk" value to be the max chunk count
        expect($chunkArgs[$index])
            ->toBe($index + 1)
            ->and($lastChunkArgs[$index])
            ->toBe(4);

        if (0 === $index) {
            continue;
        }

        // expect each chunk's schedule timestamp to be 5 seconds after the previous chunk's
        expect($task['time'])->toBe($timestamps[$index - 1] + 5);
    }
});

it('migrates orders', function (array $oldMeta) {
    /** @var \MyParcelNL\Pdk\Tests\Bootstrap\MockPdkOrderRepository $pdkOrderRepository */
    $pdkOrderRepository = Pdk::get(PdkOrderRepositoryInterface::class);
    /** @var \MyParcelNL\WooCommerce\Migration\Pdk\OrdersMigration $orderMigration */
    $orderMigration = Pdk::get(OrdersMigration::class);

    createWcOrder(['id' => 1, 'meta' => $oldMeta]);

    $orderMigration->migrateOrder(['orderIds' => [1]]);

    $postMeta = MockWpMeta::all(1);

    $requiredNewKeys = [
        Pdk::get('metaKeyOrderData'),
        Pdk::get('metaKeyOrderShipments'),
        Pdk::get('metaKeyMigrated'),
    ];

    $optionalNewKeys = [
        Pdk::get('metaKeyVersion'),
        Pdk::get('metaKeyFieldShippingStreet'),
        Pdk::get('metaKeyFieldShippingNumber'),
        Pdk::get('metaKeyFieldShippingNumberSuffix'),
    ];

    expect($postMeta)->toHaveKeys(array_merge(array_keys($oldMeta), $requiredNewKeys));

    // Expect old meta to be unchanged
    foreach (array_keys($oldMeta) as $key) {
        expect($postMeta[$key])->toEqual($oldMeta[$key]);
    }

    $filteredMeta = Arr::only($postMeta, array_merge($requiredNewKeys, $optionalNewKeys));

    $pdkOrder      = $pdkOrderRepository->get(1);
    $pdkOrderArray = Arr::only(
        $pdkOrder->toStorableArray(),
        ['deliveryOptions', 'shipments', 'shippingAddress', 'exported', 'apiIdentifier']
    );

    $shipmentsArray = $pdkOrder->shipments->toStorableArray();

    foreach ($shipmentsArray as $key => $shipment) {
        Arr::forget($shipmentsArray[$key], ['updated']);
    }

    assertMatchesJsonSnapshot(
        json_encode([
            'meta'      => (new Collection($filteredMeta))->toArrayWithoutNull(),
            'pdkOrder'  => $pdkOrderArray,
            'shipments' => $shipmentsArray,
        ])
    );
})->with('legacy meta data');
