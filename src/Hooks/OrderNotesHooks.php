<?php

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Hooks;

use MyParcelNL\Pdk\App\Api\Backend\PdkBackendActions;
use MyParcelNL\Pdk\Facade\Actions;
use MyParcelNL\WooCommerce\Hooks\Contract\WordPressHooksInterface;
use WC_Order;

final class OrderNotesHooks implements WordPressHooksInterface
{
    /**
     * @param  int       $commentId
     * @param  \WC_Order $order
     *
     * @return void
     */
    public function addOrderNotes(int $commentId, WC_Order $order): void
    {
        Actions::execute(PdkBackendActions::POST_ORDER_NOTES, [
            'orderIds' => [$order->get_id()],
        ]);
    }

    /**
     * @return void
     */
    public function apply(): void
    {
        add_action('woocommerce_order_note_added', [$this, 'addOrderNotes'], 2, 2);
    }
}
