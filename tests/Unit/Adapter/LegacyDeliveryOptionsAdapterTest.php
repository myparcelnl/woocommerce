<?php
/** @noinspection StaticClosureCanBeUsedInspection,PhpUnhandledExceptionInspection */

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Adapter;

use MyParcelNL\Pdk\Facade\Pdk;
use MyParcelNL\Pdk\Shipment\Model\DeliveryOptions;
use MyParcelNL\WooCommerce\Tests\Uses\UsesMockWcPdkInstance;
use WC_Cart;
use WC_Customer;
use WC_Order;
use function MyParcelNL\Pdk\Tests\usesShared;
use function MyParcelNL\WooCommerce\Tests\wpFactory;
use function Spatie\Snapshots\assertMatchesSnapshot;

usesShared(new UsesMockWcPdkInstance());

dataset('deliveryOptions', function () {
    return [
        'default' => [
            new DeliveryOptions([
                'deliveryType' => 'standard',
                'packageType' => 'mailbox',
                'carrier' => 'dhlforyou',
            ])
        ],
        'with shipment options' => [
            new DeliveryOptions([
                'deliveryType' => 'standard',
                'packageType' => 'package',
                'carrier' => 'postnl',
                'shipmentOptions' => [
                    'ageCheck' => true,
                    'signature' => true,
                    'onlyRecipient' => true,
                ]
            ])
        ],
        'with pickup location' => [
            new DeliveryOptions([
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
            ])
        ],
    ];
});

it('creates legacy options', function (DeliveryOptions $options) {
    /** @var LegacyDeliveryOptionsAdapter $adapter */
    $adapter = Pdk::get(LegacyDeliveryOptionsAdapter::class);

    /**
     * In the snapshots, properties in pickupLocation and shipmentOptions must be snake_case (part of the legacy)
     */
    assertMatchesSnapshot($adapter->fromDeliveryOptions($options)->toArray());
})->with('deliveryOptions');
