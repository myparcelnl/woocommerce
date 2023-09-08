<?php

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Hooks;

use Exception;
use MyParcelNL\Pdk\App\Order\Contract\PdkOrderRepositoryInterface;
use MyParcelNL\Pdk\App\Order\Model\PdkOrder;
use MyParcelNL\Pdk\Facade\Language;
use MyParcelNL\Pdk\Facade\Pdk;
use MyParcelNL\Pdk\Facade\Settings;
use MyParcelNL\Pdk\Settings\Model\OrderSettings;
use MyParcelNL\Pdk\Shipment\Model\Shipment;
use MyParcelNL\WooCommerce\Facade\Filter;
use MyParcelNL\WooCommerce\Facade\WordPress;
use MyParcelNL\WooCommerce\Hooks\Contract\WordPressHooksInterface;
use WC_Order;

final class TrackTraceHooks implements WordPressHooksInterface
{
    /**
     * @var \MyParcelNL\Pdk\App\Order\Contract\PdkOrderRepositoryInterface
     */
    private $orderRepository;

    /**
     * @param  \MyParcelNL\Pdk\App\Order\Contract\PdkOrderRepositoryInterface $pdkOrderRepository
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
            Filter::apply('trackTraceInEmailPriority')
        );

        add_action(
            'woocommerce_after_order_details',
            [$this, 'renderTrackTraceInAccountOrderDetails'],
            Filter::apply('trackTraceInOrderDetailsPriority')
        );

        add_filter(
            'woocommerce_my_account_my_orders_actions',
            [$this, 'registerTrackTraceActions'],
            Filter::apply('trackTraceInMyAccountPriority'),
            2
        );
    }

    /**
     * @param  \WC_Order $wcOrder
     *
     * @return \MyParcelNL\Pdk\App\Order\Model\PdkOrder
     */
    public function getPdkOrder(WC_Order $wcOrder): PdkOrder
    {
        return $this->orderRepository->get($wcOrder);
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
        if (! $this->shouldRender(OrderSettings::TRACK_TRACE_IN_ACCOUNT, $wcOrder)) {
            return $actions;
        }

        $lastShipment = $this->getLastShipmentWithTrackTrace($wcOrder);
        $appInfo      = Pdk::getAppInfo();

        if ($lastShipment) {
            $actions["{$appInfo->name}_track_trace"] = [
                'url'  => $lastShipment->linkConsumerPortal,
                'name' => Filter::apply('trackTraceLabel', Language::translate('track_trace'), $lastShipment),
            ];
        }

        return $actions;
    }

    /**
     * @param  \WC_Order $order
     *
     * @return void
     */
    public function renderTrackTraceInAccountOrderDetails(WC_Order $order): void
    {
        if (! $this->shouldRender(OrderSettings::TRACK_TRACE_IN_ACCOUNT, $order)) {
            return;
        }

        $deliveryOptions = $this->getPdkOrder($order)->deliveryOptions;
        $date            = $deliveryOptions->getDateAsString();

        $rows = [
            [Language::translate('carrier'), $deliveryOptions->carrier->human],
            [Language::translate('package_type'), Language::translate("package_type_$deliveryOptions->packageType")],
        ];

        if ($date) {
            $rows[] = [Language::translate('delivery_moment'), $date];
        }

        ob_start();

        printf('<h2>%s</h2>', Language::translate('delivery_options'));

        WordPress::renderTable($rows);

        $this->renderTrackTraceLink($order, 'trackTraceInOrderDetailsText');

        printf('<section class="%s-delivery-options">%s</section>', Pdk::getAppInfo()->name, ob_get_clean());
    }

    /**
     * @param  \WC_Order $wcOrder
     *
     * @return void
     */
    public function renderTrackTraceInEmail(WC_Order $wcOrder): void
    {
        if (! $this->shouldRender(OrderSettings::TRACK_TRACE_IN_EMAIL, $wcOrder)) {
            return;
        }

        $this->renderTrackTraceLink($wcOrder, 'trackTraceInEmailText');
    }

    /**
     * @param  string    $setting
     * @param  \WC_Order $wcOrder
     *
     * @return bool
     */
    protected function shouldRender(string $setting, WC_Order $wcOrder): bool
    {
        if (! Settings::get($setting, OrderSettings::ID)) {
            return false;
        }

        // TODO: Replace with $pdkOrder->isDeliverable() when available
        return $this->getPdkOrder($wcOrder)->lines->containsStrict('product.isDeliverable', true);
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
        return $this->getPdkOrder($wcOrder)
            ->shipments
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
            Filter::apply($filter, Language::translate('track_trace_link'), $lastShipment),
            $this->createTrackTraceLink($lastShipment)
        );
    }
}
