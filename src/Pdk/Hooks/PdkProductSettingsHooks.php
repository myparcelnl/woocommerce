<?php

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Pdk\Hooks;

use MyParcelNL\Pdk\Facade\Pdk;
use MyParcelNL\Pdk\Facade\RenderService;
use MyParcelNL\Pdk\Product\Repository\AbstractProductRepository;
use MyParcelNL\WooCommerce\Hooks\WordPressHooksInterface;

class PdkProductSettingsHooks implements WordPressHooksInterface
{
    public function apply(): void
    {
        // Render custom tab in product settings box
        add_filter('woocommerce_product_data_tabs', [$this, 'registerProductSettingsTab'], 99);

        // Render pdk product settings in above custom tab
        add_action('woocommerce_product_data_panels', [$this, 'renderPdkProductSettings']);
    }

    /**
     * @param  array $tabs
     *
     * @return array
     */
    public function registerProductSettingsTab(array $tabs): array
    {
        $pluginName = Pdk::get('pluginName');

        $tabs[$pluginName] = [
            'title'  => $pluginName,
            'label'  => Pdk::get('pluginTitle'),
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
        /** @var \MyParcelNL\Pdk\Product\Repository\AbstractProductRepository $productRepository */
        $productRepository = Pdk::get(AbstractProductRepository::class);
        $product           = $productRepository->getProduct(get_the_ID());

        printf(
            '<div id="%s" class="panel woocommerce_options_panel">%s</div>',
            Pdk::get('pluginName') . '_product_data',
            RenderService::renderProductSettings($product)
        );
    }
}
