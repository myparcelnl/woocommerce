<?php

/** @noinspection PhpUnhandledExceptionInspection,StaticClosureCanBeUsedInspection */

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Pdk\Plugin\Repository;

use InvalidArgumentException;
use MyParcelNL\Pdk\App\Api\Backend\PdkBackendActions;
use MyParcelNL\Pdk\App\Order\Contract\PdkOrderRepositoryInterface;
use MyParcelNL\Pdk\App\Order\Model\PdkOrder;
use MyParcelNL\Pdk\Audit\Contract\PdkAuditRepositoryInterface;
use MyParcelNL\Pdk\Facade\Actions;
use MyParcelNL\Pdk\Facade\Pdk;
use MyParcelNL\Pdk\Settings\Model\OrderSettings;
use MyParcelNL\Pdk\Tests\Api\Response\ExampleGetShipmentsResponse;
use MyParcelNL\Pdk\Tests\Bootstrap\MockApi;
use MyParcelNL\WooCommerce\Tests\Uses\UsesMockWcPdkInstance;
use Psr\Log\LoggerInterface;
use WC_Order;
use WC_Order_Factory;
use function MyParcelNL\Pdk\Tests\factory;
use function MyParcelNL\Pdk\Tests\usesShared;
use function MyParcelNL\WooCommerce\Tests\wpFactory;
use function Spatie\Snapshots\assertMatchesJsonSnapshot;

usesShared(new UsesMockWcPdkInstance());

it('creates a valid pdk order', function (WC_Order_Factory $factory) {
    /** @var \MyParcelNL\Pdk\App\Order\Contract\PdkOrderRepositoryInterface $orderRepository */
    $orderRepository = Pdk::get(PdkOrderRepositoryInterface::class);
    /** @var \MyParcelNL\Pdk\Tests\Bootstrap\MockLogger $logger */
    $logger = Pdk::get(LoggerInterface::class);

    $wcOrder = $factory->make();
    $pdkOrder = $orderRepository->get($wcOrder);

    expect($logger->getLogs())->toBe([]);

    $orderArray = array_replace(
        $pdkOrder->toArrayWithoutNull(),
        ['deliveryOptions' => $pdkOrder->deliveryOptions->toStorableArray()]
    );

    assertMatchesJsonSnapshot(json_encode($orderArray, JSON_PRETTY_PRINT));
})->with('orders');

it('adds autoExported property to order', function () {
    factory(OrderSettings::class)
        ->withConceptShipments(true)
        ->store();


    $orderFactory = wpFactory(WC_Order::class)->withShippingAddressInBelgium();
    /** @var \MyParcelNL\Pdk\App\Order\Contract\PdkOrderRepositoryInterface $orderRepository */
    $orderRepository = Pdk::get(PdkOrderRepositoryInterface::class);

    $wcOrder = $orderFactory->make();
    $pdkOrder = $orderRepository->get($wcOrder);
    expect($pdkOrder->autoExported)->toBeFalsy();

    MockApi::enqueue(new ExampleGetShipmentsResponse());

    Actions::executeAutomatic(PdkBackendActions::EXPORT_ORDERS, [
        'orderIds' => [$wcOrder->get_id()],
    ]);

    // Re-fetch the pdk order to get the updated autoExported property
    $pdkOrder = $orderRepository->get($wcOrder);
    expect($pdkOrder->autoExported)->toBeTrue();
});


it('gets order via various inputs', function ($input) {
    wpFactory(WC_Order::class)
        ->with(['id' => 123])
        ->make();

    /** @var \MyParcelNL\Pdk\App\Order\Contract\PdkOrderRepositoryInterface $orderRepository */
    $orderRepository = Pdk::get(PdkOrderRepositoryInterface::class);

    $pdkOrder = $orderRepository->get($input);

    expect($pdkOrder)->toBeInstanceOf(PdkOrder::class);
})->with([
    'string id' => function () {
        return '123';
    },
    'int id'    => function () {
        return 123;
    },
    'wc order'  => function () {
        return new WC_Order(['id' => 123]);
    },
    'post'      => function () {
        return (object) ['ID' => 123];
    },
]);

it('finds an existing order by id', function () {
    wpFactory(WC_Order::class)
        ->with(['id' => 123])
        ->make();

    /** @var \MyParcelNL\Pdk\App\Order\Contract\PdkOrderRepositoryInterface $orderRepository */
    $orderRepository = Pdk::get(PdkOrderRepositoryInterface::class);

    $pdkOrder = $orderRepository->find(123);

    expect($pdkOrder)->toBeInstanceOf(PdkOrder::class);
    expect($pdkOrder->id)->toBe('123');
});

it('returns null if order not found', function () {
    /** @var \MyParcelNL\Pdk\App\Order\Contract\PdkOrderRepositoryInterface $orderRepository */
    $orderRepository = Pdk::get(PdkOrderRepositoryInterface::class);

    $pdkOrder = $orderRepository->find(999);

    expect($pdkOrder)->toBeNull();
});

it('handles errors', function ($input) {
    /** @var \MyParcelNL\Pdk\App\Order\Contract\PdkOrderRepositoryInterface $orderRepository */
    $orderRepository = Pdk::get(PdkOrderRepositoryInterface::class);

    $orderRepository->get($input);
})
    ->throws(InvalidArgumentException::class)
    ->with([
        'unrecognized object input' => function () {
            return (object) ['foo' => 'bar'];
        },

        'array' => function () {
            return ['id' => 1];
        },
    ]);
