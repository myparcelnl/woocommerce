<?php

/** @noinspection StaticClosureCanBeUsedInspection,PhpUnhandledExceptionInspection */

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Adapter;

use MyParcelNL\Pdk\Facade\Pdk;
use MyParcelNL\Pdk\Shipment\Model\DeliveryOptions;
use MyParcelNL\Pdk\Tests\Bootstrap\TestBootstrapper;
use MyParcelNL\Sdk\Client\Generated\CoreApi\Model\RefTypesDeliveryTypeV2;
use MyParcelNL\Sdk\Client\Generated\CoreApi\Model\ShipmentDefsDeliveryOptionsDeliveryNameV2;
use MyParcelNL\Sdk\Client\Generated\CoreApi\Model\ShipmentResponsesDeliveryOptionsPackageTypeV2;
use MyParcelNL\Sdk\Services\Mapping\ApiMapperService;
use MyParcelNL\WooCommerce\Tests\Uses\UsesMockWcPdkInstance;

use function MyParcelNL\Pdk\Tests\factory;
use function MyParcelNL\Pdk\Tests\usesShared;

usesShared(new UsesMockWcPdkInstance());

beforeEach(function () {
    TestBootstrapper::hasAccount();
});

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
                        'deliveryType' => ShipmentDefsDeliveryOptionsDeliveryNameV2::STANDARD,
                        'packageType'  => ShipmentResponsesDeliveryOptionsPackageTypeV2::PACKAGE,
                        'carrier'      => 'postnl',
                        'date'         => '2037-12-31',
                    ])
                    ->make();
            },
            'expected' => [
                'date'            => '2037-12-31T00:00:00.000Z',
                'carrier'         => 'postnl',
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
                'deliveryType'    => ShipmentDefsDeliveryOptionsDeliveryNameV2::STANDARD,
                'packageType'     => ShipmentResponsesDeliveryOptionsPackageTypeV2::PACKAGE,
                'isPickup'        => false,
                'pickupLocation'  => null,
            ],
        ],
        'shipment options'      => [
            'options'  => function () {
                return factory(DeliveryOptions::class)
                    ->with([
                        'deliveryType'    => ShipmentDefsDeliveryOptionsDeliveryNameV2::STANDARD,
                        'packageType'     => ShipmentResponsesDeliveryOptionsPackageTypeV2::PACKAGE,
                        'carrier'         => 'postnl',
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
                'carrier'         => 'postnl',
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
                'deliveryType'    => ShipmentDefsDeliveryOptionsDeliveryNameV2::STANDARD,
                'packageType'     => ShipmentResponsesDeliveryOptionsPackageTypeV2::PACKAGE,
                'isPickup'        => false,
                'date'            => null,
                'pickupLocation'  => null,
            ],
        ],
        'pickup location'       => [
            'options'  => function () {
                return factory(DeliveryOptions::class)
                    ->with([
                        'deliveryType'   => ApiMapperService::forDeliveryType()->legacyNameFromV2Name(RefTypesDeliveryTypeV2::PICKUP),
                        'packageType'    => ShipmentResponsesDeliveryOptionsPackageTypeV2::PACKAGE,
                        'carrier'        => 'dpd',
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
                'carrier'         => 'dpd',
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
                'deliveryType'    => ApiMapperService::forDeliveryType()->legacyNameFromV2Name(RefTypesDeliveryTypeV2::PICKUP),
                'packageType'     => ShipmentResponsesDeliveryOptionsPackageTypeV2::PACKAGE,
                'isPickup'        => true,
                'date'            => null,
            ],
        ],
    ]
);
