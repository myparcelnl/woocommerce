<?php

use WPO\WC\MyParcelBE\Compatibility\WC_Core as WCX;
use WPO\WC\MyParcelBE\Compatibility\Order as WCX_Order;
use WPO\WC\MyParcelBE\Entity\DeliveryOptions;

if (! defined('ABSPATH')) {
    exit;
} // Exit if accessed directly

if (class_exists('WCMP_Admin')) {
    return new WCMP_Admin();
}

/**
 * Admin options, buttons & data
 */
class WCMP_Admin
{
    public const META_CONSIGNMENTS           = "_myparcelbe_consignments";
    public const META_CONSIGNMENT_ID         = "_myparcelbe_consignment_id";
    public const META_DELIVERY_OPTIONS       = "_myparcelbe_delivery_options";
    public const META_HIGHEST_SHIPPING_CLASS = "_myparcelbe_highest_shipping_class";
    public const META_LAST_SHIPMENT_IDS      = "_myparcelbe_last_shipment_ids";
    public const META_ORDER_WEIGHT           = "_myparcelbe_order_weight";
    public const META_PGADDRESS              = "_myparcelbe_pgaddress";
    public const META_SHIPMENTS              = "_myparcelbe_shipments";
    public const META_SHIPMENT_OPTIONS       = "_myparcelbe_shipment_options";
    public const META_SHIPMENT_OPTIONS_EXTRA = "_myparcelbe_shipment_options_extra";
    public const META_SIGNATURE              = "_myparcelbe_signature";
    public const META_TRACK_TRACE            = "_myparcelbe_tracktrace";

    public const SHIPMENT_OPTIONS_FORM_NAME = "myparcelbe_options";

    function __construct()
    {
        add_action("woocommerce_admin_order_actions_end", [$this, "showMyParcelSettings"], 9999);
        add_action("admin_footer", [$this, "bulk_actions"]);

        add_action("admin_footer", [$this, "offset_dialog"]);
        add_action("woocommerce_admin_order_actions_end", [$this, "admin_wc_actions"], 20);
        add_action("add_meta_boxes_shop_order", [$this, "shop_order_metabox"]);
        add_action("woocommerce_admin_order_data_after_shipping_address", [$this, "single_order_shipment_options"]);

        add_action("wp_ajax_wcmp_save_shipment_options", [$this, "save_shipment_options_ajax"]);
        add_action("wp_ajax_wcmp_get_shipment_summary_status", [$this, "order_list_ajax_get_shipment_summary"]);

        // HS code in product shipping options tab
        add_action("woocommerce_product_options_shipping", [$this, "product_hs_code_field"]);
        add_action("woocommerce_process_product_meta", [$this, "product_hs_code_field_save"]);

        // Add barcode in order grid
        add_filter("manage_edit-shop_order_columns", [$this, "barcode_add_new_order_admin_list_column"], 10, 1);
        add_action(
            "manage_shop_order_posts_custom_column",
            [$this, "barcode_add_new_order_admin_list_column_content"],
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
        if (! WCMP()->export->is_myparcelbe_destination(
            WCX_Order::get_prop($order, 'shipping_country')
        )) {
            return;
        }

        $order_id = WCX_Order::get_id($order);
//        $shipment_options = WCMP()->export->get_options($order);
//        $package_types    = WCMP()->export->get_package_types();

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
                    <?php self::renderSpinner(); ?>
                </div>
            </div>
        <?php else : ?>
            <div class="wcmp_shipment_options wcmp__has-consignments">
                <?php $this->showDeliveryOptionsForOrder($order); ?>
            </div>
        <?php endif; ?>
        <div class="wcmp_shipment_options">
            <?php printf(
                '<a href="#" class="wcmp_show_shipment_options"><span class="wcpm_package_type">%s</span> &#x25BE;</a>',
                _wcmp("Details")
            ); ?>
            <div class="wcmp_shipment_options_form" style="display: none;">
                <a class="wcmp-display--block">
                    <?php include('views/html-order-shipment-options.php'); ?>
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
        /**
         * @var $order_id
         * @var $shipment_id
         */

        $order    = wc_get_order($order_id);
        $shipment = WCMP()->export->get_shipment_data($shipment_id, $order);
        if (! empty($shipment['tracktrace'])) {
            $order_has_shipment = true;
            $tracktrace_url     = $this->get_tracktrace_url($order_id, $shipment['tracktrace']);
        }

        include('views/html-order-shipment-summary.php');
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
            'WCMP_Export'       => _wcmp('MyParcel BE: Export'),
            'wcmp_print'        => _wcmp('MyParcel BE: Print'),
            'wcmp_export_print' => _wcmp('MyParcel BE: Export & Print'),
        ];

        if ('shop_order' == $post_type) {
            ?>
            <script type="text/javascript">
                jQuery(document).ready(function () {
                    <?php foreach ($bulk_actions as $action => $title) { ?>
                    jQuery('<option>')
                        .val('<?php echo $action; ?>')
                        .html('<?php echo esc_attr($title); ?>')
                        .appendTo('select[name=\'action\'], select[name=\'action2\']');
                    <?php }    ?>
                });
            </script>
            <?php self::renderSpinner(
                [
                    "class" => ["wcmp_bulk_spinner", "waiting"],
                    "style" => "display: none;",
                ]
            );
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
                <?php _wcmpe('Labels to skip'); ?>: <input type="text" size="2" class="wc_myparcelbe_offset">
                <img src="<?php echo WCMP()->plugin_url() . '/assets/img/print-offset-icon.png'; ?>"
                     id="wcmyparcelbe-offset-icon"
                     style="vertical-align: middle;">
                <button class="button" style="display:none; margin-top: 4px"><?php _wcmpe('Print'); ?></button>
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
    public function admin_wc_actions($order): void
    {
        $this->admin_order_actions($order);
    }

    /**
     * Add print actions to the orders listing
     *
     * @param $order
     */
    public function admin_order_actions($order): void
    {
        if (empty($order)) {
            return;
        }

        $shipping_country = WCX_Order::get_prop($order, 'shipping_country');
        if (! WCMP()->export->is_myparcelbe_destination($shipping_country)) {
            return;
        }

        $order_id = WCX_Order::get_id($order);

        $listing_actions = [
            WCMP_Export::ADD_SHIPMENT => [
                "url" => wp_nonce_url(
                    admin_url(
                        "admin-ajax.php?action=wc_myparcelbe&request="
                        . WCMP_Export::ADD_SHIPMENT
                        . "&order_ids="
                        . $order_id
                    ),
                    "wc_myparcelbe"
                ),
                "img" => WCMP()->plugin_url() . "/assets/img/myparcelbe-up.png",
                "alt" => esc_attr__("Export to MyParcel BE", "woocommerce-myparcelbe"),
            ],
            WCMP_Export::GET_LABELS   => [
                "url" => wp_nonce_url(
                    admin_url(
                        "admin-ajax.php?action=wc_myparcelbe&request="
                        . WCMP_Export::GET_LABELS
                        . "&order_ids="
                        . $order_id
                    ),
                    "wc_myparcelbe"
                ),
                "img" => WCMP()->plugin_url() . "/assets/img/myparcelbe-pdf.png",
                "alt" => esc_attr__("Print MyParcel BE label", "woocommerce-myparcelbe"),
            ],
            WCMP_Export::ADD_RETURN   => [
                "url" => wp_nonce_url(
                    admin_url(
                        "admin-ajax.php?action=wc_myparcelbe&request="
                        . WCMP_Export::ADD_RETURN
                        . "&order_ids="
                        . $order_id
                    ),
                    "wc_myparcelbe"
                ),
                "img" => WCMP()->plugin_url() . "/assets/img/myparcelbe-retour.png",
                "alt" => esc_attr__("Email return label", "woocommerce-myparcelbe"),
            ],
        ];

        $consignments = $this->get_order_shipments($order);

        if (empty($consignments)) {
            unset($listing_actions[WCMP_Export::GET_LABELS]);
        }

        $processed_shipments = $this->get_order_shipments($order, true);
        if (empty($processed_shipments) || $shipping_country != 'BE') {
            unset($listing_actions[WCMP_Export::ADD_RETURN]);
        }

        $target = (WCMP()->setting_collection->getByName(WCMP_Settings::SETTING_DOWNLOAD_DISPLAY)
                   && WCMP()->setting_collection->get(WCMP_Settings::SETTING_DOWNLOAD_DISPLAY) === 'display')
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
        self::renderSpinner(
            [
                "class" => ["wcmp_spinner", "waiting"],
                "style" => "width: 17px; margin: 5px 3px;",
            ]
        );
    }

    /**
     * @param      $order
     * @param bool $exclude_concepts
     *
     * @return array|bool|mixed|void
     */
    public function get_order_shipments($order, $exclude_concepts = false)
    {
        if (empty($order)) {
            return;
        }

        $consignments = WCX_Order::get_meta($order, self::META_SHIPMENTS);
        // fallback to legacy consignment data (v1.X)
        if (empty($consignments)) {
            if ($consignment_id = WCX_Order::get_meta($order, self::META_CONSIGNMENT_ID)) {
                $consignments = [
                    [
                        'shipment_id' => $consignment_id,
                        'tracktrace'  => WCX_Order::get_meta($order, self::META_TRACK_TRACE),
                    ],
                ];
            } elseif ($legacy_consignments = WCX_Order::get_meta($order, self::META_CONSIGNMENTS)) {
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

    /**
     * On saving shipment options from the bulk options form.
     *
     * @see admin/views/html-order-shipment-options.php
     */
    public function save_shipment_options_ajax()
    {
        error_reporting(E_ALL);
        ini_set('display_errors', 1);
//        check_ajax_referer('wc_myparcelbe', 'security');
        extract($_POST);

//        action=wcmp_save_shipment_options
//        order_id=80
//        form_data[myparcelbe_options][80][carrier]=0
//        form_data[myparcelbe_options][80][extra_options][collo_amount]=10
//        form_data[myparcelbe_options][80][shipment_options][signature]=0
//        form_data[myparcelbe_options][80][shipment_options][insured]=0

        /**
         * @var $form_data
         * @var $order_id
         */
        parse_str($form_data, $form_data);
        $order = WCX::get_order($order_id);

        if (isset($form_data[self::SHIPMENT_OPTIONS_FORM_NAME][$order_id])) {
            $shipment_options = $form_data[self::SHIPMENT_OPTIONS_FORM_NAME][$order_id];

            // convert insurance option
            if (isset($shipment_options['insured']) && isset($shipment_options['insured_amount'])) {
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
                    self::META_SHIPMENT_OPTIONS_EXTRA,
                    $shipment_options['extra_options']
                );
                unset($shipment_options['extra_options']);
            }

            $deliveryOptions = self::decodeDeliveryOptionsMeta($order);
//            var_dump($deliveryOptions);
//            var_dump($shipment_options);
//            exit("\n|-------------\n" . __FILE__ . ':' . __LINE__ . "\n|-------------\n");
            $deliveryOptions["carrier"] = $shipment_options["carrier"];
            $deliveryOptions["shipmentOptions"] = $shipment_options["shipment_options"];

            WCX_Order::update_meta_data($order, self::META_DELIVERY_OPTIONS, self::encodeDeliveryOptionsMeta($deliveryOptions));
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
            _wcmp('MyParcelBE'),                        //$title
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
        if (! WCMP()->export->is_myparcelbe_destination($shipping_country)) {
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
                    <th><?php _wcmpe('Track & Trace'); ?></th>
                    <th><?php _wcmpe('Status'); ?></th>
                </tr>
                </thead>
                <tbody>
                <?php
                $action            = WCMP_Export::GET_LABELS;
                $target            = (WCMP()->setting_collection->getByName(WCMP_Settings::SETTING_DOWNLOAD_DISPLAY)
                                      && WCMP()->setting_collection->getByName(WCMP_Settings::SETTING_DOWNLOAD_DISPLAY)
                                         == 'display') ? 'target="_blank"' : '';
                $nonce             = wp_create_nonce('wc_myparcelbe');
                $label_button_text = esc_attr__('Print MyParcel BE label', 'woocommerce-myparcelbe');
                foreach ($consignments as $shipment_id => $shipment):
                    $shipment = WCMP()->export->get_shipment_data($shipment_id, $order);
                    $label_url     = wp_nonce_url(
                        admin_url(
                            'admin-ajax.php?action=wc_myparcelbe&request='
                            . WCMP_Export::GET_LABELS
                            . '&shipment_ids='
                            . $shipment_id
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
                    } elseif (isset($shipment['shipment']) && isset($shipment['shipment']['options'])) {
                        $tracktrace_link = '(' . WCMP()->export->get_package_name(
                                $shipment['shipment']['options']['package_type']
                            ) . ')';
                    } else {
                        $tracktrace_link = '(Unknown)';
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
                                WCMP()->plugin_url() . "/assets/img/myparcelbe-pdf.png",
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
        if (! WCMP()->export->is_myparcelbe_destination($shipping_country)) {
            return;
        }

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

        /**
         * Show the delivery date if it is present.
         */
        if ($deliveryOptions->getDate()) {
            $this->printDeliveryDate($deliveryOptions);
        }
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
        if ($country === 'BE') {
            // use billing postcode for pickup/pakjegemak
            if (WCMP()->export->is_pickup($order)) {
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

    /**
     * @param $order_id
     *
     * @return array|bool
     */
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

    /**
     * @param $order_id
     *
     * @return array|bool|mixed|void
     */
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
            return _wcmp('No label has created yet');
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
     * @see \WCMP_Checkout::save_delivery_options
     */
    public static function getDeliveryOptionsFromOrder(WC_Order $order): DeliveryOptions
    {
        return new DeliveryOptions(self::decodeDeliveryOptionsMeta($order));
    }

    /**
     * @param WC_Order $order
     *
     * @return array
     */
    public static function decodeDeliveryOptionsMeta(WC_Order $order): array
    {
        return (array) json_decode(stripslashes(WCX_Order::get_meta($order, self::META_DELIVERY_OPTIONS)));
    }

    /**
     * @param array $metaData
     *
     * @return string
     */
    public static function encodeDeliveryOptionsMeta(array $metaData): string
    {
        return json_encode($metaData);
    }

    /**
     * Output the delivery date.
     *
     * @param DeliveryOptions $delivery_options
     *
     * @throws Exception
     */
    private function printDeliveryDate(DeliveryOptions $delivery_options): void
    {
        $string = $delivery_options->isPickup() ? _wcmp("Pickup") : _wcmp("Standard delivery");

        printf(
            '<div class="delivery-date"><strong>%s</strong><br />%s, %s</div>',
            _wcmp("MyParcel BE shipment:"),
            $string,
            wc_format_datetime(new WC_DateTime(strtotime($delivery_options->getDate())), 'D d-m')
        );
    }

    /**
     * Output a spinner.
     *
     * @param array $args
     */
    public static function renderSpinner(array $args = ["class" => "wcmp_spinner"]): void
    {
        $arguments = [];
        foreach ($args as $arg => $value) {
            if (is_array($value)) {
                $value = implode(" ", $value);
            }
            $arguments[] = "$arg=\"$value\"";
        }

        printf(
            '<img alt="loading"
                 src="%s"
                 %s />',
            WCMP()->plugin_url() . '/assets/img/wpspin_light.gif',
            implode(" ", $arguments)
        );
    }
}

return new WCMP_Admin();
