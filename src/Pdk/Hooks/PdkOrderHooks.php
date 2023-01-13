<?php

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Pdk\Hooks;

use MyParcelNL;
use MyParcelNL\Pdk\Facade\Pdk;
use MyParcelNL\Pdk\Facade\RenderService;
use MyParcelNL\Pdk\Plugin\Repository\AbstractPdkOrderRepository;
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
        add_meta_box(
            'myparcelnl_woocommerce_order_data',
            MyParcelNL::NAME,
            [$this, 'renderPdkOrderCard'],
            'shop_order',
            'advanced',
            'high'
        );
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
}
