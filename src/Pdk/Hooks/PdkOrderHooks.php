<?php

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Pdk\Hooks;

use MyParcelNL\Pdk\Facade\Pdk;
use MyParcelNL\Pdk\Facade\RenderService;
use MyParcelNL\Pdk\Plugin\Repository\PdkOrderRepositoryInterface;
use MyParcelNL\WooCommerce\Hooks\WordPressHooksInterface;

class PdkOrderHooks implements WordPressHooksInterface
{
    public function apply(): void
    {
        // Render order card in meta box on order edit page
        add_action('add_meta_boxes', [$this, 'registerSingleOrderPageMetaBox']);
    }

    /**
     * @return void
     */
    public function registerSingleOrderPageMetaBox(): void
    {
        $appInfo = Pdk::getAppInfo();

        add_meta_box(
            'myparcelnl_woocommerce_order_data',
            $appInfo->title,
            [$this, 'renderPdkOrderBox'],
            'shop_order',
            'advanced',
            'high'
        );
    }

    /**
     * @return void
     */
    public function renderPdkOrderBox(): void
    {
        global $post;

        /** @var \MyParcelNL\Pdk\Plugin\Repository\PdkOrderRepositoryInterface $orderRepository */
        $orderRepository = Pdk::get(PdkOrderRepositoryInterface::class);
        $order           = $orderRepository->get($post->ID);

        echo RenderService::renderOrderBox($order);
    }
}
