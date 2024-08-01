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
        $cartId      = $this->generate_cart_id($productId, $variationId, $variation, $cartItemData);
        $cartItemKey = $this->find_product_in_cart($cartId);

        if ($cartItemKey) {
            $this->items[$cartItemKey]['quantity'] += $quantity;
        } else {
            $this->items[] = [
                'data'     => new WC_Product($productId),
                'quantity' => $quantity,
            ];
        }
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
        $weight = 0;
        foreach ($this->items as $item) {
            /** @var \WC_Product $wcProduct */
            $wcProduct = $item['data'];
            $weight    += $wcProduct->get_weight() * $item['quantity'];
        }

        if ($weight > 10) {
            return [];
        }

        return [
            'flat_rate:0' => [],
        ];
    }

    /**
     * Generate a unique ID for the cart item being added.
     *
     * @param  int   $productId    - id of the product the key is being generated for.
     * @param  int   $variationId  of the product the key is being generated for.
     * @param  array $variation    data for the cart item.
     * @param  array $cartItemData other cart item data passed which affects this items uniqueness in the cart.
     *
     * @return string cart item key
     */
    public function generate_cart_id(
        int   $productId,
        int   $variationId = 0,
        array $variation = [],
        array $cartItemData = []
    ): string {
        $idParts = [$productId];

        if ($variationId && 0 !== $variationId) {
            $idParts[] = $variationId;
        }

        if (is_array($variation) && ! empty($variation)) {
            $variationKey = '';
            foreach ($variation as $key => $value) {
                $variationKey .= trim($key) . trim($value);
            }
            $idParts[] = $variationKey;
        }

        if (is_array($cartItemData) && ! empty($cartItemData)) {
            $cartItemDataKey = '';
            foreach ($cartItemData as $key => $value) {
                if (is_array($value) || is_object($value)) {
                    $value = http_build_query($value);
                }
                $cartItemDataKey .= trim($key) . trim($value);
            }
            $idParts[] = $cartItemDataKey;
        }

        return apply_filters(
            'woocommerce_cart_id',
            md5(implode('_', $idParts))
        );
    }

    /**
     * Check if product is in the cart and return cart item key.
     * Cart item key will be unique based on the item and its properties, such as variations.
     * ONLY RETURNS A KEY! DOES NOT RETURN THE ITEM!
     *
     * @param  mixed $cartId id of product to find in the cart.
     *
     * @return string cart item key
     */
    public function find_product_in_cart($cartId = false): string
    {
        $thisItemsIsArray  = is_array($this->items);
        $itemAlreadyExists = isset($this->items[$cartId]);

        if ($cartId !== false && $thisItemsIsArray && $itemAlreadyExists) {
            return $cartId;
        }

        return '';
    }
}
