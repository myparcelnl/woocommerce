<?php

namespace WPO\WC\MyParcelBE\Compatibility;

use Exception;
use WCMP_Frontend;
use WPO\WC\MyParcelBE\Compatibility\Order as WCX_Order;

/**
 * Class for compatibility with the WooCommerce PDF Invoices & Packing Slips Premium Templates plugin.
 *
 * @package WPO\WC\MyParcelBE\Compatibility
 */
class WCMP_WCPDF_Compatibility
{
    public static function add_filters()
    {
        // WooCommerce PDF Invoices & Packing Slips Premium Templates compatibility
        add_filter(
            "wpo_wcpdf_templates_replace_myparcelbe_track_trace",
            ["WCMP_WCPDF_Compatibility", "track_trace"],
            10,
            2
        );

        add_filter(
            "wpo_wcpdf_templates_replace_myparcelbe_track_trace_link",
            ["WCMP_WCPDF_Compatibility", "track_trace_link"],
            10,
            2
        );
    }

    /**
     * @param $replacement
     * @param $order
     *
     * @return string
     * @throws Exception
     */
    public function track_trace($replacement, $order)
    {
        $shipments = WCMP_Frontend::getTrackTraceShipments(WCX_Order::get_id($order));

        $track_trace = [];

        foreach ($shipments as $shipment) {
            if (! empty($shipment['link'])) {
                $track_trace[] = $shipment['link'];
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
    public function track_trace_link($replacement, $order)
    {
        $track_trace_links = WCMP_Frontend::getTrackTraceShipments(WCX_Order::get_id($order));

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
