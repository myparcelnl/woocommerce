<?php

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\includes\admin\views;

use MyParcelNL\Pdk\Base\Model\ContactDetails;
use MyParcelNL\WooCommerce\includes\adapter\PdkOrderFromWCOrderAdapter;
use MyParcelNL\WooCommerce\includes\Concerns\HasApiKey;
use TypeError;
use ExportActions;

defined('ABSPATH') or die();

class MyParcelWidget
{
    use HasApiKey;

    private const DEFAULT_ORDER_AMOUNT = 5;

    /**
     * @return void
     * @throws \Exception
     */
    public function loadWidget(): void
    {
        wp_add_dashboard_widget(
            'woocommerce_myparcel_dashboard_widget',
            __('MyParcel'),
            [$this, 'myparcelDashboardWidgetHandler'],
            [$this, 'myparcelDashboardWidgetConfigHandler']
        );
        add_action('admin_enqueue_scripts', [$this, 'addStyles']);
    }

    /**
     * @throws \Exception
     */
    public function myparcelDashboardWidgetHandler(): void
    {
        $orderAmount = get_option('woocommerce_myparcel_dashboard_widget')['items'] ?? self::DEFAULT_ORDER_AMOUNT;
        $orders      = wc_get_orders(
            [
                'limit' => $orderAmount,
            ]
        );

        if (! $orders) {
            printf(esc_attr(__('no_orders_found', 'woocommerce-myparcel')));

            return;
        }

        $tableHeaders = sprintf(
            '
            <tr>
              <th>%s</th>
              <th>%s</th>
              <th>%s</th>
            </tr>',
            'Order',
            'Address',
            'Status'
        );
        $tableContent = '';

        foreach ($orders as $order) {
            try {
                $pdkOrderAdapter   = new PdkOrderFromWCOrderAdapter($order);
                $orderId           = $order->get_id();
                $shippingRecipient = $pdkOrderAdapter->getShippingRecipient();
                // TODO: get shipment ID's
                $shipmentIds       = (new ExportActions())->getShipmentIds([$orderId], ['exclude_concepts']);
                $shipmentStatus    = $this->getShipmentStatus($shipmentIds, $order);

                $tableContent .= $this->buildTableRow($orderId, $shippingRecipient, $shipmentStatus);
            } catch (TypeError $e) {
                $tableContent .= $this->buildTableRow();
            }
        }

        printf(
            '
            <div class="logo-img">
              <img src="%s" alt="MyParcel logo">
            </div>
            <table class="table table-hover">
              %s%s
            </table>
            ',
            wp_kses_post($this->getLogoImg()),
            wp_kses_post($tableHeaders),
            $tableContent
        );
    }

    /**
     * @return void
     */
    public function addStyles(): void
    {
        $directory = explode('/includes', plugin_dir_url(__FILE__))[0];
        $screen    = get_current_screen();
        if ('dashboard' === $screen->id) {
            wp_enqueue_style('myparcel_admin_dashboard_widget_style', $directory . '/assets/css/widget.css', [], '1.0');
        }
    }

    /**
     * @return void
     */
    public function myparcelDashboardWidgetConfigHandler(): void
    {
        $options      = get_option('woocommerce_myparcel_dashboard_widget') ?: $this->getDefaultWidgetConfig();
        $submit       = filter_input(INPUT_POST, 'submit');
        $ordersAmount = (int) filter_input(INPUT_POST, 'orders_amount');

        if (isset($submit)) {
            if ($ordersAmount > 0) {
                $options['items'] = $ordersAmount;
            }

            update_option('woocommerce_myparcel_dashboard_widget', $options);
        }

        printf(
            sprintf(
                '
              <p>
                <label>
                    %s:
                </label>
                  <input
                    class="form-control"
                    type="number"
                    min="1"
                    max="100"
                    step="1"
                    name="orders_amount"
                    value="%s" />
              </p>',
                esc_attr(__('order_amount', 'woocommerce-myparcel')),
                esc_attr($options['items'])
            )
        );
    }

    /**
     * @param  null|int                                       $orderId
     * @param  null|\MyParcelNL\Pdk\Base\Model\ContactDetails $shippingRecipient
     * @param  null|string                                    $shipmentStatus
     *
     * @return string
     */
    private function buildTableRow(
        int            $orderId = null,
        ContactDetails $shippingRecipient = null,
        string         $shipmentStatus = null
    ): string {
        if (! $shippingRecipient) {
            return $this->getIncompleteTableRow($orderId);
        }

        return sprintf(
            '
                <tr onclick="window.location=\'/wp-admin/post.php?post=%s&action=edit\';" style="cursor: pointer !important;">
                  <td>%s</td>
                  <td>%s %s %s %s</td>
                  <td>
                    %s
                  </td>
                </tr>',
            $orderId,
            $orderId,
            $shippingRecipient->street,
            $shippingRecipient->number,
            $shippingRecipient->numberSuffix,
            $shippingRecipient->city,
            $this->getShipmentStatusBadge($shipmentStatus)
        );
    }

    /**
     * @return array
     */
    private function getDefaultWidgetConfig(): array
    {
        return [
            'items' => self::DEFAULT_ORDER_AMOUNT,
        ];
    }

    /**
     * @return void
     */
    private function getLogoImg(): string
    {
        return sprintf('%s/assets/img/myparcel_logo_rgb.svg', WCMYPA()->plugin_url());
    }

    /**
     * @param  null|string $status
     *
     * @return string
     */
    private function getShipmentStatusBadge(?string $status): string
    {
        return $status
            ? sprintf('<span class="badge badge-primary">%s</span>', $status)
            : sprintf(
                '<span class="badge badge-secondary">%s</span>',
                __('no_status_available', 'woocommerce-myparcel')
            );
    }

    /**
     * @param  null|array $shipmentIds
     * @param             $order
     *
     * @return null|string
     * @throws \Exception
     */
    private function getShipmentStatus(?array $shipmentIds, $order): ?string
    {
        if (! $shipmentIds) {
            return null;
        }

        $firstShipmentId = $shipmentIds[0][0];
        $shipment        = WCMYPA()->export->getShipmentData([$firstShipmentId], $order);

        return $shipment ? $shipment[$firstShipmentId]['status'] : null;
    }

    /**
     * @param  null|int $orderId
     *
     * @return string
     */
    private function getIncompleteTableRow(?int $orderId): string
    {
        if (! $orderId) {
            return sprintf(
                '
                <tr>
                  <td colspan="3">%s</td>
                </tr>',
                __('status_unknown', 'woocommerce-myparcel')
            );
        }

        return sprintf(
            '<tr onclick="window.location=\'/wp-admin/post.php?post=%s&action=edit\';" style="cursor: pointer !important;">
                      <td>%s</td>
                      <td colspan="2">%s</td>
                    </tr>',
            $orderId,
            $orderId,
            __('status_unknown', 'woocommerce-myparcel')
        );
    }
}
