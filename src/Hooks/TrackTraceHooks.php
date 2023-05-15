<?php

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Hooks;

use Exception;
use MyParcelNL\Pdk\Facade\LanguageService;
use MyParcelNL\Pdk\Facade\Pdk;
use MyParcelNL\Pdk\Facade\Settings;
use MyParcelNL\Pdk\Plugin\Contract\PdkOrderRepositoryInterface;
use MyParcelNL\Pdk\Settings\Model\GeneralSettings;
use MyParcelNL\Pdk\Shipment\Model\Shipment;
use MyParcelNL\WooCommerce\Facade\Filter;
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
        add_action(
            'woocommerce_email_before_order_table',
            [$this, 'renderTrackTraceInEmail'],
            Filter::apply('trackTraceInEmailPriority'),
            2
        );

        add_action(
            'woocommerce_order_details_after_order_table',
            [$this, 'renderTrackTraceInOrderDetails'],
            Filter::apply('trackTraceInOrderDetailsPriority'),
            2
        );

        add_filter(
            'woocommerce_my_account_my_orders_actions',
            [$this, 'registerTrackTraceActions'],
            Filter::apply('trackTraceInMyAccountPriority'),
            2
        );
    }

    /**
     * @param  array    $actions
     * @param  WC_Order $wcOrder
     *
     * @return array
     * @throws Exception
     */
    public function registerTrackTraceActions(array $actions, WC_Order $wcOrder): array
    {
        if (! Settings::get(GeneralSettings::TRACK_TRACE_IN_ACCOUNT, GeneralSettings::ID)) {
            return $actions;
        }

        $lastShipment = $this->getLastShipmentWithTrackTrace($wcOrder);
        $appInfo      = Pdk::getAppInfo();

        if ($lastShipment) {
            $actions["{$appInfo->name}_track_trace"] = [
                'url'  => $lastShipment->linkConsumerPortal,
                'name' => Filter::apply('trackTraceLabel', LanguageService::translate('track_trace'), $lastShipment),
            ];
        }

        return $actions;
    }

    /**
     * @param  \WC_Order $order
     *
     * @return void
     */
    public function renderTrackTraceInEmail(WC_Order $order): void
    {
        if (! Settings::get(GeneralSettings::TRACK_TRACE_IN_EMAIL, GeneralSettings::ID)) {
            return;
        }

        $this->renderTrackTraceLink($order, 'trackTraceInEmailText');
    }

    /**
     * @param  \WC_Order $order
     *
     * @return void
     */
    public function renderTrackTraceInOrderDetails(WC_Order $order): void
    {
        if (! Settings::get(GeneralSettings::TRACK_TRACE_IN_ACCOUNT, GeneralSettings::ID)) {
            return;
        }

        $this->renderTrackTraceLink($order, 'trackTraceInOrderDetailsText');
    }

    /**
     * @param  \MyParcelNL\Pdk\Shipment\Model\Shipment $lastShipment
     *
     * @return string
     * @noinspection HtmlUnknownTarget
     */
    private function createTrackTraceLink(Shipment $lastShipment): string
    {
        return sprintf('<a href="%s">%s</a>', $lastShipment->linkConsumerPortal, $lastShipment->barcode);
    }

    /**
     * @param  \WC_Order $wcOrder
     *
     * @return null|\MyParcelNL\Pdk\Shipment\Model\Shipment
     */
    private function getLastShipmentWithTrackTrace(WC_Order $wcOrder): ?Shipment
    {
        $pdkOrder = $this->orderRepository->get($wcOrder);

        return $pdkOrder->shipments
            ->where('linkConsumerPortal', '!=', null)
            ->last();
    }

    /**
     * @param  \WC_Order $wcOrder
     * @param  string    $filter
     *
     * @return void
     */
    private function renderTrackTraceLink(WC_Order $wcOrder, string $filter): void
    {
        $lastShipment = $this->getLastShipmentWithTrackTrace($wcOrder);

        if (! $lastShipment) {
            return;
        }

        printf(
            '<p>%s %s</p>',
            Filter::apply($filter, LanguageService::translate('track_trace_link'), $lastShipment),
            $this->createTrackTraceLink($lastShipment)
        );
    }
}
