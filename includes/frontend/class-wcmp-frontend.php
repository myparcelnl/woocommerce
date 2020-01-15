<?php

use MyParcelNL\Sdk\src\Support\Arr;
use WPO\WC\MyParcel\Compatibility\WC_Core as WCX;

if (! defined('ABSPATH')) {
    exit;
} // Exit if accessed directly

if (class_exists('WCMP_Frontend')) {
    return new WCMP_Frontend();
}

/**
 * Frontend views
 */
class WCMP_Frontend
{
    public function __construct()
    {
        new WCMP_Frontend_Track_Trace();

        // pickup address in email
        // woocommerce_email_customer_details:
        // @10 = templates/email-customer-details.php
        // @20 = templates/email-addresses.php
        add_action("woocommerce_email_customer_details", [$this, "email_pickup_html"], 19, 3);

        // pickup address on thank you page
        add_action("woocommerce_thankyou", [$this, "thankyou_pickup_html"], 10, 1);

        add_filter(
            "wpo_wcpdf_templates_replace_myparcel_delivery_options",
            [$this, "wpo_wcpdf_delivery_options"],
            10,
            2
        );

        // Initialize delivery options fees
        new WCMP_Cart_Fees();

        // Output most expensive shipping class in frontend data
        add_action("woocommerce_checkout_after_order_review", [$this, "injectShippingClassInput"], 100);
        add_action("woocommerce_update_order_review_fragments", [$this, "order_review_fragments"]);
    }

    /**
     * @param      $order
     * @param bool $sent_to_admin
     * @param bool $plain_text
     *
     * @throws Exception
     */
    public function email_pickup_html($order, $sent_to_admin = false, $plain_text = false)
    {
        WCMP()->admin->showDeliveryOptionsForOrder($order);
    }

    /**
     * @param $order_id
     *
     * @throws Exception
     */
    public function thankyou_pickup_html(int $order_id)
    {
        $order = wc_get_order($order_id);
        WCMP()->admin->showDeliveryOptionsForOrder($order);
    }

    /**
     * @param $replacement
     * @param $order
     *
     * @return false|string
     * @throws Exception
     */
    public function wpo_wcpdf_delivery_options($replacement, WC_Order $order)
    {
        ob_start();
        WCMP()->admin->showDeliveryOptionsForOrder($order);
        return ob_get_clean();
    }

    /**
     * Output the highest shipping class input
     */
    public function injectShippingClassInput(): void
    {
        echo '<div class="wcmp__shipping-data">';
        $this->renderHighestShippingClassInput();
        echo '</div>';
    }

    /**
     * @return string|void
     */
    public function renderHighestShippingClassInput()
    {
        $shipping_class = WCMP_Frontend::get_cart_shipping_class();

        if ($shipping_class) {
            return sprintf(
                '<input type="hidden" value="%s" name="myparcel_highest_shipping_class">',
                $shipping_class
            );
        }
    }

    /**
     * Get the most expensive shipping class in the cart
     * Requires WC2.4+
     * Only supports 1 package, takes the first
     *
     * @return bool|int
     * @throws \Exception
     */
    public static function get_cart_shipping_class()
    {
        if (version_compare(WOOCOMMERCE_VERSION, '2.4', '<')) {
            return false;
        }

        $chosen_method = WC()->session->get('chosen_shipping_methods')[0] ?? '';

        // get package
        $packages = WC()->cart->get_shipping_packages();
        $package  = current($packages);

        $shipping_method = WCMP_Export::get_shipping_method($chosen_method);

        if (empty($shipping_method)) {
            return false;
        }

        // get shipping classes from package
        $found_shipping_classes = $shipping_method->find_shipping_classes($package);

        return WCMP()->export->get_shipping_class(
            $shipping_method,
            $found_shipping_classes
        );
    }

    /**
     * @param $fragments
     *
     * @return mixed
     */
    public function order_review_fragments($fragments)
    {
        $myparcel_shipping_data          = $this->renderHighestShippingClassInput();
        $fragments['.wcmp__shipping-data'] = $myparcel_shipping_data;

        return $fragments;
    }

    /**
     * @param $order_id
     *
     * @return array|bool|mixed|void
     * @throws Exception
     */
    public static function getTrackTraceShipments($order_id): array
    {
        $order     = WCX::get_order($order_id);
        $shipments = WCMP_Admin::get_order_shipments($order);

        if (empty($shipments)) {
            return [];
        }

        foreach ($shipments as $shipment_id => $shipment) {
            $trackTrace = Arr::get($shipment, "track_trace");

            // skip concepts
            if (! $trackTrace) {
                unset($shipments[$shipment_id]);
                continue;
            }

            $track_trace_url = WCMP_Admin::getTrackTraceUrl(
                $order_id,
                $trackTrace
            );

            // add links & urls
            Arr::set($shipments, "$shipment_id.track_trace_url", $track_trace_url);
            Arr::set(
                $shipments,
                "$shipment_id.track_trace_link",
                sprintf(
                    '<a href="%s">%s</a>',
                    $track_trace_url,
                    $trackTrace
                )
            );
        }

        return $shipments;
    }

    /**
     * @param $order_id
     *
     * @return array|bool
     * @throws Exception
     */
    public static function getTrackTraceLinks($order_id): array
    {
        $track_trace_links = [];

        $consignments = self::getTrackTraceShipments($order_id);

        foreach ($consignments as $key => $consignment) {
            $track_trace_links[] = [
                "link" => $consignment["track_trace_link"],
                "url"  => $consignment["track_trace_url"],
            ];
        }

        return $track_trace_links;
    }
}

return new WCMP_Frontend();
