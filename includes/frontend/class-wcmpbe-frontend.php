<?php

use MyParcelNL\Sdk\src\Support\Arr;
use WPO\WC\MyParcelBE\Compatibility\WC_Core as WCX;
use WPO\WC\MyParcelBE\Compatibility\Order as WCX_Order;

if (! defined('ABSPATH')) {
    exit;
} // Exit if accessed directly

if (class_exists('WCMPBE_Frontend')) {
    return new WCMPBE_Frontend();
}

/**
 * Frontend views
 */
class WCMPBE_Frontend
{
    public function __construct()
    {
        new WCMPBE_Frontend_Track_Trace();

		// Shipment information in confirmation mail
	    add_action("woocommerce_email_customer_details", [$this, "confirmationEmail"], 19, 3);

	    // Shipment information in my account
	    add_action('woocommerce_view_order', [$this, "confirmationOrderReceived"]);

	    // Shipment information on the thank you page
	    add_action("woocommerce_thankyou", [$this, "confirmationOrderReceived"], 10, 1);
        add_filter("wpo_wcpdf_templates_replace_myparcel_delivery_options", [$this, "wpo_wcpdf_delivery_options"], 10, 2);

        // Initialize delivery options fees
        new WCMPBE_Cart_Fees();

        // Output most expensive shipping class in frontend data
        add_action("woocommerce_checkout_before_order_review", [$this, "injectShippingClassInput"], 100);
        add_action("woocommerce_update_order_review_fragments", [$this, "order_review_fragments"]);

        // Ajax
        add_action('wp_ajax_get_highest_shipping_class', [$this, 'ajaxGetHighestShippingClass']);
        add_action('wp_ajax_nopriv_get_highest_shipping_class', [$this, 'ajaxGetHighestShippingClass']);
    }

    /**
     * @param \WC_Order $order
     *
     * @throws \Exception
     */
    public function confirmationEmail(WC_Order $order): void
    {
	    WCMYPABE()->admin->showShipmentConfirmation($order, true);
    }

    /**
     * @param int $order_id
     *
     * @throws Exception
     */
    public function confirmationOrderReceived(int $order_id): void
    {
        $order = wc_get_order($order_id);
        WCMYPABE()->admin->showShipmentConfirmation($order, false);
    }

    /**
     * @param string   $replacement
     * @param WC_Order $order
     *
     * @return string
     * @throws Exception
     */
    public function wpo_wcpdf_delivery_options(string $replacement, WC_Order $order): string
    {
        ob_start();
        WCMYPABE()->admin->showShipmentConfirmation($order, false);

        return ob_get_clean();
    }

    /**
     * @param string   $replacement
     * @param WC_Order $order
     *
     * @return string
     * @throws Exception
     */
    public function wpo_wcpdf_delivery_date(string $replacement, WC_Order $order): string
    {
        $deliveryOptions = WCMYPABE_Admin::getDeliveryOptionsFromOrder($order);
        $deliveryDate    = $deliveryOptions->getDate();

        if ($deliveryDate) {
            return wc_format_datetime(new WC_DateTime($deliveryDate), 'l d-m');
        }

        return $replacement;
    }

    /**
     * Output the highest shipping class input
     *
     * @throws Exception
     */
    public function injectShippingClassInput(): void
    {
        echo '<div class="wcmpbe__shipping-data">';
        $this->renderHighestShippingClassInput();
        echo '</div>';
    }

    /**
     * @return string|void
     * @throws Exception
     */
    public function renderHighestShippingClassInput()
    {
        $shipping_class = WCMPBE_Frontend::get_cart_shipping_class();

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
     * @return null|int
     * @throws \Exception
     */
    public static function get_cart_shipping_class(): ?int
    {
        if (version_compare(WOOCOMMERCE_VERSION, '2.4', '<')) {
            return null;
        }

        $shippingMethodString = WC()->session->get('chosen_shipping_methods')[0] ?? '';
        $shippingMethod       = WCMPBE_Export::getShippingMethod($shippingMethodString);

        if (empty($shippingMethod)) {
            return null;
        }

        if (method_exists($shippingMethod, 'find_shipping_classes')) {
            // get package
            $packages = WC()->cart->get_shipping_packages();
            $package  = current($packages);

            // get shipping classes from package
            $shippingClasses = $shippingMethod->find_shipping_classes($package);
        } else {
            $shippingClasses = [];
        }

        return WCMYPABE()->export->getShippingClass(
            $shippingMethod,
            $shippingClasses
        );
    }

    /**
     * @param $fragments
     *
     * @return mixed
     */
    public function order_review_fragments($fragments)
    {
        $myparcel_shipping_data            = $this->renderHighestShippingClassInput();
        $fragments['.wcmpbe__shipping-data'] = $myparcel_shipping_data;

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
        $shipments = WCMYPABE_Admin::get_order_shipments($order);

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

            $track_trace_url = WCMYPABE_Admin::getTrackTraceUrl(
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

    /**
     * @return int|null
     * @throws Exception
     */
    public function ajaxGetHighestShippingClass(): ?int
    {
        echo WCMPBE_Frontend::get_cart_shipping_class();
        die();
    }
}

return new WCMPBE_Frontend();
