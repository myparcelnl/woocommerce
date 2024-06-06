<?php
/** @noinspection StaticClosureCanBeUsedInspection,PhpUnhandledExceptionInspection */

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Adapter;

use MyParcelNL\Pdk\Carrier\Model\Carrier;
use MyParcelNL\Pdk\Facade\Pdk;
use MyParcelNL\Pdk\Shipment\Model\DeliveryOptions;
use MyParcelNL\WooCommerce\Tests\Uses\UsesMockWcPdkInstance;
use function MyParcelNL\Pdk\Tests\factory;
use function MyParcelNL\Pdk\Tests\usesShared;
use function Spatie\Snapshots\assertMatchesSnapshot;

usesShared(new UsesMockWcPdkInstance());

dataset('deliveryOptions', function () {
    return [
        'with date'        => function () {
            return factory(DeliveryOptions::class)
                ->with([
                    'deliveryType' => DeliveryOptions::DELIVERY_TYPE_STANDARD_NAME,
                    'packageType'  => DeliveryOptions::PACKAGE_TYPE_PACKAGE_NAME,
                    'carrier'      => Carrier::CARRIER_POSTNL_NAME,
                    'date'         => '2037-12-31',
                ])
                ->make();
        },
        'carrier'          => function () {
            return factory(DeliveryOptions::class)
                ->with([
                    'deliveryType' => DeliveryOptions::DELIVERY_TYPE_STANDARD_NAME,
                    'packageType'  => DeliveryOptions::PACKAGE_TYPE_MAILBOX_NAME,
                    'carrier'      => Carrier::CARRIER_DHL_FOR_YOU_NAME,
                ])
                ->make();
        },
        'shipment options' => function () {
            return factory(DeliveryOptions::class)
                ->with([
                    'deliveryType'    => DeliveryOptions::DELIVERY_TYPE_STANDARD_NAME,
                    'packageType'     => DeliveryOptions::PACKAGE_TYPE_PACKAGE_NAME,
                    'carrier'         => Carrier::CARRIER_POSTNL_NAME,
                    'shipmentOptions' => [
                        'ageCheck'          => true,
                        'signature'         => true,
                        'onlyRecipient'     => false,
                        'insurance'         => 0,
                        'return'            => false,
                        'same_day_delivery' => false,
                        'large_format'      => true,
                        'label_description' => 'test',
                        'hide_sender'       => false,
                        'extra_assurance'   => false,
                    ],
                ])
                ->make();
        },
        'pickup location'  => function () {
            return factory(DeliveryOptions::class)
                ->with([
                    'deliveryType'   => DeliveryOptions::DELIVERY_TYPE_PICKUP_NAME,
                    'packageType'    => DeliveryOptions::PACKAGE_TYPE_PACKAGE_NAME,
                    'carrier'        => Carrier::CARRIER_DPD_NAME,
                    'pickupLocation' => [
                        'locationCode'    => 'DPD-12',
                        'locationName'    => 'DPD Pakketshop',
                        'retailNetworkId' => '123',
                        'street'          => 'Deepeedee',
                        'number'          => '12',
                        'postalCode'      => '1212DP',
                        'city'            => 'Hoofddorp',
                        'country'         => 'NL',
                    ],
                ])
                ->make();
        },
    ];
});

it('creates legacy options', function (DeliveryOptions $options) {
    /** @var LegacyDeliveryOptionsAdapter $adapter */
    $adapter = Pdk::get(LegacyDeliveryOptionsAdapter::class);

    /**
     * In the snapshots, properties in pickupLocation and shipmentOptions must be snake_case (part of the legacy)
     */
    assertMatchesSnapshot($adapter->fromDeliveryOptions($options));
})->with('deliveryOptions');
