<?php

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Includes\Adapter;

use MyParcelNL\Sdk\src\Model\Fulfilment\OrderLine;
use WC_Order_Item;

class OrderLineFromWooCommerce extends OrderLine
{
    /**
     * OrderLineFromWooCommerce constructor.
     *
     * @param \WC_Order_Item $wcOrderItem
     */
    public function __construct(WC_Order_Item $wcOrderItem)
    {
        $standardizedDataArray = $this->prepareItemData($wcOrderItem);

        parent::__construct($standardizedDataArray);
    }

    /**
     * @param \WC_Order_Item $wcOrderItem
     *
     * @return array
     */
    protected function prepareItemData(WC_Order_Item $wcOrderItem): array
    {
        $wcItemData = $wcOrderItem->get_data();

        $price = (int) ($wcItemData['subtotal'] * 100.0);
        $vat   = (int) ($wcItemData['subtotal_tax'] * 100.0);

        return [
            'price'           => $price,
            'vat'             => $vat,
            'price_after_vat' => $price + $vat,
            'quantity'        => $wcOrderItem->get_quantity(),
            'product'         => $this->prepareProductData($wcOrderItem),
        ];
    }

    /**
     * @param \WC_Order_Item $wcOrderItem
     *
     * @return array
     */
    protected function prepareProductData(WC_Order_Item $wcOrderItem): array
    {
        $wcItemData = $wcOrderItem->get_data();
        $wcProduct  = $wcOrderItem->get_product();

        return [
            'external_identifier' => (string) ($wcItemData['variation_id'] ?: $wcItemData['product_id']),
            'name'                => $wcItemData['name'],
            'sku'                 => $wcProduct->get_sku(),
            'height'              => (int) $wcProduct->get_height() ?: 0,
            'length'              => (int) $wcProduct->get_length() ?: 0,
            'weight'              => (int) $wcProduct->get_weight() ?: 0,
            'width'               => (int) $wcProduct->get_width() ?: 0,
            'description'         => $wcProduct->get_short_description(),
        ];
    }
}
