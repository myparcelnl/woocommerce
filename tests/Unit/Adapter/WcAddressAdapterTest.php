<?php
/** @noinspection StaticClosureCanBeUsedInspection,PhpUnhandledExceptionInspection */

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Adapter;

use MyParcelNL\Pdk\App\Cart\Model\PdkCart;
use MyParcelNL\Pdk\Facade\Pdk;
use MyParcelNL\WooCommerce\Tests\Uses\UsesMockWcPdkInstance;
use WC_Cart;
use WC_Customer;
use WC_Order;
use function MyParcelNL\Pdk\Tests\usesShared;
use function MyParcelNL\WooCommerce\Tests\wpFactory;
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
                'shipping_state'      => 'NL-NH',
            ],
            'meta'        => [],
        ],

        '2 letter state' => [
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

        'unrecognized state' => [
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
                'shipping_state'      => 'Noord-Holland',
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
                'shipping_state'      => 'NL-NH',
            ],
            'meta'        => [
                '_shipping_street_name'         => 'Siriusdreef',
                '_shipping_house_number'        => '66',
                '_shipping_house_number_suffix' => '-68',
            ],
        ],

        'separate address fields with address_1 set' => [
            'addressType' => 'shipping',
            'address'     => [
                'billing_email'       => 'test@test.com',
                'billing_phone'       => '0612345678',
                'shipping_address_1'  => 'Siriusdreef 66',
                'shipping_address_2'  => '',
                'shipping_city'       => 'Hoofddorp',
                'shipping_company'    => 'MyParcel',
                'shipping_country'    => 'NL',
                'shipping_first_name' => 'Sirius',
                'shipping_last_name'  => 'Parcel',
                'shipping_postcode'   => '2132WT',
                'shipping_state'      => 'NL-NH',
            ],
            'meta'        => [
                '_shipping_street_name'         => 'Siriusdreef',
                '_shipping_house_number'        => '66',
                '_shipping_house_number_suffix' => '-68',
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
                'shipping_state'      => 'NL-NH',
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

        'german address' => [
            'addressType' => 'shipping',
            'address'     => [
                'shipping_email'      => 'de@myparcel.nl',
                'shipping_phone'      => '0698765432',
                'shipping_address_1'  => 'Straßmannstraße 2',
                'shipping_address_2'  => '',
                'shipping_city'       => 'Berlin',
                'shipping_country'    => 'DE',
                'shipping_first_name' => 'Rolli',
                'shipping_last_name'  => 'Rita',
                'shipping_postcode'   => '10249',
                'shipping_state'      => 'DE-BE',
            ],
            'meta'        => [],
        ],
    ];
});

dataset('fullStreetAddresses', function () {
    $baseAddress = [
        'billing_email'       => 'test@test.com',
        'billing_phone'       => '0612345678',
        'shipping_address_2'  => '',
        'shipping_city'       => 'Hoofddorp',
        'shipping_company'    => 'MyParcel',
        'shipping_country'    => 'NL',
        'shipping_first_name' => 'Felicia',
        'shipping_last_name'  => 'Parcel',
        'shipping_postcode'   => '2132JE',
    ];

    return [
        'NL address' => [
            'address'    => array_merge($baseAddress, [
                'shipping_address_1' => 'Antareslaan 31',
            ]),
            'expected'   => [
                'street' => 'Antareslaan',
                'number' => '31',
            ],
            'absentKeys' => [],
        ],

        'NL address with number suffix' => [
            'address'    => array_merge($baseAddress, [
                'shipping_address_1' => 'Siriusdreef 66 b',
            ]),
            'expected'   => [
                'street'       => 'Siriusdreef',
                'number'       => '66',
                'numberSuffix' => 'b',
            ],
            'absentKeys' => [],
        ],

        'NL address with address_2' => [
            'address'    => array_merge($baseAddress, [
                'shipping_address_1' => 'Antareslaan 31',
                'shipping_address_2' => 'Unit 4',
            ]),
            'expected'   => [
                'street'               => 'Antareslaan',
                'number'               => '31',
                'streetAdditionalInfo' => 'Unit 4',
            ],
            'absentKeys' => [],
        ],

        'BE address with box number' => [
            'address'    => array_merge($baseAddress, [
                'shipping_address_1' => 'Adriaan Brouwerstraat 16 bus 2',
                'shipping_city'      => 'Antwerpen',
                'shipping_country'   => 'BE',
                'shipping_postcode'  => '2000',
            ]),
            'expected'   => [
                'street'    => 'Adriaan Brouwerstraat',
                'number'    => '16',
                'boxNumber' => '2',
            ],
            'absentKeys' => [],
        ],

        'DE address is not split' => [
            'address'    => array_merge($baseAddress, [
                'shipping_address_1' => 'Straßmannstraße 2',
                'shipping_city'      => 'Berlin',
                'shipping_country'   => 'DE',
                'shipping_postcode'  => '10249',
            ]),
            'expected'   => [
                'address1' => 'Straßmannstraße 2',
            ],
            'absentKeys' => ['street', 'number'],
        ],

        'NL address without house number falls back to address_1' => [
            'address'    => array_merge($baseAddress, [
                'shipping_address_1' => 'Antareslaan',
            ]),
            'expected'   => [
                'address1' => 'Antareslaan',
            ],
            'absentKeys' => ['street', 'number'],
        ],
    ];
});

it('splits address_1 into separate address fields for orders without separate address data', function (
    array $address,
    array $expected,
    array $absentKeys
) {
    /** @var WcAddressAdapter $adapter */
    $adapter = Pdk::get(WcAddressAdapter::class);

    $order = wpFactory(WC_Order::class)
        ->fromScratch()
        ->with(array_merge($address, ['id' => 1244, 'meta' => []]))
        ->make();

    $result = $adapter->fromWcOrder($order, 'shipping');

    foreach ($expected as $key => $value) {
        expect($result[$key] ?? null)->toBe($value);
    }

    foreach ($absentKeys as $key) {
        expect($result)->not->toHaveKey($key);
    }
})->with('fullStreetAddresses');

it('creates address from WC_Order', function (string $addressType, array $address, array $meta) {
    /** @var WcAddressAdapter $adapter */
    $adapter = Pdk::get(WcAddressAdapter::class);

    $order = wpFactory(WC_Order::class)
        ->fromScratch()
        ->with(array_merge($address, ['id' => 1233, 'meta' => $meta]))
        ->make();

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

it('passes the cart company through to the pdk cart as isBusiness, without storing the company', function (
    ?string $company,
    bool    $expected
) {
    /** @var WcAddressAdapter $adapter */
    $adapter = Pdk::get(WcAddressAdapter::class);

    $address = [
        'shipping_address_1' => 'Antareslaan 31',
        'shipping_city'      => 'Hoofddorp',
        'shipping_country'   => 'NL',
        'shipping_postcode'  => '2132JE',
    ];

    if (null !== $company) {
        $address['shipping_company'] = $company;
    }

    $cart   = new WC_Cart(['customer' => new WC_Customer($address)]);
    $result = $adapter->fromWcCart($cart, 'shipping');

    // Build the cart the same way the repository does — the bare PDK Address derives isBusiness
    // from the company and drops the name, so no personal data is stored on the PII-free cart.
    $shippingAddress = (new PdkCart(['shippingMethod' => ['shippingAddress' => $result]]))
        ->shippingMethod->shippingAddress;

    expect($shippingAddress->isBusiness)->toBe($expected)
        ->and($shippingAddress->toArray())->not->toHaveKey('company');
})->with([
    'business (company entered)' => ['Acme B.V.', true],
    'consumer (no company)'      => [null, false],
]);
