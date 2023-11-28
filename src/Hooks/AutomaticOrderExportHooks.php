<?php

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Hooks;

use MyParcelNL\Pdk\App\Api\Backend\PdkBackendActions;
use MyParcelNL\Pdk\Facade\Actions;
use MyParcelNL\Pdk\Facade\Settings;
use MyParcelNL\Pdk\Settings\Model\OrderSettings;
use MyParcelNL\WooCommerce\Hooks\Contract\WordPressHooksInterface;
use MyParcelNL\WooCommerce\WooCommerce\Contract\WcOrderRepositoryInterface;

final class AutomaticOrderExportHooks implements WordPressHooksInterface
{
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
        add_action('woocommerce_order_status_changed', [$this, 'automaticExportOrder'], 1000, 3);
    }

    /**
     * @param  int    $orderId
     * @param  string $oldStatus
     * @param  string $newStatus
     *
     * @return void
     * @throws \Throwable
     */
    public function automaticExportOrder(int $orderId, string $oldStatus, string $newStatus): void
    {
        $automaticExportStatus = Settings::get(OrderSettings::PROCESS_DIRECTLY, OrderSettings::ID);
        $prefixedNewStatus     = sprintf('wc-%s', $newStatus);

        if ($prefixedNewStatus !== $automaticExportStatus || $this->wcOrderRepository->hasLocalPickup($orderId)) {
            return;
        }

        Actions::executeAutomatic(PdkBackendActions::EXPORT_ORDERS, [
            'orderIds' => [$orderId],
        ]);
    }
}
