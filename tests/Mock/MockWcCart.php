<?php

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Tests\Mock;

use WC_Product;

/**
 * @extends \WC_Cart
 */
class MockWcCart extends MockWcClass
{
    /**
     * @var \WC_Product[]
     */
    private $items = [];

    /**
     * @param  int   $productId
     * @param  int   $quantity
     * @param  int   $variationId
     * @param  array $variation
     * @param  array $cartItemData
     *
     * @return void
     * @throws \Throwable
     * @see \WC_Cart::add_to_cart()
     */
    public function add_to_cart(
        int   $productId = 0,
        int   $quantity = 1,
        int   $variationId = 0,
        array $variation = [],
        array $cartItemData = []
    ): void {
        for ($i = 0; $i < $quantity; $i++) {
            $this->items[] = new WC_Product($productId);
        }
    }

    /**
     * @return void
     */
    public function empty_cart(): void
    {
        $this->items = [];
    }

    /**
     * @return array
     */
    public function get_shipping_packages(): array
    {
        // calculate weight of all products in cart
        $weight = array_reduce(
            $this->items,
            static function (float $carry, WC_Product $item) {
                return $carry + (float) $item->get_weight();
            },
            0
        );

        if ($weight > 10) {
            return [];
        }

        return [
            'flat_rate:0' => [],
        ];
    }
}
