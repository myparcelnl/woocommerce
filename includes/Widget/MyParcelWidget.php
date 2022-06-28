<?php

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\includes\Widget;

use MyParcelNL\WooCommerce\includes\admin\OrderSettings;
use MyParcelNL\WooCommerce\includes\Concerns\HasApiKey;
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
        if (! $this->isWidgetEnabled()) {
            return;
        }

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
        $orders      = wc_get_orders(['limit' => $orderAmount,]);

        if (! $orders) {
            echo '<h4>No orders found</h4>';
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
            $orderSettings     = new OrderSettings($order);
            $orderId           = $order->get_id();
            $shippingRecipient = $orderSettings->getShippingRecipient();
            $shipmentIds       = (new WCMP_Export())->getShipmentIds([$orderId], ['exclude_concepts']);
            $shipmentStatus    = $this->getShipmentStatus($shipmentIds, $order);
            $tableContent      .= sprintf(
                '
                <tr onclick="window.location=\'/wp-admin/post.php?post=' . $orderId . '&action=edit\';" style="cursor: pointer !important;">
                  <td>%s</td>
                  <td>%s %s %s %s</td>
                  <td>
                    %s
                  </td>
                </tr>',
                $orderId,
                $shippingRecipient->getStreet(),
                $shippingRecipient->getNumber(),
                $shippingRecipient->getNumberSuffix(),
                $shippingRecipient->getCity(),
                $this->getShipmentStatusBadge($shipmentStatus)
            );
        }

        echo sprintf(
            '
            <img src="%s" alt="MyParcel logo">
            <table class="table table-hover">
              %s%s
            </table>
            ',
            $this->getLogoImg(),
            $tableHeaders,
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
        $options = get_option('woocommerce_myparcel_dashboard_widget') ?: $this->getDefaultWidgetConfig();

        if (isset($_POST['submit'])) {
            if (isset($_POST['orders_amount']) && (int) $_POST['orders_amount'] > 0) {
                $options['items'] = (int) $_POST['orders_amount'];
            }

            update_option('woocommerce_myparcel_dashboard_widget', $options);
        }

        ?>
      <p>
      <div class="form-group">
        <label><?php
            _e('Number of orders:'); ?>
          <input
            class="form-control"
            type="number"
            min="1"
            max="100"
            step="1"
            name="orders_amount"
            value="<?php
            echo esc_attr($options['items']); ?>" />
        </label>
      </div></p>
        <?php
    }

    /**
     * @return array
     */
    private function getDefaultWidgetConfig(): array
    {
        return [
            'items' => 5,
        ];
    }

    /**
     * @return bool
     */
    private function isWidgetEnabled(): bool
    {
        return (bool) WCMP_Export_Consignments::getSetting(WCMYPA_Settings::SETTING_SHOW_WIDGET);
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
            : sprintf('<span class="badge badge-secondary">%s</span>', __('no_status_available', 'woocommerce-myparcel'));
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
        return $shipment[$firstShipmentId]['status'];
    }
}
