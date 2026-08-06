<?php

/** @noinspection StaticClosureCanBeUsedInspection */

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Migration;

use MyParcelNL\Pdk\App\Options\Definition\NoTrackingDefinition;
use MyParcelNL\Pdk\App\Order\Contract\PdkProductRepositoryInterface;
use MyParcelNL\Pdk\App\Order\Model\PdkProduct;
use MyParcelNL\Pdk\Base\Contract\WeightServiceInterface;
use MyParcelNL\Pdk\Facade\Pdk;
use MyParcelNL\Pdk\Storage\Contract\StorageInterface;
use MyParcelNL\Pdk\Types\Service\TriStateService;
use MyParcelNL\WooCommerce\Pdk\Product\Repository\WcPdkProductRepository;
use MyParcelNL\WooCommerce\Tests\Mock\MockWcData;
use MyParcelNL\WooCommerce\Tests\Uses\UsesMockWcPdkInstance;
use RuntimeException;
use WC_Order;
use WC_Product;

use function MyParcelNL\Pdk\Tests\mockPdkProperties;
use function MyParcelNL\Pdk\Tests\usesShared;
use function MyParcelNL\WooCommerce\Tests\createWcOrder;
use function MyParcelNL\WooCommerce\Tests\wpFactory;

usesShared(new UsesMockWcPdkInstance());

/**
 * Writes the settings meta verbatim, so the legacy key survives. Going through the settings model would
 * drop it, since its attributes come from the option definitions and no longer include it.
 */
function givenProductWithStoredSettings(int $id, array $settings): void
{
    wpFactory(WC_Product::class)
        ->withId($id)
        ->withMeta([Pdk::get('metaKeyProductSettings') => $settings])
        ->make();
}

function migrateProductChunk(array $ids): void
{
    /** @var NoTrackingChunkMigrator $migrator */
    $migrator = Pdk::get(NoTrackingChunkMigrator::class);

    $migrator->migrateProductSettingsChunk(['ids' => $ids, 'chunk' => 1]);
}

function storedNoTracking(int $id): int
{
    /** @var PdkProductRepositoryInterface $productRepository */
    $productRepository = Pdk::get(PdkProductRepositoryInterface::class);

    $key = (new NoTrackingDefinition())->getProductSettingsKey();

    return $productRepository->getProduct($id)->settings->{$key};
}

it('turns tracking on into no tracking off', function () {
    // The merchant wanted tracking on this product, so the opt-out must end up switched off.
    givenProductWithStoredSettings(8001, [NoTrackingChunkMigrator::LEGACY_TRACKED_KEY => TriStateService::ENABLED]);

    migrateProductChunk([8001]);

    expect(storedNoTracking(8001))->toBe(TriStateService::DISABLED);
});

it('turns tracking off into no tracking on', function () {
    // The merchant did not want tracking, so the opt-out must end up switched on.
    givenProductWithStoredSettings(8002, [NoTrackingChunkMigrator::LEGACY_TRACKED_KEY => TriStateService::DISABLED]);

    migrateProductChunk([8002]);

    expect(storedNoTracking(8002))->toBe(TriStateService::ENABLED);
});

it('leaves inherit alone', function () {
    // Inherit means "not set", so inverting it would invent a choice the merchant never made.
    givenProductWithStoredSettings(8003, [NoTrackingChunkMigrator::LEGACY_TRACKED_KEY => TriStateService::INHERIT]);

    migrateProductChunk([8003]);

    expect(storedNoTracking(8003))->toBe(TriStateService::INHERIT);
});

it('leaves products that never stored the option alone', function () {
    givenProductWithStoredSettings(8004, ['exportSignature' => TriStateService::ENABLED]);

    migrateProductChunk([8004]);

    expect(storedNoTracking(8004))->toBe(TriStateService::INHERIT);
});

it('is safe to run twice', function () {
    // A second pass over the same product would otherwise flip tracking back on for a merchant who
    // deliberately switched it off.
    givenProductWithStoredSettings(8005, [NoTrackingChunkMigrator::LEGACY_TRACKED_KEY => TriStateService::DISABLED]);

    migrateProductChunk([8005]);
    migrateProductChunk([8005]);

    expect(storedNoTracking(8005))->toBe(TriStateService::ENABLED);
});

it('converts every product in the chunk', function () {
    givenProductWithStoredSettings(8006, [NoTrackingChunkMigrator::LEGACY_TRACKED_KEY => TriStateService::ENABLED]);
    givenProductWithStoredSettings(8007, [NoTrackingChunkMigrator::LEGACY_TRACKED_KEY => TriStateService::DISABLED]);

    migrateProductChunk([8006, 8007]);

    expect(storedNoTracking(8006))->toBe(TriStateService::DISABLED)
        ->and(storedNoTracking(8007))->toBe(TriStateService::ENABLED);
});

/**
 * Both stored order data and each stored shipment nest the options the same way, because a Shipment
 * carries DeliveryOptions which carries ShipmentOptions.
 */
function withShipmentOptions(array $options): array
{
    return ['deliveryOptions' => ['shipmentOptions' => $options]];
}

function givenOrderMeta(string $metaKeyConfig, $value): int
{
    $order = createWcOrder();
    $order->update_meta_data(Pdk::get($metaKeyConfig), $value);
    $order->save();

    return $order->get_id();
}

function readOrderMeta(int $orderId, string $metaKeyConfig)
{
    return wc_get_order($orderId)->get_meta(Pdk::get($metaKeyConfig));
}

function givenOrderWithBothStores(array $orderDataOptions, array $shipmentsOptions): int
{
    $order = createWcOrder();
    $order->update_meta_data(Pdk::get('metaKeyOrderData'), withShipmentOptions($orderDataOptions));
    $order->update_meta_data(
        Pdk::get('metaKeyOrderShipments'),
        array_map('MyParcelNL\WooCommerce\Migration\withShipmentOptions', $shipmentsOptions)
    );
    $order->save();

    return $order->get_id();
}

function migrateOrderChunkFor(array $ids): void
{
    /** @var NoTrackingChunkMigrator $migrator */
    $migrator = Pdk::get(NoTrackingChunkMigrator::class);

    $migrator->migrateOrderChunk(['ids' => $ids, 'chunk' => 1]);
}

it('flips the option on stored order data', function () {
    $orderId = givenOrderMeta(
        'metaKeyOrderData',
        withShipmentOptions([NoTrackingChunkMigrator::LEGACY_SHIPMENT_OPTION_KEY => TriStateService::ENABLED])
    );

    migrateOrderChunkFor([$orderId]);

    $options = readOrderMeta($orderId, 'metaKeyOrderData')['deliveryOptions']['shipmentOptions'];

    expect($options['noTracking'])->toBe(TriStateService::DISABLED)
        ->and($options)->not->toHaveKey(NoTrackingChunkMigrator::LEGACY_SHIPMENT_OPTION_KEY);
});

it('leaves order data without the old option alone', function () {
    $orderId = givenOrderMeta('metaKeyOrderData', withShipmentOptions(['signature' => TriStateService::ENABLED]));

    migrateOrderChunkFor([$orderId]);

    expect(readOrderMeta($orderId, 'metaKeyOrderData')['deliveryOptions']['shipmentOptions'])
        ->toBe(['signature' => TriStateService::ENABLED]);
});

it('flips the option on every stored shipment of an order', function () {
    // A shipment that went out untracked must keep saying so, under the option that now expresses it.
    $orderId = givenOrderMeta('metaKeyOrderShipments', [
        withShipmentOptions([NoTrackingChunkMigrator::LEGACY_SHIPMENT_OPTION_KEY => TriStateService::ENABLED]),
        withShipmentOptions([NoTrackingChunkMigrator::LEGACY_SHIPMENT_OPTION_KEY => TriStateService::DISABLED]),
    ]);

    migrateOrderChunkFor([$orderId]);

    $shipments = readOrderMeta($orderId, 'metaKeyOrderShipments');

    expect($shipments[0]['deliveryOptions']['shipmentOptions']['noTracking'])->toBe(TriStateService::DISABLED)
        ->and($shipments[1]['deliveryOptions']['shipmentOptions']['noTracking'])->toBe(TriStateService::ENABLED);
});

it('converts the order data and its shipments in the same pass', function () {
    // The reason both stores share one pass: an order is loaded and saved once, not twice.
    $orderId = givenOrderWithBothStores(
        [NoTrackingChunkMigrator::LEGACY_SHIPMENT_OPTION_KEY => TriStateService::ENABLED],
        [[NoTrackingChunkMigrator::LEGACY_SHIPMENT_OPTION_KEY => TriStateService::DISABLED]]
    );

    migrateOrderChunkFor([$orderId]);

    expect(readOrderMeta($orderId, 'metaKeyOrderData')['deliveryOptions']['shipmentOptions']['noTracking'])
        ->toBe(TriStateService::DISABLED)
        ->and(
            readOrderMeta($orderId, 'metaKeyOrderShipments')[0]['deliveryOptions']['shipmentOptions']['noTracking']
        )->toBe(TriStateService::ENABLED);
});

it('is safe to run over the same order twice', function () {
    $orderId = givenOrderWithBothStores(
        [NoTrackingChunkMigrator::LEGACY_SHIPMENT_OPTION_KEY => TriStateService::DISABLED],
        [[NoTrackingChunkMigrator::LEGACY_SHIPMENT_OPTION_KEY => TriStateService::DISABLED]]
    );

    migrateOrderChunkFor([$orderId]);
    migrateOrderChunkFor([$orderId]);

    expect(readOrderMeta($orderId, 'metaKeyOrderData')['deliveryOptions']['shipmentOptions']['noTracking'])
        ->toBe(TriStateService::ENABLED)
        ->and(
            readOrderMeta($orderId, 'metaKeyOrderShipments')[0]['deliveryOptions']['shipmentOptions']['noTracking']
        )->toBe(TriStateService::ENABLED);
});

it('does nothing when the chunk holds no ids', function () {
    givenProductWithStoredSettings(8008, [NoTrackingChunkMigrator::LEGACY_TRACKED_KEY => TriStateService::ENABLED]);

    migrateProductChunk([]);

    // Still unset rather than flipped: the migrator converts the ids it was handed, not everything it
    // can find.
    expect(storedNoTracking(8008))->toBe(TriStateService::INHERIT);
});

/**
 * Makes one product fail on write, the way a broken record or an unavailable store would.
 */
function givenProductUpdateFailsFor(int $failingId): void
{
    $repository = new class(
        Pdk::get(StorageInterface::class),
        Pdk::get(WeightServiceInterface::class)
    ) extends WcPdkProductRepository {
        /** @var int */
        public $failingId = 0;

        public function update(PdkProduct $product): void
        {
            if ((int) $product->externalIdentifier === $this->failingId) {
                throw new RuntimeException('Product could not be saved');
            }

            parent::update($product);
        }
    };

    $repository->failingId = $failingId;

    mockPdkProperties([PdkProductRepositoryInterface::class => $repository]);
}

/**
 * Stores an order that throws when it is written, so a chunk can hold one record that cannot be saved.
 */
function givenOrderThatCannotBeSaved(array $orderDataOptions): int
{
    $order = new class extends WC_Order {
        public function save(): void
        {
            throw new RuntimeException('Order could not be saved');
        }
    };

    $order->set_id(9101);
    $order->update_meta_data(Pdk::get('metaKeyOrderData'), withShipmentOptions($orderDataOptions));

    MockWcData::create($order);

    return $order->get_id();
}

it('keeps converting the rest of the chunk when one product cannot be saved', function () {
    givenProductWithStoredSettings(8009, [NoTrackingChunkMigrator::LEGACY_TRACKED_KEY => TriStateService::ENABLED]);
    givenProductWithStoredSettings(8010, [NoTrackingChunkMigrator::LEGACY_TRACKED_KEY => TriStateService::ENABLED]);

    givenProductUpdateFailsFor(8009);

    migrateProductChunk([8009, 8010]);

    // A chunk is scheduled once and never retried, so one unusable record must not take the rest of its
    // batch down with it.
    expect(storedNoTracking(8010))->toBe(TriStateService::DISABLED);
});

it('keeps converting the rest of the chunk when one order cannot be saved', function () {
    $failingId = givenOrderThatCannotBeSaved([
        NoTrackingChunkMigrator::LEGACY_SHIPMENT_OPTION_KEY => TriStateService::ENABLED,
    ]);

    $goodId = givenOrderMeta(
        'metaKeyOrderData',
        withShipmentOptions([NoTrackingChunkMigrator::LEGACY_SHIPMENT_OPTION_KEY => TriStateService::ENABLED])
    );

    migrateOrderChunkFor([$failingId, $goodId]);

    expect(readOrderMeta($goodId, 'metaKeyOrderData')['deliveryOptions']['shipmentOptions']['noTracking'])
        ->toBe(TriStateService::DISABLED);
});
