<?php

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Hooks;

use MyParcelNL\Pdk\App\Api\Backend\PdkBackendActions;
use MyParcelNL\Pdk\Facade\Actions;
use MyParcelNL\Pdk\Facade\Settings;
use MyParcelNL\Pdk\Settings\Model\OrderSettings;
use MyParcelNL\WooCommerce\Hooks\Contract\WordPressHooksInterface;
use MyParcelNL\WooCommerce\WooCommerce\Contract\WcOrderRepositoryInterface;
use WC_Order;
use WC_Order_Item_Product;

final class AutomaticOrderExportHooks implements WordPressHooksInterface
{
    /**
     * @var array<int, bool>
     */
    private $exportsInProgress = [];

    /**
     * @var \MyParcelNL\WooCommerce\WooCommerce\Contract\WcOrderRepositoryInterface
     */
    private $wcOrderRepository;

    public function __construct(WcOrderRepositoryInterface $wcOrderRepository)
    {
        $this->wcOrderRepository = $wcOrderRepository;
    }

    public function apply(): void
    {
        add_action('woocommerce_order_status_changed', [$this, 'automaticExportOrder'], 1000, 4);
    }

    /**
     * @param  int            $orderId
     * @param  string         $oldStatus
     * @param  string         $newStatus
     * @param  null|\WC_Order $wcOrder
     *
     * @return void
     * @throws \Throwable
     */
    public function automaticExportOrder(int $orderId, string $oldStatus, string $newStatus, ?WC_Order $wcOrder = null): void
    {
        if (isset($this->exportsInProgress[$orderId])) {
            return;
        }

        $automaticExportStatus = Settings::get(OrderSettings::PROCESS_DIRECTLY, OrderSettings::ID);
        $prefixedNewStatus     = sprintf('wc-%s', $newStatus);

        if ($prefixedNewStatus !== $automaticExportStatus) {
            return;
        }

        $this->exportsInProgress[$orderId] = true;

        try {
            if (null !== $wcOrder) {
                // WooCommerce has cleared the transition on this object before firing the hook.
                // A cached clone can still hold the old transition and replay it on save().
                $this->wcOrderRepository->updateCache($wcOrder);
            }

            if ($this->wcOrderRepository->hasLocalPickup($orderId)) {
                return;
            }

            if (! $this->hasShippableProducts($wcOrder ?? $this->wcOrderRepository->get($orderId))) {
                return;
            }

            Actions::executeAutomatic(PdkBackendActions::EXPORT_ORDERS, [
                'orderIds' => [$orderId],
            ]);
        } finally {
            // A failed export must remain retryable later in this request or a future one.
            unset($this->exportsInProgress[$orderId]);
        }
    }

    private function hasShippableProducts(WC_Order $order): bool
    {
        foreach ($order->get_items() as $item) {
            $product = $item instanceof WC_Order_Item_Product ? $item->get_product() : null;

            if ($product && $product->needs_shipping()) {
                return true;
            }
        }

        return false;
    }
}
