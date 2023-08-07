<?php

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Pdk\Product\Repository;

use Exception;
use MyParcelNL\Pdk\App\Order\Collection\PdkProductCollection;
use MyParcelNL\Pdk\App\Order\Model\PdkProduct;
use MyParcelNL\Pdk\App\Order\Repository\AbstractPdkPdkProductRepository;
use MyParcelNL\Pdk\Base\Contract\WeightServiceInterface;
use MyParcelNL\Pdk\Facade\Pdk;
use MyParcelNL\Pdk\Settings\Model\ProductSettings;
use MyParcelNL\Pdk\Storage\Contract\StorageInterface;
use WC_Product;
use WC_Product_Variation;

class WcPdkProductRepository extends AbstractPdkPdkProductRepository
{
    /**
     * @var \MyParcelNL\WooCommerce\Pdk\Service\WcWeightService
     */
    protected $weightService;

    /**
     * @param  \MyParcelNL\Pdk\Storage\Contract\StorageInterface    $storage
     * @param  \MyParcelNL\Pdk\Base\Contract\WeightServiceInterface $weightService
     */
    public function __construct(StorageInterface $storage, WeightServiceInterface $weightService)
    {
        parent::__construct($storage);
        $this->weightService = $weightService;
    }

    /**
     * @param  \WC_Product|string|int $identifier
     *
     * @return \MyParcelNL\Pdk\App\Order\Model\PdkProduct
     */
    public function getProduct($identifier): PdkProduct
    {
        $product = $this->getWcProduct($identifier);

        return $this->retrieve((string) $product->get_id(), function () use ($product) {
            return new PdkProduct([
                'externalIdentifier' => (string) $product->get_id(),
                'sku'                => $product->get_sku(),
                'isDeliverable'      => $product->needs_shipping(),
                'name'               => $product->get_name(),
                'price'              => [
                    'amount'   => (float) $product->get_price() * 100,
                    'currency' => get_woocommerce_currency(),
                ],
                'weight'             => $this->weightService->convertToGrams((float) $product->get_weight()),
                'length'             => $product->get_length(),
                'width'              => $product->get_width(),
                'height'             => $product->get_height(),
                'settings'           => $this->getProductSettings($product),
                'parent'             => $product instanceof WC_Product_Variation ? $this->getProduct(
                    $product->get_parent_id()
                ) : null,
            ]);
        });
    }

    /**
     * @param $identifier
     *
     * @return \MyParcelNL\Pdk\Settings\Model\ProductSettings
     */
    public function getProductSettings($identifier): ProductSettings
    {
        $product = $this->getWcProduct($identifier);

        return $this->retrieve(sprintf('product_settings_%s', $product->get_id()), function () use ($product) {
            $meta = $product->get_meta(Pdk::get('metaKeyProductSettings'));

            return new ProductSettings($meta ?: []);
        });
    }

    /**
     * @param  array $identifiers
     *
     * @return \MyParcelNL\Pdk\App\Order\Collection\PdkProductCollection
     */
    public function getProducts(array $identifiers = []): PdkProductCollection
    {
        return new PdkProductCollection(array_map([$this, 'getProduct'], $identifiers));
    }

    /**
     * @param  \MyParcelNL\Pdk\App\Order\Model\PdkProduct $product
     *
     * @return void
     * @throws \MyParcelNL\Pdk\Base\Exception\InvalidCastException
     */
    public function update(PdkProduct $product): void
    {
        update_post_meta(
            $product->externalIdentifier,
            Pdk::get('metaKeyProductSettings'),
            $product->settings->toStorableArray()
        );

        $this->save($product->externalIdentifier, $product);
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
                try {
                    $product = new WC_Product_Variation($identifier);
                } catch (Exception $e) {
                    $product = new WC_Product($identifier);
                }

                return $product;
            });
        }

        return $product;
    }
}
