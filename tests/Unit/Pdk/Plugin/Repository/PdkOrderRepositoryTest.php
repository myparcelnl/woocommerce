<?php

/** @noinspection PhpUnhandledExceptionInspection,StaticClosureCanBeUsedInspection */

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Pdk\Plugin\Repository;

use InvalidArgumentException;
use MyParcelNL\Pdk\App\Api\Backend\PdkBackendActions;
use MyParcelNL\Pdk\App\Options\Definition\SignatureDefinition;
use MyParcelNL\Pdk\App\Order\Contract\PdkOrderRepositoryInterface;
use MyParcelNL\Pdk\App\Order\Model\PdkOrder;
use MyParcelNL\Pdk\App\Order\Model\PdkOrderNote;
use MyParcelNL\Pdk\Audit\Contract\PdkAuditRepositoryInterface;
use MyParcelNL\Pdk\Carrier\Model\Carrier;
use MyParcelNL\Pdk\Facade\Actions;
use MyParcelNL\Pdk\Facade\Pdk;
use MyParcelNL\Pdk\Settings\Model\OrderSettings;
use MyParcelNL\Pdk\Shipment\Model\DeliveryOptions;
use MyParcelNL\Pdk\Shipment\Model\ShipmentOptions;
use MyParcelNL\Pdk\Tests\Api\Response\ExampleGetShipmentsResponse;
use MyParcelNL\Pdk\Tests\Bootstrap\MockApi;
use MyParcelNL\Pdk\Tests\Bootstrap\TestBootstrapper;
use MyParcelNL\Pdk\Types\Service\TriStateService;
use MyParcelNL\WooCommerce\Tests\Uses\UsesMockWcPdkInstance;
use Psr\Log\LoggerInterface;
use WC_Order;
use WC_Order_Factory;
use function MyParcelNL\Pdk\Tests\factory;
use function MyParcelNL\Pdk\Tests\usesShared;
use function MyParcelNL\WooCommerce\Tests\wpFactory;

usesShared(new UsesMockWcPdkInstance());

beforeEach(function () {
    TestBootstrapper::hasAccount();
});

it('maps order identity, lines and totals from a WC order', function () {
    /** @var PdkOrderRepositoryInterface $orderRepository */
    $orderRepository = Pdk::get(PdkOrderRepositoryInterface::class);
    /** @var \MyParcelNL\Pdk\Tests\Bootstrap\MockLogger $logger */
    $logger = Pdk::get(LoggerInterface::class);

    $wcOrder  = wpFactory(WC_Order::class)->make();
    $pdkOrder = $orderRepository->get($wcOrder);

    expect($logger->getLogs())->toBe([])
        ->and($pdkOrder)->toBeInstanceOf(PdkOrder::class)
        ->and($pdkOrder->externalIdentifier)->toBe((string) $wcOrder->get_id())
        ->and($pdkOrder->orderPrice)->toBe(300000)
        ->and($pdkOrder->totalPrice)->toBe(300000)
        ->and($pdkOrder->lines->count())->toBe(3);

    // Products are mapped onto the lines that have one; the deliverable physical
    // product and the non-deliverable digital product are distinguished correctly.
    $products = $pdkOrder->lines
        ->map(function ($line) {
            return $line->product;
        })
        ->filter()
        ->keyBy('externalIdentifier');

    expect($products->get('3214')->sku)->toBe('WVS-0001')
        ->and($products->get('3214')->isDeliverable)->toBeTrue()
        ->and($products->get('2324')->isDeliverable)->toBeFalse();
});

it('maps the shipping address per destination country', function (string $factoryMethod, array $expected) {
    /** @var PdkOrderRepositoryInterface $orderRepository */
    $orderRepository = Pdk::get(PdkOrderRepositoryInterface::class);
    /** @var \MyParcelNL\Pdk\Tests\Bootstrap\MockLogger $logger */
    $logger = Pdk::get(LoggerInterface::class);

    $factory = wpFactory(WC_Order::class);
    if ('' !== $factoryMethod) {
        $factory = $factory->{$factoryMethod}();
    }

    $pdkOrder = $orderRepository->get($factory->make());

    expect($logger->getLogs())->toBe([]);

    $address = $pdkOrder->shippingAddress;

    expect($address->cc)->toBe($expected['cc'])
        ->and($address->city)->toBe($expected['city'])
        ->and($address->postalCode)->toBe($expected['postalCode'])
        ->and($address->street)->toBe($expected['street'])
        ->and($address->person)->toBe($expected['person'])
        ->and($address->company)->toBe($expected['company']);
})->with([
    'NL' => ['', ['cc' => 'NL', 'city' => 'Hoofddorp', 'postalCode' => '2132 JE', 'street' => 'Antareslaan 31', 'person' => 'John Doe', 'company' => 'MyParcel']],
    'BE' => ['withShippingAddressInBelgium', ['cc' => 'BE', 'city' => 'Antwerpen', 'postalCode' => '1000', 'street' => 'Adriaan Brouwerstraat 16', 'person' => 'Fomo Parcel', 'company' => 'MyParcel BE']],
    'DE' => ['withShippingAddressInGermany', ['cc' => 'DE', 'city' => 'Berlin', 'postalCode' => '10249', 'street' => 'Straßmannstraße 2', 'person' => 'Bier Parcel', 'company' => 'MyParcel DE']],
    'US' => ['withShippingAddressInTheUsa', ['cc' => 'US', 'city' => 'New York', 'postalCode' => '10001', 'street' => '123 Fake St', 'person' => 'Abe Lincoln', 'company' => 'MyParcel US']],
]);

it('reads saved delivery options and normalises the legacy carrier', function () {
    /** @var PdkOrderRepositoryInterface $orderRepository */
    $orderRepository = Pdk::get(PdkOrderRepositoryInterface::class);

    $wcOrder = wpFactory(WC_Order::class)->withMeta([
        Pdk::get('metaKeyOrderData') => [
            'deliveryOptions' => factory(DeliveryOptions::class)
                ->withCarrier(Carrier::CARRIER_DHL_FOR_YOU_LEGACY_NAME)
                ->withDeliveryType(DeliveryOptions::DELIVERY_TYPE_MORNING_NAME)
                ->withDate('2039-12-31 12:00:00')
                ->withShipmentOptions([(new SignatureDefinition())->getShipmentOptionsKey() => TriStateService::ENABLED])
                ->make()
                ->toStorableArray(),
        ],
    ])->make();

    $deliveryOptions = $orderRepository->get($wcOrder)->deliveryOptions;

    // Legacy "dhlforyou" is normalised to the v2 carrier name on read.
    expect($deliveryOptions->carrier->carrier)->toBe('DHL_FOR_YOU')
        ->and($deliveryOptions->deliveryType)->toBe('morning')
        ->and($deliveryOptions->date->format('Y-m-d H:i:s'))->toBe('2039-12-31 12:00:00')
        ->and($deliveryOptions->shipmentOptions->signature)->toBe(TriStateService::ENABLED);
});

it('reads saved shipment options', function () {
    /** @var PdkOrderRepositoryInterface $orderRepository */
    $orderRepository = Pdk::get(PdkOrderRepositoryInterface::class);

    $wcOrder = wpFactory(WC_Order::class)->withMeta([
        Pdk::get('metaKeyOrderData') => [
            'deliveryOptions' => factory(DeliveryOptions::class)
                ->withCarrier(Carrier::CARRIER_DHL_FOR_YOU_LEGACY_NAME)
                ->withDeliveryType(DeliveryOptions::DELIVERY_TYPE_MORNING_NAME)
                ->withShipmentOptions(factory(ShipmentOptions::class))
                ->withAllShipmentOptions()
                ->make()
                ->toStorableArray(),
        ],
    ])->make();

    $shipmentOptions = $orderRepository->get($wcOrder)->deliveryOptions->shipmentOptions;

    expect($shipmentOptions->ageCheck)->toBe(TriStateService::ENABLED)
        ->and($shipmentOptions->onlyRecipient)->toBe(TriStateService::ENABLED)
        ->and($shipmentOptions->signature)->toBe(TriStateService::ENABLED)
        ->and($shipmentOptions->insurance)->toBe(100);
});

it('reads saved order notes', function () {
    /** @var PdkOrderRepositoryInterface $orderRepository */
    $orderRepository = Pdk::get(PdkOrderRepositoryInterface::class);

    $wcOrder = wpFactory(WC_Order::class)->withMeta([
        Pdk::get('metaKeyOrderData') => [
            'notes' => [
                factory(PdkOrderNote::class)
                    ->byWebshop()
                    ->withApiIdentifier('12345')
                    ->withExternalIdentifier('40')
                    ->make()
                    ->toStorableArray(),
            ],
        ],
    ])->make();

    $notes = $orderRepository->get($wcOrder)->notes;

    expect($notes->count())->toBe(1)
        ->and($notes->first()->apiIdentifier)->toBe('12345')
        ->and($notes->first()->externalIdentifier)->toBe('40')
        ->and($notes->first()->author)->toBe('webshop');
});

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
        ->store();

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
        ->store();

    /** @var \MyParcelNL\Pdk\App\Order\Contract\PdkOrderRepositoryInterface $orderRepository */
    $orderRepository = Pdk::get(PdkOrderRepositoryInterface::class);

    $pdkOrder = $orderRepository->find(123);

    expect($pdkOrder)->toBeInstanceOf(PdkOrder::class);
    expect($pdkOrder->externalIdentifier)->toBe('123');
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
