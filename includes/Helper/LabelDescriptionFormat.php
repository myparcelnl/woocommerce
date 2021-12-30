<?php

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Helper;

use MyParcelNL\Sdk\src\Support\Str;
use WC_Product;
use WCMP_Export;

class LabelDescriptionFormat
{
    private $order;
    private $orderSettings;
    private $deliveryOptions;

    public function __construct($order, $orderSettings, $deliveryOptions)
    {
        $this->order           = $order;
        $this->orderSettings   = $orderSettings;
        $this->deliveryOptions = $deliveryOptions;
    }

    /**
     * Get the label description from OrderSettings and replace any variables in it.
     *
     * @return string
     */
    public function getFormattedLabelDescription(): string
    {
        $productIds      = [];
        $productNames    = [];
        $productSkus     = [];
        $productQuantity = [];
        $deliveryDate    = $this->deliveryOptions->getDate();

        foreach ($this->order->get_items() as $item) {
            if (! method_exists($item, 'get_product')) {
                continue;
            }

            /** @var WC_Product $product */
            $product = $item->get_product();
            if (! $product) {
                continue;
            }

            $sku = $product->get_sku();

            $productIds[]      = $product->get_id();
            $productNames[]    = $product->get_name();
            $productSkus[]     = empty($sku) ? '–' : $sku;
            $productQuantity[] = $item->get_quantity();

        }

        $formattedLabelDescription = strtr(
            $this->orderSettings->getLabelDescription(),
            [
                '[DELIVERY_DATE]' => $deliveryDate ? date('d-m-Y', strtotime($deliveryDate)) : '',
                '[ORDER_NR]'      => $this->order->get_order_number(),
                '[PRODUCT_ID]'    => implode(', ', $productIds),
                '[PRODUCT_NAME]'  => implode(', ', $productNames),
                '[PRODUCT_QTY]'   => implode(', ', $productQuantity),
                '[PRODUCT_SKU]'   => implode(', ', $productSkus),
                '[CUSTOMER_NOTE]' => $this->order->get_customer_note(),
            ]
        );

        // Add filter to let plugins change the label description
        $formattedLabelDescription = apply_filters('wcmp_formatted_label_description', $formattedLabelDescription, $this->order);

        return Str::limit($formattedLabelDescription, WCMP_Export::ORDER_DESCRIPTION_MAX_LENGTH);
    }
}
