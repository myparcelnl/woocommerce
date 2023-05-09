<?php
/** @noinspection StaticClosureCanBeUsedInspection,PhpUnhandledExceptionInspection */

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Factory;

use MyParcelNL\Pdk\Facade\Pdk;
use MyParcelNL\WooCommerce\Tests\Uses\UsesMockWcPdkInstance;
use WC_Cart;
use WC_Customer;
use WC_Order;
use function MyParcelNL\Pdk\Tests\usesShared;
use function Spatie\Snapshots\assertMatchesSnapshot;

usesShared(new UsesMockWcPdkInstance());

dataset('addresses', function () {
    return [
        'default' => [
            'addressType' => 'shipping',
            'address'     => [
                'billing_email'       => 'test@test.com',
                'billing_phone'       => '0612345678',
                'shipping_address_1'  => 'Antareslaan 31',
                'shipping_address_2'  => '',
                'shipping_city'       => 'Hoofddorp',
                'shipping_company'    => 'MyParcel',
                'shipping_country'    => 'NL',
                'shipping_first_name' => 'Felicia',
                'shipping_last_name'  => 'Parcel',
                'shipping_postcode'   => '2132JE',
                'shipping_state'      => 'NH',
            ],
            'meta'        => [],
        ],

        'separate address fields' => [
            'addressType' => 'shipping',
            'address'     => [
                'billing_email'       => 'test@test.com',
                'billing_phone'       => '0612345678',
                'shipping_address_1'  => '',
                'shipping_address_2'  => '',
                'shipping_city'       => 'Hoofddorp',
                'shipping_company'    => 'MyParcel',
                'shipping_country'    => 'NL',
                'shipping_first_name' => 'Sirius',
                'shipping_last_name'  => 'Parcel',
                'shipping_postcode'   => '2132WT',
                'shipping_state'      => 'NH',
            ],
            'meta'        => [
                '_shipping_street'        => 'Siriusdreef',
                '_shipping_number'        => '66',
                '_shipping_number_suffix' => '-68',
            ],
        ],

        'vat fields' => [
            'addressType' => 'shipping',
            'address'     => [
                'shipping_address_1'  => 'Hoofdweg 679',
                'shipping_address_2'  => '',
                'shipping_city'       => 'Hoofddorp',
                'shipping_company'    => 'MyParcel',
                'shipping_country'    => 'NL',
                'shipping_first_name' => 'Eori',
                'shipping_last_name'  => 'Parcel',
                'shipping_postcode'   => '2131 BC',
                'shipping_state'      => 'NH',
            ],
            'meta'        => [
                '_shipping_vat_number'  => 'NL123456789B01',
                '_shipping_eori_number' => 'NL123456789',
            ],
        ],

        'billing address' => [
            'addressType' => 'billing',
            'address'     => [
                'billing_email'      => 'bill@myparcel.nl',
                'billing_phone'      => '0698765432',
                'billing_address_1'  => 'Adriaan Brouwerstraat 16',
                'billing_address_2'  => '',
                'billing_city'       => 'Antwerpen',
                'billing_company'    => 'MyParcel',
                'billing_country'    => 'BE',
                'billing_first_name' => 'Bill',
                'billing_last_name'  => 'Parcel',
                'billing_postcode'   => '2000',
            ],
            'meta'        => [],
        ],
    ];
});

it('creates address from WC_Order', function (string $addressType, array $address, array $meta) {
    /** @var WcAddressAdapter $adapter */
    $adapter = Pdk::get(WcAddressAdapter::class);

    $order = new WC_Order(
        array_merge($address, [
            'id'   => 1233,
            'meta' => $meta,
        ])
    );

    assertMatchesSnapshot($adapter->fromWcOrder($order, $addressType));
})->with('addresses');

it('creates address from WC_Customer', function (string $addressType, array $address) {
    /** @var WcAddressAdapter $adapter */
    $adapter = Pdk::get(WcAddressAdapter::class);

    $customer = new WC_Customer($address);

    assertMatchesSnapshot($adapter->fromWcCustomer($customer, $addressType));
})->with('addresses');

it('creates address from WC_Cart', function (string $addressType, array $address) {
    /** @var WcAddressAdapter $adapter */
    $adapter = Pdk::get(WcAddressAdapter::class);

    $cart = new WC_Cart(['customer' => new WC_Customer($address)]);

    assertMatchesSnapshot($adapter->fromWcCart($cart, $addressType));
})->with('addresses');
