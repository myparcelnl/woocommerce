<?php

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Pdk\Product\Repository;

use MyParcelNL\Pdk\Plugin\Collection\PdkProductCollection;
use MyParcelNL\Pdk\Plugin\Model\PdkProduct;
use MyParcelNL\Pdk\Product\Repository\AbstractProductRepository;
use MyParcelNL\Pdk\Settings\Model\ProductSettings;
use WC_Product;

class PdkProductRepository extends AbstractProductRepository
{
    protected const WC_PRODUCT_META_SETTINGS = 'myparcelnl_product_settings';

    /**
     * @param  \WC_Order|string|int $identifier
     *
     * @return \MyParcelNL\Pdk\Plugin\Model\PdkProduct
     */
    public function getProduct($identifier): PdkProduct
    {
        $product = $this->getWcProduct($identifier);

        return $this->retrieve((string) $product->get_id(), function () use ($product) {
            return new PdkProduct([
                'sku'      => '',
                'name'     => '',
                'weight'   => '',
                'settings' => $this->getProductSettings($product),
            ]);
        });
    }

    public function getProductSettings($identifier): ProductSettings
    {
        $product = $this->getWcProduct($identifier);

        return $this->retrieve('product_settings_' . $product->get_id(), function () use ($product) {
            $settings = $product->get_meta(self::WC_PRODUCT_META_SETTINGS) ?: [];

            return new ProductSettings($settings);
        });
    }

    public function getProducts(array $identifiers = []): PdkProductCollection
    {
        return new PdkProductCollection(array_map([$this, 'getProduct'], $identifiers));
    }

    public function store(PdkProduct $product): void
    {
        // TODO: Implement store() method.
    }

    /**
     * @param $identifier
     *
     * @return \WC_Product
     */
    private function getWcProduct($identifier): WC_Product
    {
        if ($identifier instanceof \WC_Product) {
            $product = $identifier;
        } else {
            $product = $this->retrieve('wc_product' . $identifier, function () use ($identifier) {
                return new WC_Product($identifier);
            });
        }

        return $product;
    }
}
