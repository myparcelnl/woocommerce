<?php
/** @noinspection StaticClosureCanBeUsedInspection */

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Hooks;

use MyParcelNL\Pdk\App\Options\Definition\SignatureDefinition;
use MyParcelNL\Pdk\Base\Support\SettingKey;
use MyParcelNL\Pdk\Facade\Pdk;
use MyParcelNL\Pdk\Settings\Contract\PdkSettingsRepositoryInterface;
use MyParcelNL\Pdk\Settings\Model\ProductSettings;
use MyParcelNL\Pdk\Shipment\Model\DeliveryOptions;
use MyParcelNL\Pdk\Tests\Bootstrap\TestBootstrapper;
use MyParcelNL\Pdk\Types\Service\TriStateService;
use MyParcelNL\Sdk\Client\Generated\CoreApi\Model\RefTypesDeliveryTypeV2;
use MyParcelNL\WooCommerce\Tests\Uses\UsesMockWcPdkInstance;
use WC_Cart;
use WC_Product;
use function MyParcelNL\Pdk\Tests\usesShared;
use function MyParcelNL\WooCommerce\Tests\wpFactory;

usesShared(new UsesMockWcPdkInstance());

const SESSION_KEY = '_myparcelcom_delivery_options';

/*
 * Shadow filter_input_array() within this namespace so the classic-checkout path of
 * CartFeesHooks::resolveDeliveryOptionsData() reads $_POST. The real filter_input_array(INPUT_POST)
 * reads the SAPI request body, which is always empty under the CLI test runner, so tests could
 * otherwise never exercise the posted-selection branch. For non-POST input types it delegates to
 * the genuine function. Behaviour is identical to the real one when $_POST is empty (returns null).
 */
if (! function_exists(__NAMESPACE__ . '\\filter_input_array')) {
    function filter_input_array($type, $options = null, $addEmpty = true)
    {
        if (INPUT_POST === $type) {
            return $_POST ?: null;
        }

        return \filter_input_array($type, $options, $addEmpty);
    }
}

/**
 * Builds a cart containing one product so it qualifies (or not) for delivery options. The fee guard
 * resolves WC_Cart -> PdkCart and reads PdkShippingMethod::hasDeliveryOptions, which is true only for
 * a deliverable (non-virtual) product that doesn't have delivery options disabled. Each call uses a
 * fresh product id so the per-id PdkProduct cache can't bleed between tests.
 */
function cartWithProduct(bool $needsShipping = true, array $productSettings = [], float $shippingTotal = 0.0): WC_Cart
{
    static $nextId = 7100;

    $factory = wpFactory(WC_Product::class)
        ->withId($nextId++)
        ->withNeedsShipping($needsShipping);

    if ($productSettings) {
        $factory = $factory->withMeta([Pdk::get('metaKeyProductSettings') => $productSettings]);
    }

    $cart                = new WC_Cart(['shipping_total' => $shippingTotal]);
    $cart->cart_contents = [
        'item' => [
            'data'              => $factory->make(),
            'quantity'          => 1,
            'line_subtotal'     => 0.0,
            'line_subtotal_tax' => 0.0,
        ],
    ];

    return $cart;
}

beforeEach(function () {
    $_POST = [];

    TestBootstrapper::hasAccount();

    // Return early in WcTaxService::getShippingTaxClass() instead of hitting WC_Tax.
    update_option('woocommerce_shipping_tax_class', 'standard');

    // Configure POSTNL prices so the fee service produces fees.
    /** @var PdkSettingsRepositoryInterface $settingsRepo */
    $settingsRepo = Pdk::get(PdkSettingsRepositoryInterface::class);
    $settingsRepo->store(Pdk::get('createSettingsKey')('carrier'), [
        'POSTNL' => [
            SettingKey::priceDeliveryType(RefTypesDeliveryTypeV2::STANDARD) => 4.5,
            (new SignatureDefinition())->getPriceSettingsKey()             => 1.1,
        ],
    ]);
});

afterEach(function () {
    // Don't leak posted data into other tests in this namespace that share the filter_input_array shadow.
    $_POST = [];
});

it('adds delivery-options fees from the blocks session when no post data is present', function () {
    WC()->session->set(SESSION_KEY, [
        'carrier'         => 'postnl',
        'shipmentOptions' => [
            (new SignatureDefinition())->getShipmentOptionsKey() => true,
        ],
    ]);

    $cart = cartWithProduct();

    /** @var CartFeesHooks $hooks */
    $hooks = Pdk::get(CartFeesHooks::class);
    $hooks->calculateDeliveryOptionsFees($cart);

    expect($cart->fees)->toHaveCount(2)
        ->and(array_column($cart->fees, 'amount'))->toBe([4.5, 1.1]);
});

it('still adds fees when a regular shipping method is chosen', function () {
    WC()->session->set(SESSION_KEY, [
        'carrier'         => 'postnl',
        'shipmentOptions' => [
            (new SignatureDefinition())->getShipmentOptionsKey() => true,
        ],
    ]);
    WC()->session->set('chosen_shipping_methods', ['flat_rate:1']);

    $cart = cartWithProduct();

    /** @var CartFeesHooks $hooks */
    $hooks = Pdk::get(CartFeesHooks::class);
    $hooks->calculateDeliveryOptionsFees($cart);

    expect($cart->fees)->toHaveCount(2);
});

it('adds no fees when classic local pickup is chosen', function () {
    WC()->session->set(SESSION_KEY, [
        'carrier'         => 'postnl',
        'shipmentOptions' => [
            (new SignatureDefinition())->getShipmentOptionsKey() => true,
        ],
    ]);
    WC()->session->set('chosen_shipping_methods', ['local_pickup:1']);

    $cart = new WC_Cart();

    /** @var CartFeesHooks $hooks */
    $hooks = Pdk::get(CartFeesHooks::class);
    $hooks->calculateDeliveryOptionsFees($cart);

    expect($cart->fees)->toBeEmpty();
});

it('adds no fees when the blocks pickup_location method is chosen', function () {
    WC()->session->set(SESSION_KEY, [
        'carrier'         => 'postnl',
        'shipmentOptions' => [
            (new SignatureDefinition())->getShipmentOptionsKey() => true,
        ],
    ]);
    WC()->session->set('chosen_shipping_methods', ['pickup_location:0']);

    $cart = new WC_Cart();

    /** @var CartFeesHooks $hooks */
    $hooks = Pdk::get(CartFeesHooks::class);
    $hooks->calculateDeliveryOptionsFees($cart);

    expect($cart->fees)->toBeEmpty();
});

it('adds no fees when there is no selection in post data or session', function () {
    $cart = cartWithProduct();

    /** @var CartFeesHooks $hooks */
    $hooks = Pdk::get(CartFeesHooks::class);
    $hooks->calculateDeliveryOptionsFees($cart);

    expect($cart->fees)->toBeEmpty();
});

it('registers a store api update callback that stashes the selection in the session', function () {
    $GLOBALS['__mpwc_store_api_update_callbacks'] = [];

    /** @var CartFeesHooks $hooks */
    $hooks = Pdk::get(CartFeesHooks::class);
    $hooks->registerStoreApiUpdateCallback();

    $callback = $GLOBALS['__mpwc_store_api_update_callbacks']['myparcelcom-delivery-options'] ?? null;

    expect($callback)->toBeCallable();

    $callback(['carrier' => 'postnl']);

    expect(WC()->session->get(SESSION_KEY))->toBe(['carrier' => 'postnl']);
});

it('stashes the blocks-checkout selection from the request body at order placement', function () {
    WC()->session->set(SESSION_KEY, null);

    $request = new class {
        public function get_body(): string
        {
            return json_encode([
                'extensions' => [
                    'myparcelcom-delivery-options' => ['carrier' => 'postnl'],
                ],
            ]);
        }
    };

    /** @var CartFeesHooks $hooks */
    $hooks = Pdk::get(CartFeesHooks::class);
    $hooks->stashBlocksCheckoutSelection(WC()->customer, $request);

    expect(WC()->session->get(SESSION_KEY))->toBe(['carrier' => 'postnl']);
});

it('does not stash anything when the request body has no selection', function () {
    WC()->session->set(SESSION_KEY, null);

    $request = new class {
        public function get_body(): string
        {
            return '{}';
        }
    };

    /** @var CartFeesHooks $hooks */
    $hooks = Pdk::get(CartFeesHooks::class);
    $hooks->stashBlocksCheckoutSelection(WC()->customer, $request);

    expect(WC()->session->get(SESSION_KEY))->toBeNull();
});

it('clears the stashed selection from the session', function () {
    WC()->session->set(SESSION_KEY, ['carrier' => 'postnl']);

    /** @var CartFeesHooks $hooks */
    $hooks = Pdk::get(CartFeesHooks::class);
    $hooks->clearDeliveryOptionsSession();

    expect(WC()->session->get(SESSION_KEY))->toBeNull();
});

it('adds fees from the classic post_data selection (AJAX recalculation)', function () {
    // Classic checkout AJAX posts the form as a urlencoded string under `post_data`.
    $_POST['post_data'] = http_build_query([
        Pdk::get('checkoutHiddenInputName') => json_encode([
            'carrier'         => 'postnl',
            'shipmentOptions' => [
                (new SignatureDefinition())->getShipmentOptionsKey() => true,
            ],
        ]),
    ]);

    $cart = cartWithProduct();

    /** @var CartFeesHooks $hooks */
    $hooks = Pdk::get(CartFeesHooks::class);
    $hooks->calculateDeliveryOptionsFees($cart);

    expect($cart->fees)->toHaveCount(2)
        ->and(array_column($cart->fees, 'amount'))->toBe([4.5, 1.1]);
});

it('adds fees from the directly posted selection (classic checkout finalization)', function () {
    // On order placement the selection arrives directly under the hidden input name, not in post_data.
    $_POST[Pdk::get('checkoutHiddenInputName')] = json_encode([
        'carrier'         => 'postnl',
        'shipmentOptions' => [
            (new SignatureDefinition())->getShipmentOptionsKey() => true,
        ],
    ]);

    $cart = cartWithProduct();

    /** @var CartFeesHooks $hooks */
    $hooks = Pdk::get(CartFeesHooks::class);
    $hooks->calculateDeliveryOptionsFees($cart);

    expect($cart->fees)->toHaveCount(2)
        ->and(array_column($cart->fees, 'amount'))->toBe([4.5, 1.1]);
});

it('prefers the posted selection over the stashed session selection', function () {
    // Posted selection has signature → 2 fees.
    $_POST[Pdk::get('checkoutHiddenInputName')] = json_encode([
        'carrier'         => 'postnl',
        'shipmentOptions' => [
            (new SignatureDefinition())->getShipmentOptionsKey() => true,
        ],
    ]);
    // Session selection has no signature → only 1 fee; it must be ignored when post data is present.
    WC()->session->set(SESSION_KEY, ['carrier' => 'postnl']);

    $cart = cartWithProduct();

    /** @var CartFeesHooks $hooks */
    $hooks = Pdk::get(CartFeesHooks::class);
    $hooks->calculateDeliveryOptionsFees($cart);

    expect($cart->fees)->toHaveCount(2);
});

it('still adds fees when only some packages use local pickup', function () {
    WC()->session->set(SESSION_KEY, [
        'carrier'         => 'postnl',
        'shipmentOptions' => [
            (new SignatureDefinition())->getShipmentOptionsKey() => true,
        ],
    ]);
    // Mixed cart: one package is picked up, another still ships a parcel → fees still apply.
    WC()->session->set('chosen_shipping_methods', ['local_pickup:1', 'flat_rate:2']);

    $cart = cartWithProduct();

    /** @var CartFeesHooks $hooks */
    $hooks = Pdk::get(CartFeesHooks::class);
    $hooks->calculateDeliveryOptionsFees($cart);

    expect($cart->fees)->toHaveCount(2);
});

it('clamps the pickup discount so shipping + pickup never goes below zero', function () {
    /** @var PdkSettingsRepositoryInterface $settingsRepo */
    $settingsRepo = Pdk::get(PdkSettingsRepositoryInterface::class);
    $settingsRepo->store(Pdk::get('createSettingsKey')('carrier'), [
        'POSTNL' => [
            SettingKey::priceDeliveryType(RefTypesDeliveryTypeV2::PICKUP) => -5.0,
        ],
    ]);

    WC()->session->set(SESSION_KEY, [
        'carrier'      => 'postnl',
        'deliveryType' => 'pickup',
    ]);

    // Shipping is only 2.00, so the -5.00 pickup discount must be clamped to -2.00.
    $cart = cartWithProduct(true, [], 2.0);

    /** @var CartFeesHooks $hooks */
    $hooks = Pdk::get(CartFeesHooks::class);
    $hooks->calculateDeliveryOptionsFees($cart);

    expect($cart->fees)->toHaveCount(1)
        ->and($cart->fees[0]['amount'])->toBe(-2.0);
});

it('applies the full pickup discount when shipping covers it', function () {
    /** @var PdkSettingsRepositoryInterface $settingsRepo */
    $settingsRepo = Pdk::get(PdkSettingsRepositoryInterface::class);
    $settingsRepo->store(Pdk::get('createSettingsKey')('carrier'), [
        'POSTNL' => [
            SettingKey::priceDeliveryType(RefTypesDeliveryTypeV2::PICKUP) => -3.0,
        ],
    ]);

    WC()->session->set(SESSION_KEY, [
        'carrier'      => 'postnl',
        'deliveryType' => 'pickup',
    ]);

    // Shipping (10.00) fully covers the discount, so the full -3.00 applies.
    $cart = cartWithProduct(true, [], 10.0);

    /** @var CartFeesHooks $hooks */
    $hooks = Pdk::get(CartFeesHooks::class);
    $hooks->calculateDeliveryOptionsFees($cart);

    expect($cart->fees)->toHaveCount(1)
        ->and($cart->fees[0]['amount'])->toBe(-3.0);
});

it('adds no fees when the cart no longer needs shipping (virtual-only)', function () {
    $selection = [
        'carrier'         => 'postnl',
        'shipmentOptions' => [
            (new SignatureDefinition())->getShipmentOptionsKey() => true,
        ],
    ];
    WC()->session->set(SESSION_KEY, $selection);

    // A virtual product makes the cart non-deliverable, so a lingering selection must not be charged.
    $cart = cartWithProduct(false);

    /** @var CartFeesHooks $hooks */
    $hooks = Pdk::get(CartFeesHooks::class);
    $hooks->calculateDeliveryOptionsFees($cart);

    // Skip-only: no fees, and the stashed selection is left intact (restored if a parcel is re-added).
    expect($cart->fees)->toBeEmpty()
        ->and(WC()->session->get(SESSION_KEY))->toBe($selection);
});

it('adds no fees when a product has delivery options disabled', function () {
    $selection = [
        'carrier'         => 'postnl',
        'shipmentOptions' => [
            (new SignatureDefinition())->getShipmentOptionsKey() => true,
        ],
    ];
    WC()->session->set(SESSION_KEY, $selection);

    // Deliverable product, but its product setting disables delivery options for the whole cart.
    $cart = cartWithProduct(true, [
        ProductSettings::DISABLE_DELIVERY_OPTIONS => TriStateService::ENABLED,
    ]);

    /** @var CartFeesHooks $hooks */
    $hooks = Pdk::get(CartFeesHooks::class);
    $hooks->calculateDeliveryOptionsFees($cart);

    expect($cart->fees)->toBeEmpty()
        ->and(WC()->session->get(SESSION_KEY))->toBe($selection);
});
