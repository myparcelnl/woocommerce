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
    public $cart_contents = [];

    /**
     * Fees recorded via add_fee(), so tests can assert what was applied to the cart.
     *
     * @var array<int, array{name: string, amount: float, taxable: bool, taxClass: string}>
     */
    public $fees = [];

    /**
     * @param  string $name
     * @param  float  $amount
     * @param  bool   $taxable
     * @param  string $taxClass
     *
     * @return void
     * @see \WC_Cart::add_fee()
     */
    public function add_fee($name, $amount, $taxable = false, $taxClass = ''): void
    {
        $this->fees[] = [
            'name'     => $name,
            'amount'   => (float) $amount,
            'taxable'  => (bool) $taxable,
            'taxClass' => $taxClass,
        ];
    }

    /**
     * @return float
     * @see \WC_Cart::get_shipping_total()
     */
    public function get_shipping_total(): float
    {
        return (float) ($this->attributes['shipping_total'] ?? 0);
    }

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
            $this->cart_contents[$cartItemKey]['quantity'] += $quantity;
        } else {
            $cartItemKey = $cartId;

            $this->cart_contents[$cartItemKey] = [
                'data'     => new WC_Product($productId),
                'quantity' => $quantity,
            ];
        }
    }

    /**
     * @return void
     */
    public function empty_cart(): void
    {
        $this->cart_contents = [];
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
        $thisItemsIsArray  = is_array($this->cart_contents);
        $itemAlreadyExists = isset($this->cart_contents[$cartId]);

        if (false !== $cartId && $thisItemsIsArray && $itemAlreadyExists) {
            return $cartId;
        }

        return '';
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

    public function get_cart()
    {
        return $this->cart_contents;
    }

    /**
     * @return array
     */
    public function get_shipping_packages(): array
    {
        $shippingPackages = [
            ['contents' => $this->cart_contents],
        ];

        return apply_filters(
            'woocommerce_cart_shipping_packages',
            $shippingPackages
        );
    }
}
