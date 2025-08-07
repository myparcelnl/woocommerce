<?php

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Pdk\Hooks;

use MyParcelNL\Pdk\App\Order\Contract\PdkOrderRepositoryInterface;
use MyParcelNL\Pdk\Facade\Frontend;
use MyParcelNL\Pdk\Facade\Pdk;
use MyParcelNL\WooCommerce\Hooks\Contract\WordPressHooksInterface;
use MyParcelNL\WooCommerce\WooCommerce\Contract\WcOrderRepositoryInterface;

class PdkOrderHooks implements WordPressHooksInterface
{
    /**
     * @var \MyParcelNL\Pdk\App\Order\Contract\PdkOrderRepositoryInterface
     */
    private $pdkOrderRepository;

    /**
     * @var \MyParcelNL\WooCommerce\WooCommerce\Contract\WcOrderRepositoryInterface
     */
    private $wcOrderRepository;

    /**
     * @param  \MyParcelNL\Pdk\App\Order\Contract\PdkOrderRepositoryInterface $pdkOrderRepository
     * @param  \MyParcelNL\WooCommerce\WooCommerce\Contract\WcOrderRepositoryInterface $wcOrderRepository
     */
    public function __construct(
        PdkOrderRepositoryInterface $pdkOrderRepository,
        WcOrderRepositoryInterface $wcOrderRepository
    ) {
        $this->pdkOrderRepository = $pdkOrderRepository;
        $this->wcOrderRepository = $wcOrderRepository;
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
     * @param  \WP_Post|mixed $orderInput - WP_Post in legacy, Order object if HPOS
     *
     * @return void
     */
    public function renderPdkOrderBox($orderInput): void
    {
        // Check if the order has local pickup
        try {
            if ($this->wcOrderRepository->hasLocalPickup($orderInput)) {
                // Don't render anything for local pickup orders
                return;
            }
        } catch (\Throwable $e) {
            // If we can't determine, continue with normal rendering
        }

        $order = $this->pdkOrderRepository->get($orderInput);

        echo Frontend::renderOrderBox($order);
    }
}
