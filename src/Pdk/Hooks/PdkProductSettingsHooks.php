<?php

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Pdk\Hooks;

use MyParcelNL\Pdk\App\Order\Contract\PdkProductRepositoryInterface;
use MyParcelNL\Pdk\Facade\Frontend;
use MyParcelNL\Pdk\Facade\Pdk;
use MyParcelNL\Sdk\src\Support\Str;
use MyParcelNL\WooCommerce\Hooks\Contract\WordPressHooksInterface;

class PdkProductSettingsHooks implements WordPressHooksInterface
{
    public function apply(): void
    {
        // Render custom tab in product settings box
        add_filter('woocommerce_product_data_tabs', [$this, 'registerProductSettingsTab'], 99);

        // Render pdk product settings in above custom tab
        add_action('woocommerce_product_data_panels', [$this, 'renderPdkProductSettings']);

        // Save pdk product settings
        add_action('woocommerce_process_product_meta', [$this, 'savePdkProductSettings']);
    }

    /**
     * @param  array $tabs
     *
     * @return array
     */
    public function registerProductSettingsTab(array $tabs): array
    {
        $appInfo    = Pdk::getAppInfo();
        $pluginName = $appInfo->name;

        $tabs[$pluginName] = [
            'title'  => $pluginName,
            'label'  => $appInfo->title,
            'target' => "{$pluginName}_product_data",
            'class'  => ['show_if_simple', 'show_if_variable', 'show_if_grouped', 'show_if_external'],
        ];

        return $tabs;
    }

    /**
     * @return void
     */
    public function renderPdkProductSettings(): void
    {
        /** @var PdkProductRepositoryInterface $productRepository */
        $productRepository = Pdk::get(PdkProductRepositoryInterface::class);
        $product           = $productRepository->getProduct(get_the_ID());

        echo Frontend::renderProductSettings($product);
    }

    /**
     * @param  int $productId
     *
     * @return void
     */
    public function savePdkProductSettings(int $productId): void
    {
        $post    = filter_input_array(INPUT_POST, FILTER_SANITIZE_STRING);
        $appInfo = Pdk::getAppInfo();

        $values = array_filter($post, static function ($key) use ($appInfo) {
            return Str::startsWith($key, '_' . $appInfo->name);
        }, ARRAY_FILTER_USE_KEY);

        /** @var \MyParcelNL\WooCommerce\Pdk\Product\Repository\PdkProductRepository $productRepository */
        $productRepository = Pdk::get(PdkProductRepositoryInterface::class);
        $product           = $productRepository->getProduct($productId);
        $productRepository->update($productRepository->convertDbValuesToProductSettings($product, $values));
    }
}
