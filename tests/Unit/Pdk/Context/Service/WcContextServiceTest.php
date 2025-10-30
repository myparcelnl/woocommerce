<?php
/** @noinspection PhpUnhandledExceptionInspection,StaticClosureCanBeUsedInspection */

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Pdk\Context\Service;

use MyParcelNL\Pdk\App\Cart\Model\PdkCart;
use MyParcelNL\Pdk\App\Order\Model\PdkOrderLine;
use MyParcelNL\Pdk\App\Order\Model\PdkProduct;
use MyParcelNL\Pdk\App\ShippingMethod\Model\PdkShippingMethod;
use MyParcelNL\Pdk\Base\Support\Collection;
use MyParcelNL\Pdk\Facade\Pdk;
use MyParcelNL\Pdk\Settings\Model\CheckoutSettings;
use MyParcelNL\WooCommerce\Tests\Mock\MockWpCache;
use MyParcelNL\WooCommerce\Tests\Uses\UsesMockWcPdkInstance;
use WC_Product;
use WC_Shipping_Flat_Rate;
use WC_Shipping_Method;
use WP_Term;
use function MyParcelNL\Pdk\Tests\factory;
use function MyParcelNL\Pdk\Tests\usesShared;
use function MyParcelNL\WooCommerce\Tests\wpFactory;

usesShared(new UsesMockWcPdkInstance());

function add_term_to_cache(WP_Term $wpTerm, bool $asArray = false): void
{
    if ($asArray) {
        wp_cache_add((string) $wpTerm->term_id, [
            'term_id' => $wpTerm->term_id,
            'slug'    => $wpTerm->slug,
            'name'    => $wpTerm->name,
        ], 'terms');

        return;
    }
    wp_cache_add((string) $wpTerm->term_id, $wpTerm, 'terms');
}

function add_product_to_cart(int $product_id = 6789, ?int $shippingClassId = null)
{
    $wcProduct = wpFactory(WC_Product::class)
        ->withId($product_id);
    if (null !== $shippingClassId) {
        $wcProduct->withShippingClassId($shippingClassId);
    }
    $wcProduct->make();

    WC()->cart->add_to_cart($product_id);
}

it('creates checkout context', function ($input, $expected) {
    MockWPCache::reset();
    WC()->cart->empty_cart();

    $contextService = Pdk::get(WcContextService::class);

    factory(CheckoutSettings::class)
        ->withAllowedShippingMethods($input['allowShippingMethods'] ?? [])
        ->store();

    $shippingMethodClassName = $input['shippingMethod'];
    $shippingPrice           = $input['shippingPrice'] ?? 0;
    $termAsArray             = $input['termAsArray'] ?? false;

    if (empty($input['products'])) {
        throw new \InvalidArgumentException('You must define products to test with.');
    }

    foreach ($input['products'] as $productId => $specs) {
        if ($specs['shippingClassId'] ?? null) {
            $wpTerm          = new WP_Term();
            $wpTerm->term_id = (int) $specs['shippingClassId'];
            $wpTerm->name    = $wpTerm->slug = bin2hex(random_bytes(5));

            add_product_to_cart($productId, $wpTerm->term_id);

            add_term_to_cache($wpTerm, $termAsArray);
        } else {
            add_product_to_cart($productId);
        }
    }

    $adaptedCart = [
        'lines' => (new Collection(array_map(function (array $item) {
            $wcProduct     = $item['data'];
            $pdkProduct    = new PdkProduct([
                'externalIdentifier' => $wcProduct->id,
                'title'              => $wcProduct->name,
                'sku'                => $wcProduct->sku,
            ]);
            $priceAfterVat = $item['line_subtotal'] + $item['line_subtotal_tax'];

            return [
                'quantity'      => (int) $item['quantity'],
                'price'         => (int) (100 * (float) $item['line_subtotal']),
                'vat'           => (int) (100 * (float) $item['line_subtotal_tax']),
                'priceAfterVat' => (int) (100 * (float) $priceAfterVat),
                'product'       => $pdkProduct,
            ];
        }, array_values(WC()->cart->cart_contents))))->mapInto(PdkOrderLine::class),
    ];
    $pdkCart     = new PdkCart($adaptedCart);

    $pdkShippingMethod       = factory(PdkShippingMethod::class)
        ->withId('flexible_shipping:456')
        ->make();
    $pdkCart->shippingMethod = $pdkShippingMethod;

    $shippingMethod = wpFactory($shippingMethodClassName)
        ->withId(456);
    if ($shippingPrice) {
        $shippingMethod->withInstanceSettings(['class_cost_12' => $shippingPrice]);
    }
    $shippingMethod->store();

    $checkoutContext = $contextService->createCheckoutContext($pdkCart);

    expect($checkoutContext->config->basePrice)
        ->toBe($expected['basePrice'])
        ->and($checkoutContext->settings['highestShippingClass'])
        ->toBe($expected['highestShippingClass']);
})->with([
    'product with specific shipping class'                => [
        'input'    => [
            'allowShippingMethods' => ['mailbox' => ['shipping_class:12']],
            'shippingMethod'       => WC_Shipping_Flat_Rate::class,
            'products'             => [999 => ['shippingClassId' => 12]],
        ],
        'expected' => [
            'basePrice'            => 0.0,
            'highestShippingClass' => 'shipping_class:12',
        ],
    ],
    'flexible shipping'                                   => [
        'input'    => [
            'allowShippingMethods' => ['-1' => ['shipping_class:12']],
            'shippingMethod'       => WC_Shipping_Method::class,
            'products'             => [999 => ['shippingClassId' => 12]],
        ],
        'expected' => [
            'basePrice'            => 0.0,
            'highestShippingClass' => '',
        ],
    ],
    'flat rate with price'                                => [
        'input'    => [
            'allowShippingMethods' => ['-1' => ['shipping_class:12']],
            'shippingMethod'       => WC_Shipping_Flat_Rate::class,
            'products'             => [999 => ['shippingClassId' => 12]],
            'shippingPrice'        => 5.12,
        ],
        'expected' => [
            'basePrice'            => 0.0,
            'highestShippingClass' => '',
        ],
    ],
    'product without shipping class'                      => [
        'input'    => [
            'allowShippingMethods' => ['-1' => ['shipping_class:12']],
            'shippingMethod'       => WC_Shipping_Flat_Rate::class,
            'products'             => [999 => []],
        ],
        'expected' => [
            'basePrice'            => 0.0,
            'highestShippingClass' => '',
        ],
    ],
    'flat rate without price'                             => [
        'input'    => [
            'allowShippingMethods' => ['-1' => ['shipping_class:12']],
            'shippingMethod'       => WC_Shipping_Flat_Rate::class,
            'products'             => [999 => ['shippingClassId' => 12]],
        ],
        'expected' => [
            'basePrice'            => 0.0,
            'highestShippingClass' => '',
        ],
    ],
    'term as array'                                       => [
        'input'    => [
            'allowShippingMethods' => ['-1' => ['shipping_class:12']],
            'shippingMethod'       => WC_Shipping_Flat_Rate::class,
            'products'             => [999 => ['shippingClassId' => 12]],
            'termAsArray'          => true,
        ],
        'expected' => [
            'basePrice'            => 0.0,
            'highestShippingClass' => '',
        ],
    ],
    'no allowed shipping methods'                         => [
        'input'    => [
            'allowShippingMethods' => [],
            'shippingMethod'       => WC_Shipping_Method::class,
            'products'             => [999 => ['shippingClassId' => 12]],
        ],
        'expected' => [
            'basePrice'            => 0.0,
            'highestShippingClass' => '',
        ],
    ],
    'biggest package of 2 available classes'              => [
        'input'    => [
            'allowShippingMethods' => ['mailbox' => ['shipping_class:12'], 'package_small' => ['shipping_class:15']],
            'shippingMethod'       => WC_Shipping_Flat_Rate::class,
            'products'             => [
                999  => ['shippingClassId' => 12],
                1010 => ['shippingClassId' => 15],
            ],
        ],
        'expected' => [
            'basePrice'            => 0.0,
            'highestShippingClass' => 'shipping_class:15',
        ],
    ],
    'automatic selection with missing class'              => [
        'input'    => [
            'allowShippingMethods' => ['mailbox' => ['shipping_class:12'], 'package_small' => ['shipping_class:15']],
            'shippingMethod'       => WC_Shipping_Flat_Rate::class,
            'products'             => [
                999  => ['shippingClassId' => 12],
                1010 => ['shippingClassId' => 15],
                4001 => [],
            ],
        ],
        'expected' => [
            'basePrice'            => 0.0,
            'highestShippingClass' => '',
        ],
    ],
    'biggest package of 3 available classes'              => [
        'input'    => [
            'allowShippingMethods' => [
                'package'       => ['shipping_class:7'],
                'mailbox'       => ['shipping_class:12'],
                'package_small' => ['shipping_class:15'],
            ],
            'shippingMethod'       => WC_Shipping_Flat_Rate::class,
            'products'             => [
                1010 => ['shippingClassId' => 7],
                999  => ['shippingClassId' => 12],
                333  => ['shippingClassId' => 15],
                706  => ['shippingClassId' => 12],
                901  => ['shippingClassId' => 12],
            ],
        ],
        'expected' => [
            'basePrice'            => 0.0,
            'highestShippingClass' => 'shipping_class:7',
        ],
    ],
    'biggest package of available classes, many products' => [
        'input'    => [
            'allowShippingMethods' => ['package_small' => ['shipping_class:4'], 'mailbox' => ['shipping_class:12']],
            'shippingMethod'       => WC_Shipping_Flat_Rate::class,
            'products'             => [
                999 => ['shippingClassId' => 12],
                311 => ['shippingClassId' => 4],
                400 => ['shippingClassId' => 12],
                501 => ['shippingClassId' => 12],
                777 => ['shippingClassId' => 12],
            ],
        ],
        'expected' => [
            'basePrice'            => 0.0,
            'highestShippingClass' => 'shipping_class:4',
        ],
    ],
]);
