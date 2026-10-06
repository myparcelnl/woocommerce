<?php
/** @noinspection StaticClosureCanBeUsedInspection */

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Tests\Datasets;

use MyParcelNL\Pdk\App\Options\Definition\SignatureDefinition;
use MyParcelNL\Pdk\App\Order\Model\PdkOrderNote;
use MyParcelNL\Pdk\Facade\Pdk;
use MyParcelNL\Pdk\Shipment\Model\DeliveryOptions;
use MyParcelNL\Pdk\Shipment\Model\ShipmentOptions;
use MyParcelNL\Pdk\Types\Service\TriStateService;
use WC_Order;
use MyParcelNL\Sdk\Client\Generated\CoreApi\Model\ShipmentDefsDeliveryOptionsDeliveryNameV2;
use MyParcelNL\Sdk\Client\Generated\CoreApi\Model\ShipmentParametersCarrierName;
use function MyParcelNL\Pdk\Tests\factory;
use function MyParcelNL\WooCommerce\Tests\wpFactory;

dataset('orders', [
    'simple order' => function () {
        return wpFactory(WC_Order::class);
    },

    'BE order' => function () {
        return wpFactory(WC_Order::class)->withShippingAddressInBelgium();
    },

    'EU order' => function () {
        return wpFactory(WC_Order::class)->withShippingAddressInGermany();
    },

    'ROW order' => function () {
        return wpFactory(WC_Order::class)->withShippingAddressInTheUsa();
    },

    'order with saved notes' => function () {
        return wpFactory(WC_Order::class)->withMeta([
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
        ]);
    },

    'order with saved delivery options' => function () {
        return wpFactory(WC_Order::class)->withMeta([
            Pdk::get('metaKeyOrderData') => [
                'deliveryOptions' => factory(DeliveryOptions::class)
                    ->withCarrier(ShipmentParametersCarrierName::DHL_FOR_YOU)
                    ->withDeliveryType(ShipmentDefsDeliveryOptionsDeliveryNameV2::MORNING)
                    ->withDate('2039-12-31 12:00:00')
                    ->withShipmentOptions([(new SignatureDefinition())->getShipmentOptionsKey() => TriStateService::ENABLED])
                    ->make()
                    ->toStorableArray(),
            ],
        ]);
    },

    'order with all shipment options' => function () {
        return wpFactory(WC_Order::class)->withMeta([
            Pdk::get('metaKeyOrderData') => [
                'deliveryOptions' => factory(DeliveryOptions::class)
                    ->withCarrier(ShipmentParametersCarrierName::DHL_FOR_YOU)
                    ->withDeliveryType(ShipmentDefsDeliveryOptionsDeliveryNameV2::MORNING)
                    ->withDate('2039-12-31 12:00:00')
                    ->withShipmentOptions(factory(ShipmentOptions::class))
                    ->withAllShipmentOptions()
                    ->make()
                    ->toStorableArray(),
            ],
        ]);
    },
]);
