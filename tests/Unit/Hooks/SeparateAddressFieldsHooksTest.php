<?php
/** @noinspection StaticClosureCanBeUsedInspection */

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Hooks;

use MyParcelNL\Pdk\Facade\Pdk;
use MyParcelNL\WooCommerce\Tests\Uses\UsesMockWcPdkInstance;
use function MyParcelNL\Pdk\Tests\usesShared;

usesShared(new UsesMockWcPdkInstance());

it('adds the separate address fields to the billing fields', function () {
    /** @var SeparateAddressFieldsHooks $hook */
    $hook = Pdk::get(SeparateAddressFieldsHooks::class);

    $fields = $hook->extendBillingFields([]);

    expect($fields)
        ->toHaveKeys(['billing_street_name', 'billing_house_number', 'billing_house_number_suffix'])
        ->and($fields['billing_street_name'])
        ->toHaveKeys(['class', 'label', 'priority'])
        ->and($fields['billing_house_number']['type'])
        ->toBe('number');
});

it('preserves the locale-applied required and hidden values on the separate address fields', function () {
    /** @var SeparateAddressFieldsHooks $hook */
    $hook = Pdk::get(SeparateAddressFieldsHooks::class);

    // WooCommerce applies the country locale before the woocommerce_billing_fields filter runs, so the
    // fields arrive here carrying `required`/`hidden`. Regression test for #1805: these values were
    // replaced by the filter, which disabled the server-side required validation.
    $fields = $hook->extendBillingFields([
        'billing_street_name'         => ['required' => true, 'hidden' => false],
        'billing_house_number'        => ['required' => true, 'hidden' => false],
        'billing_house_number_suffix' => ['required' => false, 'hidden' => false],
    ]);

    expect($fields['billing_street_name']['required'])
        ->toBeTrue()
        ->and($fields['billing_house_number']['required'])
        ->toBeTrue()
        ->and($fields['billing_house_number_suffix']['required'])
        ->toBeFalse()
        ->and($fields['billing_street_name']['hidden'])
        ->toBeFalse()
        // Presentation attributes must still be added alongside the preserved values.
        ->and($fields['billing_street_name'])
        ->toHaveKeys(['class', 'label', 'priority']);
});

it('preserves the locale-applied values on the shipping fields as well', function () {
    /** @var SeparateAddressFieldsHooks $hook */
    $hook = Pdk::get(SeparateAddressFieldsHooks::class);

    $fields = $hook->extendShippingFields([
        'shipping_street_name'  => ['required' => true, 'hidden' => false],
        'shipping_house_number' => ['required' => true, 'hidden' => false],
    ]);

    expect($fields['shipping_street_name']['required'])
        ->toBeTrue()
        ->and($fields['shipping_house_number']['required'])
        ->toBeTrue();
});

it('leaves unrelated fields untouched', function () {
    /** @var SeparateAddressFieldsHooks $hook */
    $hook = Pdk::get(SeparateAddressFieldsHooks::class);

    $postcode = ['label' => 'Postcode', 'required' => true];

    $fields = $hook->extendBillingFields(['billing_postcode' => $postcode]);

    expect($fields['billing_postcode'])->toBe($postcode);
});
