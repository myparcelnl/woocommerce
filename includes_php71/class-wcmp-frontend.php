<?php

use WPO\WC\MyParcelBE\Collections\SettingsCollection;
use WPO\WC\MyParcelBE\Compatibility\WC_Core as WCX;
use WPO\WC\MyParcelBE\Compatibility\Order as WCX_Order;

if (! defined('ABSPATH')) {
    exit;
} // Exit if accessed directly

if (! class_exists('WooCommerce_MyParcelBE_Frontend')) :

    /**
     * Frontend views
     */
    class WooCommerce_MyParcelBE_Frontend
    {
        const RADIO_CHECKED = 'on';

        /**
         * @var SettingsCollection
         */
        public $settings;

        /**
         * @var WooCommerce_MyParcelBE_Checkout
         */
        private $checkout;

        /**
         * WooCommerce_MyParcelBE_Frontend constructor.
         */
        function __construct()
        {
            WooCommerce_MyParcelBE()->initSettings();
            $this->settings = WooCommerce_MyParcelBE()->setting_collection;
            $this->checkout = new WooCommerce_MyParcelBE_Checkout();

            // Customer Emails
            if ($this->settings->isEnabled('email_tracktrace')) {
                add_action('woocommerce_email_before_order_table', array($this, 'track_trace_email'), 10, 2);
            }

            // Track & Trace in my account
            if ($this->settings->isEnabled('myaccount_tracktrace')) {
                add_filter('woocommerce_my_account_my_orders_actions', array($this, 'track_trace_myaccount'), 10, 2);
            }

            // pickup address in email
            // woocommerce_email_customer_details:
            // @10 = templates/email-customer-details.php
            // @20 = templates/email-addresses.php
            add_action('woocommerce_email_customer_details', array($this, 'email_pickup_html'), 19, 3);

            // pickup address on thank you page
            add_action('woocommerce_thankyou', array($this, 'thankyou_pickup_html'), 10, 1);

            // WooCommerce PDF Invoices & Packing Slips Premium Templates compatibility
            //add_filter('wpo_wcpdf_templates_replace_myparcelbe_delivery_date', array($this, 'wpo_wcpdf_delivery_date'), 10, 2); options.delivery_date custom delivery date not supported for carrier bpost
            add_filter('wpo_wcpdf_templates_replace_myparcelbe_tracktrace', array(
                $this,
                'wpo_wcpdf_tracktrace'
            ), 10, 2);
            add_filter('wpo_wcpdf_templates_replace_myparcelbe_tracktrace_link', array(
                $this,
                'wpo_wcpdf_tracktrace_link'
            ), 10, 2);
            add_filter('wpo_wcpdf_templates_replace_myparcelbe_delivery_options', array(
                $this,
                'wpo_wcpdf_delivery_options'
            ), 10, 2);

            // Change the position of the checkout
            if (WooCommerce_MyParcelBE()->setting_collection->getByName('checkout_position')) {
                $checkout_position = WooCommerce_MyParcelBE()->setting_collection->getByName('checkout_position');
            } else {
                $checkout_position = 'woocommerce_after_checkout_billing_form';
            }

            add_action('wp_enqueue_scripts', array($this, 'inject_delivery_options_variables'), 9999);
            add_action(apply_filters('wc_myparcelbe_delivery_options_location', $checkout_position), array(
                $this,
                'output_delivery_options'
            ), 10);

            // Save delivery options data
            add_action('woocommerce_checkout_update_order_meta', array($this, 'save_delivery_options'), 10, 2);

            // Delivery options fees
            add_action('woocommerce_cart_calculate_fees', array($this, 'get_delivery_options_fees'));

            // Output most expensive shipping class in frontend data
            add_action('woocommerce_checkout_after_order_review', array($this, 'output_shipping_data'));
            add_action('woocommerce_update_order_review_fragments', array($this, 'order_review_fragments'));


            // Checkout actions
            add_action(
                'wp_enqueue_scripts',
                [$this->checkout, 'inject_delivery_options_variables'],
                9999
            );

            add_action(
                apply_filters('wc_myparcelbe_delivery_options_location', $this->checkout->get_checkout_place()),
                [$this->checkout, 'output_delivery_options'],
                10
            );
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

            $tracktrace_links = WooCommerce_MyParcelBE()->admin->get_tracktrace_links($order_id);
            if (! empty($tracktrace_links)) {
                $email_text = __(
                    'You can track your order with the following bpost Track & Trace code:',
                    'woocommerce-myparcelbe'
                );
                $email_text = apply_filters('wcmyparcelbe_email_text', $email_text, $order);
                ?>
                <p><?php echo $email_text . ' ' . implode(', ', $tracktrace_links); ?></p>

                <?php
            }
        }

        public function email_pickup_html($order, $sent_to_admin = false, $plain_text = false)
        {
            WooCommerce_MyParcelBE()->admin->show_order_delivery_options($order);
        }

        public function thankyou_pickup_html($order_id)
        {
            $order = wc_get_order($order_id);
            WooCommerce_MyParcelBE()->admin->show_order_delivery_options($order);
        }

        public function track_trace_myaccount($actions, $order)
        {
            $order_id = WCX_Order::get_id($order);
            if ($consignments = WooCommerce_MyParcelBE()->admin->get_tracktrace_shipments($order_id)) {
                foreach ($consignments as $key => $consignment) {
                    $actions['myparcelbe_tracktrace_' . $consignment['tracktrace']] = array(
                        'url'  => $consignment['tracktrace_url'],
                        'name' => apply_filters(
                            'wcmyparcelbe_myaccount_tracktrace_button',
                            __('Track & Trace', 'wooocommerce-myparcelbe')
                        )
                    );
                }
            }

            return $actions;
        }

        public function wpo_wcpdf_delivery_options($order)
        {
            ob_start();
            WooCommerce_MyParcelBE()->admin->show_order_delivery_options($order);

            return ob_get_clean();
        }

        public function wpo_wcpdf_tracktrace($replacement, $order)
        {
            if ($shipments = WooCommerce_MyParcelBE()->admin->get_tracktrace_shipments(WCX_Order::get_id($order))) {
                $tracktrace = array();
                foreach ($shipments as $shipment) {
                    if (! empty($shipment['tracktrace'])) {
                        $tracktrace[] = $shipment['tracktrace'];
                    }
                }
                $replacement = implode(', ', $tracktrace);
            }

            return $replacement;
        }

        public function wpo_wcpdf_tracktrace_link($replacement, $order)
        {
            $tracktrace_links = WooCommerce_MyParcelBE()->admin->get_tracktrace_links(WCX_Order::get_id($order));
            if (! empty($tracktrace_links)) {
                $replacement = implode(', ', $tracktrace_links);
            }

            return $replacement;
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
         * Save delivery options to order when used
         *
         * @param int $order_id
         * @param array $posted
         *
         * @return void
         */
        public function save_delivery_options($order_id, $posted)
        {
            $order = WCX::get_order($order_id);

            if ($_POST['myparcelbe_highest_shipping_class'] != null) {
                WCX_Order::update_meta_data(
                    $order,
                    '_myparcelbe_highest_shipping_class',
                    $_POST['myparcelbe_highest_shipping_class']
                );
            } else {
                if (isset($_POST['shipping_method'])) {
                    WCX_Order::update_meta_data(
                        $order,
                        '_myparcelbe_highest_shipping_class',
                        $_POST['shipping_method'][0]
                    );
                }
            }

            if (isset($_POST['myparcelbe-signature-selector'])) {
                WCX_Order::update_meta_data($order, '_myparcelbe_signature', self::RADIO_CHECKED);
            }

            if (! empty($_POST['mypabe-post-nl-data'])) {
                $delivery_options = json_decode(stripslashes($_POST['mypabe-post-nl-data']), true);
                WCX_Order::update_meta_data($order, '_myparcelbe_delivery_options', $delivery_options);
            }
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
                                $fee_name = __('Signature on delivery', 'woocommerce-myparcelbe');
                                break;
                        }

                        if (! empty($fee)) {
                            $this->add_fee($fee_name, $fee);
                        }
                    }
                }
                /* Fees for pickup */
                $this->getPickupFee($delivery_options, 'bpost_pickup_fee');
            }
        }

        /**
         * @param $delivery_options
         * @param string $pickupFee
         *
         * @return void
         */
        public function getPickupFee($delivery_options, string $pickupFee): void
        {
            if (isset($delivery_options['price_comment'])) {
                switch ($delivery_options['price_comment']) {
                    case 'retail':
                        if (! empty(WooCommerce_MyParcelBE()->setting_collection->getByName($pickupFee))) {
                            $fee      = WooCommerce_MyParcelBE()->setting_collection->getByName($pickupFee);
                            $fee_name = __('Pickup', 'woocommerce-myparcelbe');
                        }
                        break;
                }

                if (! empty($fee) && ! empty($fee_name) ) {
                    $this->add_fee($fee_name, $fee);
                }
            }
            return;
        }

        /**
         * Get shipping tax class
         * adapted from WC_Tax::get_shipping_tax_rates
         * assumes per order shipping (per item shipping not supported for myparcel yet)
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
         * @return [type] [description]
         */
        public function get_cart_shipping_class()
        {
            if (version_compare(WOOCOMMERCE_VERSION, '2.4', '<')) {
                return false;
            }

            $chosen_method = isset(WC()->session->chosen_shipping_methods[0]) ? WC()->session->chosen_shipping_methods[0] : '';

            // get package
            $packages = WC()->shipping->get_packages();
            $package  = current($packages);

            $shipping_method = WooCommerce_MyParcelBE()->export->get_shipping_method($chosen_method);
            if (empty($shipping_method)) {
                return false;
            }

            // get shipping classes from package
            $found_shipping_classes = $shipping_method->find_shipping_classes($package);
            // return print_r( $found_shipping_classes, true );

            $highest_class = WooCommerce_MyParcelBE()->export->get_shipping_class(
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

        private function get_checkout_config()
        {
            $carriers = $this
                ->settings
                ->like('name', 'myparcelbe_carrier_enable_')
                ->pluck('carrier')
                ->toArray();

            $myParcelConfig = [
                "address"         => [
                    "cc"         => '',
                    "postalCode" => '',
                    "number"     => '',
                    "city"       => '',
                ],
                "config"          => [
                    "apiBaseUrl" => WooCommerce_MyParcelBE_Frontend_Settings::BASE_URL,
                    "carriers"   => $carriers,
                    "platform"   => "sendmyparcel",
                    "locale"     => "nl-NL",
                ],
                "carrierSettings" => [
                    "bpost" => [
                        "allowPickupPoints"     => $this->settings->getByName('bpost_pickup_enabled'),
                        "allowSignature"        => $this->settings->getByName('bpost_signature_enabled'),
                        "cutoffTime"            => $this->settings->getByName('bpost_cutoff_time'),
                        "deliveryDaysWindow"    => $this->settings->getByName('bpost_deliverydays_window'),
                        "dropOffDays"           => $this->settings->getByName('bpost_dropoff_days'),
                        "dropOffDelay"          => $this->settings->getByName('bpost_dropoff_delay'),
                        "pricePickup"           => $this->settings->getByName('bpost_pickup_fee'),
                        "priceSignature"        => $this->settings->getByName('bpost_signature_fee'),
                        "priceStandardDelivery" => null,
                        "signatureTitle"        => $this->settings->getByName('bpost_signature_title'),
                    ],
                    "dpd"   => [
                        "allowPickupPoints"     => $this->settings->getByName('dpd_pickup_enabled'),
                        "cutoffTime"            => $this->settings->getByName('dpd_cutoff_time'),
                        "deliveryDaysWindow"    => $this->settings->getByName('dpd_deliverydays_window'),
                        "dropOffDays"           => $this->settings->getByName('dpd_dropoff_days'),
                        "dropOffDelay"          => $this->settings->getByName('dpd_dropoff_delay'),
                        "pricePickup"           => $this->settings->getByName('dpd_pickup_fee'),
                        "priceStandardDelivery" => null,
                    ],
                ],
                "strings"         => [
                    /*
                     * todo translate null
                     */
                    "addressNotFound"       => __('Address details are not entered', 'woocommerce-myparcelbe'),
                    "city"                  => __('City', 'woocommerce-myparcelbe'),
                    "closed"                => __('Closed', 'woocommerce-myparcelbe'),
                    "deliveryTitle"         => __('Standard delivery title', 'woocommerce-myparcelbe'),
                    "headerDeliveryOptions" => __('Delivery options title', 'woocommerce-myparcelbe'),
                    "houseNumber"           => __('House number', 'woocommerce-myparcelbe'),
                    "openingHours"          => __('Opening hours', 'woocommerce-myparcelbe'),
                    "pickUpFrom"            => __('Pick up from', 'woocommerce-myparcelbe'),
                    "pickupTitle"           => __('bpost pickup', 'woocommerce-myparcelbe'),
                    "postcode"              => __('Postcode', 'woocommerce-myparcelbe'),
                    "retry"                 => __('Retry', 'woocommerce-myparcelbe'),
                    "wrongHouseNumberCity"  => __('Postcode/city combination unknown', 'woocommerce-myparcelbe'),
                ],
            ];
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
        private function add_fee_from_setting($post_data, $post_data_value, $delivery_type, $backend_setting, $delivery_title)
        {
            // Fee for "delivery" option
            if (isset($post_data[$post_data_value]) && $post_data[$post_data_value] == $delivery_type) {
                if (! empty(WooCommerce_MyParcelBE()->bpost_settings[$backend_setting])) {
                    $fee      = WooCommerce_MyParcelBE()->bpost_settings[$backend_setting];
                    $fee_name = __($delivery_title, 'woocommerce-myparcelbe');
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

            $fee = WooCommerce_MyParcelBE()->bpost_settings['signature_fee'];

            if (! empty($fee)) {
                $fee_name = __($delivery_title, 'woocommerce-myparcelbe');
                $this->add_fee($fee_name, $fee);
            }
        }

        /**
         * @return string
         */
        private function get_delivery_options_shipping_methods()
        {
            if (WooCommerce_MyParcelBE()->setting_collection->getByName('shipping_methods_package_types')
                && (WooCommerce_MyParcelBE()->setting_collection->getByName('shipping_methods_package_types')
                [WooCommerce_MyParcelBE_Export::PACKAGE])) {
                // Shipping methods associated with parcels = enable delivery options
                $delivery_options_shipping_methods = WooCommerce_MyParcelBE()->setting_collection->getByName('shipping_methods_package_types')
                [WooCommerce_MyParcelBE_Export::PACKAGE];
            } else {
                $delivery_options_shipping_methods = array();
            }

            return json_encode($delivery_options_shipping_methods);
        }

        /**
         * @return bool
         */
        private function myparcelbe_delivery_options_always_display()
        {
            if (WooCommerce_MyParcelBE()->setting_collection->getByName('checkout_display')
                && WooCommerce_MyParcelBE()->setting_collection->getByName('checkout_display') == 'all_methods') {
                return true;
            }

            return false;
        }
    }

endif; // class_exists

new WooCommerce_MyParcelBE_Frontend();
