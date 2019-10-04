<?php

use MyParcelNL\Sdk\src\Model\DeliveryOptions\DeliveryOptions;
use WPO\WC\MyParcelBE\Compatibility\Order as WCX_Order;

if (! defined('ABSPATH')) {
    exit;
} // Exit if accessed directly

if (class_exists('WCMP_Frontend_Track_Trace')) {
    return new WCMP_Frontend_Track_Trace();
}

/**
 * Track & Trace
 */
class WCMP_Frontend_Track_Trace
{
    public function __construct()
    {
        // Customer Emails
        add_action("woocommerce_email_before_order_table", [$this, "track_trace_email"], 10, 2);

        // Track & Trace in my account
        add_filter("woocommerce_my_account_my_orders_actions", [$this, "track_trace_myaccount"], 10, 2);

        // WooCommerce PDF Invoices & Packing Slips Premium Templates compatibility
        add_filter("wpo_wcpdf_templates_replace_myparcelbe_tracktrace", [$this, "wpo_wcpdf_tracktrace"], 10, 2);
        add_filter(
            "wpo_wcpdf_templates_replace_myparcelbe_tracktrace_link",
            [$this, "wpo_wcpdf_tracktrace_link"],
            10,
            2
        );
    }

    /**
     * @param WC_Order $order
     * @param          $sent_to_admin
     */
    public function track_trace_email(WC_Order $order, $sent_to_admin): void
    {
        if (! WCMP()->setting_collection->isEnabled('email_tracktrace')) {
            return;
        }

        if ($sent_to_admin) {
            return;
        }

        if (WCX_Order::get_status($order) != 'completed') {
            return;
        }

        $order_id = WCX_Order::get_id($order);

        $tracktrace_links = WCMP()->admin->get_tracktrace_links($order_id);
        if (! empty($tracktrace_links)) {
            $email_text = __("You can track your order with the following bpost Track & Trace code:", "woocommerce-myparcelbe");
            $email_text = apply_filters("wcmyparcelbe_email_text", $email_text, $order);
            ?>
            <p><?php echo $email_text . ' ' . implode(', ', $tracktrace_links); ?></p>
            <?php
        }
    }

    /**
     * @param array    $actions
     * @param WC_Order $order
     *
     * @return array
     */
    public function track_trace_myaccount(array $actions, WC_Order $order): array
    {
        if (! WCMP()->setting_collection->isEnabled('myaccount_tracktrace')) {
            return $actions;
        }

        $order_id = WCX_Order::get_id($order);

        if ($consignments = WCMP()->admin->get_tracktrace_shipments($order_id)) {
            foreach ($consignments as $key => $consignment) {
                $actions['myparcelbe_tracktrace_' . $consignment['tracktrace']] = [
                    'url'  => $consignment['tracktrace_url'],
                    'name' => apply_filters(
                        'wcmyparcelbe_myaccount_tracktrace_button',
                        __('Track & Trace', 'wooocommerce-myparcelbe')
                    ),
                ];
            }
        }

        return $actions;
    }

    /**
     * @param $replacement
     * @param $order
     *
     * @return string
     */
    public function wpo_wcpdf_tracktrace($replacement, $order)
    {
        if ($shipments = WCMP()->admin->get_tracktrace_shipments(WCX_Order::get_id($order))) {
            $tracktrace = [];
            foreach ($shipments as $shipment) {
                if (! empty($shipment['tracktrace'])) {
                    $tracktrace[] = $shipment['tracktrace'];
                }
            }
            $replacement = implode(', ', $tracktrace);
        }

        return $replacement;
    }

    /**
     * @param $replacement
     * @param $order
     *
     * @return string
     */
    public function wpo_wcpdf_tracktrace_link($replacement, $order)
    {
        $tracktrace_links = WCMP()->admin->get_tracktrace_links(WCX_Order::get_id($order));
        if (! empty($tracktrace_links)) {
            $replacement = implode(', ', $tracktrace_links);
        }

        return $replacement;
    }
}

return new WCMP_Frontend_Track_Trace();
