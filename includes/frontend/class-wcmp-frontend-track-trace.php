<?php

declare(strict_types=1);

use WPO\WC\MyParcel\Compatibility\Order as WCX_Order;
use WPO\WC\MyParcel\Compatibility\WCMP_WCPDF_Compatibility;

if (! defined('ABSPATH')) {
    exit;
} // Exit if accessed directly

if (class_exists('WCMP_Frontend_Track_Trace')) {
    return;
}

/**
 * Track & Trace
 */
class WCMP_Frontend_Track_Trace
{
    public function __construct()
    {
        // Customer Emails
        add_action("woocommerce_email_before_order_table", [$this, "addTrackTraceToEmail"], 10, 2);

        // Track & Trace in my account
        add_filter("woocommerce_my_account_my_orders_actions", [$this, "showTrackTraceActionInMyAccount"], 10, 2);

        WCMP_WCPDF_Compatibility::add_filters();
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
        if (! WCMYPA()->setting_collection->isEnabled(WCMYPA_Settings::SETTING_TRACK_TRACE_EMAIL)) {
            return;
        }

        if ($sentToAdmin || WCX_Order::get_status($order) !== 'completed' || $order->get_refunds()) {
            return;
        }

        $orderId         = WCX_Order::get_id($order);
        $trackTraceLinks = WCMP_Frontend::getTrackTraceLinks($orderId);

        if (empty($trackTraceLinks)) {
            return;
        }

        $createLinkCallback = function ($trackTrace) {
            return sprintf('<a href="%s">%s</a>', $trackTrace['url'], $trackTrace['link']);
        };

        echo wp_kses_post(sprintf(
            '<p>%s %s</p>',
            apply_filters(
                'wcmyparcel_email_text',
                __('You can track your order with the following Track & Trace link:', 'woocommerce-myparcel'),
                $order
            ),
            implode(
                '<br />',
                array_map($createLinkCallback, $trackTraceLinks)
            )
        ));
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
        if (! WCMYPA()->setting_collection->isEnabled(WCMYPA_Settings::SETTING_TRACK_TRACE_MY_ACCOUNT)) {
            return $actions;
        }

        $order_id = WCX_Order::get_id($order);

        $consignments = WCMP_Frontend::getTrackTraceLinks($order_id);

        foreach ($consignments as $key => $consignment) {
            $actions['myparcel_tracktrace_' . $consignment['link']] = [
                'url'  => $consignment['url'],
                'name' => apply_filters(
                    'wcmyparcel_myaccount_tracktrace_button',
                    __('Track & Trace', 'woocommerce-myparcel')
                ),
            ];
        }

        return $actions;
    }
}
