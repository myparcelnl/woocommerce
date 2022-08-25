<?php

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\includes\admin\views;

use MyParcelNL\Sdk\src\Model\Recipient;
use MyParcelNL\WooCommerce\includes\admin\OrderSettings;
use MyParcelNL\WooCommerce\includes\Concerns\HasApiKey;
use TypeError;
use WC_Order;
use WCMP_Export;
use WCMP_Export_Consignments;
use WCMYPA_Settings;

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

        $orders = $this->filterOrders($orders);

        $tableHeaders = sprintf(
            '
            <tr>
              <th>%s</th>
              <th>%s</th>
              <th>%s</th>
            </tr>',
            __('Order', 'woocommerce-myparcel'),
            __('Address', 'woocommerce-myparcel'),
            __('Status', 'woocommerce-myparcel')
        );
        $tableContent = '';

        foreach ($orders as $order) {
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
              </p>
              <p>
                <label>
                    %s:
                </label>
                  <input
                    name="showMyParcelOrders"
                    class="form-control"
                    type="checkbox"
                    %s
                    />
              </p>',
                esc_attr(__('order_amount', 'woocommerce-myparcel')),
                esc_attr($options['items']),
                esc_attr(__('show_myparcel_orders_only', 'woocommerce-myparcel')),
                isset($options['showMyParcelOrders']) ? 'checked' : ''
            )
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
        int       $orderId = null,
        Recipient $shippingRecipient = null,
        string    $shipmentStatus = null
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
            $shippingRecipient->getStreet(),
            $shippingRecipient->getNumber(),
            $shippingRecipient->getNumberSuffix(),
            $shippingRecipient->getCity(),
            $this->getShipmentStatusBadge($shipmentStatus)
        );
    }

    /**
     * @param  \WC_Order $order
     *
     * @return null|string
     */
    private function findHighestShippingClass(WC_Order $order): ?string
    {
        $metaData = $order->get_meta_data();

        foreach ($metaData as $item) {
            $data = $item->get_data();

            if ('_myparcel_highest_shipping_class' === $data['key']) {
                return $data['value'];
            }
        }

        return null;
    }

    /**
     * @param  array $orders
     *
     * @return array
     */
    private function filterOrders(array $orders): array
    {
        $myParcelMethods    = WCMP_Export_Consignments::getSetting(
            WCMYPA_Settings::SETTING_SHIPPING_METHODS_PACKAGE_TYPES
        );
        $shippingMethods    = $this->flattenArray($myParcelMethods);
        $showMyParcelOrders = get_option('woocommerce_myparcel_dashboard_widget')['showMyParcelOrders'];

        return array_filter($orders, function ($order) use ($shippingMethods, $showMyParcelOrders) {
            if (! $order->get_shipping_address_1()) {
                return false;
            }

            if (! $showMyParcelOrders) {
                return $order;
            }

            $highestShippingClass = $this->findHighestShippingClass($order);
            $shippingClasses      = $order->get_shipping_methods();
            $shippingClass        = reset($shippingClasses)->get_method_id();

            if (in_array($shippingClass . ':' . $highestShippingClass, $shippingMethods, true)) {
                return $order;
            }

            return false;
        });
    }

    /**
     * @param  array $array
     *
     * @return array
     */
    private function flattenArray(array $array): array
    {
        $return = [];
        array_walk_recursive($array, static function ($a) use (&$return) { $return[] = $a; });
        return $return;
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
