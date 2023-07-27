<?php

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Tests;

use MyParcelNL\Pdk\Facade\Pdk;
use WC_DateTime;
use WC_Order_Item;
use WC_Order_Item_Product;
use WC_Product;

function getOrderDefaults(): array
{
    return [
        'id'                  => 1,
        'billing_address_1'   => 'Antareslaan 31',
        'billing_address_2'   => '',
        'billing_city'        => 'Hoofddorp',
        'billing_company'     => 'MyParcel',
        'billing_country'     => 'NL',
        'billing_email'       => 'test@myparcel.nl',
        'billing_first_name'  => 'John',
        'billing_last_name'   => 'Doe',
        'billing_phone'       => '0612345678',
        'billing_postcode'    => '2132 JE',
        'billing_state'       => '',
        'customer_note'       => 'This is a test order',
        'date_created'        => new WC_DateTime('2021-01-01 18:03:41'),
        'shipping_address_1'  => 'Antareslaan 31',
        'shipping_address_2'  => '',
        'shipping_city'       => 'Hoofddorp',
        'shipping_company'    => 'MyParcel',
        'shipping_country'    => 'NL',
        'shipping_email'      => 'test@myparcel.nl',
        'shipping_first_name' => 'John',
        'shipping_last_name'  => 'Doe',
        'shipping_phone'      => '0612345678',
        'shipping_postcode'   => '2132 JE',
        'shipping_state'      => '',
        'status'              => 'pending',
        'items'               => [
            new WC_Order_Item_Product([
                'product'  => new WC_Product([
                    'id'             => 3214,
                    'name'           => 'Test product',
                    'sku'            => 'WVS-0001',
                    'needs_shipping' => true,
                    'price'          => 500,
                    'weight'         => 1000,
                    'length'         => 100,
                    'width'          => 80,
                    'height'         => 50,
                    'meta'           => [
                        '_pest_product_country_of_origin'        => 'NL',
                        '_pest_product_customs_code'             => '1234',
                        '_pest_product_disable_delivery_options' => false,
                        '_pest_product_drop_off_delay'           => 1,
                        '_pest_product_export_age_check'         => -1,
                        '_pest_product_export_insurance'         => -1,
                        '_pest_product_export_large_format'      => -1,
                        '_pest_product_export_only_recipient'    => -1,
                        '_pest_product_export_signature'         => -1,
                        '_pest_product_fit_in_digital_stamp'     => 2,
                        '_pest_product_fit_in_mailbox'           => 4,
                        '_pest_product_package_type'             => 'mailbox',
                        '_pest_product_return_shipments'         => 0,
                    ],
                ]),
                'quantity' => 2,
                'total'    => 1000,
            ]),
            new WC_Order_Item([
                'product'  => null,
                'quantity' => 1,
                'total'    => 1000,
            ]),
            new WC_Order_Item_Product([
                'product'  => new WC_Product([
                    'id'             => 2324,
                    'name'           => 'Test digital product',
                    'sku'            => 'WVS-0002',
                    'needs_shipping' => false,
                ]),
                'quantity' => 2,
                'total'    => 1000,
            ]),
        ],
    ];
}

function createDeliveryOptionsMeta(array $deliveryOptions = []): array
{
    return [
        'meta' => [
            Pdk::get('metaKeyOrderData') => [
                'deliveryOptions' => array_replace_recursive([
                    'carrier'         => 'dhlforyou',
                    'deliveryType'    => 'morning',
                    'date'            => '2024-12-31 12:00:00',
                    'shipmentOptions' => [
                        'signature' => true,
                    ],
                ], $deliveryOptions),
            ],
        ],
    ];
}

function createNotesMeta(array $notes = []): array
{
    return [
        'meta' => [
            Pdk::get('metaKeyOrderData') => [
                'notes' => $notes,
            ],
        ],
    ];
}
