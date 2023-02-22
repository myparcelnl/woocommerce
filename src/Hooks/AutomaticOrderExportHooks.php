<?php

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Hooks;

use MyParcelNL\Pdk\Facade\Actions;
use MyParcelNL\Pdk\Facade\Settings;
use MyParcelNL\Pdk\Plugin\Api\Backend\PdkBackendActions;
use MyParcelNL\Pdk\Settings\Model\GeneralSettings;

final class AutomaticOrderExportHooks implements WordPressHooksInterface
{
    public function apply(): void
    {
        add_action('woocommerce_payment_complete', [$this, 'automaticExportOrder'], 1000);
        add_action('woocommerce_order_status_changed', [$this, 'automaticExportOrder'], 1000, 3);
    }

    /**
     * @param  int $orderId
     *
     * @return void
     */
    public function automaticExportOrder(int $orderId): void
    {
        if (! Settings::get(GeneralSettings::ORDER_MODE, GeneralSettings::ID)) {
            return;
        }

        Actions::execute(PdkBackendActions::EXPORT_ORDERS, [
            'orderIds' => [$orderId],
        ]);
    }
}
