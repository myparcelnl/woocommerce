<?php

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Pdk\Service;

use MyParcelNL;
use MyParcelNL\Pdk\Facade\Pdk;
use MyParcelNL\Pdk\Facade\RenderService;
use MyParcelNL\Pdk\Plugin\Repository\AbstractPdkOrderRepository;

class HookRenderService
{
    public function registerHooks(): void
    {
        // Render pdk init scripts in the footer
        add_action('admin_footer', [$this, 'renderPdkInitScripts']);

        // Render main notification container
        add_action('admin_notices', [$this, 'renderPdkNotifications']);

        // Render pdk order list column in above custom order grid column
        add_action('manage_shop_order_posts_custom_column', [$this, 'renderPdkOrderListColumn']);

        // Render product settings
        add_action('woocommerce_product_options_shipping', [$this, 'renderPdkProductSettings']);

        // Render order card
        add_action('woocommerce_admin_order_data_after_shipping_address', [$this, 'renderPdkOrderCard']);
    }

    /**
     * @return void
     */
    public function renderPdkInitScripts(): void
    {
        echo RenderService::renderInitScript();
        echo RenderService::renderModals();
    }

    /**
     * @return void
     */
    public function renderPdkNotifications(): void
    {
        echo RenderService::renderNotifications();
    }

    /**
     * @return void
     */
    public function renderPdkOrderCard(): void
    {
        global $post;

        $orderRepository = Pdk::get(AbstractPdkOrderRepository::class);
        $order           = $orderRepository->get($post->ID);

        echo RenderService::renderOrderCard($order);
    }

    /**
     * @param  string|mixed $column
     *
     * @return void
     */
    public function renderPdkOrderListColumn($column): void
    {
        global $post;

        if (MyParcelNL::CUSTOM_ORDER_COLUMN_ID === $column) {
            /** @var \MyParcelNL\Pdk\Plugin\Repository\AbstractPdkOrderRepository $orderRepository */
            $orderRepository = Pdk::get(AbstractPdkOrderRepository::class);

            $pdkOrder = $orderRepository->get($post->ID);

            echo RenderService::renderOrderListColumn($pdkOrder);
        }
    }

    /**
     * @return void
     */
    public function renderPdkPluginSettings(): void
    {
        echo RenderService::renderPluginSettings();
    }

    /**
     * @return void
     */
    public function renderPdkProductSettings(): void
    {
        global $post;

        /** @var \MyParcelNL\Pdk\Product\Repository\AbstractProductRepository $productRepository */
        $productRepository = Pdk::get(MyParcelNL\Pdk\Product\Repository\AbstractProductRepository::class);
        $product           = $productRepository->getProduct($post->ID);

        echo RenderService::renderProductSettings($product);
    }
}
