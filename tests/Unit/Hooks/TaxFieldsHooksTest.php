<?php
/** @noinspection StaticClosureCanBeUsedInspection */

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Hooks;

use MyParcelNL\Pdk\Facade\Pdk;
use MyParcelNL\WooCommerce\Tests\Uses\UsesMockWcPdkInstance;
use function MyParcelNL\Pdk\Tests\usesShared;

usesShared(new UsesMockWcPdkInstance());

it('applies hooks correctly', function () {
    /** @var TaxFieldsHooks $hook */
    $hook = Pdk::get(TaxFieldsHooks::class);

    $hook->apply();

    expect($hook)->toBeInstanceOf(TaxFieldsHooks::class);
});

it('implements WooCommerceInitCallbacksInterface', function () {
    /** @var TaxFieldsHooks $hook */
    $hook = Pdk::get(TaxFieldsHooks::class);

    expect($hook)->toBeInstanceOf(\MyParcelNL\WooCommerce\Hooks\Contract\WooCommerceInitCallbacksInterface::class);
});

it('has onWoocommerceInit method', function () {
    /** @var TaxFieldsHooks $hook */
    $hook = Pdk::get(TaxFieldsHooks::class);

    expect(method_exists($hook, 'onWoocommerceInit'))->toBe(true);
});

it('has registerAdditionalBlocksCheckoutFields method', function () {
    /** @var TaxFieldsHooks $hook */
    $hook = Pdk::get(TaxFieldsHooks::class);

    expect(method_exists($hook, 'registerAdditionalBlocksCheckoutFields'))->toBe(true);
});

it('has storeTaxFieldsForBlocksCheckout method', function () {
    /** @var TaxFieldsHooks $hook */
    $hook = Pdk::get(TaxFieldsHooks::class);

    expect(method_exists($hook, 'storeTaxFieldsForBlocksCheckout'))->toBe(true);
});
