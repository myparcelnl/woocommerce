<?php

namespace WPO\WC\MyParcel\Compatibility;

use WC_Order;
use WPO\WC\MyParcel\Compatibility\Order as WCX_Order;

/**
 * Class for compatibility with the ChannelEngine plugin.
 *
 * @see     https://wordpress.org/plugins/channelengine-woocommerce
 * @see     https://github.com/channelengine/woocommerce
 * @package WPO\WC\MyParcel\Compatibility
 */
class WCMP_ChannelEngine_Compatibility
{
    /**
     * Add the created Track & Trace code and set shipping method to postnl in ChannelEngine's meta data
     *
     * @param WC_Order $order
     * @param          $data
     */
    public static function updateMetaOnExport(WC_Order $order, $data)
    {
        if (! class_exists('Channel_Engine') || WCX_Order::has_meta($order, "_shipping_ce_track_and_trace")) {
            return;
        }

        WCX_Order::update_meta_data($order, "_shipping_ce_track_and_trace", $data);

        // Todo: Check if this has to be changed
        WCX_Order::update_meta_data($order, "_shipping_ce_shipping_method", "PostNL");
    }
}
