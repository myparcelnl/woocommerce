<?php
/** @noinspection StaticClosureCanBeUsedInspection,PhpUnhandledExceptionInspection */

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Adapter;

use MyParcelNL\Pdk\App\Cart\Model\PdkCart;
use MyParcelNL\Pdk\Base\Model\Address;
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

it('allows filtering address fields through the wcAddressFields filter', function () {
    /** @var WcAddressAdapter $adapter */
    $adapter = Pdk::get(WcAddressAdapter::class);

    $address = [
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
    ];

    $order = wpFactory(WC_Order::class)
        ->fromScratch()
        ->with(array_merge($address, ['id' => 1234, 'meta' => []]))
        ->make();

    $filter = function (array $fields, $object, string $addressType) {
        expect($object)->toBeInstanceOf(WC_Order::class)
            ->and($addressType)->toBe('shipping');

        $fields['company'] = 'Filtered Company';

        return $fields;
    };

    add_filter('mpwc_checkout_wc_address_fields', $filter, 10, 3);

    $result = $adapter->fromWcOrder($order, 'shipping');

    expect($result['company'])->toBe('Filtered Company');

    remove_filter('mpwc_checkout_wc_address_fields', $filter, 10);
});

dataset('filteredAddresses', function () {
    // Meta keys get the "_{addressType}_" prefix in the test.
    return [
        'address from the address widget' => [
            function () {
                return [
                    Pdk::get('checkoutAddressHiddenInputName') => json_encode([
                        'city'        => 'Hoofddorp',
                        'countryCode' => 'NL',
                        'houseNumber' => '31',
                        'postalCode'  => '2132JE',
                        'street'      => 'Antareslaan',
                    ]),
                ];
            },
            ['company' => 'Filtered Company'],
            ['company' => 'Filtered Company'],
        ],

        'street fields on an NL order with separate address fields' => [
            [
                'street_name'  => 'Antareslaan',
                'house_number' => '31',
            ],
            ['street' => 'Siriusdreef', 'number' => '66', 'numberSuffix' => 'b'],
            ['street' => 'Siriusdreef', 'number' => '66', 'numberSuffix' => 'b'],
        ],

        'address1 on an NL order is split after filtering' => [
            [],
            ['address1' => 'Siriusdreef 66 b'],
            ['street' => 'Siriusdreef', 'number' => '66', 'numberSuffix' => 'b'],
        ],

        'eori and vat numbers' => [
            [
                'eori_number' => 'NL123456789',
                'vat_number'  => 'NL123456789B01',
            ],
            ['eoriNumber' => 'NL987654321', 'vatNumber' => 'NL987654321B01'],
            ['eoriNumber' => 'NL987654321', 'vatNumber' => 'NL987654321B01'],
        ],
    ];
});

dataset('filterAddressTypes', ['shipping', 'billing']);

/**
 * Prefix WooCommerce address fields and order meta keys with the address type.
 */
function addressTypeFields(string $addressType, array $fields, array $meta = []): array
{
    $prefixed = [
        'id'            => 1235,
        'billing_email' => 'test@test.com',
        'billing_phone' => '0612345678',
        'meta'          => [],
    ];

    foreach ($fields as $key => $value) {
        $prefixed["{$addressType}_{$key}"] = $value;
    }

    foreach ($meta as $key => $value) {
        $prefixed['meta']["_{$addressType}_{$key}"] = $value;
    }

    return $prefixed;
}

it('uses the fields that the wcAddressFields filter returns', function (
    array  $meta,
    array  $filtered,
    array  $expected,
    string $addressType
) {
    /** @var WcAddressAdapter $adapter */
    $adapter = Pdk::get(WcAddressAdapter::class);

    $order = wpFactory(WC_Order::class)
        ->fromScratch()
        ->with(addressTypeFields($addressType, [
            'address_1'  => 'Antareslaan 31',
            'address_2'  => '',
            'city'       => 'Hoofddorp',
            'company'    => 'MyParcel',
            'country'    => 'NL',
            'first_name' => 'Felicia',
            'last_name'  => 'Parcel',
            'postcode'   => '2132JE',
        ], $meta))
        ->make();

    add_filter('mpwc_checkout_wc_address_fields', function (array $fields) use ($filtered) {
        return array_merge($fields, $filtered);
    });

    $result = $adapter->fromWcOrder($order, $addressType);

    foreach ($expected as $key => $value) {
        expect($result[$key] ?? null)->toBe($value);
    }
})->with('filteredAddresses')->with('filterAddressTypes');

it('sets isBusiness when the filter adds a company to an order without one', function (string $addressType) {
    /** @var WcAddressAdapter $adapter */
    $adapter = Pdk::get(WcAddressAdapter::class);

    $order = wpFactory(WC_Order::class)
        ->fromScratch()
        ->with(addressTypeFields($addressType, [
            'address_1' => 'Straßmannstraße 2',
            'city'      => 'Berlin',
            'country'   => 'DE',
            'postcode'  => '10249',
        ]))
        ->make();

    add_filter('mpwc_checkout_wc_address_fields', function (array $fields) {
        return array_merge($fields, ['company' => 'MyParcel']);
    });

    $address = new Address($adapter->fromWcOrder($order, $addressType));

    expect($address->isBusiness)->toBeTrue();
})->with('filterAddressTypes');

it('ignores an isBusiness set by the filter that does not match the company', function (
    ?string $company,
    bool    $filteredIsBusiness,
    string  $addressType
) {
    /** @var WcAddressAdapter $adapter */
    $adapter = Pdk::get(WcAddressAdapter::class);

    $order = wpFactory(WC_Order::class)
        ->fromScratch()
        ->with(addressTypeFields($addressType, [
            'address_1' => 'Straßmannstraße 2',
            'city'      => 'Berlin',
            'company'   => $company,
            'country'   => 'DE',
            'postcode'  => '10249',
        ]))
        ->make();

    add_filter('mpwc_checkout_wc_address_fields', function (array $fields) use ($filteredIsBusiness) {
        return array_merge($fields, ['isBusiness' => $filteredIsBusiness]);
    });

    $address = new Address($adapter->fromWcOrder($order, $addressType));

    expect($address->isBusiness)->toBe(! $filteredIsBusiness);
})->with([
    'company, filter sets false'   => ['MyParcel', false],
    'no company, filter sets true' => [null, true],
])->with('filterAddressTypes');
