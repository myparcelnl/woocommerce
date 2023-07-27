<?php
/** @noinspection StaticClosureCanBeUsedInspection */

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Tests\Datasets;

use function MyParcelNL\WooCommerce\Tests\createDeliveryOptionsMeta;
use function MyParcelNL\WooCommerce\Tests\createNotesMeta;
use function MyParcelNL\WooCommerce\Tests\getOrderDefaults;

dataset('orders', [
    'simple order' => function () {
        return getOrderDefaults();
    },

    'BE order' => function () {
        return array_replace(getOrderDefaults(), [
            'shipping_address_1'  => 'Adriaan Brouwerstraat 16',
            'shipping_city'       => 'Antwerpen',
            'shipping_company'    => 'MyParcel BE',
            'shipping_country'    => 'BE',
            'shipping_first_name' => 'Fomo',
            'shipping_last_name'  => 'Parcel',
            'shipping_postcode'   => '1000',
        ]);
    },

    'EU order' => function () {
        return array_replace(getOrderDefaults(), [
            'shipping_address_1'  => 'Hauptstraße 1',
            'shipping_city'       => 'Berlin',
            'shipping_company'    => 'MyParcel DE',
            'shipping_country'    => 'DE',
            'shipping_first_name' => 'Bier',
            'shipping_last_name'  => 'Parcel',
            'shipping_postcode'   => '10115',
        ]);
    },

    'ROW order' => function () {
        return array_replace(getOrderDefaults(), [
            'shipping_address_1'  => '123 Fake St',
            'shipping_city'       => 'New York',
            'shipping_company'    => 'MyParcel US',
            'shipping_country'    => 'US',
            'shipping_first_name' => 'Abe',
            'shipping_last_name'  => 'Lincoln',
            'shipping_postcode'   => '10001',
        ]);
    },

    'order with saved notes' => function () {
        return getOrderDefaults() + createNotesMeta([
                [
                    'apiIdentifier'      => '12345',
                    'externalIdentifier' => '40',
                    'author'             => 'customer',
                    'note'               => 'moo',
                    'createdAt'          => '2021-01-01 18:03:41',
                    'updatedAt'          => '2021-01-01 18:03:41',
                ],
            ]);
    },

    'order with saved delivery options' => function () {
        return getOrderDefaults() + createDeliveryOptionsMeta();
    },

    'order with all shipment options' => function () {
        return getOrderDefaults() + createDeliveryOptionsMeta([
                'shipmentOptions' => [
                    'ageCheck'         => true,
                    'insurance'        => 50000,
                    'labelDescription' => 'test',
                    'largeFormat'      => true,
                    'onlyRecipient'    => true,
                    'return'           => true,
                    'sameDayDelivery'  => true,
                    'signature'        => true,
                ],
            ]);
    },
]);
