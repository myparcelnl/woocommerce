<?php

use WPO\WC\MyParcelBE\Compatibility\Order as WCX_Order;
use WPO\WC\MyParcelBE\Compatibility\WCMP_WCPDF_Compatibility;

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

        WCMP_WCPDF_Compatibility::add_filters();
    }

    /**
     * @param WC_Order $order
     * @param          $sent_to_admin
     *
     * @throws Exception
     */
    public function track_trace_email(WC_Order $order, $sent_to_admin): void
    {
        if (! WCMP()->setting_collection->isEnabled(WCMP_Settings::SETTING_TRACK_TRACE_EMAIL)) {
            return;
        }

        if ($sent_to_admin) {
            return;
        }

        if (WCX_Order::get_status($order) !== 'completed') {
            return;
        }

        $order_id = WCX_Order::get_id($order);

        $track_trace_links = WCMP_Frontend::getTrackTraceLinks($order_id);

        if (! empty($track_trace_links)) {
            $email_text = __("You can track your order with the following bpost Track & Trace code:", "woocommerce-myparcelbe");
            $email_text = apply_filters("wcmyparcelbe_email_text", $email_text, $order);
            ?>
            <p><?php echo $email_text . ' ' . implode(', ', $track_trace_links); ?></p>
            <?php
        }
    }

    /**
     * @param array    $actions
     * @param WC_Order $order
     *
     * @return array
     * @throws Exception
     */
    public function track_trace_myaccount(array $actions, WC_Order $order): array
    {
        if (! WCMP()->setting_collection->isEnabled(WCMP_Settings::SETTING_TRACK_TRACE_MY_ACCOUNT)) {
            return $actions;
        }

        $order_id = WCX_Order::get_id($order);

        $consignments = WCMP()->admin->get_track_trace_shipments($order_id);

        foreach ($consignments as $key => $consignment) {
            $actions['myparcelbe_tracktrace_' . $consignment['track_trace']] = [
                'url'  => $consignment['track_trace_url'],
                'name' => apply_filters(
                    'wcmyparcelbe_myaccount_tracktrace_button',
                    __('Track & Trace', 'wooocommerce-myparcelbe')
                ),
            ];
        }

        return $actions;
    }
}

return new WCMP_Frontend_Track_Trace();
