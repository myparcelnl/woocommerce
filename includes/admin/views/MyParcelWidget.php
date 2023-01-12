<?php

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\includes\admin\views;

use MyParcelNL\Sdk\src\Model\Recipient;
use MyParcelNL\WooCommerce\includes\admin\OrderSettings;
use TypeError;
use WC_Order;
use WCMP_Export;

defined('ABSPATH') or die();

class MyParcelWidget
{
    private const DEFAULT_ORDER_AMOUNT       = 5;
    private const DEFAULT_FETCH_ORDER_AMOUNT = 100;

    /**
     * @return void
     * @noinspection PhpUnused
     */
    public function addStyles(): void
    {
        $directory = explode('/includes', plugin_dir_url(__FILE__))[0] ?? null;
        $screen    = get_current_screen();

        if ($screen && $directory && 'dashboard' === $screen->id) {
            wp_enqueue_style('myparcel_admin_dashboard_widget_style', "$directory/assets/css/widget.css", [], '1.0');
        }
    }

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
     * @return void
     */
    public function myparcelDashboardWidgetConfigHandler(): void
    {
        $options            = get_option('woocommerce_myparcel_dashboard_widget') ?: $this->getDefaultWidgetConfig();
        $submit             = filter_input(INPUT_POST, 'submit');
        $ordersAmount       = (int) filter_input(INPUT_POST, 'orders_amount');
        $showMyParcelOrders = filter_input(INPUT_POST, 'showMyParcelOrders');

        if (isset($submit)) {
            if ($ordersAmount > 0) {
                $options['items']              = $ordersAmount;
                $options['showMyParcelOrders'] = $showMyParcelOrders;
            }

            update_option('woocommerce_myparcel_dashboard_widget', $options);
        }

        printf(
            "
              <p>
                <label>%s:</label>
                 <input class=\"form-control\" type=\"number\" min=\"1\" max=\"100\" step=\"1\" name=\"orders_amount\" value=\"%s\" />
              </p>
              <p>
                <label>%s:</label>
                <input name=\"showMyParcelOrders\" class=\"form-control\" type=\"checkbox\" %s />
              </p>",
            __('order_amount', 'woocommerce-myparcel'),
            esc_attr($options['items']),
            __('show_myparcel_orders_only', 'woocommerce-myparcel'),
            esc_attr(isset($options['showMyParcelOrders']) ? 'checked' : '')
        );
    }

    /**
     * @throws \Exception
     */
    public function myparcelDashboardWidgetHandler(): void
    {
        $orders = wc_get_orders(['limit' => self::DEFAULT_FETCH_ORDER_AMOUNT]);

        if (! $orders) {
            printf(esc_attr(__('no_orders_found', 'woocommerce-myparcel')));
            return;
        }

        $tableHeaders = sprintf(
            '<tr><th>%s</th><th>%s</th><th>%s</th></tr>',
            __('Order', 'woocommerce-myparcel'),
            __('Address', 'woocommerce-myparcel'),
            __('Status', 'woocommerce-myparcel')
        );
        $tableContent = '';

        foreach ($this->filterOrders($orders) as $order) {
            try {
                $orderSettings     = new OrderSettings($order);
                $orderId           = $order->get_id();
                $shippingRecipient = $orderSettings->getShippingRecipient();
                $shipmentIds       = (new WCMP_Export())->getShipmentIds([$orderId], ['exclude_concepts']);
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
            wp_kses($tableContent, [
                'td' => ['colspan' => [],],
                'tr' => ['onclick' => [], 'style' => [],],
            ])
        );
    }

    /**
     * @param  null|int                                 $orderId
     * @param  null|\MyParcelNL\Sdk\src\Model\Recipient $shippingRecipient
     * @param  null|string                              $shipmentStatus
     *
     * @return string
     */
    private function buildTableRow(
        int        $orderId = null,
        ?Recipient $shippingRecipient = null,
        ?string    $shipmentStatus = null
    ): string {
        $adminUrl = get_admin_url();

        if (! $shippingRecipient) {
            return $this->getIncompleteTableRow($orderId);
        }

        return "<tr onclick=\"window.location='{$adminUrl}post.php?post={$orderId}&action=edit';\" style=\"cursor: pointer !important;\">
                  <td>$orderId</td>
                  <td>{$shippingRecipient->getStreet()} {$shippingRecipient->getNumber()} {$shippingRecipient->getNumberSuffix()} {$shippingRecipient->getCity()}</td>
                  <td>
                    {$this->getShipmentStatusBadge($shipmentStatus)}
                  </td>
                </tr>";
    }

    /**
     * @param  array $orders
     *
     * @return \WC_Order[]
     * @throws \JsonException
     */
    private function filterOrders(array $orders): array
    {
        $widgetConfig       = get_option('woocommerce_myparcel_dashboard_widget') ?: $this->getDefaultWidgetConfig();
        $orderAmount        = $widgetConfig['items'] ?? self::DEFAULT_ORDER_AMOUNT;
        $showMyParcelOrders = $widgetConfig['showMyParcelOrders'] ?? true;

        $filteredOrders = array_filter($orders, static function ($order) use ($showMyParcelOrders) {
            if (! $order instanceof WC_Order) {
                return false;
            }

            $shippingClasses = $order->get_shipping_methods();
            $orderSettings   = new OrderSettings($order);

            if (! $showMyParcelOrders) {
                return true;
            }

            if (! $shippingClasses || ! $order->get_shipping_address_1() || $orderSettings->hasLocalPickup()) {
                return false;
            }

            return true;
        });

        return array_slice($filteredOrders, 0, $orderAmount);
    }

    /**
     * @return array
     */
    private function getDefaultWidgetConfig(): array
    {
        return [
            'items'              => self::DEFAULT_ORDER_AMOUNT,
            'showMyParcelOrders' => true,
        ];
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
                esc_html__('status_unknown', 'woocommerce-myparcel')
            );
        }

        return sprintf(
            '<tr onclick="window.location=\'/wp-admin/post.php?post=%s&action=edit\';" style="cursor: pointer !important;">
                      <td>%s</td>
                      <td colspan="2">%s</td>
                    </tr>',
            $orderId,
            $orderId,
            esc_html__('status_unknown', 'woocommerce-myparcel')
        );
    }

    /**
     * @return void
     */
    private function getLogoImg(): string
    {
        return sprintf('%s/assets/img/myparcel_logo_rgb.svg', WCMYPA()->plugin_url());
    }

    /**
     * @param  null|array $shipmentIds
     * @param  \WC_Order  $order
     *
     * @return null|string
     * @throws \Exception
     */
    private function getShipmentStatus(array $shipmentIds, WC_Order $order): ?string
    {
        if (empty($shipmentIds)) {
            return null;
        }

        $firstShipmentId = $shipmentIds[0][0] ?? null;

        if (! $firstShipmentId) {
            return null;
        }

        $shipment = WCMYPA()->export->getShipmentData([$firstShipmentId], $order);

        return $shipment[$firstShipmentId]['status'] ?? null;
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
}
