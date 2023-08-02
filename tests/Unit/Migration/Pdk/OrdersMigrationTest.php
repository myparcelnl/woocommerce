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
use MyParcelNL\WooCommerce\Tests\Uses\UsesMockWcPdkInstance;
use WC_Order;
use function MyParcelNL\Pdk\Tests\usesShared;
use function Spatie\Snapshots\assertMatchesJsonSnapshot;

usesShared(new UsesMockWcPdkInstance());

it('migrates orders', function (array $oldMeta) {
    /** @var \MyParcelNL\Pdk\Tests\Bootstrap\MockPdkOrderRepository $pdkOrderRepository */
    $pdkOrderRepository = Pdk::get(PdkOrderRepositoryInterface::class);
    /** @var \MyParcelNL\WooCommerce\Migration\Pdk\OrdersMigration $orderMigration */
    $orderMigration = Pdk::get(OrdersMigration::class);

    new WC_Order(['id' => 1, 'meta' => $oldMeta]);

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

    $filteredMeta  = Arr::only($postMeta, array_merge($requiredNewKeys, $optionalNewKeys));

    $pdkOrder      = $pdkOrderRepository->get(1);
    $pdkOrderArray = Arr::only(
        $pdkOrder->toStorableArray(),
        ['deliveryOptions', 'shipments', 'shippingAddress', 'exported', 'apiIdentifier']
    );

    $shipmentsArray = $pdkOrder->shipments->toStorableArray();

    /**
     * @todo put dates back in when it's fixed in the pdk
     * @see  https://github.com/myparcelnl/pdk/pulls/139
     * @see  https://github.com/myparcelnl/pdk/pulls/138
     */
    Arr::forget($pdkOrderArray, ['deliveryOptions.date']);

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
})->with('legacyMetaData');
