<?php

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Pdk\Product\Repository;

use MyParcelNL\Pdk\Base\Concern\WeightServiceInterface;
use MyParcelNL\Pdk\Facade\Pdk;
use MyParcelNL\Pdk\Plugin\Collection\PdkProductCollection;
use MyParcelNL\Pdk\Plugin\Model\PdkProduct;
use MyParcelNL\Pdk\Product\Repository\AbstractProductRepository;
use MyParcelNL\Pdk\Settings\Model\ProductSettings;
use MyParcelNL\Pdk\Storage\StorageInterface;
use MyParcelNL\Sdk\src\Support\Str;
use WC_Product;

class PdkProductRepository extends AbstractProductRepository
{
    /**
     * @var \MyParcelNL\WooCommerce\Pdk\Service\WcWeightService
     */
    protected $weightService;

    /**
     * @param  \MyParcelNL\Pdk\Storage\StorageInterface            $storage
     * @param  \MyParcelNL\Pdk\Base\Concern\WeightServiceInterface $weightService
     */
    public function __construct(StorageInterface $storage, WeightServiceInterface $weightService)
    {
        parent::__construct($storage);
        $this->weightService = $weightService;
    }

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
                'externalIdentifier' => (string) $product->get_id(),
                'sku'                => $product->get_sku(),
                'name'               => $product->get_name(),
                'price'              => [
                    'amount'   => (float) $product->get_price() * 100,
                    'currency' => get_woocommerce_currency(),
                ],
                'weight'             => $this->weightService->convertToGrams((float) $product->get_weight()),
                'settings'           => $this->getProductSettings($product),
            ]);
        });
    }

    public function getProductSettings($identifier): ProductSettings
    {
        $product = $this->getWcProduct($identifier);

        /** @var array $appInfo */
        $appInfo = Pdk::get('appInfo');
        $key     = sprintf('product_settings_%s', $product->get_id());

        return $this->retrieve($key, function () use ($appInfo, $product) {
            $productSettings = new ProductSettings();

            foreach ($productSettings->getAttributes() as $key => $value) {
                $metaKey = sprintf('%s_product_%s', $appInfo['name'], Str::snake($key));
                $value   = $product->get_meta($metaKey) ?: null;

                if (! $value) {
                    continue;
                }

                $productSettings->setAttribute($key, $value);
            }

            return $productSettings;
        });
    }

    public function getProducts(array $identifiers = []): PdkProductCollection
    {
        return new PdkProductCollection(array_map([$this, 'getProduct'], $identifiers));
    }

    /**
     * @param  \MyParcelNL\Pdk\Plugin\Model\PdkProduct $product
     *
     * @return void
     */
    public function store(PdkProduct $product): void
    {
        foreach ($product->settings->getAttributes() as $key => $value) {
            update_meta($product->externalIdentifier, $key, $value);
        }
    }

    /**
     * @param $identifier
     *
     * @return \WC_Product
     */
    private function getWcProduct($identifier): WC_Product
    {
        if ($identifier instanceof WC_Product) {
            $product = $identifier;
        } else {
            $product = $this->retrieve('wc_product' . $identifier, function () use ($identifier) {
                return new WC_Product($identifier);
            });
        }

        return $product;
    }
}
