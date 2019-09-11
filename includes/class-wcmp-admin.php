<?php

use WPO\WC\MyParcelBE\Compatibility\WC_Core as WCX;
use WPO\WC\MyParcelBE\Compatibility\Order as WCX_Order;
use WPO\WC\MyParcelBE\Entity\DeliveryOptions;

if (! defined('ABSPATH')) {
    exit;
} // Exit if accessed directly

if (! class_exists('wcmp_admin')) :

    /**
     * Admin options, buttons & data
     */
    class wcmp_admin
    {

        function __construct()
        {
            add_action('woocommerce_admin_order_actions_end', [$this, 'showMyParcelSettings'], 9999);
            add_action('admin_footer', [$this, 'bulk_actions']);

            add_action('admin_footer', [$this, 'offset_dialog']);
            add_action('woocommerce_admin_order_actions_end', [$this, 'admin_wc_actions'], 20);
            add_action('add_meta_boxes_shop_order', [$this, 'shop_order_metabox']);
            add_action('woocommerce_admin_order_data_after_shipping_address', [$this, 'single_order_shipment_options']);

            add_action('wp_ajax_wcmp_save_shipment_options', [$this, 'save_shipment_options_ajax']);
            add_action('wp_ajax_wcmp_get_shipment_summary_status', [$this, 'order_list_ajax_get_shipment_summary']);

            // HS code in product shipping options tab
            add_action('woocommerce_product_options_shipping', [$this, 'product_hs_code_field']);
            add_action('woocommerce_process_product_meta', [$this, 'product_hs_code_field_save']);

            // Add barcode in order grid
            add_filter('manage_edit-shop_order_columns', [$this, 'barcode_add_new_order_admin_list_column'], 10, 1);
            add_action(
                'manage_shop_order_posts_custom_column',
                [$this, 'barcode_add_new_order_admin_list_column_content'],
                10,
                2
            );
        }

        /**
         * @param      $order
         * @param bool $hide
         *
         * @throws Exception
         */
        public function showMyParcelSettings($order): void
        {
            if (! WooCommerce_MyParcelBE()->export->is_myparcelbe_destination(
                WCX_Order::get_prop($order, 'shipping_country')
            )) {
                return;
            }

            $order_id         = WCX_Order::get_id($order);
            $shipment_options = WooCommerce_MyParcelBE()->export->get_options($order);
            $package_types    = WooCommerce_MyParcelBE()->export->get_package_types();

            $consignments         = $this->get_order_shipments($order, true);

            // if we have shipments, then we show status & link to Track & Trace, settings under i
            if (! empty($consignments)) :
                // only use last shipment
                $last_shipment = array_pop($consignments);
                $last_shipment_id = $last_shipment['shipment_id'];

                ?>
                <div class="wcmp_shipment_summary">
                    <?php $this->showDeliveryOptionsForOrder($order); ?>
                    <a class="wcmp_show_shipment_summary"><span class="encircle wcmp_show_shipment_summary">i</span></a>
                    <div class="wcmp_shipment_summary_list"
                         data-loaded=""
                         data-shipment_id="<?php echo $last_shipment_id; ?>"
                         data-order_id="<?php echo $order_id; ?>"
                         style="display: none;">
                        <img alt="loading"
                             src="<?php echo WooCommerce_MyParcelBE()->plugin_url()
                                             . '/assets/img/wpspin_light.gif'; ?>"
                             class="wcmp_spinner"/>
                    </div>
                </div>
            <?php else : ?>
                <div class="wcmp_shipment_options">
                    <?php $this->showDeliveryOptionsForOrder($order); ?>
                </div>
            <?php endif; ?>
            <div class="wcmp_shipment_options">
                <?php printf(
                    '<a href="#" class="wcmp_show_shipment_options"><span class="wcpm_package_type">%s</span> &#x25BE;</a>',
                    $package_types[$shipment_options['package_type']]
                ); ?>
                <div class="wcmp_shipment_options_form" style="display: none;">
                    <a class="wcmp-display--block">
                        <?php include('views/wcmp-order-shipment-options.php'); ?>
                    </a>
                </div>
            </div>
            <?php
        }

        /**
         * Get shipment status + Track & Trace link via AJAX
         */
        public function order_list_ajax_get_shipment_summary()
        {
            check_ajax_referer('wc_myparcelbe', 'security');
            extract($_POST); // order_id, shipment_id
            $order    = wc_get_order($order_id);
            $shipment = WooCommerce_MyParcelBE()->export->get_shipment_data($shipment_id, $order);
            if (! empty($shipment['tracktrace'])) {
                $order_has_shipment = true;
                $tracktrace_url     = $this->get_tracktrace_url($order_id, $shipment['tracktrace']);
            }
            $package_types = WooCommerce_MyParcelBE()->export->get_package_types();

            include('views/wcmp-order-shipment-summary.php');
            die();
        }

        /**
         * Add export option to bulk action drop down menu
         * Using Javascript until WordPress core fixes: http://core.trac.wordpress.org/ticket/16031
         *
         * @access public
         * @return void
         */
        public function bulk_actions()
        {
            global $post_type;
            $bulk_actions = [
                'wcmp_export'       => __('MyParcel BE: Export', 'woocommerce-myparcelbe'),
                'wcmp_print'        => __('MyParcel BE: Print', 'woocommerce-myparcelbe'),
                'wcmp_export_print' => __('MyParcel BE: Export & Print', 'woocommerce-myparcelbe'),
            ];

            if ('shop_order' == $post_type) {
                ?>
                <script type="text/javascript">
                jQuery(document).ready(function() {
                    <?php foreach ($bulk_actions as $action => $title) { ?>
                  jQuery('<option>')
                    .val('<?php echo $action; ?>')
                    .html('<?php echo esc_attr($title); ?>')
                    .appendTo('select[name=\'action\'], select[name=\'action2\']');
                    <?php }    ?>
                });
                </script><img src="<?php echo WooCommerce_MyParcelBE()->plugin_url()
                                              . '/assets/img/wpspin_light.gif'; ?>"
                              class="wcmp_bulk_spinner waiting"
                              style="display:none;"/>
                <?php
            }
        }

        /**
         * Show dialog to choose print position (offset)
         *
         * @access public
         * @return void
         */
        public function offset_dialog()
        {
            global $post_type;
            if ('shop_order' == $post_type) {
                ?>
                <div id="wcmyparcelbe_offset_dialog" style="display:none;">
                    <?php _e('Labels to skip', 'woocommerce-myparcelbe'); ?>: <input type="text"
                                                                                     size="2"
                                                                                     class="wc_myparcelbe_offset"> <img
                        src="<?php echo WooCommerce_MyParcelBE()->plugin_url()
                                        . '/assets/img/print-offset-icon.png'; ?>"
                        id="wcmyparcelbe-offset-icon"
                        style="vertical-align: middle;">
                    <button class="button" style="display:none; margin-top: 4px"><?php _e(
                            'Print',
                            'woocommerce-myparcelbe'
                        ); ?></button>
                </div>
                <?php
            }
        }

        /**
         * Add print actions to the orders listing
         * Support wc > 3.3.0
         * Call the function admin_order_actions for the same settings
         *
         * @param $order
         */
        public function admin_wc_actions($order)
        {
            return $this->admin_order_actions($order);
        }

        /**
         * Add print actions to the orders listing
         */
        public function admin_order_actions($order)
        {
            if (empty($order)) {
                return;
            }

            $shipping_country = WCX_Order::get_prop($order, 'shipping_country');
            if (! WooCommerce_MyParcelBE()->export->is_myparcelbe_destination($shipping_country)) {
                return;
            }

            $order_id = WCX_Order::get_id($order);

            $listing_actions = [
                'add_shipment' => [
                    'url' => wp_nonce_url(
                        admin_url('admin-ajax.php?action=wc_myparcelbe&request=add_shipment&order_ids=' . $order_id),
                        'wc_myparcelbe'
                    ),
                    'img' => WooCommerce_MyParcelBE()->plugin_url() . '/assets/img/myparcelbe-up.png',
                    'alt' => esc_attr__('Export to MyParcel BE', 'woocommerce-myparcelbe'),
                ],
                'get_labels'   => [
                    'url' => wp_nonce_url(
                        admin_url('admin-ajax.php?action=wc_myparcelbe&request=get_labels&order_ids=' . $order_id),
                        'wc_myparcelbe'
                    ),
                    'img' => WooCommerce_MyParcelBE()->plugin_url() . '/assets/img/myparcelbe-pdf.png',
                    'alt' => esc_attr__('Print MyParcel BE label', 'woocommerce-myparcelbe'),
                ],
                'add_return'   => [
                    'url' => wp_nonce_url(
                        admin_url('admin-ajax.php?action=wc_myparcelbe&request=add_return&order_ids=' . $order_id),
                        'wc_myparcelbe'
                    ),
                    'img' => WooCommerce_MyParcelBE()->plugin_url() . '/assets/img/myparcelbe-retour.png',
                    'alt' => esc_attr__('Email return label', 'woocommerce-myparcelbe'),
                ],
            ];

            $consignments = $this->get_order_shipments($order);

            if (empty($consignments)) {
                unset($listing_actions['get_labels']);
            }

            $processed_shipments = $this->get_order_shipments($order, true);
            if (empty($processed_shipments) || $shipping_country != 'BE') {
                unset($listing_actions['add_return']);
            }

            $target = (WooCommerce_MyParcelBE()->setting_collection->getByName('download_display')
                       && WooCommerce_MyParcelBE()->setting_collection->get('download_display') == 'display')
                ? 'target="_blank"' : '';
            $nonce  = wp_create_nonce('wc_myparcelbe');
            foreach ($listing_actions as $action => $data) {
                printf(
                    '<a href="%1$s" class="button tips myparcelbe %2$s" alt="%3$s" data-tip="%3$s" data-order-id="%4$s" data-request="%2$s" data-nonce="%5$s" %6$s>',
                    $data['url'],
                    $action,
                    $data['alt'],
                    $order_id,
                    $nonce,
                    $target
                );
                ?>
                <img src="<?php echo $data['img']; ?>"
                     alt="<?php echo $data['alt']; ?>"
                     style="width:17px; margin: 5px 3px; pointer-events: none;"
                     class="wcmp_button_img"></a>
                <?php
            }
            ?>
            <img src="<?php echo WooCommerce_MyParcelBE()->plugin_url() . '/assets/img/wpspin_light.gif'; ?>"
                 style="width: 17px; margin: 5px 3px;"
                 class="wcmp_spinner waiting"/>
            <?php
        }

        public function get_order_shipments($order, $exclude_concepts = false)
        {
            if (empty($order)) {
                return;
            }

            $consignments = WCX_Order::get_meta($order, '_myparcelbe_shipments');
            // fallback to legacy consignment data (v1.X)
            if (empty($consignments)) {
                if ($consignment_id = WCX_Order::get_meta($order, '_myparcelbe_consignment_id')) {
                    $consignments = [
                        [
                            'shipment_id' => $consignment_id,
                            'tracktrace'  => WCX_Order::get_meta($order, '_myparcelbe_tracktrace'),
                        ],
                    ];
                } else {
                    if ($legacy_consignments = WCX_Order::get_meta($order, '_myparcelbe_consignments')) {
                        $consignments = [];
                        foreach ($legacy_consignments as $consignment) {
                            if (isset($consignment['consignment_id'])) {
                                $consignments[] = [
                                    'shipment_id' => $consignment['consignment_id'],
                                    'tracktrace'  => $consignment['tracktrace'],
                                ];
                            }
                        }
                    }
                }
            }

            if (empty($consignments) || ! is_array($consignments)) {
                return false;
            }

            if (! empty($consignments) && $exclude_concepts) {
                foreach ($consignments as $key => $consignment) {
                    if (empty($consignment['tracktrace'])) {
                        unset($consignments[$key]);
                    }
                }
            }

            return $consignments;
        }

        public function save_shipment_options_ajax()
        {
            check_ajax_referer('wc_myparcelbe', 'security');
            extract($_POST);
            parse_str($form_data, $form_data);
            $order = WCX::get_order($order_id);

            if (isset($form_data['myparcelbe_options'][$order_id])) {
                $shipment_options = $form_data['myparcelbe_options'][$order_id];

                // convert insurance option
                if (isset($shipment_options['insured'])) {
                    unset($shipment_options['insured']);
                    $shipment_options['insurance'] = [
                        'amount'   => (int) $shipment_options['insured_amount'] * 100,
                        'currency' => 'EUR',
                    ];
                    unset($shipment_options['insured_amount']);
                }

                // separate extra options
                if (isset($shipment_options['extra_options'])) {
                    WCX_Order::update_meta_data(
                        $order,
                        '_myparcelbe_shipment_options_extra',
                        $shipment_options['extra_options']
                    );
                    unset($shipment_options['extra_options']);
                }

                WCX_Order::update_meta_data($order, '_myparcelbe_shipment_options', $shipment_options);
            }

            // Quit out
            die();
        }

        /**
         * Add the meta box on the single order page
         */
        public function shop_order_metabox()
        {
            add_meta_box(
                'myparcelbe',                               //$id
                __('MyParcelBE', 'woocommerce-myparcelbe'), //$title
                [$this, 'create_box_content'],              //$callback
                'shop_order',                               //$post_type
                'side',                                     //$context
                'default' //$priority
            );
        }

        /**
         * Callback: Create the meta box content on the single order page
         */
        public function create_box_content()
        {
            global $post_id;
            // get order
            $order = WCX::get_order($post_id);

            if (! $order) {
                return;
            }

            $order_id = WCX_Order::get_id($order);

            $shipping_country = WCX_Order::get_prop($order, 'shipping_country');
            if (! WooCommerce_MyParcelBE()->export->is_myparcelbe_destination($shipping_country)) {
                return;
            }

            // show buttons and check if WooCommerce > 3.3.0 is used and select the correct function and class
            if (version_compare(WOOCOMMERCE_VERSION, '3.3.0', '>=')) {
                echo '<div class="single_wc_actions">';
                $this->admin_wc_actions($order, false);
            } else {
                echo '<div class="single_order_actions">';
                $this->admin_order_actions($order, false);
            }
            echo '</div>';

            $consignments = $this->get_order_shipments($order);
            // show shipments if available
            if (! empty($consignments)) {
                ?>
                <table class="tracktrace_status">
                    <thead>
                    <tr>
                        <th>&nbsp;</th>
                        <th><?php _e('Track & Trace', 'woocommerce-myparcelbe'); ?></th>
                        <th><?php _e('Status', 'woocommerce-myparcelbe'); ?></th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php
                    $action            = 'get_labels';
                    $target            = (WooCommerce_MyParcelBE()->setting_collection->getByName('download_display')
                                          && WooCommerce_MyParcelBE()->setting_collection->getByName('download_display')
                                             == 'display') ? 'target="_blank"' : '';
                    $nonce             = wp_create_nonce('wc_myparcelbe');
                    $label_button_text = esc_attr__('Print MyParcel BE label', 'woocommerce-myparcelbe');
                    foreach ($consignments as $shipment_id => $shipment):
                        $shipment = WooCommerce_MyParcelBE()->export->get_shipment_data($shipment_id, $order);
                        $label_url     = wp_nonce_url(
                            admin_url(
                                'admin-ajax.php?action=wc_myparcelbe&request=get_labels&shipment_ids=' . $shipment_id
                            ),
                            'wc_myparcelbe'
                        );
                        if (isset($shipment['tracktrace'])) {
                            $tracktrace_url  = $this->get_tracktrace_url($order_id, $shipment['tracktrace']);
                            $tracktrace_link = sprintf(
                                '<a href="%s" target="_blank">%s</a>',
                                $tracktrace_url,
                                $shipment['tracktrace']
                            );
                        } else {
                            if (isset($shipment['shipment']) && isset($shipment['shipment']['options'])) {
                                $tracktrace_link = '(' . WooCommerce_MyParcelBE()->export->get_package_name(
                                        $shipment['shipment']['options']['package_type']
                                    ) . ')';
                            } else {
                                $tracktrace_link = '(Unknown)';
                            }
                        }
                        $status = isset($shipment['status']) ? $shipment['status'] : '-';
                        ?>
                        <tr>
                            <td class="wcmp-create-label">
                                <?php
                                printf(
                                    '<a href="%1$s" class="button tips myparcelbe %2$s" alt="%3$s" data-tip="%3$s" data-order-id="%4$s" data-request="%2$s" data-nonce="%5$s" %6$s>',
                                    $label_url,
                                    $action,
                                    $label_button_text,
                                    $order_id,
                                    $nonce,
                                    $target
                                );
                                printf(
                                    '<img class="wcmp_button_img" src="%1$s" alt="%2$s" width="16" />',
                                    WooCommerce_MyParcelBE()->plugin_url() . "/assets/img/myparcelbe-pdf.png",
                                    $label_button_text
                                );
                                printf("</a>");
                                ?>
                            </td>
                            <td class="wcmp-tracktrace"><?php echo $tracktrace_link; ?></td>
                            <td class="wcmp-status"><?php echo $status; ?></td>
                        </tr>
                    <?php endforeach ?>
                    </tbody>
                </table>
                <?php
            }
        }

        /**
         * @param $order
         *
         * @throws Exception
         */
        public function single_order_shipment_options($order)
        {
            $shipping_country = WCX_Order::get_prop($order, 'shipping_country');
            if (! WooCommerce_MyParcelBE()->export->is_myparcelbe_destination($shipping_country)) {
                return;
            }

            echo '<div style="clear: both;"><strong>'
                 . __('MyParcel BE shipment:', 'woocommerce-myparcelbe')
                 . '</strong><br/>';

            $this->showMyParcelSettings($order);
            echo '</div>';
        }

        /**
         * @param WC_Order $order
         *
         * @throws Exception
         */
        public function showDeliveryOptionsForOrder(WC_Order $order): void
        {
            $deliveryOptions = self::getDeliveryOptionsFromOrder($order);

            $deliveryDaysWindow = WooCommerce_MyParcelBE()->setting_collection->getByName(
                $deliveryOptions->carrier . "_delivery_days_window"
            );

            echo "<div class=\"delivery-options\">";

            /**
             * Show the delivery date if it is present.
             */
            if ($deliveryOptions->date || $deliveryDaysWindow === 0) {
                $this->printDeliveryDate($deliveryOptions);
            }

            /**
             * If the order will be sent to a pickup location show its address.
             */
            if ("pickup" === $deliveryOptions->deliveryType) {
                $this->printPickupLocation($deliveryOptions);
            }

            echo "</div>";
        }

        /**
         * @param $order_id
         * @param $tracktrace
         *
         * @return string|void
         */
        public function get_tracktrace_url($order_id, $tracktrace)
        {
            if (empty($order_id)) {
                return;
            }

            $order    = WCX::get_order($order_id);
            $country  = WCX_Order::get_prop($order, 'shipping_country');
            $postcode = preg_replace('/\s+/', '', WCX_Order::get_prop($order, 'shipping_postcode'));

            // set url for NL or foreign orders
            if ($country == 'BE') {
                // use billing postcode for pickup/pakjegemak
                if (WooCommerce_MyParcelBE()->export->is_pickup($order)) {
                    $postcode = preg_replace('/\s+/', '', WCX_Order::get_prop($order, 'billing_postcode'));
                }

                $tracktrace_url = sprintf(
                    'https://sendmyparcel.me/track-trace/%s/%s/%s',
                    $tracktrace,
                    $postcode,
                    $country
                );
            } else {
                $tracktrace_url = sprintf(
                    'https://track.bpost.be/btr/web/#/search?itemCode=',
                    $tracktrace,
                    $country,
                    $postcode
                );
            }

            return $tracktrace_url;
        }

        public function get_tracktrace_links($order_id)
        {
            if ($consignments = $this->get_tracktrace_shipments($order_id)) {
                foreach ($consignments as $key => $consignment) {
                    $tracktrace_links[] = $consignment['tracktrace_link'];
                }

                return $tracktrace_links;
            } else {
                return false;
            }
        }

        public function get_tracktrace_shipments($order_id)
        {
            $order     = WCX::get_order($order_id);
            $shipments = $this->get_order_shipments($order, true);

            if (empty($shipments)) {
                return false;
            }

            foreach ($shipments as $shipment_id => $shipment) {
                // skip concepts
                if (empty($shipment['tracktrace'])) {
                    unset($shipments[$shipment_id]);
                    continue;
                }
                // add links & urls
                $shipments[$shipment_id]['tracktrace_url']  = $tracktrace_url = $this->get_tracktrace_url(
                    $order_id,
                    $shipment['tracktrace']
                );
                $shipments[$shipment_id]['tracktrace_link'] = sprintf(
                    '<a href="%s">%s</a>',
                    $tracktrace_url,
                    $shipment['tracktrace']
                );
            }

            if (empty($shipments)) {
                return false;
            }

            return $shipments;
        }

        /**
         * @snippet       Add Column to Orders Table (e.g. Barcode) - WooCommerce
         *
         * @param $columns
         *
         * @return mixed
         */
        public function barcode_add_new_order_admin_list_column($columns)
        {
            // I want to display Barcode column just after the date column
            return array_slice($columns, 0, 6, true) + ['barcode' => 'Barcode'] + array_slice($columns, 6, null, true);
        }

        /**
         * @param $column
         */
        public function barcode_add_new_order_admin_list_column_content($column)
        {
            global $post;

            if ('barcode' === $column) {
                $order = WCX::get_order($post->ID);
                echo $this->get_barcode($order);
            }
        }

        /**
         * @param      $order
         * @param null $barcode
         *
         * @return string|null
         */
        public function get_barcode($order, $barcode = null)
        {
            $shipments = $this->get_order_shipments($order, true);

            if (empty($shipments)) {
                return __('No label has created yet', 'woocommerce-myparcelbe');
            }

            foreach ($shipments as $shipment_id => $shipment) {
                $barcode .= "<a target='_blank' href="
                            . $this->get_tracktrace_url($order, $shipment['tracktrace'])
                            . ">"
                            . $shipment['tracktrace']
                            . "</a> <br>";
            }

            return $barcode;
        }

        /**
         * Get delivery options array from the given order's meta data.
         *
         * @param WC_Order $order
         *
         * @return DeliveryOptions
         * @throws Exception
         * @see \wcmp_checkout::save_delivery_options
         */
        public static function getDeliveryOptionsFromOrder(WC_Order $order): DeliveryOptions
        {
            return new DeliveryOptions(
                (array) json_decode(stripslashes(WCX_Order::get_meta($order, DeliveryOptions::HIDDEN_INPUT_NAME)))
            );
        }

        /**
         * Output the pickup location address.
         *
         * @param DeliveryOptions $delivery_options
         */
        private function printPickupLocation(DeliveryOptions $delivery_options)
        {
            $pickup = $delivery_options->pickupLocation;

            printf(
                "<div class=\"pickup-location\"><strong>%s:</strong> %s<br />%s %s<br />%s %s</div>",
                __('bpost Pickup', 'woocommerce-myparcelbe'),
                $pickup->location_name,
                $pickup->street,
                $pickup->number,
                $pickup->postal_code,
                $pickup->city
            );
        }

        /**
         * Output the delivery date.
         *
         * @param DeliveryOptions $delivery_options
         */
        private function printDeliveryDate(DeliveryOptions $delivery_options)
        {
            $formatted_date = date_i18n(
                apply_filters('wcmyparcelbe_delivery_date_format', wc_date_format()),
                strtotime($delivery_options->date)
            );

            $time_title = __('Standard delivery', 'woocommerce-myparcelbe');

            printf(
                '<div class="delivery-date"><strong>%s: </strong>%s %s</div>',
                __("Delivery date", "woocommerce-myparcelbe"),
                $formatted_date,
                $time_title
            );
        }
    }

endif; // class_exists

return new wcmp_admin();
