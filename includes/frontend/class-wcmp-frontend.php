<?php

use MyParcelNL\Sdk\src\Model\DeliveryOptions\DeliveryOptions;
use WPO\WC\MyParcelBE\Compatibility\Order as WCX_Order;

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
            "wpo_wcpdf_templates_replace_myparcelbe_delivery_options",
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
        printf('<div class="wcmp__shipping-data">%s</div>', $this->renderHighestShippingClassInput());
    }

    /**
     * @return string|void
     */
    public function renderHighestShippingClassInput()
    {
        $shipping_class = $this->get_cart_shipping_class();

        if ($shipping_class) {
            return sprintf(
                '<input type="hidden" value="%s" name="myparcelbe_highest_shipping_class">',
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
     */
    public function get_cart_shipping_class()
    {
        if (version_compare(WOOCOMMERCE_VERSION, '2.4', '<')) {
            return false;
        }

        $chosen_method = WC()->session->get('chosen_shipping_methods')[0] ?? '';

        // get package
        $packages = WC()->shipping()->get_packages();
        $package  = current($packages);

        $shipping_method = WCMP()->export->get_shipping_method($chosen_method);

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
        $myparcelbe_shipping_data          = $this->renderHighestShippingClassInput();
        $fragments['.wcmp__shipping-data'] = $myparcelbe_shipping_data;

        return $fragments;
    }

}

return new WCMP_Frontend();
