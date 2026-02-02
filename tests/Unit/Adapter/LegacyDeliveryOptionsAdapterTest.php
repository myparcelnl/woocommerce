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

usesShared(new UsesMockWcPdkInstance());

it('creates legacy options', function (DeliveryOptions $options, array $expected) {
    /** @var LegacyDeliveryOptionsAdapter $adapter */
    $adapter = Pdk::get(LegacyDeliveryOptionsAdapter::class);

    expect($adapter->fromDeliveryOptions($options))->toBe($expected);
})->with(
    [
        'with carrier and date' => [
            'options'  => function () {
                return factory(DeliveryOptions::class)
                    ->with([
                        'deliveryType' => DeliveryOptions::DELIVERY_TYPE_STANDARD_NAME,
                        'packageType'  => DeliveryOptions::PACKAGE_TYPE_PACKAGE_NAME,
                        'carrier'      => Carrier::CARRIER_POSTNL_LEGACY_NAME,
                        'date'         => '2037-12-31',
                    ])
                    ->make();
            },
            'expected' => [
                'date'            => '2037-12-31T00:00:00.000Z',
                'carrier'         => Carrier::CARRIER_POSTNL_LEGACY_NAME,
                'labelAmount'     => 1,
                'shipmentOptions' => [
                    'signature'         => null,
                    'insurance'         => null,
                    'age_check'         => null,
                    'only_recipient'    => null,
                    'return'            => null,
                    'same_day_delivery' => null,
                    'large_format'      => null,
                    'label_description' => null,
                    'hide_sender'       => null,
                    'extra_assurance'   => null,
                ],
                'deliveryType'    => DeliveryOptions::DELIVERY_TYPE_STANDARD_NAME,
                'packageType'     => DeliveryOptions::PACKAGE_TYPE_PACKAGE_NAME,
                'isPickup'        => false,
                'pickupLocation'  => null,
            ],
        ],
        'shipment options'      => [
            'options'  => function () {
                return factory(DeliveryOptions::class)
                    ->with([
                        'deliveryType'    => DeliveryOptions::DELIVERY_TYPE_STANDARD_NAME,
                        'packageType'     => DeliveryOptions::PACKAGE_TYPE_PACKAGE_NAME,
                        'carrier'         => Carrier::CARRIER_POSTNL_LEGACY_NAME,
                        'shipmentOptions' => [
                            'ageCheck'         => true,
                            'signature'        => true,
                            'onlyRecipient'    => false,
                            'insurance'        => 0,
                            'return'           => false,
                            'sameDayDelivery'  => false,
                            'largeFormat'      => true,
                            'labelDescription' => 'test',
                            'hideSender'       => false,
                            'extraAssurance'   => false,
                        ],
                    ])
                    ->make();
            },
            'expected' => [
                'carrier'         => Carrier::CARRIER_POSTNL_LEGACY_NAME,
                'labelAmount'     => 1,
                'shipmentOptions' => [
                    'signature'         => true,
                    'insurance'         => 0,
                    'age_check'         => true,
                    'only_recipient'    => false,
                    'return'            => false,
                    'same_day_delivery' => false,
                    'large_format'      => true,
                    'label_description' => 'test',
                    'hide_sender'       => false,
                    'extra_assurance'   => null, // null because the option does not exist anymore
                ],
                'deliveryType'    => DeliveryOptions::DELIVERY_TYPE_STANDARD_NAME,
                'packageType'     => DeliveryOptions::PACKAGE_TYPE_PACKAGE_NAME,
                'isPickup'        => false,
                'date'            => null,
                'pickupLocation'  => null,
            ],
        ],
        'pickup location'       => [
            'options'  => function () {
                return factory(DeliveryOptions::class)
                    ->with([
                        'deliveryType'   => DeliveryOptions::DELIVERY_TYPE_PICKUP_NAME,
                        'packageType'    => DeliveryOptions::PACKAGE_TYPE_PACKAGE_NAME,
                        'carrier'        => Carrier::CARRIER_DPD_LEGACY_NAME,
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
            'expected' => [
                'carrier'         => Carrier::CARRIER_DPD_LEGACY_NAME,
                'labelAmount'     => 1,
                'pickupLocation'  => [
                    'postal_code'       => '1212DP',
                    'street'            => 'Deepeedee',
                    'number'            => '12',
                    'city'              => 'Hoofddorp',
                    'location_code'     => 'DPD-12',
                    'location_name'     => 'DPD Pakketshop',
                    'cc'                => null,
                    'retail_network_id' => '123',
                ],
                'shipmentOptions' => [
                    'signature'         => null,
                    'insurance'         => null,
                    'age_check'         => null,
                    'only_recipient'    => null,
                    'return'            => null,
                    'same_day_delivery' => null,
                    'large_format'      => null,
                    'label_description' => null,
                    'hide_sender'       => null,
                    'extra_assurance'   => null,
                ],
                'deliveryType'    => DeliveryOptions::DELIVERY_TYPE_PICKUP_NAME,
                'packageType'     => DeliveryOptions::PACKAGE_TYPE_PACKAGE_NAME,
                'isPickup'        => true,
                'date'            => null,
            ],
        ],
    ]
);
