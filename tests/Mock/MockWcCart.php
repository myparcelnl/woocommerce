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
        $this->items[] = [
            'data' => new WC_Product($productId),
            'quantity'  => $quantity,
        ];
    }

    public function get_cart()
    {
        return $this->items;
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
            static function (float $carry, array $item) {
                /** @var \WC_Product $wcProduct */
                $wcProduct = $item['data'];

                return $carry + (float) $wcProduct->get_weight();
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
