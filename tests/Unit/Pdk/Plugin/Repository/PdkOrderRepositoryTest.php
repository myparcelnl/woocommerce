<?php
/** @noinspection PhpUnhandledExceptionInspection,StaticClosureCanBeUsedInspection */

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Tests\Unit\Pdk\Plugin\Repository;

use InvalidArgumentException;
use MyParcelNL\Pdk\App\Order\Contract\PdkOrderRepositoryInterface;
use MyParcelNL\Pdk\App\Order\Contract\PdkProductRepositoryInterface;
use MyParcelNL\Pdk\App\Order\Model\PdkOrder;
use MyParcelNL\Pdk\Facade\Pdk;
use MyParcelNL\WooCommerce\Pdk\Plugin\Repository\PdkOrderRepository;
use MyParcelNL\WooCommerce\Pdk\Product\Repository\WcPdkProductRepository;
use MyParcelNL\WooCommerce\Tests\Uses\UsesMockWcPdkInstance;
use Psr\Log\LoggerInterface;
use WC_DateTime;
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

it('creates a valid pdk order', function (array $input) {
    /** @var \MyParcelNL\Pdk\App\Order\Contract\PdkOrderRepositoryInterface $orderRepository */
    $orderRepository = Pdk::get(PdkOrderRepositoryInterface::class);
    /** @var \MyParcelNL\Pdk\Tests\Bootstrap\MockLogger $logger */
    $logger = Pdk::get(LoggerInterface::class);

    $wcOrder  = new WC_Order($input);
    $pdkOrder = $orderRepository->get($wcOrder);

    expect($logger->getLogs())->toBe([]);

    assertMatchesJsonSnapshot(json_encode($pdkOrder->toArrayWithoutNull(), JSON_PRETTY_PRINT));
})->with([
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

it('gets order via various inputs', function ($input) {
    /** @var \MyParcelNL\Pdk\App\Order\Contract\PdkOrderRepositoryInterface $orderRepository */
    $orderRepository = Pdk::get(PdkOrderRepositoryInterface::class);

    $pdkOrder = $orderRepository->get($input);

    expect($pdkOrder)->toBeInstanceOf(PdkOrder::class);
})->with([
    'string id' => ['1'],
    'int id'    => [1],
    'wc order'  => [new WC_Order(getOrderDefaults())],
    'post'      => [(object) ['ID' => 1]],
]);

it('handles errors', function ($input) {
    /** @var \MyParcelNL\Pdk\App\Order\Contract\PdkOrderRepositoryInterface $orderRepository */
    $orderRepository = Pdk::get(PdkOrderRepositoryInterface::class);

    $orderRepository->get($input);
})
    ->throws(InvalidArgumentException::class)
    ->with([
            'unrecognized object input' => function () {
                return (object) ['foo' => 'bar'];
            },

            'array' => function () {
                return ['id' => 1];
            },
        ]
    );
