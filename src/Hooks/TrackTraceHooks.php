<?php

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Hooks;

use Exception;
use MyParcelNL\Pdk\Facade\Settings;
use MyParcelNL\Pdk\Plugin\Contract\PdkOrderRepositoryInterface;
use MyParcelNL\Pdk\Plugin\Model\PdkOrder;
use MyParcelNL\Pdk\Settings\Model\GeneralSettings;
use MyParcelNL\WooCommerce\Hooks\Contract\WordPressHooksInterface;
use WC_Order;

final class TrackTraceHooks implements WordPressHooksInterface
{
    /**
     * @var \MyParcelNL\Pdk\Plugin\Contract\PdkOrderRepositoryInterface
     */
    private $orderRepository;

    /**
     * @param  \MyParcelNL\Pdk\Plugin\Contract\PdkOrderRepositoryInterface $pdkOrderRepository
     */
    public function __construct(PdkOrderRepositoryInterface $pdkOrderRepository)
    {
        $this->orderRepository = $pdkOrderRepository;
    }

    /**
     * @return void
     */
    public function apply(): void
    {
        add_action('woocommerce_email_before_order_table', [$this, 'renderTrackTraceInEmail'], 10, 2);
        add_filter('woocommerce_my_account_my_orders_actions', [$this, 'showTrackTraceActionInMyAccount'], 10, 2);
    }

    /**
     * @param  \WC_Order $order
     * @param  bool      $sentToAdmin
     *
     * @return void
     */
    public function renderTrackTraceInEmail(WC_Order $order, bool $sentToAdmin): void
    {
        //        if ($sentToAdmin
        //            || ! Settings::get('general.trackTraceInEmail')
        //            || 'completed' !== $order->get_status()
        //            || $order->get_refunds()) {
        //            return;
        //        }

        $pdkOrder        = $this->orderRepository->get($order->get_id());
        $trackTraceLinks = $this->getTrackTraceLink($pdkOrder);

        if (empty($trackTraceLinks)) {
            return;
        }

        echo
        sprintf(
            '<p>%s %s</p>',
            apply_filters(
                'wcmyparcel_email_text',
                'You can track your order with the following Track & Trace link:',
                $order
            ),
            $trackTraceLinks['link']
        );
    }

    /**
     * @param  array    $actions
     * @param  WC_Order $order
     *
     * @return array
     * @throws Exception
     */
    public function showTrackTraceActionInMyAccount(array $actions, WC_Order $order): array
    {
        if (! Settings::get(GeneralSettings::TRACK_TRACE_IN_ACCOUNT, GeneralSettings::ID)) {
            return $actions;
        }

        $pdkOrder        = $this->orderRepository->get($order->get_id());
        $trackTraceArray = $this->getTrackTraceLink($pdkOrder);

        $actions[sprintf('myparcel_tracktrace_%s', $trackTraceArray['url'])] = [
            'url'  => $trackTraceArray['url'],
            'name' => apply_filters('wcmyparcel_myaccount_tracktrace_button', 'Track & Trace'),
        ];

        return $actions;
    }

    /**
     * @param  \MyParcelNL\Pdk\Plugin\Model\PdkOrder $pdkOrder
     *
     * @return array
     */
    private function getTrackTraceLink(PdkOrder $pdkOrder): array
    {
        if ($pdkOrder->shipments->isEmpty()) {
            return [];
        }

        /** @var \MyParcelNL\Pdk\Shipment\Model\Shipment $lastShipment */
        $lastShipment = $pdkOrder->shipments->last();
        $url          = $lastShipment->getTrackTraceLink();

        return [
            'url'     => $url,
            'link'    => sprintf('<a href="%s">%s</a>', $url, $lastShipment->barcode),
            'barcode' => $lastShipment->barcode,
        ];
    }
}
