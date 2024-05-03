<?php
/** @noinspection StaticClosureCanBeUsedInspection,PhpUnhandledExceptionInspection */

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Adapter;

use MyParcelNL\Pdk\Facade\Pdk;
use MyParcelNL\Pdk\Shipment\Model\DeliveryOptions;
use MyParcelNL\WooCommerce\Tests\Uses\UsesMockWcPdkInstance;
use function MyParcelNL\Pdk\Tests\usesShared;
use function Spatie\Snapshots\assertMatchesSnapshot;
use function MyParcelNL\Pdk\Tests\factory;

usesShared(new UsesMockWcPdkInstance());

dataset('deliveryOptions', function () {
    return [
        'carrier' => [
            [
                'deliveryType' => 'standard',
                'packageType' => 'mailbox',
                'carrier' => 'dhlforyou',
            ]
        ],
        'shipment options' => [
            [
                'deliveryType' => 'standard',
                'packageType' => 'package',
                'carrier' => 'postnl',
                'shipmentOptions' => [
                    'ageCheck' => true,
                    'signature' => true,
                    'onlyRecipient' => true,
                ]
            ]
        ],
        'pickup location' => [
            [
                'deliveryType' => 'pickup',
                'packageType' => 'package',
                'carrier' => 'dpd',
                'pickupLocation' => [
                    'locationCode' => 'DPD-12',
                    'locationName' => 'DPD Pakketshop',
                    'retailNetworkId' => '123',
                    'street' => 'Deepeedee',
                    'number' => '12',
                    'postalCode' => '1212DP',
                    'city' => 'Hoofddorp',
                    'country' => 'NL',
               ]
            ]
        ],
    ];
});

it('creates legacy options', function (array $options) {
    /** @var LegacyDeliveryOptionsAdapter $adapter */
    $adapter = Pdk::get(LegacyDeliveryOptionsAdapter::class);

    $boo = factory(DeliveryOptions::class)
        ->with($options)
        ->make();
    /**
     * In the snapshots, properties in pickupLocation and shipmentOptions must be snake_case (part of the legacy)
     */
    assertMatchesSnapshot($adapter->fromDeliveryOptions($boo)->toArray());
})->with('deliveryOptions');
