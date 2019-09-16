<?php

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
    function __construct()
    {
        // Customer Emails
        if (WCMP()->setting_collection->isEnabled('email_tracktrace')) {
            add_action('woocommerce_email_before_order_table', [$this, 'track_trace_email'], 10, 2);
        }

        // Track & Trace in my account
        if (WCMP()->setting_collection->isEnabled('myaccount_tracktrace')) {
            add_filter('woocommerce_my_account_my_orders_actions', [$this, 'track_trace_myaccount'], 10, 2);
        }

        // pickup address in email
        // woocommerce_email_customer_details:
        // @10 = templates/email-customer-details.php
        // @20 = templates/email-addresses.php
        add_action('woocommerce_email_customer_details', [$this, 'email_pickup_html'], 19, 3);

        // pickup address on thank you page
        add_action('woocommerce_thankyou', [$this, 'thankyou_pickup_html'], 10, 1);

        // WooCommerce PDF Invoices & Packing Slips Premium Templates compatibility
        //add_filter('wpo_wcpdf_templates_replace_myparcelbe_delivery_date', array($this, 'wpo_wcpdf_delivery_date'), 10, 2); options.delivery_date custom delivery date not supported for carrier bpost
        add_filter('wpo_wcpdf_templates_replace_myparcelbe_tracktrace', [$this, 'wpo_wcpdf_tracktrace'], 10, 2);
        add_filter(
            'wpo_wcpdf_templates_replace_myparcelbe_tracktrace_link',
            [$this, 'wpo_wcpdf_tracktrace_link'],
            10,
            2
        );
        add_filter(
            'wpo_wcpdf_templates_replace_myparcelbe_delivery_options',
            [$this, 'wpo_wcpdf_delivery_options'],
            10,
            2
        );

        // Delivery options fees
        add_action('woocommerce_cart_calculate_fees', [$this, 'get_delivery_options_fees']);

        // Output most expensive shipping class in frontend data
        add_action('woocommerce_checkout_after_order_review', [$this, 'output_shipping_data']);
        add_action('woocommerce_update_order_review_fragments', [$this, 'order_review_fragments']);
    }

    public function track_trace_email($order, $sent_to_admin)
    {
        if ($sent_to_admin) {
            return;
        }

        if (WCX_Order::get_status($order) != 'completed') {
            return;
        }

        $order_id = WCX_Order::get_id($order);

        $tracktrace_links = WCMP()->admin->get_tracktrace_links($order_id);
        if (! empty($tracktrace_links)) {
            $email_text = _wcmp('You can track your order with the following bpost Track & Trace code:');
            $email_text = apply_filters('wcmyparcelbe_email_text', $email_text, $order);
            ?>
            <p><?php echo $email_text . ' ' . implode(', ', $tracktrace_links); ?></p>
            <?php
        }
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
    public function thankyou_pickup_html($order_id)
    {
        $order = wc_get_order($order_id);
        WCMP()->admin->showDeliveryOptionsForOrder($order);
    }

    public function track_trace_myaccount($actions, $order)
    {
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
     * @return false|string
     * @throws Exception
     */
    public function wpo_wcpdf_delivery_options($replacement, $order)
    {
        ob_start();
        WCMP()->admin->showDeliveryOptionsForOrder($order);
        return ob_get_clean();
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

    /**
     * Output some stuff.
     * Return to hide delivery options
     */
    public function output_delivery_options()
    {
        do_action('woocommerce_myparcelbe_before_delivery_options');
        require_once(WCMP()->plugin_path() . '/templates/wcmp-delivery-options-template.php');
        do_action('woocommerce_myparcelbe_after_delivery_options');
    }

    public function output_shipping_data()
    {
        $shipping_data = $this->get_shipping_data();
        printf('<div class="myparcelbe-shipping-data">%s</div>', $shipping_data);
    }

    public function get_shipping_data()
    {
        if ($shipping_class = $this->get_cart_shipping_class()) {
            $shipping_data = sprintf(
                '<input type="hidden" value="%s" id="myparcelbe_highest_shipping_class" name="myparcelbe_highest_shipping_class">',
                $shipping_class
            );

            return $shipping_data;
        }

        return false;
    }

    /**
     * Get delivery fee in your order overview, at the front of the website
     *
     * @param $cart
     */
    public function get_delivery_options_fees()
    {
        if (! $_POST || (is_admin() && ! is_ajax())) {
            return;
        }

        if (isset($_POST['post_data'])) {
            // non-default post data for AJAX calls
            parse_str($_POST['post_data'], $post_data);
        } else {
            // checkout finalization
            $post_data = $_POST;
        }

        /*  check for delivery options & add fees*/
        if (! empty($post_data['mypabe-post-nl-data'])) {
            $delivery_options = json_decode(stripslashes($post_data['mypabe-post-nl-data']), true);

            /*  Fees for delivery time options*/
            if (isset($delivery_options['time'])) {
                $time = array_shift($delivery_options['time']); // take first element in time array
                if (isset($time['price_comment'])) {
                    switch ($time['price_comment']) {
                        case 'standard':
                            $this->add_fee_signature($delivery_options, 'Signature on delivery');
                            $fee_name = _wcmp('Signature on delivery');
                            break;
                    }

                    if (! empty($fee)) {
                        $this->add_fee($fee_name, $fee);
                    }
//                    if (date('w', strtotime($delivery_options['date'])) == 6){
//                        $fee = WooCommerce_MyParcelBE()->checkout_settings['saturday_cutoff_fee'];
//                        $fee_name = _wcmp('Saturday delivery');
//                        $this->add_fee($fee_name, $fee);
//                    }
                }
            }

            /* Fees for pickup */
            if (isset($delivery_options['price_comment'])) {
                switch ($delivery_options['price_comment']) {
                    case 'retail':
                        if (! empty(WCMP()->checkout_settings['pickup_fee'])) {
                            $fee      = WCMP()->checkout_settings['pickup_fee'];
                            $fee_name = _wcmp('bpost pickup');
                        }
                        break;
                }

                if (! empty($fee)) {
                    $this->add_fee($fee_name, $fee);
                }
            }
        }
    }

    /**
     * Get shipping tax class
     * adapted from WC_Tax::get_shipping_tax_rates
     * assumes per order shipping (per item shipping not supported for myparcel yet)
     *
     * @return string tax class
     */
    public function get_shipping_tax_class()
    {
        $shipping_tax_class = get_option('woocommerce_shipping_tax_class');
        // WC3.0+ sets 'inherit' for taxes based on items, empty for 'standard'
        if (version_compare(WOOCOMMERCE_VERSION, '3.0', '>=') && 'inherit' !== $shipping_tax_class) {
            $shipping_tax_class = '' === $shipping_tax_class ? 'standard' : $shipping_tax_class;

            return $shipping_tax_class;
        } else if (! empty($shipping_tax_class) && 'inherit' !== $shipping_tax_class) {
            return $shipping_tax_class;
        }

        if ($shipping_tax_class == 'inherit') {
            $shipping_tax_class = '';
        }

        // See if we have an explicitly set shipping tax class
        if ($shipping_tax_class) {
            $tax_class = 'standard' === $shipping_tax_class ? '' : $shipping_tax_class;
        }

        $location = WC_Tax::get_tax_location('');

        if (sizeof($location) === 4) {
            list($country, $state, $postcode, $city) = $location;

            // This will be per order shipping - loop through the order and find the highest tax class rate
            $cart_tax_classes = WC()->cart->get_cart_item_tax_classes();
            // If multiple classes are found, use the first one. Don't bother with standard rate, we can get that later.
            if (sizeof($cart_tax_classes) > 1 && ! in_array('', $cart_tax_classes)) {
                $tax_classes = WC_Tax::get_tax_classes();

                foreach ($tax_classes as $tax_class) {
                    $tax_class = sanitize_title($tax_class);
                    if (in_array($tax_class, $cart_tax_classes)) {
                        // correct $tax_class is now set
                        break;
                    }
                }
                // If a single tax class is found, use it
            } else {
                if (sizeof($cart_tax_classes) == 1) {
                    $tax_class = array_pop($cart_tax_classes);
                }
            }

            // no rate = standard rate
            if (empty($tax_class)) {
                $tax_class = 'standard';
            }
        }

        return $tax_class;
    }

    /**
     * Get the most expensive shipping class in the cart
     * Requires WC2.4+
     * Only supports 1 package, takes the first
     *
     * @return [type] [description]
     */
    public function get_cart_shipping_class()
    {
        if (version_compare(WOOCOMMERCE_VERSION, '2.4', '<')) {
            return false;
        }

        $chosen_method =
            isset(WC()->session->chosen_shipping_methods[0]) ? WC()->session->chosen_shipping_methods[0] : '';

        // get package
        $packages = WC()->shipping->get_packages();
        $package  = current($packages);

        $shipping_method = WCMP()->export->get_shipping_method($chosen_method);

        if (empty($shipping_method)) {
            return false;
        }

        // get shipping classes from package
        $found_shipping_classes = $shipping_method->find_shipping_classes($package);

        $highest_class = WCMP()->export->get_shipping_class(
            $shipping_method,
            $found_shipping_classes
        );

        return $highest_class;
    }

    public function order_review_fragments($fragments)
    {
        $myparcelbe_shipping_data               = $this->get_shipping_data();
        $fragments['.myparcelbe-shipping-data'] = $myparcelbe_shipping_data;

        return $fragments;
    }

    // converts price string to float value, assuming no thousand-separators used
    public function normalize_price($price)
    {
        $price = str_replace(',', '.', $price);
        $price = floatval($price);

        return $price;
    }

    private function add_fee($fee_name, $fee)
    {
        $fee = $this->normalize_price($fee);
        // get shipping tax data
        $shipping_tax_class = $this->get_shipping_tax_class();
        if ($shipping_tax_class) {
            if ($shipping_tax_class == 'standard') {
                $shipping_tax_class = '';
            }
            WC()->cart->add_fee($fee_name, $fee, true, $shipping_tax_class);
        } else {
            WC()->cart->add_fee($fee_name, $fee);
        }
    }

    /**
     * Check witch delivery option is selected
     *
     * @param $post_data
     * @param $post_data_value
     * @param $delivery_type
     * @param $backend_setting
     * @param $delivery_title
     *
     * @return bool
     */
    private function add_fee_from_setting(
        $post_data,
        $post_data_value,
        $delivery_type,
        $backend_setting,
        $delivery_title
    ) {
        // Fee for "delivery" option
        if (isset($post_data[$post_data_value]) && $post_data[$post_data_value] == $delivery_type) {
            if (! empty(WCMP()->checkout_settings[$backend_setting])) {
                $fee      = WCMP()->checkout_settings[$backend_setting];
                $fee_name = _wcmp($delivery_title);
                $this->add_fee($fee_name, $fee);

                return true;
            }
        }

        return false;
    }

    private function add_fee_signature($delivery_options, $delivery_title)
    {
        if ($delivery_options['signature'] !== 1) {
            return;
        }

        $fee = WCMP()->checkout_settings['signature_fee'];

        if (! empty($fee)) {
            $fee_name = _wcmp($delivery_title);
            $this->add_fee($fee_name, $fee);
        }
    }
}

return new WCMP_Frontend();
