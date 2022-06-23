<?php

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\includes\Widget;

use MyParcelNL\WooCommerce\includes\admin\OrderSettings;

defined('ABSPATH') or die();

class MyParcelWidget
{
    public const variabele = 'hoi';

    /**
     * @return void
     */
    public function loadWidget()
    {
        wp_add_dashboard_widget(
            'woocommerce_myparcel_dashboard_widget',
            __('MyParcel'),
            [$this, 'myparcelDashboardWidgetHandler']
        );
    }

    public function myparcelDashboardWidgetHandler()
    {
        $orders = wc_get_orders([
            'limit' => 15,
        ]);

        $tableHeaders = '<tr>
    <th>Order Id</th>
    <th>Address</th>
    <th>Status</th>
  </tr>';
        $tablecontent = '';

        foreach ($orders as $order) {
            $orderSettings = new OrderSettings($order);
            $shippingRecipient = $orderSettings->getShippingRecipient();
            $tablecontent .= sprintf(
                '<tr>
<td>%s</td>
<td>%s %s %s %s</td>
<td>%s</td>
</tr>',
                $order->get_id(),
                $shippingRecipient->getStreet(),
                $shippingRecipient->getNumber(),
                $shippingRecipient->getNumberSuffix(),
                $shippingRecipient->getCity(),
                $order->get_status()
            );
        }

        echo sprintf('<table>%s%s</table>', $tableHeaders, $tablecontent);

//        $feeds = array(
//            array(
//                'url'          => 'https://www.cssigniter.com/blog/feed/',
//                'items'        =>2,
//                'show_summary' => 1,
//                'show_author'  => 0,
//                'show_date'    => 1,
//            ),
//        );
//
//        wp_dashboard_primary_output( 'woocommerce_myparcel_dashboard_widget', $feeds );
    }
}
