<?php

use MyParcelNL\Sdk\src\Model\DeliveryOptions\DeliveryOptions;
use WPO\WC\MyParcelBE\Compatibility\WC_Core as WCX;
use WPO\WC\MyParcelBE\Compatibility\Order as WCX_Order;
use WPO\WC\MyParcelBE\Entity\LegacyDeliveryOptions;

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
        add_action("admin_footer", [$this, "bulk_actions"]);
        add_action("admin_footer", [$this, "offset_dialog"]);

        /**
         * Orders page
         * --
         * showMyParcelSettings is on the woocommerce_admin_order_actions_end hook because there is no hook to put it
         * in the shipping address column... It is put in the right place after loading using JavaScript.
         *
         * @see wcmp-admin.js -> runTriggers()
         */
        add_action("woocommerce_admin_order_actions_end", [$this, "showMyParcelSettings"], 9999);
        add_action("woocommerce_admin_order_actions_end", [$this, "showOrderActions"], 20);

        /*
         * Single order page
         */
        add_action("add_meta_boxes_shop_order", [$this, "add_order_meta_box"]);
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
    public function showMyParcelSettings(WC_Order $order): void
    {
        if (! WCMP_Country_Codes::isMyParcelBeDestination(
            WCX_Order::get_prop($order, 'shipping_country')
        )) {
            return;
        }

        $order_id             = WCX_Order::get_id($order);
        $consignments         = $this->get_order_shipments($order, true);

        // if we have shipments, then we show status & link to Track & Trace, settings under i
        if (! empty($consignments)) :
            // only use last shipment
            $last_shipment = array_pop($consignments);
            $last_shipment_id = $last_shipment['shipment_id'];

            ?>
            <div class="wcmp__shipment-summary">
                <?php $this->showDeliveryOptionsForOrder($order); ?>
                <a class="wcmp__shipment-summary__show"><span class="wcmp__encircle wcmp__shipment-summary__show">i</span></a>
                <div class="wcmp__shipment-summary__list"
                     data-loaded=""
                     data-shipment_id="<?php echo $last_shipment_id; ?>"
                     data-order_id="<?php echo $order_id; ?>"
                     style="display: none;">
                    <?php self::renderSpinner(); ?>
                </div>
            </div>
        <?php else : ?>
            <div class="wcmp__shipment-options wcmp__has-consignments" style="display: none;">
                <?php $this->showDeliveryOptionsForOrder($order); ?>
            </div>
        <?php endif; ?>
        <div class="wcmp__shipment-options" style="display: none;">
            <?php printf(
                '<a href="#" class="wcmp__shipment-options__show">%s &#x25BE;</a>',
                __("Details", "woocommerce-myparcelbe")
            ); ?>
            <div class="wcmp__shipment-options__form" style="display: none;">
                <a class="wcmp__d--flex">
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
            'wcmp_export'       => __("MyParcel BE: Export", "woocommerce-myparcelbe"),
            'wcmp_print'        => __("MyParcel BE: Print", "woocommerce-myparcelbe"),
            'wcmp_export_print' => __("MyParcel BE: Export & Print", "woocommerce-myparcelbe"),
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
            </script>
            <?php
            self::renderSpinner();
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
            <div class="wcmp__offset-dialog" style="display:none;">
                <?php _e("Labels to skip", "woocommerce-myparcelbe"); ?>: <input type="text" size="2" class="wcmp__offset-dialog__offset">
                <img src="<?php echo WCMP()->plugin_url() . '/assets/img/print-offset-icon.png'; ?>"
                     class="wcmp__offset-dialog__icon"
                     style="vertical-align: middle;">
                <button class="button" style="display:none; margin-top: 4px"><?php _e("Print", "woocommerce-myparcelbe"); ?></button>
            </div>
            <?php
        }
    }

    /**
     * Add print actions to the orders listing
     *
     * @param $order
     */
    public function showOrderActions($order): void
    {
        if (empty($order)) {
            return;
        }

        $shipping_country = WCX_Order::get_prop($order, 'shipping_country');

        if (! WCMP_Country_Codes::isMyParcelBeDestination($shipping_country)) {
            return;
        }

        $order_id = WCX_Order::get_id($order);

        $baseUrl      = "admin-ajax.php?action=" . WCMP_Export::EXPORT;
        $addShipments = WCMP_Export::ADD_SHIPMENTS;
        $getLabels    = WCMP_Export::GET_LABELS;
        $addReturn    = WCMP_Export::ADD_RETURN;

        $listing_actions = [
            $addShipments => [
                "url" => wp_nonce_url(
                    admin_url("$baseUrl&request=$addShipments&order_ids=$order_id"),
                    "wc_myparcelbe"
                ),
                "img" => WCMP()->plugin_url() . "/assets/img/myparcelbe-up.png",
                "alt" => __("Export to MyParcel BE", "woocommerce-myparcelbe"),
            ],
            $getLabels    => [
                "url" => wp_nonce_url(
                    admin_url("$baseUrl&request=$getLabels&order_ids=$order_id"),
                    "wc_myparcelbe"
                ),
                "img" => WCMP()->plugin_url() . "/assets/img/myparcelbe-pdf.png",
                "alt" => __("Print MyParcel BE label", "woocommerce-myparcelbe"),
            ],
            $addReturn    => [
                "url" => wp_nonce_url(
                    admin_url("$baseUrl&request=$addReturn&order_ids=$order_id"),
                    "wc_myparcelbe"
                ),
                "img" => WCMP()->plugin_url() . "/assets/img/myparcelbe-retour.png",
                "alt" => __("Email return label", "woocommerce-myparcelbe"),
            ],
        ];

        $consignments = $this->get_order_shipments($order);

        if (empty($consignments)) {
            unset($listing_actions[$getLabels]);
        }

        $processed_shipments = $this->get_order_shipments($order, true);
        if (empty($processed_shipments) || $shipping_country != 'BE') {
            unset($listing_actions[$addReturn]);
        }

        $atts =
            (WCMP()->setting_collection->getByName(WCMP_Settings::SETTING_DOWNLOAD_DISPLAY) === 'display')
                ? 'target="_blank"' : '';

        foreach ($listing_actions as $request => $data) {
            $this->renderAction(
                $data['url'],
                $request,
                $data['alt'],
                $order_id,
                $atts,
                $data["img"]
            );
        }
    }

    /**
     * @param WC_Order $order
     * @param bool     $exclude_concepts
     *
     * @return array|bool|mixed|void
     */
    public function get_order_shipments(WC_Order $order, bool $exclude_concepts = false)
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
     * @throws Exception
     * @see admin/views/html-order-shipment-options.php
     */
    public function save_shipment_options_ajax()
    {
        parse_str($_POST["form_data"], $form_data);

        foreach ($form_data[self::SHIPMENT_OPTIONS_FORM_NAME] as $order_id => $data) {
            $order              = WCX::get_order($order_id);
            $newShipmentOptions = [];

            // Cast the option values to booleans
            foreach ($data["shipment_options"] as $option => $value) {
                $newShipmentOptions[$option] = (bool) $value;
            }

            $meta = self::decodeDeliveryOptionsMeta($order);

            $meta["carrier"] = $data["carrier"] ?? $meta["carrier"];

            $meta["shipmentOptions"] = array_replace_recursive(
                $meta["shipmentOptions"],
                $newShipmentOptions
            );

            $newDeliveryOptions = (new DeliveryOptions($meta))->toArray();

            WCX_Order::update_meta_data(
                $order,
                self::META_DELIVERY_OPTIONS,
                self::encodeDeliveryOptionsMeta($newDeliveryOptions)
            );

            // Save extra options
            WCX_Order::update_meta_data(
                $order,
                self::META_SHIPMENT_OPTIONS_EXTRA,
                $data["extra_options"]
            );
        }
    }

    /**
     * Add the meta box on the single order page
     */
    public function add_order_meta_box(): void
    {
        add_meta_box(
            "myparcelbe",
            __("MyParcelBE", "woocommerce-myparcelbe"),
            [$this, "createMetaBox"],
            "shop_order",
            "side",
            "default"
        );
    }

    /**
     * Callback: Create the meta box content on the single order page
     */
    public function createMetaBox(): void
    {
        global $post_id;
        // get order
        $order = WCX::get_order($post_id);

        if (! $order) {
            return;
        }

        $order_id = WCX_Order::get_id($order);

        $shipping_country = WCX_Order::get_prop($order, 'shipping_country');
        if (! WCMP_Country_Codes::isMyParcelBeDestination($shipping_country)) {
            return;
        }

        $class = version_compare(WOOCOMMERCE_VERSION, '3.3.0', '>=') ? "single_wc_actions" : "single_order_actions";
        // show buttons and check if WooCommerce > 3.3.0 is used and select the correct function and class
        echo "<div class=\"$class\">";
        $this->showOrderActions($order);
        echo '</div>';

        $downloadDisplay = WCMP()->setting_collection->getByName(WCMP_Settings::SETTING_DOWNLOAD_DISPLAY) === 'display';
        $consignments    = $this->get_order_shipments($order);

        // show shipments if available
        if (! empty($consignments)) {
            ?>
            <table class="wcmp__table--track-trace">
                <thead>
                <tr>
                    <th><?php _e("Track & Trace", "woocommerce-myparcelbe"); ?></th>
                    <th><?php _e("Status", "woocommerce-myparcelbe"); ?></th>
                    <th>&nbsp;</th>
                </tr>
                </thead>
                <tbody>
                <?php

                foreach ($consignments as $shipment_id => $shipment):
                    try {
                        $shipment = WCMP()->export->get_shipment_data($shipment_id, $order);
                    } catch (Exception $e) {
                        $message = $e->getMessage();
                    }

                    if (isset($message)) {
                        echo "<p>$message</p>";
                    }

                    ?>
                    <tr>
                        <td class="wcmp__order__track-trace">
                            <?php $this->renderTrackTraceLink($shipment, $order_id); ?>
                        </td>
                        <td class="wcmp__order__status">
                            <?php $this->renderStatus($shipment) ?>
                        </td>
                        <td class="wcmp__td--create-label">
                            <?php
                            $action  = WCMP_Export::EXPORT;
                            $request = WCMP_Export::GET_LABELS;

                            $this->renderAction(
                                wp_nonce_url(
                                    admin_url(
                                        "admin-ajax.php?action=$action&request=$request&shipment_ids=$shipment_id"
                                    ),
                                    'wc_myparcelbe'
                                ),
                                WCMP_Export::GET_LABELS,
                                __("Print MyParcel BE label", "woocommerce-myparcelbe"),
                                $order_id,
                                $downloadDisplay ? 'target="_blank"' : '',
                                WCMP()->plugin_url() . "/assets/img/myparcelbe-pdf.png"
                            );

                            ?>
                        </td>
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
    public function single_order_shipment_options(WC_Order $order)
    {
        $shipping_country = WCX_Order::get_prop($order, "shipping_country");

        if (! WCMP_Country_Codes::isMyParcelBeDestination($shipping_country)) {
            return;
        }

        $this->showMyParcelSettings($order);
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
            return __("No label has created yet", "woocommerce-myparcelbe");
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
        $meta = self::decodeDeliveryOptionsMeta($order);

        // This attribute always exists if the order has 4.0.0+ delivery options. If it doesn't, migrate them first.
        if (! array_key_exists("carrier", $meta)) {
            return (new LegacyDeliveryOptions($meta))->getDeliveryOptions();
        }

        return new DeliveryOptions($meta);
    }

    /**
     * @param WC_Order $order
     *
     * @return array
     */
    public static function decodeDeliveryOptionsMeta(WC_Order $order): array
    {
        $meta = WCX_Order::get_meta($order, self::META_DELIVERY_OPTIONS);

        if (is_array($meta)) {
            return $meta;
        }

        return json_decode(stripslashes($meta), true) ?? [];
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
        $string = $delivery_options->isPickup() ? __("Pickup") : __("Standard delivery", "woocommerce-myparcelbe", "woocommerce-myparcelbe");

        printf(
            '<div class="delivery-date"><strong>%s</strong><br />%s, %s</div>',
            __("MyParcel BE shipment:", "woocommerce-myparcelbe"),
            $string,
            wc_format_datetime(new WC_DateTime($delivery_options->getDate()), 'l d-m')
        );
    }

    /**
     * Output a spinner.
     *
     * @param string $state
     * @param array  $args
     */
    public static function renderSpinner(string $state = "", array $args = []): void
    {
        $spinners = [
            "loading" => get_site_url() . "/wp-admin/images/spinner.gif",
            "success" => get_site_url() . "/wp-admin/images/yes.png",
            "failed"  => get_site_url() . "/wp-admin/images/no.png",
        ];

        $arguments = [];

        $args["class"][] = "wcmp__spinner";

        if ($state) {
            $args["class"][] = "wcmp__spinner--$state";
        }

        foreach ($args as $arg => $value) {
            if (is_array($value)) {
                $value = implode(" ", $value);
            }
            $arguments[] = "$arg=\"$value\"";
        }

        $attributes = implode(" ", $arguments);

        echo "<div $attributes>";
        foreach ($spinners as $spinnerState => $icon) {
            printf(
                '<img class="wcmp__spinner__%1$s" alt="%1$s" src="%2$s" style="display: %3$s;" />',
                $spinnerState,
                $icon,
                $state === $spinnerState ? "block" : "none"
            );
        }
        echo '</div>';
    }

    /**
     * @param string $url
     * @param string $request
     * @param string $alt
     * @param string $orderId
     * @param string $extraAtts
     * @param string $icon
     */
    private function renderAction(
        string $url,
        string $request,
        string $alt,
        string $orderId,
        string $extraAtts,
        string $icon
    ): void
    {
        printf(
            '<a href="%1$s" 
                    class="button tips wcmp__action wcmp__d--flex" 
                    data-tip="%3$s" 
                    data-order-id="%4$s" 
                    data-request="%2$s" 
                    data-nonce="%5$s" 
                    %6$s>
                <img class="wcmp__action__img" src="%7$s" alt="%3$s" />',
            $url,
            $request,
            $alt,
            $orderId,
            wp_create_nonce('wc_myparcelbe'),
            $extraAtts,
            $icon
        );

        self::renderSpinner();
        echo "</a>";
    }

    /**
     * @param array $shipment
     * @param int   $order_id
     */
    private function renderTrackTraceLink(array $shipment, int $order_id): void
    {
        $track_trace = $shipment["track_trace"] ?? null;

        if ($track_trace) {
            $track_trace_url  = $this->get_tracktrace_url($order_id, $track_trace);
            $track_trace_link = sprintf(
                '<a href="%s" target="_blank">%s</a>',
                $track_trace_url,
                $track_trace
            );
        } elseif (isset($shipment["shipment"]) && isset($shipment["shipment"]["options"])) {
            $package_type     = WCMP()->export->get_package_name($shipment["shipment"]["options"]["package_type"]);
            $track_trace_link = "($package_type)";
        } else {
            $track_trace_link = __("(Unknown)", "woocommerce-myparcelbe");
        }

        echo $track_trace_link;
    }

    /**
     * @param array $shipment
     */
    private function renderStatus(array $shipment): void
    {
        $status = isset($shipment["status"]) ? $shipment["status"] : "-";

        echo $status;
    }
}

return new WCMP_Admin();
