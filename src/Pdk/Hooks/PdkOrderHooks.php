<?php

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Pdk\Hooks;

use Automattic\WooCommerce\Admin\Overrides\Order;
use MyParcelNL\Pdk\App\Order\Contract\PdkOrderRepositoryInterface;
use MyParcelNL\Pdk\Facade\Frontend;
use MyParcelNL\Pdk\Facade\Pdk;
use MyParcelNL\WooCommerce\Hooks\Contract\WordPressHooksInterface;

class PdkOrderHooks implements WordPressHooksInterface
{
    /**
     * @var \MyParcelNL\Pdk\App\Order\Contract\PdkOrderRepositoryInterface
     */
    private $pdkOrderRepository;

    /**
     * @param  \MyParcelNL\Pdk\App\Order\Contract\PdkOrderRepositoryInterface $pdkOrderRepository
     */
    public function __construct(PdkOrderRepositoryInterface $pdkOrderRepository)
    {
        $this->pdkOrderRepository = $pdkOrderRepository;
    }

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
            Pdk::get('orderPageId'),
            'advanced',
            'high'
        );
    }

    /**
     * @param  \WP_Post|Order $orderInput - WP_Post in legacy, Order in HPOS
     *
     * @return void
     */
    public function renderPdkOrderBox($orderInput): void
    {
        $order = $this->pdkOrderRepository->get($orderInput);

        echo Frontend::renderOrderBox($order);
    }
}
