<?php

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Hooks;

use MyParcelNL\Pdk\App\Api\Backend\PdkBackendActions;
use MyParcelNL\Pdk\Facade\Actions;
use MyParcelNL\Pdk\Facade\Settings;
use MyParcelNL\Pdk\Settings\Model\OrderSettings;
use MyParcelNL\WooCommerce\Hooks\Contract\WordPressHooksInterface;

final class AutomaticOrderExportHooks implements WordPressHooksInterface
{
    public function apply(): void
    {
        add_action('woocommerce_payment_complete', [$this, 'automaticExportOrder'], 1000);
    }

    /**
     * @param  int $orderId
     *
     * @return void
     */
    public function automaticExportOrder(int $orderId): void
    {
        if (! Settings::get(OrderSettings::PROCESS_DIRECTLY, OrderSettings::ID)) {
            return;
        }

        Actions::execute(PdkBackendActions::EXPORT_ORDERS, [
            'orderIds' => [$orderId],
        ]);
    }
}
