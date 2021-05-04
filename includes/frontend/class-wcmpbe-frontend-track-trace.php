<?php

use WPO\WC\MyParcelBE\Compatibility\Order as WCX_Order;
use WPO\WC\MyParcelBE\Compatibility\WCMPBE_WCPDF_Compatibility;

if (! defined('ABSPATH')) {
    exit;
} // Exit if accessed directly

if (class_exists('WCMPBE_Frontend_Track_Trace')) {
    return;
}

/**
 * Track & Trace
 */
class WCMPBE_Frontend_Track_Trace
{
    public function __construct()
    {
        // Customer Emails
        add_action("woocommerce_email_before_order_table", [$this, "addTrackTraceToEmail"], 10, 2);

        // Track & Trace in my account
        add_filter("woocommerce_my_account_my_orders_actions", [$this, "showTrackTraceActionInMyAccount"], 10, 2);

        WCMPBE_WCPDF_Compatibility::add_filters();
    }

    /**
     * Filter the emails sent to customers, adding Track & Trace link(s) if related order is completed.
     *
     * @param WC_Order $order
     * @param bool     $sentToAdmin
     *
     * @throws Exception
     */
    public function addTrackTraceToEmail(WC_Order $order, bool $sentToAdmin): void
    {
        if (! WCMYPABE()->setting_collection->isEnabled(WCMPBE_Settings::SETTING_TRACK_TRACE_EMAIL)) {
            return;
        }

        if ($sentToAdmin || WCX_Order::get_status($order) !== "completed") {
            return;
        }

        $orderId         = WCX_Order::get_id($order);
        $trackTraceLinks = WCMPBE_Frontend::getTrackTraceLinks($orderId);

        if (empty($trackTraceLinks)) {
            return;
        }

        $createLinkCallback = function ($trackTrace) {
            return sprintf('<a href="%s">%s</a>', $trackTrace["url"], $trackTrace["link"]);
        };

        printf(
            '<p>%s %s</p>',
            apply_filters(
                "wcmyparcelbe_email_text",
                __("You can track your order with the following Track & Trace link:", "woocommerce-myparcelbe"),
                $order
            ),
            implode(
                '<br />',
                array_map($createLinkCallback, $trackTraceLinks)
            )
        );
    }

    /**
     * @param array    $actions
     * @param WC_Order $order
     *
     * @return array
     * @throws Exception
     */
    public function showTrackTraceActionInMyAccount(array $actions, WC_Order $order): array
    {
        if (! WCMYPABE()->setting_collection->isEnabled(WCMPBE_Settings::SETTING_TRACK_TRACE_MY_ACCOUNT)) {
            return $actions;
        }

        $order_id = WCX_Order::get_id($order);

        $consignments = WCMPBE_Frontend::getTrackTraceLinks($order_id);

        foreach ($consignments as $key => $consignment) {
            $actions['myparcelbe_tracktrace_' . $consignment['link']] = [
                'url'  => $consignment['url'],
                'name' => apply_filters(
                    'wcmyparcelbe_myaccount_tracktrace_button',
                    __('Track & Trace', 'woocommerce-myparcelbe')
                ),
            ];
        }

        return $actions;
    }
}
