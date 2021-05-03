<?php

namespace WPO\WC\MyParcelBE\Compatibility;

use Exception;
use WCMPBE_Frontend;
use WPO\WC\MyParcelBE\Compatibility\Order as WCX_Order;

/**
 * Class for compatibility with the WooCommerce PDF Invoices & Packing Slips Premium Templates plugin.
 *
 * @package WPO\WC\MyParcelBE\Compatibility
 */
class WCMPBE_WCPDF_Compatibility
{
    public static function add_filters()
    {
        // WooCommerce PDF Invoices & Packing Slips Premium Templates compatibility
        add_filter("wpo_wcpdf_templates_replace_myparcel_tracktrace", [__CLASS__, "track_trace"], 10, 2);
        add_filter("wpo_wcpdf_templates_replace_myparcel_track_trace", [__CLASS__, "track_trace"], 10, 2);

        add_filter("wpo_wcpdf_templates_replace_myparcel_tracktrace_link", [__CLASS__, "track_trace_link"], 10, 2);
        add_filter("wpo_wcpdf_templates_replace_myparcel_track_trace_link", [__CLASS__, "track_trace_link"], 10, 2);
    }

    /**
     * @param $replacement
     * @param $order
     *
     * @return string
     * @throws Exception
     */
    public function track_trace($replacement, $order): string
    {
        $shipments = WCMPBE_Frontend::getTrackTraceShipments(WCX_Order::get_id($order));

        $track_trace = [];

        foreach ($shipments as $shipment) {
            if (! empty($shipment['track_trace'])) {
                $track_trace[] = $shipment['track_trace'];
            }
        }

        return implode(', ', $track_trace);
    }

    /**
     * @param $replacement
     * @param $order
     *
     * @return string
     * @throws Exception
     */
    public function track_trace_link($replacement, $order): string
    {
        $track_trace_links = WCMPBE_Frontend::getTrackTraceLinks(WCX_Order::get_id($order));

        $track_trace_links = array_map(
            function ($link) {
                return $link["link"];
            },
            $track_trace_links
        );

        if (! empty($track_trace_links)) {
            $replacement = implode(', ', $track_trace_links);
        }

        return $replacement;
    }
}
