<?php
/** @noinspection PhpUnhandledExceptionInspection,StaticClosureCanBeUsedInspection */

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Tests\Unit\Pdk\Plugin\Repository;

use DateTimeImmutable;
use MyParcelNL\Pdk\App\Order\Contract\PdkOrderRepositoryInterface;
use MyParcelNL\Pdk\App\Order\Contract\PdkProductRepositoryInterface;
use MyParcelNL\Pdk\Facade\Pdk;
use MyParcelNL\WooCommerce\Pdk\Plugin\Repository\PdkOrderRepository;
use MyParcelNL\WooCommerce\Pdk\Product\Repository\WcPdkProductRepository;
use MyParcelNL\WooCommerce\Tests\Uses\UsesMockWcPdkInstance;
use WC_Order;
use WC_Order_Item;
use WC_Order_Item_Product;
use WC_Product;
use function DI\autowire;
use function MyParcelNL\Pdk\Tests\usesShared;
use function Spatie\Snapshots\assertMatchesJsonSnapshot;

usesShared(
    new UsesMockWcPdkInstance([
        PdkProductRepositoryInterface::class => autowire(WcPdkProductRepository::class),
        PdkOrderRepositoryInterface::class   => autowire(PdkOrderRepository::class),
    ])
);

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
        'date_created'        => new DateTimeImmutable('2021-01-01 18:03:41'),
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

function createOrderMeta(array $deliveryOptions = []): array
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

it('creates a valid pdk order', function (array $input) {
    /** @var \MyParcelNL\Pdk\App\Order\Contract\PdkOrderRepositoryInterface $orderRepository */
    $orderRepository = Pdk::get(PdkOrderRepositoryInterface::class);

    $wcOrder  = new WC_Order($input);
    $pdkOrder = $orderRepository->get($wcOrder);

    assertMatchesJsonSnapshot(json_encode($pdkOrder->toArray(), JSON_PRETTY_PRINT));
})->with([
    'simple order' => function () {
        return getOrderDefaults();
    },

    'order with saved delivery options' => function () {
        return getOrderDefaults() + createOrderMeta();
    },

    'order with label description CUSTOMER_NOTE' => function () {
        return getOrderDefaults() + createOrderMeta([
                'shipmentOptions' => ['labelDescription' => 'CUSTOMER_NOTE: [CUSTOMER_NOTE]'],
            ]);
    },

    'order with label description ORDER_NR' => function () {
        return getOrderDefaults() + createOrderMeta([
                'shipmentOptions' => ['labelDescription' => 'ORDER_NR: [ORDER_NR]'],
            ]);
    },

    'order with label description PRODUCT_ID' => function () {
        return getOrderDefaults() + createOrderMeta([
                'shipmentOptions' => ['labelDescription' => 'PRODUCT_ID: [PRODUCT_ID]'],
            ]);
    },

    'order with label description PRODUCT_NAME' => function () {
        return getOrderDefaults() + createOrderMeta([
                'shipmentOptions' => ['labelDescription' => 'PRODUCT_NAME: [PRODUCT_NAME]'],
            ]);
    },

    'order with label description PRODUCT_QTY' => function () {
        return getOrderDefaults() + createOrderMeta([
                'shipmentOptions' => ['labelDescription' => 'PRODUCT_QTY: [PRODUCT_QTY]'],
            ]);
    },

    'order with label description PRODUCT_SKU'           => function () {
        return getOrderDefaults() + createOrderMeta([
                'shipmentOptions' => ['labelDescription' => 'PRODUCT_SKU: [PRODUCT_SKU]'],
            ]);
    },
    'order with multiple label description placeholders' => function () {
        return getOrderDefaults() + createOrderMeta([
                'shipmentOptions' => ['labelDescription' => '[ORDER_NR] | [PRODUCT_ID] | [PRODUCT_NAME] | [PRODUCT_QTY] | [PRODUCT_SKU]'],
            ]);
    },

    'order with all shipment options' => function () {
        return getOrderDefaults() + createOrderMeta([
                'shipmentOptions' => [
                    'ageCheck'         => true,
                    'insurance'        => 50000,
                    'labelDescription' => 'hello',
                    'largeFormat'      => true,
                    'onlyRecipient'    => true,
                    'return'           => true,
                    'sameDayDelivery'  => true,
                    'signature'        => true,
                ],
            ]);
    },
]);
