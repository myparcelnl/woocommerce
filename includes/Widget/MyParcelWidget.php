<?php

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\includes\Widget;

use MyParcelNL\WooCommerce\includes\admin\OrderSettings;
use WCMP_Export;

defined('ABSPATH') or die();

class MyParcelWidget
{
    /**
     * @return void
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
    public function myparcelDashboardWidgetHandler()
    {
        $orderAmount = get_option('woocommerce_myparcel_dashboard_widget')['items'] ?? 0;
        $orders      = wc_get_orders([
            'limit' => $orderAmount,
        ]);

        if (! $orders) {
            echo '<h4>No orders found</h4>';
            return;
        }

        $tableHeaders = sprintf('
  <tr class=""><th>%s</th><th>%s</th><th>%s</th></tr>', 'Order', 'Address', 'Status');
        $tableContent = '';

        foreach ($orders as $order) {
            $orderSettings     = new OrderSettings($order);
            $orderId           = $order->get_id();
            $shippingRecipient = $orderSettings->getShippingRecipient();
            $shipmentIds       = (new WCMP_Export())->getShipmentIds([$orderId], ['exclude_concepts']);
            $shipment          = WCMYPA()->export->getShipmentData($shipmentIds ? $shipmentIds[0] : [], $order);

            $shipmentStatus = null;

            if ($shipment) {
                $shipmentStatus = $shipment[$shipmentIds[0][0]]['status'];
            }

            $tableContent      .= sprintf(
                '
<tr onclick="window.location=\'/wp-admin/post.php?post='. $orderId .'&action=edit\';" style="cursor: pointer !important;">
  <td>%s</td>
  <td>%s %s %s %s</td>
  <td>
    <span class="badge badge-primary">%s</span>
  </td>
</tr>',
                $orderId,
                $shippingRecipient->getStreet(),
                $shippingRecipient->getNumber(),
                $shippingRecipient->getNumberSuffix(),
                $shippingRecipient->getCity(),
                $shipmentStatus
            );
        }

        echo sprintf('
<div class="card">
  <div class="card-header">
      <img src="https://www.myparcel.nl/assets/novio/img/MyParcel_logo_wit.svg" alt="Card image cap">
   </div>
  <div class="card-body">
    <table class="table table-hover">
      %s%s
    </table>
  </div>
</div>
', $tableHeaders, $tableContent);
    }

    public function addStyles()
    {
        $directory = explode('/includes', plugin_dir_url( __FILE__ ))[0];
        $screen = get_current_screen();
        if ('dashboard' === $screen->id) {
            wp_enqueue_style( 'myparcel_admin_dashboard_widget_style', $directory . '/assets/css/widget.css', array(), '1.0' );
        }
    }

    /**
     * @return void
     */
    public function myparcelDashboardWidgetConfigHandler(): void
    {
        $options = get_option('woocommerce_myparcel_dashboard_widget') ?: $this->getDefaultWidgetConfig();

        if ( isset( $_POST['submit'] ) ) {
            if ( isset( $_POST['orders_amount'] ) && (int) $_POST['orders_amount'] > 0 ) {
                $options['items'] = (int) $_POST['orders_amount'];
            }

            update_option( 'woocommerce_myparcel_dashboard_widget', $options );
        }

        ?>
        <div class="card">
          <div class="card-header">
            <img src="https://www.myparcel.nl/assets/novio/img/MyParcel_logo_wit.svg" alt="Card image cap">
          </div>
        </div>
        <p>
            <div class="form-group">
                <label><?php _e( 'Number of orders:'); ?>
                    <input class="form-control" type="number" min="1" max="100" step="1" name="orders_amount" value="<?php echo esc_attr( $options['items'] ); ?>" />
                </label>
            </div>
        </p>
        <?php
    }

    /**
     * @return array
     */
    function getDefaultWidgetConfig(): array
    {
        return [
            'items' => 10,
        ];
    }
}
