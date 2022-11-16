<?php

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Helper;

use Data;
use ErrorException;
use MyParcelNL\Pdk\Base\Service\CountryService;
use WC_Order;
use WC_Order_Item_Product;
use WC_Product;
use ExportActions;
use WCMYPA_Admin;
use WCMYPA_Settings;
use WPO\WC\MyParcel\Compatibility\Product as WCX_Product;

class ExportRow
{
    public const DEFAULT_PRODUCT_QUANTITY = 1;

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
        $defaultCountryOfOrigin   = WCMYPA()->settingCollection->getByName(WCMYPA_Settings::SETTING_COUNTRY_OF_ORIGIN);
        $productCountryOfOrigin   = WCX_Product::get_meta($this->product, WCMYPA_Admin::META_COUNTRY_OF_ORIGIN, true);
        $variationCountryOfOrigin = WCX_Product::get_meta(
            $this->product,
            WCMYPA_Admin::META_COUNTRY_OF_ORIGIN_VARIATION,
            true
        );
        $fallbackCountryOfOrigin  = WC()->countries->get_base_country() ?? CountryService::CC_NL;

        return $variationCountryOfOrigin ?: $productCountryOfOrigin ?: $defaultCountryOfOrigin ?: $fallbackCountryOfOrigin;
    }

    /**
     * @return int
     * @throws \ErrorException|\JsonException
     */
    public function getHsCode(): int
    {
        $defaultHsCode   = WCMYPA()->settingCollection->getByName(WCMYPA_Settings::SETTING_HS_CODE);
        $productHsCode   = WCX_Product::get_meta($this->product, WCMYPA_Admin::META_HS_CODE, true);
        $variationHsCode = WCX_Product::get_meta($this->product, WCMYPA_Admin::META_HS_CODE_VARIATION, true);

        $hsCode = $productHsCode ?: $defaultHsCode;

        if ($variationHsCode) {
            $hsCode = $variationHsCode;
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

        if (strlen($description) > ExportActions::ITEM_DESCRIPTION_MAX_LENGTH) {
            $description = substr_replace($description, '...', ExportActions::ITEM_DESCRIPTION_MAX_LENGTH - 3);
        }

        return $description;
    }

    /**
     * @return int
     */
    public function getItemWeight(): int
    {
        $weight = $this->product->get_weight() ?: 0;

        return Data::convertWeightToGrams($weight);
    }

    /**
     * @return array
     */
    public function getValueOfItem(): array
    {
        $total = $this->order->get_subtotal();
        $tax   = $this->order->get_cart_tax();

        return [
            'amount'   => (int) (($total + $tax) * 100),
            'currency' => $this->getCurrency(),
        ];
    }

    /**
     * @return string
     */
    public function getCurrency(): string
    {
      return get_woocommerce_currency();
    }
}
