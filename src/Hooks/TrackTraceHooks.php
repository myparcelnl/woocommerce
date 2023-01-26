<?php

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Hooks;

use Exception;
use MyParcelNL\Pdk\Facade\Settings;
use MyParcelNL\Pdk\Plugin\Model\PdkOrder;
use MyParcelNL\Pdk\Plugin\Repository\AbstractPdkOrderRepository;
use MyParcelNL\Pdk\Shipment\Model\Shipment;
use WC_Order;

class TrackTraceHooks implements WordPressHooksInterface
{
    /**
     * @var \MyParcelNL\Pdk\Plugin\Repository\AbstractPdkOrderRepository
     */
    private $orderRepository;

    /**
     * @param  \MyParcelNL\Pdk\Plugin\Repository\AbstractPdkOrderRepository $pdkOrderRepository
     */
    public function __construct(AbstractPdkOrderRepository $pdkOrderRepository)
    {
        $this->orderRepository = $pdkOrderRepository;
    }

    /**
     * @return void
     */
    public function apply(): void
    {
        add_action('woocommerce_email_before_order_table', [$this, 'addTrackTraceToEmail'], 10, 2);
        add_filter('woocommerce_my_account_my_orders_actions', [$this, 'showTrackTraceActionInMyAccount'], 10, 2);
    }

    /**
     * @param  \MyParcelNL\Pdk\Plugin\Model\PdkOrder $pdkOrder
     *
     * @return array
     */
    public function getTrackTraceLinks(PdkOrder $pdkOrder): array
    {
        $result = [];

        foreach ($pdkOrder->shipments->toArray() as $shipmentArray) {
            $shipment      = new Shipment($shipmentArray);
            $trackTraceUrl = $shipment->getTrackTraceLink();
            $result[]      = [
                'url'  => $trackTraceUrl,
                'link' => sprintf(
                    '<a href="%s">%s</a>',
                    $trackTraceUrl,
                    $shipment->barcode
                ),
            ];
        }

        return $result;
    }

    /**
     * @param  \WC_Order $order
     * @param  bool      $sentToAdmin
     *
     * @return void
     */
    public function addTrackTraceToEmail(WC_Order $order, bool $sentToAdmin): void
    {
        if ($sentToAdmin
            || ! Settings::get('general.trackTraceInEmail')
            || 'completed' !== $order->get_status()
            || $order->get_refunds()) {
            return;
        }

        $pdkOrder        = $this->orderRepository->get($order->get_id());
        $trackTraceLinks = $this->getTrackTraceLinks($pdkOrder);

        if (empty($trackTraceLinks)) {
            return;
        }

        $createLinkCallback = static function ($trackTrace) {
            return sprintf('<a href="%s">%s</a>', $trackTrace['url'], $trackTrace['link']);
        };

        echo wp_kses_post(
            sprintf(
                '<p>%s %s</p>',
                apply_filters(
                    'wcmyparcel_email_text',
                    __('You can track your order with the following Track & Trace link:', 'woocommerce-myparcel'),
                    $order
                ),
                implode(
                    '<br />',
                    array_map($createLinkCallback, $trackTraceLinks)
                )
            )
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
        if (! Settings::get('general.trackTraceInAccount')) {
            return $actions;
        }

        $pdkOrder  = $this->orderRepository->get($order->get_id());
        $shipments = $this->getTrackTraceLinks($pdkOrder);

        foreach ($shipments as $key => $shipment) {
            $actions['myparcel_tracktrace_' . $shipment['link']] = [
                'url'  => $shipment['url'],
                'name' => apply_filters(
                    'wcmyparcel_myaccount_tracktrace_button',
                    __('Track & Trace', 'woocommerce-myparcel')
                ),
            ];
        }

        return $actions;
    }
}
