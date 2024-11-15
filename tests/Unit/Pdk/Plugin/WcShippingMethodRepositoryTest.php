<?php
/** @noinspection StaticClosureCanBeUsedInspection,PhpUnhandledExceptionInspection */

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Pdk\Plugin;

use InvalidArgumentException;
use MyParcelNL\Pdk\App\ShippingMethod\Collection\PdkShippingMethodCollection;
use MyParcelNL\Pdk\Facade\Pdk;
use MyParcelNL\WooCommerce\Tests\Uses\UsesMockWcPdkInstance;
use MyParcelNL\WooCommerce\WooCommerce\Repository\WcShippingRepository;
use WC_Shipping;
use WC_Shipping_Method;
use WP_Term;
use function MyParcelNL\Pdk\Tests\usesShared;

usesShared(new UsesMockWcPdkInstance());

it('gets shipping method collection', function ($input) {
    $wcShipping                   = WC_Shipping::instance();
    $wcShipping->enabled          = true;
    $wcShipping->shipping_classes = [];

    foreach ($input as $method) {
        // convert array to wp_term
        if (is_array($method)) {
            $wpTerm          = new WP_Term();
            $wpTerm->term_id = (int) $method['id'];
            $wpTerm->name    = str_replace('-', ' ', $method['slug']);
            $wpTerm->slug    = $method['slug'];
            wp_cache_add((string) $wpTerm->term_id, $wpTerm, 'terms');
            $wcShipping->shipping_classes[] = $wpTerm;
            continue;
        }
        $wcShipping->shipping_classes[] = $method;
    }

    $wcShippingRepository       = Pdk::get(WcShippingRepository::class);
    $wcShippingMethodRepository = new WcShippingMethodRepository($wcShippingRepository);
    $shippingMethods            = array_map(static function ($item) {
        return ['id' => $item['id'], 'name' => $item['name']];
    },
        $wcShippingMethodRepository->all()
            ->toArray());

    expect(count($shippingMethods))->toBe(count($input));
})->with([
    'shipping methods'     => [
        'input' => [
            new WC_Shipping_Method(['id' => 1, 'method_title' => 'table-rate']),
            new WC_Shipping_Method(['id' => 5, 'method_title' => 'flatrate']),
        ],
    ],
    'wp terms and methods' => [
        'input' => [
            ['id' => 1, 'name' => 'table-rate'],
            new WC_Shipping_Method(['id' => 127, 'method_title' => 'flexible-shipping']),
        ],
    ],
]);

it('throws error for illegal input', function ($input) {
    $wcShipping                   = WC_Shipping::instance();
    $wcShipping->enabled          = true;
    $wcShipping->shipping_classes = [];

    foreach ($input as $method) {
        $wcShipping->shipping_classes[] = $method;
    }

    $wcShippingRepository       = Pdk::get(WcShippingRepository::class);
    $wcShippingMethodRepository = new WcShippingMethodRepository($wcShippingRepository);

    expect($wcShippingMethodRepository->all())->toBeInstanceOf(PdkShippingMethodCollection::class);
})
    ->with([
        'string' => [
            [
                'string',
                new WC_Shipping_Method(['id' => 127, 'method_title' => 'flexible-shipping']),
            ],
        ],
        'null'   => [
            [
                null,
                new WC_Shipping_Method(['id' => 127, 'method_title' => 'flexible-shipping']),
            ],
        ],
    ])
    ->throws(InvalidArgumentException::class);

