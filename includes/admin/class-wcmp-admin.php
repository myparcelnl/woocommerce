<?php

use MyParcelNL\Sdk\src\Adapter\DeliveryOptions\AbstractDeliveryOptionsAdapter as DeliveryOptions;
use MyParcelNL\Sdk\src\Factory\DeliveryOptionsAdapterFactory;
use WPO\WC\MyParcel\Compatibility\WC_Core as WCX;
use WPO\WC\MyParcel\Compatibility\Order as WCX_Order;
use WPO\WC\MyParcel\Compatibility\Product as WCX_Product;
use WPO\WC\MyParcel\Entity\SettingsFieldArguments;

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
    public const META_CONSIGNMENTS           = "_myparcel_consignments";
    public const META_CONSIGNMENT_ID         = "_myparcel_consignment_id";
    public const META_DELIVERY_OPTIONS       = "_myparcel_delivery_options";
    public const META_HIGHEST_SHIPPING_CLASS = "_myparcel_highest_shipping_class";
    public const META_LAST_SHIPMENT_IDS      = "_myparcel_last_shipment_ids";
    public const META_ORDER_VERSION          = "_myparcel_order_version";
    public const META_ORDER_WEIGHT           = "_myparcel_order_weight";
    public const META_PGADDRESS              = "_myparcel_pgaddress";
    public const META_SHIPMENTS              = "_myparcel_shipments";
    public const META_SHIPMENT_OPTIONS_EXTRA = "_myparcel_shipment_options_extra";
    public const META_TRACK_TRACE            = "_myparcel_tracktrace";
    public const META_HS_CODE                = "_myparcel_hs_code";
    public const META_COUNTRY_OF_ORIGIN      = "_myparcel_country_of_origin";

    public const SHIPMENT_OPTIONS_FORM_NAME = "myparcel_options";

    public const BULK_ACTION_EXPORT       = "wcmp_export";
    public const BULK_ACTION_PRINT        = "wcmp_print";
    public const BULK_ACTION_EXPORT_PRINT = "wcmp_export_print";

    public function __construct()
    {
        if (is_wp_version_compatible("4.7.0")) {
            add_action("bulk_actions-edit-shop_order", [$this, "addBulkActions"], 100);
        } else {
            add_action("admin_footer", [$this, "bulk_actions"]);
        }

        add_action("admin_footer", [$this, "renderOffsetDialog"]);

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
        add_action("woocommerce_product_options_shipping", [$this, "productHsCodeField"]);
        add_action("woocommerce_process_product_meta", [$this, "productHsCodeFieldSave"]);

        // Country of Origin in product shipping options tab
        add_action("woocommerce_product_options_shipping", [$this, "productCountryOfOriginField"]);
        add_action("woocommerce_process_product_meta", [$this, "productCountryOfOriginFieldSave"]);

        // Add barcode in order grid
        add_filter("manage_edit-shop_order_columns", [$this, "barcode_add_new_order_admin_list_column"], 10, 1);
        add_action("manage_shop_order_posts_custom_column", [$this, "addBarcodeToOrderColumn"], 10, 2);
    }

    /**
     * @param WC_Order $order
     *
     * @throws Exception
     */
    public function showMyParcelSettings(WC_Order $order): void
    {
        if (! WCMP_Country_Codes::isAllowedDestination(
            WCX_Order::get_prop($order, 'shipping_country')
        )) {
            return;
        }

        $order_id             = WCX_Order::get_id($order);
        $consignments         = WCMP_Admin::get_order_shipments($order);

        // if we have shipments, then we show status & link to Track & Trace, settings under i
        if (! empty($consignments)) :
            // only use last shipment
            $last_shipment = array_pop($consignments);
            $last_shipment_id = $last_shipment['shipment_id'];

            ?>
            <div class="wcmp__shipment-summary">
                <?php $this->showDeliveryOptionsForOrder($order); ?>
                <a class="wcmp__shipment-summary__show"><span
                            class="wcmp__encircle wcmp__shipment-summary__show">i</span></a>
                <div class="wcmp__box wcmp__shipment-summary__list"
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
                __("Details", "woocommerce-myparcel")
            ); ?>
            <div class="wcmp__box wcmp__shipment-options__form" style="display: none;">
                <a class="wcmp__d--flex">
                    <?php include('views/html-order-shipment-options.php'); ?>
                </a>
            </div>
        </div>
        <?php
    }

    /**
     * Get shipment status + Track & Trace link via AJAX
     *
     * @throws Exception
     */
    public function order_list_ajax_get_shipment_summary()
    {
        check_ajax_referer(WCMP::NONCE_ACTION, 'security');

        include('views/html-order-shipment-summary.php');
        die();
    }

    /**
     * Add export option to bulk action drop down menu.
     *
     * @param array $actions
     *
     * @return array
     * @since WordPress 4.7.0
     *
     */
    public function addBulkActions(array $actions): array
    {
        $actions = array_merge(
            $actions,
            [
                self::BULK_ACTION_EXPORT       => __("MyParcel: Export", "woocommerce-myparcel"),
                self::BULK_ACTION_PRINT        => __("MyParcel: Print", "woocommerce-myparcel"),
                self::BULK_ACTION_EXPORT_PRINT => __("MyParcel: Export & Print", "woocommerce-myparcel"),
            ]
        );

        return $actions;
    }

    /**
     * Add export option to bulk action drop down menu
     * Using Javascript until WordPress core fixes: http://core.trac.wordpress.org/ticket/16031
     *
     * Used pre WordPress 4.7.0
     *
     * @access public
     * @return void
     */
    public function bulk_actions()
    {
        global $post_type;
        $bulk_actions = [
            self::BULK_ACTION_EXPORT       => __("MyParcel: Export", "woocommerce-myparcel"),
            self::BULK_ACTION_PRINT        => __("MyParcel: Print", "woocommerce-myparcel"),
            self::BULK_ACTION_EXPORT_PRINT => __("MyParcel: Export & Print", "woocommerce-myparcel"),
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
    public function renderOffsetDialog(): void
    {
        if (! WCMP()->setting_collection->isEnabled(WCMP_Settings::SETTING_ASK_FOR_PRINT_POSITION)) {
            return;
        }

        $field = [
            "name"              => "offset",
            "class"             => ["wcmp__d--inline-block"],
            "input_class"       => ["wcmp__offset-dialog__offset"],
            "type"              => "number",
            "label"             => __("Labels to skip", "woocommerce-myparcel"),
            "custom_attributes" => [
                "step" => "1",
                "min"  => "0",
                "max"  => "4",
                "size" => "2",
            ],
        ];

        $class = new SettingsFieldArguments($field);
        ?>

        <div
                class="wcmp wcmp__box wcmp__offset-dialog"
                style="display: none;">
            <div class="wcmp__offset-dialog__inner wcmp__d--flex">
                <div>
                    <?php woocommerce_form_field($field["name"], $class->getArguments(false), ""); ?>

                    <img
                            src="<?php echo WCMP()->plugin_url() . "/assets/img/print-offset-icon.png"; ?>"
                            alt="<?php implode(", ", WCMP_Export::DEFAULT_POSITIONS) ?>"
                            class="wcmp__offset-dialog__icon"/>
                    <div>
                        <a
                                href="#"
                                class="wcmp__action wcmp__offset-dialog__button button">
                            <?php _e("Print", "woocommerce-myparcel"); ?>
                            <?php WCMP_Admin::renderSpinner(); ?>
                        </a>
                    </div>
                </div>
                <div class="wcmp__close-button dashicons dashicons-no-alt wcmp__offset-dialog__close"></div>
            </div>
        </div>
        <?php
    }

    /**
     * Add print actions to the orders listing
     *
     * @param $order
     *
     * @throws Exception
     */
    public function showOrderActions($order): void
    {
        if (empty($order)) {
            return;
        }

        $shipping_country = WCX_Order::get_prop($order, 'shipping_country');

        if (! WCMP_Country_Codes::isAllowedDestination($shipping_country)) {
            return;
        }

        $order_id = WCX_Order::get_id($order);

        $baseUrl      = "admin-ajax.php?action=" . WCMP_Export::EXPORT;
        $addShipments = WCMP_Export::ADD_SHIPMENTS;
        $getLabels    = WCMP_Export::GET_LABELS;
        $addReturn    = WCMP_Export::ADD_RETURN;

        $listing_actions = [
            $addShipments => [
                "url" => admin_url("$baseUrl&request=$addShipments&order_ids=$order_id"),
                "img" => WCMP()->plugin_url() . "/assets/img/myparcel-up.png",
                "alt" => __("Export to MyParcel", "woocommerce-myparcel"),
            ],
            $getLabels    => [
                "url" => admin_url("$baseUrl&request=$getLabels&order_ids=$order_id"),
                "img" => WCMP()->plugin_url() . "/assets/img/myparcel-pdf.png",
                "alt" => __("Print MyParcel label", "woocommerce-myparcel"),
            ],
            $addReturn    => [
                "url" => admin_url("$baseUrl&request=$addReturn&order_ids=$order_id"),
                "img" => WCMP()->plugin_url() . "/assets/img/myparcel-retour.png",
                "alt" => __("Email return label", "woocommerce-myparcel"),
            ],
        ];

        $consignments = WCMP_Admin::get_order_shipments($order);

        if (empty($consignments)) {
            unset($listing_actions[$getLabels]);
        }

        $processed_shipments = WCMP_Admin::get_order_shipments($order);
        if (empty($processed_shipments) || $shipping_country !== 'NL') {
            unset($listing_actions[$addReturn]);
        }

        $display = WCMP()->setting_collection->getByName(WCMP_Settings::SETTING_DOWNLOAD_DISPLAY) === 'display';

        $attributes = [];

        if ($display) {
            $attributes["target"] = "_blank";
        }

        foreach ($listing_actions as $request => $data) {
            self::renderAction(
                $data['url'],
                $data['alt'],
                $data["img"],
                $attributes
            );
        }
    }

    /**
     * @param WC_Order $order
     * @param bool     $exclude_concepts
     *
     * @return array
     */
    public static function get_order_shipments(WC_Order $order, bool $exclude_concepts = false): array
    {
        $consignments = WCX_Order::get_meta($order, self::META_SHIPMENTS);

        // fallback to legacy consignment data (v1.X)
        if (empty($consignments)) {
            if ($consignment_id = WCX_Order::get_meta($order, self::META_CONSIGNMENT_ID)) {
                $consignments = [
                    [
                        "shipment_id" => $consignment_id,
                        "track_trace" => WCX_Order::get_meta($order, self::META_TRACK_TRACE),
                    ],
                ];
            } elseif ($legacy_consignments = WCX_Order::get_meta($order, self::META_CONSIGNMENTS)) {
                $consignments = [];
                foreach ($legacy_consignments as $consignment) {
                    if (isset($consignment["consignment_id"])) {
                        $consignments[] = [
                            "shipment_id" => $consignment["consignment_id"],
                            "track_trace" => $consignment["track_trace"],
                        ];
                    }
                }
            }
        }

        if (empty($consignments) || ! is_array($consignments)) {
            return [];
        }

        /**
         * Filter out concepts.
         */
        if ($exclude_concepts) {
            $consignments = array_filter($consignments, function($consignment) {
                return isset($consignment["track_trace"]);
            });
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
            $order = WCX::get_order($order_id);
            /**
             * @var DeliveryOptions $deliveryOptions
             */
            $deliveryOptions = self::getDeliveryOptionsFromOrder($order, $data);

            WCX_Order::update_meta_data(
                $order,
                self::META_DELIVERY_OPTIONS,
                $deliveryOptions
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
            "myparcel",
            __("MyParcel", "woocommerce-myparcel"),
            [$this, "createMetaBox"],
            "shop_order",
            "side",
            "default"
        );
    }

    /**
     * Callback: Create the meta box content on the single order page
     *
     * @throws Exception
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
        if (! WCMP_Country_Codes::isAllowedDestination($shipping_country)) {
            return;
        }

        $class = version_compare(WOOCOMMERCE_VERSION, '3.3.0', '>=') ? "single_wc_actions" : "single_order_actions";
        // show buttons and check if WooCommerce > 3.3.0 is used and select the correct function and class
        echo "<div class=\"$class\">";
        $this->showOrderActions($order);
        echo '</div>';

        $downloadDisplay = WCMP()->setting_collection->getByName(WCMP_Settings::SETTING_DOWNLOAD_DISPLAY) === 'display';
        $consignments    = WCMP_Admin::get_order_shipments($order);

        // show shipments if available
        if (empty($consignments)) {
            return;
        }

        include('views/html-order-track-trace-table.php');
    }

    /**
     * @param $order
     *
     * @throws Exception
     */
    public function single_order_shipment_options(WC_Order $order)
    {
        $shipping_country = WCX_Order::get_prop($order, "shipping_country");

        if (! WCMP_Country_Codes::isAllowedDestination($shipping_country)) {
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
     * @param $track_trace
     *
     * @return string|void
     * @throws Exception
     */
    public static function getTrackTraceUrl($order_id, $track_trace)
    {
        if (empty($order_id)) {
            return;
        }

        $order    = WCX::get_order($order_id);
        $country  = WCX_Order::get_prop($order, 'shipping_country');
        $postcode = preg_replace('/\s+/', '', WCX_Order::get_prop($order, 'shipping_postcode'));

        // set url for NL or foreign orders
        if ($country === 'NL') {
            $deliveryOptions = self::getDeliveryOptionsFromOrder($order);

            // use billing postcode for pickup/pakjegemak
            if ($deliveryOptions->isPickup()) {
                $postcode = preg_replace('/\s+/', '', WCX_Order::get_prop($order, 'billing_postcode'));
            }

            $trackTraceUrl = sprintf(
                'https://myparcel.me/track-trace/%s/%s/%s',
                $track_trace,
                $postcode,
                $country
            );
        } else {
            $trackTraceUrl = sprintf(
                'https://www.internationalparceltracking.com/Main.aspx#/track/%s/%s/%s',
                $track_trace,
                $country,
                $postcode
            );
        }

        return $trackTraceUrl;
    }

    public function productHsCodeField()
    {
        echo '<div class="options_group">';
        woocommerce_wp_text_input(
            array(
                'id'          => self::META_HS_CODE,
                'label'       => __('HS Code', 'woocommerce-myparcel'),
                'description' => sprintf(
                    __('HS Codes are used for MyParcel world shipments, you can find the appropriate code on the %ssite of the Dutch Customs%s.', 'woocommerce-myparcel'),
                    '<a href="http://tarief.douane.nl/arctictariff-public-web/#!/home" target="_blank">',
                    '</a>'
                )
            )
        );
        echo '</div>';
    }

    public function productHsCodeFieldSave($post_id)
    {
        // check if hs code is passed and not an array (=variation hs code)
        if (isset($_POST[self::META_HS_CODE]) && ! is_array($_POST[self::META_HS_CODE])) {
            $product = wc_get_product($post_id);
            $hs_code = $_POST[self::META_HS_CODE];
            if (! empty($hs_code)) {
                WCX_Product::update_meta_data($product, self::META_HS_CODE, esc_attr($hs_code));
            } else {
                if (isset($_POST[self::META_HS_CODE]) && empty($hs_code)) {
                    WCX_Product::delete_meta_data($product, self::META_HS_CODE);
                }
            }
        }
    }

    public function productCountryOfOriginField()
    {
        echo '<div class="options_group">';
        woocommerce_wp_text_input(
            array(
                'id'          => self::META_COUNTRY_OF_ORIGIN,
                'label'       => __('Country of Origin', 'woocommerce-myparcel'),
                'description' => sprintf(
                    __('Country of origin is required for world shipments. Defaults to shop base.')
                )
            )
        );
        echo '</div>';
    }

    public function productCountryOfOriginFieldSave($postId)
    {
        if (isset($_POST[self::META_COUNTRY_OF_ORIGIN]) && ! is_array($_POST[self::META_COUNTRY_OF_ORIGIN])) {
            $product = wc_get_product($postId);
            $countryOfOrigin = $_POST[self::META_COUNTRY_OF_ORIGIN];
            if (! empty($countryOfOrigin)) {
                WCX_Product::update_meta_data($product, self::META_HS_CODE, esc_attr($countryOfOrigin));
                return;
            } 
            if (isset($_POST[self::META_COUNTRY_OF_ORIGIN]) && empty($countryOfOrigin)) {
                WCX_Product::delete_meta_data($product, self::META_COUNTRY_OF_ORIGIN);
            }
        }
        return;
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
     *
     * @throws Exception
     */
    public function addBarcodeToOrderColumn($column)
    {
        global $post;

        if ("barcode" === $column) {
            $this->renderBarcodes(WCX::get_order($post->ID));
        }
    }

    /**
     * @param WC_Order $order
     *
     * @return void
     * @throws Exception
     */
    public function renderBarcodes(WC_Order $order): void
    {
        $shipments = WCMP_Admin::get_order_shipments($order, true);

        if (empty($shipments)) {
            echo __("No label has been created yet.", "woocommerce-myparcel");

            return;
        }

        echo '<div class="wcmp__barcodes">';
        foreach ($shipments as $shipment_id => $shipment) {
            printf(
                '<a target="_blank" class="wcmp__barcode-link" title="%2$s" href="%1$s">%2$s</a><br>',
                WCMP_Admin::getTrackTraceUrl($order, $shipment["track_trace"]),
                $shipment["track_trace"]
            );
        }
        echo "</div>";
    }

    /**
     * Get DeliveryOptions object from the given order's meta data. Uses legacy delivery options if found, if that data
     * is invalid it falls back to defaults.
     *
     * @param WC_Order $order
     * @param array    $inputData
     *
     * @return DeliveryOptions
     * @throws \Exception
     * @see \WCMP_Checkout::save_delivery_options
     */
    public static function getDeliveryOptionsFromOrder(WC_Order $order, array $inputData = []): DeliveryOptions
    {
        $meta = WCX_Order::get_meta($order, self::META_DELIVERY_OPTIONS);

        // $meta is a json string, create an instance
        if (! empty($meta) && ! $meta instanceof DeliveryOptions) {
            if (is_string($meta)) {
                $meta = json_decode(stripslashes($meta), true);
            }

            $meta["carrier"] = WCMP_Data::DEFAULT_CARRIER;

            try {
                // create new instance from known json
                $meta = DeliveryOptionsAdapterFactory::create((array) $meta);
            } catch (BadMethodCallException $e) {
                // create new instance from unknown json data
                $meta = new WCMP_DeliveryOptionsFromOrderAdapter(null, (array) $meta);
            }
        }

        if (empty($meta)) {
            $meta = null;
        }

        // Create or update immutable adapter from order with a instanceof DeliveryOptionsAdapter
        $meta = new WCMP_DeliveryOptionsFromOrderAdapter($meta, $inputData);

        return $meta;
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
        $string = $delivery_options->isPickup() ? __("Pickup") : __("Standard delivery", "woocommerce-myparcel", "woocommerce-myparcel");

        printf(
            '<div class="delivery-date"><strong>%s</strong><br />%s, %s</div>',
            __("MyParcel shipment:", "woocommerce-myparcel"),
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

        echo "<span $attributes>";
        foreach ($spinners as $spinnerState => $icon) {
            printf(
                '<img class="wcmp__spinner__%1$s" alt="%1$s" src="%2$s" style="display: %3$s;" />',
                $spinnerState,
                $icon,
                $state === $spinnerState ? "block" : "none"
            );
        }
        echo '</span>';
    }

    /**
     * @param string $url
     * @param string $alt
     * @param string $icon
     * @param array  $rawAttributes
     */
    public static function renderAction(
        string $url,
        string $alt,
        string $icon,
        array $rawAttributes = []
    ): void {
        printf(
            '<a href="%1$s" 
                    class="button tips wcmp__action wcmp__d--flex" 
                    data-tip="%2$s" 
                    %4$s>
                <img class="wcmp__action__img" src="%3$s" alt="%2$s" />',
            wp_nonce_url($url, WCMP::NONCE_ACTION),
            $alt,
            $icon,
            wc_implode_html_attributes($rawAttributes)
        );

        self::renderSpinner();
        echo "</a>";
    }

    /**
     * @param array $shipment
     * @param int   $order_id
     *
     * @throws Exception
     */
    public static function renderTrackTraceLink(array $shipment, int $order_id): void
    {
        $track_trace = $shipment["track_trace"] ?? null;

        if ($track_trace) {
            $track_trace_url  = WCMP_Admin::getTrackTraceUrl($order_id, $track_trace);
            $track_trace_link = sprintf(
                '<a href="%s" target="_blank">%s</a>',
                $track_trace_url,
                $track_trace
            );
        } elseif (isset($shipment["shipment"]) && isset($shipment["shipment"]["options"])) {
            $package_type     = WCMP()->export->getPackageType($shipment["shipment"]["options"]["package_type"]);
            $track_trace_link = "($package_type)";
        } else {
            $track_trace_link = __("(Unknown)", "woocommerce-myparcel");
        }

        echo $track_trace_link;
    }

    /**
     * @param array $shipment
     */
    public static function renderStatus(array $shipment): void
    {
        echo $shipment["status"] ?? "–";
    }
}

return new WCMP_Admin();
