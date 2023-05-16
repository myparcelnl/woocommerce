<?php

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Pdk\Hooks;

use MyParcelNL\Pdk\App\Order\Contract\PdkOrderRepositoryInterface;
use MyParcelNL\Pdk\Facade\Frontend;
use MyParcelNL\Pdk\Facade\Pdk;
use MyParcelNL\WooCommerce\Hooks\Contract\WordPressHooksInterface;

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
            Pdk::get('orderMetaBoxId'),
            Pdk::get('orderMetaBoxTitle'),
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

        /** @var PdkOrderRepositoryInterface $orderRepository */
        $orderRepository = Pdk::get(PdkOrderRepositoryInterface::class);
        $order           = $orderRepository->get($post->ID);

        echo Frontend::renderOrderBox($order);
    }
}
