<?php

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Helper;

use ErrorException;
use MyParcelNL\Sdk\src\Model\Consignment\AbstractConsignment;
use WC_Order;
use WC_Order_Item_Product;
use WC_Product;
use WCMP_Export;
use WCMYPA_Admin;
use WCMYPA_Settings;
use WPO\WC\MyParcel\Compatibility\Product as WCX_Product;

class ExportRow
{
    public const DEFAULT_PRODUCT_QUANTITY = 1;
    public const CURRENCY_EURO            = 'EUR';

    /**
     * @var \WC_Order
     */
    private $order;

    /**
     * @var \WC_Product
     */
    private $product;

    public function __construct(WC_Order $order, WC_Product $product)
    {
        $this->order   = $order;
        $this->product = $product;
    }

    /**
     * @return string
     * @throws \JsonException
     */
    public function getCountryOfOrigin(): string
    {
        $cc = WCX_Product::get_meta($this->product, WCMYPA_Admin::META_COUNTRY_OF_ORIGIN_VARIATION, true);

        if (! $cc) {
            $cc = WCX_Product::get_meta($this->product, WCMYPA_Admin::META_COUNTRY_OF_ORIGIN, true);
        }

        if (! $cc && $this->product->get_parent_id()) {
            $cc = get_post_meta($this->product->get_parent_id(), WCMYPA_Admin::META_COUNTRY_OF_ORIGIN, true);
        }

        if (! $cc) {
            $cc = WCMYPA()->setting_collection->getByName(WCMYPA_Settings::SETTING_COUNTRY_OF_ORIGIN);
        }

        if (! $cc) {
            $cc = WC()->countries->get_base_country() ?? AbstractConsignment::CC_NL;
        }

        return $cc;
    }

    /**
     * @return string
     */
    public function getCurrency(): string
    {
        return get_woocommerce_currency();
    }

    /**
     * @return int
     * @throws \ErrorException|\JsonException
     */
    public function getHsCode(): int
    {
        $hsCode = WCX_Product::get_meta($this->product, WCMYPA_Admin::META_HS_CODE_VARIATION, true);

        if (! $hsCode) {
            $hsCode = WCX_Product::get_meta($this->product, WCMYPA_Admin::META_HS_CODE, true);
        }

        if (! $hsCode && $this->product->get_parent_id()) {
            $hsCode = get_post_meta($this->product->get_parent_id(), WCMYPA_Admin::META_HS_CODE, true);
        }

        if (! $hsCode) {
            $hsCode = WCMYPA()->setting_collection->getByName(WCMYPA_Settings::SETTING_HS_CODE);
        }

        if (! $hsCode) {
            throw new ErrorException(__('no_hs_code_found', 'woocommerce-myparcel'));
        }

        return (int) $hsCode;
    }

    /**
     * @param  \WC_Order_Item_Product $item
     *
     * @return int
     */
    public function getItemAmount(WC_Order_Item_Product $item): int
    {
        return (int) ($item['qty'] ?? self::DEFAULT_PRODUCT_QUANTITY);
    }

    /**
     * @return string
     */
    public function getItemDescription(): string
    {
        $description = $this->product->get_name();

        if (strlen($description) > WCMP_Export::ITEM_DESCRIPTION_MAX_LENGTH) {
            $description = substr_replace($description, '...', WCMP_Export::ITEM_DESCRIPTION_MAX_LENGTH - 3);
        }

        return $description;
    }

    /**
     * @return int
     */
    public function getItemWeight(): int
    {
        return WCMP_Export::convertWeightToGrams($this->product->get_weight());
    }

    /**
     * @return array
     */
    public function getValueOfItem(): array
    {
        $total = $this->product->get_price();

        return [
            'amount'   => (int) ($total * 100),
            'currency' => $this->getCurrency(),
        ];
    }
}
