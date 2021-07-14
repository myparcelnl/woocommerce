<?php

use MyParcelNL\Sdk\src\Adapter\DeliveryOptions\AbstractDeliveryOptionsAdapter as DeliveryOptions;
use MyParcelNL\Sdk\src\Factory\DeliveryOptionsAdapterFactory;
use MyParcelNL\Sdk\src\Model\Consignment\AbstractConsignment;
use MyParcelNL\Sdk\src\Support\Arr;
use WPO\WC\MyParcel\Compatibility\Order as WCX_Order;
use WPO\WC\MyParcel\Compatibility\Product as WCX_Product;
use WPO\WC\MyParcel\Compatibility\WC_Core as WCX;
use WPO\WC\MyParcel\Entity\SettingsFieldArguments;

if (! defined('ABSPATH')) {
    exit;
} // Exit if accessed directly

if (class_exists('WCMYPA_Admin')) {
    return new WCMYPA_Admin();
}

/**
 * Admin options, buttons & data
 */
class WCMYPA_Admin
{
    public const META_CONSIGNMENTS           = "_myparcel_consignments";
    public const META_CONSIGNMENT_ID         = "_myparcel_consignment_id";
    public const META_DELIVERY_OPTIONS       = "_myparcel_delivery_options";
    public const META_HIGHEST_SHIPPING_CLASS = "_myparcel_highest_shipping_class";
    public const META_LAST_SHIPMENT_IDS      = "_myparcel_last_shipment_ids";
    public const META_RETURN_SHIPMENT_IDS    = "_myparcel_return_shipment_ids";
    public const META_ORDER_VERSION          = "_myparcel_order_version";
    public const META_DELIVERY_DATE          = "_myparcel_delivery_date";
    public const META_PGADDRESS              = "_myparcel_pgaddress";
    public const META_SHIPMENTS              = "_myparcel_shipments";
    public const META_SHIPMENT_OPTIONS_EXTRA = "_myparcel_shipment_options_extra";
    public const META_TRACK_TRACE            = "_myparcel_tracktrace";
    public const META_HS_CODE                = "_myparcel_hs_code";
    public const META_HS_CODE_VARIATION      = "_myparcel_hs_code_variation";
    public const META_COUNTRY_OF_ORIGIN_VARIATION = "_myparcel_country_of_origin_variation";
    public const META_COUNTRY_OF_ORIGIN      = "_myparcel_country_of_origin";
    public const META_AGE_CHECK              = "_myparcel_age_check";
    public const META_PPS                    = '_myparcel_pps';

    public const META_PPS_EXPORTED           = 'pps_exported';
    public const META_PPS_EXPORT_DATE        = 'pps_export_date';
    public const META_PPS_UUID               = 'pps_uuid';

    public const BULK_ACTION_EXPORT       = 'wcmp_export';
    public const BULK_ACTION_PRINT        = 'wcmp_print';
    public const BULK_ACTION_EXPORT_PRINT = 'wcmp_export_print';


    /**
     * @deprecated use weight property in META_SHIPMENT_OPTIONS_EXTRA.
     */
    public const META_ORDER_WEIGHT = "_myparcel_order_weight";

    /**
     * Legacy meta keys.
     */
    public const META_SHIPMENT_OPTIONS_LT_4_0_0 = "_myparcel_shipment_options";

    // Ids referring to shipment statuses.
    public const ORDER_STATUS_DELIVERED_AT_RECIPIENT      = 7;
    public const ORDER_STATUS_DELIVERED_READY_FOR_PICKUP  = 8;
    public const ORDER_STATUS_DELIVERED_PACKAGE_PICKED_UP = 9;
    public const ORDER_STATUS_PRINTED_LETTER              = 12;
    public const ORDER_STATUS_PRINTED_DIGITAL_STAMP       = 14;

    public const SHIPMENT_OPTIONS_FORM_NAME = "myparcel_options";

    public const PRODUCT_OPTIONS_ENABLED  = "yes";
    public const PRODUCT_OPTIONS_DISABLED = "no";
    public const PRODUCT_OPTIONS_DEFAULT  = null;

    public function __construct()
    {
        if (is_wp_version_compatible("4.7.0")) {
            add_action("bulk_actions-edit-shop_order", [$this, "addBulkActions"], 100);
        } else {
            add_action("admin_footer", [$this, "bulk_actions"]);
        }

        add_action("admin_footer", [$this, "renderOffsetDialog"]);
        add_action("admin_footer", [$this, "renderShipmentOptionsForm"]);

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
        add_action("wp_ajax_wcmp_get_shipment_options", [$this, "ajaxGetShipmentOptions"]);

        // Add barcode in order grid
        add_filter("manage_edit-shop_order_columns", [$this, "barcode_add_new_order_admin_list_column"], 10, 1);
        add_action("manage_shop_order_posts_custom_column", [$this, "addBarcodeToOrderColumn"], 10, 2);

        add_action('restrict_manage_posts', [$this, 'addDeliveryDayFilterToOrdergrid'], 10, 1);
        add_filter('request', [$this, 'getDeliveryDateFromOrder'], 10, 1);

        add_action('woocommerce_payment_complete', [$this, 'automaticExportOrder'], 1000);
        add_action('woocommerce_order_status_changed', [$this, 'automaticExportOrder'], 1000, 3);

        // At the moment this doens't work correctly. Shipment will disappear when status is delivered.
        // add_action("init", [$this, "registerDeliveredPostStatus"], 10, 1);
        // add_filter("wc_order_statuses", [$this, "displayDeliveredPostStatus"], 10, 2);

        add_action('woocommerce_product_after_variable_attributes', [$this, 'variation_hs_code_field'], 10, 3);
        add_action('woocommerce_save_product_variation', [$this, 'save_variation_hs_code_field'], 10, 2);
        add_filter('woocommerce_available_variation', [$this, 'load_variation_hs_code_field'], 10, 1);

        add_action("woocommerce_product_options_shipping", [$this, "productOptionsFields"]);
        add_action("woocommerce_process_product_meta", [$this, "productOptionsFieldSave"]);

        add_action('woocommerce_product_after_variable_attributes', [$this, 'renderVariationCountryOfOriginField'], 10, 3);
        add_action('woocommerce_save_product_variation', [$this, 'saveVariationCountryOfOriginField'], 10, 2);
        add_filter('woocommerce_available_variation', [$this, 'loadVariationCountryOfOriginField'], 10, 1);
    }

    /**
     * @throws \Exception
     */
    public function addDeliveryDayFilterToOrdergrid(): void
    {
        global $typenow;

        if (in_array($typenow, wc_get_order_types('order-meta-boxes'))
            && (apply_filters('deliveryDayFilter', true))) {
            $this->deliveryDayFilter();
        }
    }

    /**
     * @throws \Exception
     */
    public function deliveryDayFilter(): void
    {
        if (is_admin() && ! empty($_GET['post_type']) == 'shop_order') {
            $selected = (isset($_GET['deliveryDate'])
                ? sanitize_text_field($_GET['deliveryDate'])
                : false);
            ?>

            <select name="deliveryDate">
                <option value=""><?php _e('all_delivery_days', 'woocommerce-myparcel'); ?></option>
                <?php
                $carrierName       = WCMYPA_Settings::SETTINGS_POSTNL;
                $deliveryDayWindow = (int) WCMYPA()->setting_collection->getByName(
                    $carrierName . "_" . WCMYPA_Settings::SETTING_CARRIER_DELIVERY_DAYS_WINDOW
                );

                foreach (range(1, $deliveryDayWindow) as $number) {
                    $date       = date('Y-m-d', strtotime($number . 'days'));
                    $dateString = wc_format_datetime(new WC_DateTime($date), 'D d-m');

                    if (1 === $number) {
                        $dateString = __('tomorrow', 'woocommerce-myparcel') . ' ' . $dateString;
                    }

                    printf(
                        '<option value="%s""%s">%s</option>',
                        $date,
                        selected($date, $selected),
                        $dateString
                    );
                }
                ?>
            </select>
            <?php
        }
    }

    /**
     * @param array $deliveryDate
     *
     * @return array
     */
    public function getDeliveryDateFromOrder(array $deliveryDate): array
    {
        global $typenow;

        $hasDeliveryDate = isset($_GET['deliveryDate']) && ! empty($_GET['deliveryDate']);

        if (in_array($typenow, wc_get_order_types('order-meta-boxes')) && $hasDeliveryDate) {
            $deliveryDate['meta_query'] = [
                [
                    'key'     => '_myparcel_delivery_date',
                    'value'   => sanitize_text_field($_GET['deliveryDate']),
                    'compare' => '=',
                ],
            ];
        }

        return $deliveryDate;
    }

    /**
     * @param \MyParcelNL\Sdk\src\Adapter\DeliveryOptions\AbstractDeliveryOptionsAdapter $deliveryOptions
     */
    public static function renderPickupLocation(DeliveryOptions $deliveryOptions): void
    {
        $pickup = $deliveryOptions->getPickupLocation();

        if (! $pickup || ! $deliveryOptions->isPickup()) {
            return;
        }

        printf(
            "<div class=\"pickup-location\"><strong>%s:</strong><br /> %s<br />%s %s<br />%s %s</div>",
            __("Pickup location", "woocommerce-myparcel"),
            $pickup->getLocationName(),
            $pickup->getStreet(),
            $pickup->getNumber(),
            $pickup->getPostalCode(),
            $pickup->getCity()
        );

        echo "<hr>";
    }



    /**
     * @param int    $loop
     * @param array  $variationData
     * @param object $variation
     */
    public function renderVariationCountryOfOriginField(int $loop, array $variationData, object $variation): void
    {
        woocommerce_wp_select(
            [
                'id'            => self::META_COUNTRY_OF_ORIGIN_VARIATION . "[{$loop}]",
                'name'          => self::META_COUNTRY_OF_ORIGIN_VARIATION . "[{$loop}]",
                'type'          => 'select',
                'options'       => (new WC_Countries())->get_countries(),
                'value'         => get_post_meta($variation->ID, self::META_COUNTRY_OF_ORIGIN_VARIATION, true),
                'label'         => __('product_variable_country_of_origin', 'woocommerce-myparcel'),
                'desc_tip'      => true,
                'description'   => __(
                    'product_variable_country_of_origin_description',
                    'woocommerce-myparcel'
                ),
                'wrapper_class' => 'form-row form-row-full',
            ]
        );
    }

    /**
     * @param int $variationId
     * @param int $loop
     */
    public function saveVariationCountryOfOriginField(int $variationId, int $loop): void
    {
        $countryOfOriginValue = $_POST[self::META_COUNTRY_OF_ORIGIN_VARIATION][$loop];

        if (! empty($countryOfOriginValue)) {
            update_post_meta($variationId, self::META_COUNTRY_OF_ORIGIN_VARIATION, esc_attr($countryOfOriginValue));
        }
    }

    /**
     * @param array $variation
     *
     * @return array
     */
    public function loadVariationCountryOfOriginField(array $variation): array
    {
        $variation[self::META_COUNTRY_OF_ORIGIN_VARIATION] = get_post_meta($variation['variation_id'], self::META_COUNTRY_OF_ORIGIN_VARIATION, true);

        return $variation;
    }

    /**
     * @param $loop
     * @param $variationData
     * @param $variation
     */
    public function variation_hs_code_field($loop, $variationData, $variation)
    {
        woocommerce_wp_text_input(
            [
                'id'            => self::META_HS_CODE_VARIATION . "[{$loop}]",
                'name'          => self::META_HS_CODE_VARIATION . "[{$loop}]",
                'value'         => get_post_meta($variation->ID, self::META_HS_CODE_VARIATION, true),
                'label'         => __('hs_code', 'woocommerce-myparcel'),
                'desc_tip'      => true,
                'description'   => __('hs_code_variations', 'woocommerce-myparcel'),
                'wrapper_class' => 'form-row form-row-full',
            ]
        );
    }

    /**
     * @param $variationId
     * @param $loop
     */
    public function save_variation_hs_code_field($variationId, $loop)
    {
        $hsCodeValue = $_POST[self::META_HS_CODE_VARIATION][$loop];

        if (! empty($hsCodeValue)) {
            update_post_meta($variationId, self::META_HS_CODE_VARIATION, esc_attr($hsCodeValue));
        }
    }

    /**
     * @param $variation
     *
     * @return mixed
     */
    public function load_variation_hs_code_field($variation)
    {
        $variation[self::META_HS_CODE_VARIATION] = get_post_meta($variation['variation_id'], self::META_HS_CODE_VARIATION, true);

        return $variation;
    }

    /**
     * Add delivered post type to order statuses list
     */
    public function registerDeliveredPostStatus(): void
    {
        register_post_status('wc-custom-delivered',
            [
                'label'                     => 'Delivered',
                'public'                    => true,
                'exclude_from_search'       => false,
                'show_in_admin_all_list'    => true,
                'show_in_admin_status_list' => true,
                'label_count'               => _n_noop('Delivered (%s)', 'Delivered (%s)'),
            ]
        );
    }

    /**
     * @param array $order_statuses
     *
     * @return array
     */
    public function displayDeliveredPostStatus(array $order_statuses): array
    {
        $new_order_statuses = [];

        foreach ($order_statuses as $key => $status) {
            $new_order_statuses[$key] = $status;

            if ('wc-processing' === $key) {
                $new_order_statuses['wc-custom-delivered'] = 'Delivered';
            }
        }

        return $new_order_statuses;
    }

    /**
     * @param             $orderId
     * @param string|null $oldStatus
     * @param string|null $newStatus will be passed when order status change triggers this method
     *
     * @throws \ErrorException
     * @throws \MyParcelNL\Sdk\src\Exception\ApiException
     * @throws \MyParcelNL\Sdk\src\Exception\MissingFieldException
     */
    public function automaticExportOrder($orderId, ?string $oldStatus = null, ?string $newStatus = null): void
    {
        if (! WCMYPA()->setting_collection->isEnabled(WCMYPA_Settings::SETTING_AUTOMATIC_EXPORT)) {
            return;
        }

        $newStatus             = $newStatus ?? WCMP_Settings_Data::NOT_ACTIVE;
        $automaticExportStatus = WCMYPA()->setting_collection->getByName(
            WCMYPA_Settings::SETTING_AUTOMATIC_EXPORT_STATUS
        );

        if ($automaticExportStatus === $newStatus) {
            (new WCMP_Export())->exportByOrderId($orderId);
        }
    }

    /**
     * @param WC_Order $order
     *
     * @throws Exception
     */
    public function showMyParcelSettings(WC_Order $order): void
    {
        $orderSettings        = new OrderSettings($order);
        $isAllowedDestination = WCMP_Country_Codes::isAllowedDestination($orderSettings->getShippingCountry());

        if (! $isAllowedDestination) {
            return;
        }

        echo '<div class="wcmp__shipment-settings-wrapper" style="display: none;">';
        $this->printDeliveryDate($orderSettings->getDeliveryOptions());

        $consignments  = self::get_order_shipments($order);
        // if we have shipments, then we show status & link to Track & Trace, settings under i
        if (! empty($consignments)) :
            // only use last shipment
            $lastShipment   = array_pop($consignments);
            $lastShipmentId = $lastShipment['shipment_id'];

            ?>
            <a class="wcmp__shipment-summary__show">
                <span class="wcmp__encircle wcmp__shipment-summary__show">i</span>
            </a>
            <div
                class="wcmp__box wcmp__shipment-summary__list"
                data-loaded=""
                data-shipment_id="<?php echo $lastShipmentId; ?>"
                data-order_id="<?php echo $order->get_id(); ?>"
                style="display: none;">
                <?php self::renderSpinner(); ?>
            </div>
        <?php endif;

        printf(
            '<a href="#" class="wcmp__shipment-options__show" data-order-id="%d">%s &#x25BE;</a>',
            $order->get_id(),
            WCMP_Data::getPackageTypeHuman(
                (new WCMP_Export())->getAllowedPackageType($order, $orderSettings->getPackageType())
            )
        );

        echo "</div>";
    }

    /**
     * Get shipment status + Track & Trace link via AJAX
     *
     * @throws Exception
     */
    public function order_list_ajax_get_shipment_summary(): void
    {
        check_ajax_referer(WCMYPA::NONCE_ACTION, 'security');

        include('views/html-order-shipment-summary.php');
        die();
    }

    /**
     * @return array
     */
    public function getMyParcelBulkActions(): array
    {
        $exportMode  = WCMYPA()->setting_collection->getByName(WCMYPA_Settings::SETTING_EXPORT_MODE);
        $returnValue = [
            self::BULK_ACTION_EXPORT => __('myparcel_bulk_action_export', 'woocommerce-myparcel'),
        ];

        if (WCMP_Settings_Data::EXPORT_MODE_SHIPMENTS === $exportMode) {
            $returnValue[self::BULK_ACTION_PRINT]        = __('myparcel_bulk_action_print', 'woocommerce-myparcel');
            $returnValue[self::BULK_ACTION_EXPORT_PRINT] = __('myparcel_bulk_action_export_print', 'woocommerce-myparcel');
        }

        return $returnValue;
    }

    /**
     * Add export option to bulk action drop down menu.
     *
     * @param array $actions
     *
     * @return array
     * @since WordPress 4.7.0
     */
    public function addBulkActions(array $actions): array
    {
        $actions = array_merge(
            $actions,
            $this->getMyParcelBulkActions()
        );

        self::renderSpinner('bulkAction');

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

        $bulk_actions = $this->getMyParcelBulkActions();

        if ('shop_order' === $post_type) {
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
        if (! WCMYPA()->setting_collection->isEnabled(WCMYPA_Settings::SETTING_ASK_FOR_PRINT_POSITION)) {
            return;
        }

        $field = [
            "name"              => "offset",
            "class"             => ["wcmp__d--inline-block"],
            "input_class"       => ["wcmp__offset-dialog__offset"],
            "type"              => "number",
            "custom_attributes" => [
                "step" => "1",
                "min"  => "0",
                "max"  => "4",
                "size" => "2",
            ],
        ];

        $class = new SettingsFieldArguments($field, false);
        ?>

        <div
            class="wcmp wcmp__box wcmp__offset-dialog wcmp__ws--nowrap"
            style="display: none;">
            <div class="wcmp__offset-dialog__inner wcmp__d--flex">
                <div>
                    <div class="wcmp__pb--2">
                        <?php printf(
                            '<label for="%s">%s</label>',
                            $class->getId(),
                            __("Labels to skip", "woocommerce-myparcel")
                        ); ?>
                    </div>
                    <div class="wcmp__d--flex wcmp__pb--2">
                        <?php woocommerce_form_field($field["name"], $class->getArguments(false), ""); ?>
                        <img
                          src="<?php echo WCMYPA()->plugin_url() . "/assets/img/offset.svg"; ?>"
                          alt="<?php implode(", ", WCMP_Export::DEFAULT_POSITIONS) ?>"
                          class="wcmp__offset-dialog__icon wcmp__pl--1"/>
                    </div>
                    <div>
                        <a
                            href="#"
                            class="wcmp__offset-dialog__button button"
                            style="display: none;"
                            target="_blank">
                            <?php _e("Print", "woocommerce-myparcel"); ?>
                            <?php self::renderSpinner(); ?>
                        </a>
                    </div>
                </div>
                <div class="wcmp__close-button dashicons dashicons-no-alt wcmp__offset-dialog__close wcmp__pl--2"></div>
            </div>
        </div>
        <?php
    }

    /**
     * Hide an empty shipment options form in the footer.
     */
    public function renderShipmentOptionsForm(): void
    {
        echo '<div class="wcmp__box wcmp__shipment-options-dialog" style="display: none; position: absolute;"></div>';
    }

    /**
     * Get the new html content for the shipment options form based on the passed order id.
     */
    public function ajaxGetShipmentOptions(): void
    {
        // Order is used in views/html-order-shipment-options.php
        $order = wc_get_order((int) $_POST['orderId']);

        include('views/html-order-shipment-options.php');

        die();
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

        $shippingCountry = WCX_Order::get_prop($order, 'shipping_country');

        if (! WCMP_Country_Codes::isAllowedDestination($shippingCountry)) {
            return;
        }

        $listingActions = self::getListingActions($order);
        $attributes     = self::getListingAttributes($order);

        foreach ($listingActions as $data) {
            self::renderAction(
                $data['url'],
                $data['alt'],
                $data['img'],
                $attributes
            );
        }
    }

    /**
     * @param \WC_Order $order
     *
     * @return array|array[]
     * @throws \Exception
     */
    public static function getListingActions(WC_Order $order): array
    {
        $orderId         = WCX_Order::get_id($order);
        $shippingCountry = WCX_Order::get_prop($order, 'shipping_country');
        $exportMode      = WCMYPA()->setting_collection->getByName(WCMYPA_Settings::SETTING_EXPORT_MODE);
        $consignments    = self::get_order_shipments($order);
        $listingActions  = self::getDefaultListingActions($orderId);

        if (WCMP_Settings_Data::EXPORT_MODE_PPS === $exportMode) {
            $metaPps        = get_post_meta($orderId, self::META_PPS);
            $listingActions = self::updateExportButtonForPps($listingActions, $metaPps);
        }

        if (empty($consignments) || WCMP_Settings_Data::EXPORT_MODE_PPS === $exportMode) {
            unset($listingActions[WCMP_Export::GET_LABELS]);
        }

        if (empty($consignments) || WCMP_Data::DEFAULT_COUNTRY_CODE !== $shippingCountry) {
            unset($listingActions[WCMP_Export::EXPORT_RETURN]);
        }

        return $listingActions;
    }

    /**
     * @param \WC_Order $order
     *
     * @return array
     */
    public static function getListingAttributes(WC_Order $order): array
    {
        $downloadDisplay = WCMYPA()->setting_collection->getByName(WCMYPA_Settings::SETTING_DOWNLOAD_DISPLAY);
        $orderId         = WCX_Order::get_id($order);
        $metaPps         = get_post_meta($orderId, self::META_PPS);
        $attributes      = [];

        if ('display' === $downloadDisplay) {
            $attributes['target'] = '_blank';
        }

        if (is_array($metaPps) && count($metaPps)) {
            $attributes['data-pps'] = htmlentities(var_export($metaPps, true));
        }

        return $attributes;
    }

    /**
     * @param int $orderId
     *
     * @return array[]
     */
    public static function getDefaultListingActions(int $orderId): array
    {
        $addShipments = WCMP_Export::EXPORT_ORDER;
        $getLabels    = WCMP_Export::GET_LABELS;
        $addReturn    = WCMP_Export::EXPORT_RETURN;
        $pluginUrl    = WCMYPA()->plugin_url();
        $baseUrl      = 'admin-ajax.php?action=' . WCMP_Export::EXPORT;

        return [
            $addShipments => [
                'url' => admin_url("$baseUrl&request=$addShipments&order_ids=$orderId"),
                'img' => "{$pluginUrl}/assets/img/export.svg",
                'alt' => __('action_export_to_myparcel', 'woocommerce-myparcel'),
            ],
            $getLabels    => [
                'url' => admin_url("$baseUrl&request=$getLabels&order_ids=$orderId"),
                'img' => "{$pluginUrl}/assets/img/print.svg",
                'alt' => __('action_print_myparcel_label', 'woocommerce-myparcel'),
            ],
            $addReturn    => [
                'url' => admin_url("$baseUrl&request=$addReturn&order_ids=$orderId"),
                'img' => "{$pluginUrl}/assets/img/return.svg",
                'alt' => __('action_email_return_label', 'woocommerce-myparcel'),
            ],
        ];
    }

    /**
     * @param array $listingActions
     * @param array $metaPps
     *
     * @return array
     */
    public static function updateExportButtonForPps(array $listingActions, array $metaPps): array
    {
        if (! $metaPps) {
            return $listingActions;
        }

        $pluginUrl                                        = WCMYPA()->plugin_url();
        $listingActions[WCMP_Export::EXPORT_ORDER]['img'] = "{$pluginUrl}/assets/img/myparcel.svg";

        foreach ($metaPps as $metaPpsFeedback) {

            if (is_array($metaPpsFeedback) && $metaPpsFeedback[self::META_PPS_EXPORTED]) {

                $listingActions[WCMP_Export::EXPORT_ORDER]['alt'] = __(
                    'export_hint_already_exported',
                    'woocommerce-myparcel'
                );

                break;
            }
        }

        return $listingActions;
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
            $consignments = array_filter($consignments,
                function($consignment) {
                    return isset($consignment["track_trace"]);
                }
            );
        }

        return $consignments;
    }

    /**
     * On saving shipment options from the bulk options form.
     *
     * @throws Exception
     * @see admin/views/html-order-shipment-options.php
     */
    public function save_shipment_options_ajax(): void
    {
        parse_str($_POST["form_data"], $form_data);

        foreach ($form_data[self::SHIPMENT_OPTIONS_FORM_NAME] as $order_id => $data) {
            $order         = WCX::get_order($order_id);
            $data          = self::removeDisallowedDeliveryOptions($data, $order->get_shipping_country());
            $orderSettings = new OrderSettings($order, $data);

            WCX_Order::update_meta_data(
                $order,
                self::META_DELIVERY_OPTIONS,
                $orderSettings->getDeliveryOptions()->toArray()
            );

            // Save extra options
            WCX_Order::update_meta_data(
                $order,
                self::META_SHIPMENT_OPTIONS_EXTRA,
                array_merge(
                    $orderSettings->getExtraOptions(),
                    $data["extra_options"]
                )
            );
        }

        die();
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

        $downloadDisplay = WCMYPA()->setting_collection->getByName(WCMYPA_Settings::SETTING_DOWNLOAD_DISPLAY) === 'display';
        $consignments    = self::get_order_shipments($order);

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
    public function showDeliveryDateForOrder(WC_Order $order): void
    {
        $deliveryOptions = self::getDeliveryOptionsFromOrder($order);
        $this->printDeliveryDate($deliveryOptions);
    }

    /**
     * @param \WC_Order $order
     * @param bool      $isEmail
     *
     * @throws \Exception
     */
    public function showShipmentConfirmation(WC_Order $order, bool $isEmail): void
    {
        $deliveryOptions  = self::getDeliveryOptionsFromOrder($order);
        $confirmationData = $this->getConfirmationData($deliveryOptions);
        $isEmail
            ? $this->printEmailConfirmation($confirmationData)
            : $this->printThankYouConfirmation($confirmationData);
    }

    /**
     * Go through all getProductOptions and show them on the screen
     */
    public function productOptionsFields(): void
    {
        echo '<div class="options_group">';
        foreach ($this->getProductOptions() as $productOption) {
            $type = $productOption['type'];
            if ('text' === $type) {
                woocommerce_wp_text_input(
                    [
                        'id'          => $productOption['id'],
                        'label'       => $productOption['label'],
                        'description' => $productOption['description'],
                    ]
                );
            } elseif ('select' === $type) {
                woocommerce_wp_select(
                    [
                        'id'          => $productOption['id'],
                        'label'       => $productOption['label'],
                        'options'     => $productOption['options'],
                        'description' => $productOption['description'],
                    ]
                );
            }
        }
        echo '</div>';
    }

    /**
     * @param int $postId
     */
    public function productOptionsFieldSave(int $postId): void
    {
        foreach ($this->getProductOptions() as $productOption) {
            // check if hs code is passed and not an array (=variation hs code)
            if (isset($_POST[$productOption['id']]) && ! is_array($_POST[$productOption['id']])) {
                $product   = wc_get_product($postId);
                $productId = $_POST[$productOption['id']];

                if (! empty($productId)) {
                    WCX_Product::update_meta_data($product, $productOption['id'], esc_attr($productId));
                } else {
                    if (isset($_POST[$productOption['id']]) && empty($productId)) {
                        WCX_Product::delete_meta_data($product, $productOption['id']);
                    }
                }
            }
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

    /**
     * @return array
     */
    public function getProductOptions(): array
    {
        return [
            'HS-Code'           => [
                'id'          => self::META_HS_CODE,
                'label'       => __('HS Code', 'woocommerce-myparcel'),
                'type'        => 'text',
                'description' => wc_help_tip(
                    sprintf(
                        __(
                            'HS Codes are used for MyParcel world shipments, you can find the appropriate code on the %ssite of the Dutch Customs%s',
                            'woocommerce-myparcel'
                        ),
                        '<a href="https://tarief.douane.nl/arctictariff-public-web/#!/home" target="_blank">',
                        '</a>'
                    )
                ),
            ],
            'Country-of-origin' => [
                'id'          => self::META_COUNTRY_OF_ORIGIN,
                'label'       => __('product_options_country_of_origin', 'woocommerce-myparcel'),
                'type'        => 'select',
                'options'     => array_merge(
                    [
                      null => __('Default', 'woocommerce-myparcel'),
                    ],
                    (new WC_Countries())->get_countries()
                ),
                'description' => wc_help_tip(
                    __('setting_country_of_origin_help_text', 'woocommerce-myparcel')
                ),
            ],
            'Age-check'         => [
                'id'          => self::META_AGE_CHECK,
                'label'       => __('shipment_options_age_check', 'woocommerce-myparcel'),
                'type'        => 'select',
                'options'     => [
                    null                           => __('Default', 'woocommerce-myparcel'),
                    self::PRODUCT_OPTIONS_DISABLED => __('Disabled', 'woocommerce-myparcel'),
                    self::PRODUCT_OPTIONS_ENABLED  => __('Enabled', 'woocommerce-myparcel'),
                ],
                'description' => wc_help_tip(__('shipment_options_age_check_help_text', 'woocommerce-myparcel')),
            ],
        ];
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
        $shipments  = self::get_order_shipments($order, true);
        $exportMode = WCMYPA()->setting_collection->getByName(WCMYPA_Settings::SETTING_EXPORT_MODE);

        if (WCMP_Settings_Data::EXPORT_MODE_PPS === $exportMode) {
            $orderId = WCX_Order::get_id($order);
            $metaPps = get_post_meta($orderId, self::META_PPS);

            if ($metaPps) {
                echo sprintf(__('export_hint_how_many_times', 'woocommerce-myparcel'), count($metaPps));
            } else {
                echo __('export_hint_not_exported', 'woocommerce-myparcel');
            }
        } elseif (empty($shipments)) {
            echo __('export_hint_no_label_created_yet', 'woocommerce-myparcel');

            return;
        }

        echo '<div class="wcmp__barcodes">';

        foreach ($shipments as $shipment_id => $shipment) {
            $shipmentStatusId = $shipment['shipment']['status'];
            $printedStatuses  = [WCMYPA_Admin::ORDER_STATUS_PRINTED_DIGITAL_STAMP, WCMYPA_Admin::ORDER_STATUS_PRINTED_LETTER];

            if (in_array($shipmentStatusId, $printedStatuses)) {
                echo __("The label has been printed.", "woocommerce-myparcel");
                continue;
            }

            if (empty($shipment["track_trace"])) {
                echo __("Concept created but not printed.", "woocommerce-myparcel");
                continue;
            }

            printf(
                '<a target="_blank" class="wcmp__barcode-link" title="%2$s" href="%1$s">%2$s</a><br>',
                self::getTrackTraceUrl($order, $shipment["track_trace"]),
                $shipment["track_trace"]
            );
        }
        echo "</div>";
    }

    /**
     * Get DeliveryOptions object from the given order's meta data. Uses legacy delivery options if found, if that
     * data is invalid it falls back to defaults.
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
        $meta = WCX_Order::get_meta($order, self::META_DELIVERY_OPTIONS) ?: null;

        // $meta is a json string, create an instance
        if (! empty($meta) && ! $meta instanceof DeliveryOptions) {
            if (is_string($meta)) {
                $meta = json_decode(stripslashes($meta), true);
            }

            $meta['carrier'] = $meta['carrier'] ?? WCMP_Data::DEFAULT_CARRIER;
            $meta['date']    = $meta['date'] ?? '';

            try {
                // create new instance from known json
                $meta = DeliveryOptionsAdapterFactory::create((array) $meta);
            } catch (BadMethodCallException $e) {
                // create new instance from unknown json data
                $meta = new WCMP_DeliveryOptionsFromOrderAdapter(null, (array) $meta);
            }
        }

        // Create or update immutable adapter from order with a instanceof DeliveryOptionsAdapter
        if (empty($meta) || ! empty($inputData)) {
            $meta = new WCMP_DeliveryOptionsFromOrderAdapter($meta, $inputData);
        }

        return apply_filters("wc_myparcel_order_delivery_options", $meta, $order);
    }

    /**
     * @param WC_Order $order
     *
     * @return array
     * @throws JsonException
     */
    public static function getExtraOptionsFromOrder(WC_Order $order): array
    {
        $meta = WCX_Order::get_meta($order, self::META_SHIPMENT_OPTIONS_EXTRA) ?: null;

        if (empty($meta)) {
            $meta['collo_amount'] = OrderSettings::DEFAULT_COLLO_AMOUNT;
        }

        return $meta;
    }

    /**
     * Output the delivery date if there is a date and the show delivery day setting is enabled.
     *
     * @param DeliveryOptions $deliveryOptions
     *
     * @throws Exception
     */
    private function printDeliveryDate(DeliveryOptions $deliveryOptions): void
    {
        $showDeliveryDay = WCMYPA()->setting_collection->isEnabled(WCMYPA_Settings::SETTING_SHOW_DELIVERY_DAY);

        if ($showDeliveryDay && $deliveryOptions->getDate()) {
            printf(
                '<div class="delivery-date"><strong>%s</strong><br />%s, %s</div>',
                __("MyParcel shipment:", "woocommerce-myparcel"),
                WCMP_Data::getDeliveryTypesHuman()[$deliveryOptions->getDeliveryType()],
                wc_format_datetime(new WC_DateTime($deliveryOptions->getDate()), 'D d-m')
            );
        }
    }

	/**
	 * Output the chosen delivery options or the chosen pickup options.
	 *
	 * @param DeliveryOptions $deliveryOptions
	 *
	 * @return array[]|null
	 * @throws \Exception
	 */
    private function getConfirmationData(DeliveryOptions $deliveryOptions): ?array
    {
        $deliveryOptionsEnabled = WCMYPA()->setting_collection->isEnabled(
            WCMYPA_Settings::SETTING_DELIVERY_OPTIONS_ENABLED
        );

        if (! $deliveryOptionsEnabled || ! $deliveryOptions->getCarrier()) {
            return null;
        }

        $signatureTitle     = WCMP_Checkout::getDeliveryOptionsTitle(WCMYPA_Settings::SETTING_SIGNATURE_TITLE);
        $onlyRecipientTitle = WCMP_Checkout::getDeliveryOptionsTitle(WCMYPA_Settings::SETTING_ONLY_RECIPIENT_TITLE);
        $hasSignature       = $deliveryOptions->getShipmentOptions()->hasSignature();
        $hasOnlyRecipient   = $deliveryOptions->getShipmentOptions()->hasOnlyRecipient();

        if (AbstractConsignment::DELIVERY_TYPE_PICKUP_NAME === $deliveryOptions->getDeliveryType()) {
            $pickupLocation = $deliveryOptions->getPickupLocation();
            return [
                __("delivery_type", "woocommerce-myparcel")   => WCMP_Data::getDeliveryTypesHuman()[$deliveryOptions->getDeliveryType()],
                __("pickup_location", "woocommerce-myparcel") =>
                    sprintf("%s<br>%s %s<br>%s %s",
                            $pickupLocation->getLocationName(),
                            $pickupLocation->getStreet(),
                            $pickupLocation->getNumber(),
                            $pickupLocation->getPostalCode(),
                            $pickupLocation->getCity()
                    )
            ];
        }

        $confirmationData = [
            __("delivery_type", "woocommerce-myparcel") => WCMP_Data::getDeliveryTypesHuman()[$deliveryOptions->getDeliveryType()],
        ];

        if (WCMYPA()->setting_collection->isEnabled(WCMYPA_Settings::SETTING_SHOW_DELIVERY_DAY)) {
            $confirmationData[__("Date:", 'woocommerce')] = wc_format_datetime(new WC_DateTime($deliveryOptions->getDate()));;
        }

        if ($hasSignature || $hasOnlyRecipient) {
            $confirmationData[__("extra_options", "woocommerce-myparcel")] =
                sprintf("%s<br>%s",
                    $hasSignature ? $signatureTitle : null,
                    $hasOnlyRecipient ? $onlyRecipientTitle : null);
        }

        return $confirmationData;
    }

    /**
     * Print a table with the chosen delivery options on the confirmation page.
     *
     * @param array[]|null $selectedDeliveryOptions
     */
    public function printThankYouConfirmation(?array $selectedDeliveryOptions): void
    {
        printf($this->generateThankYouConfirmation($selectedDeliveryOptions));
    }

    /**
     * Print a table with the chosen delivery options in the confirmation email.
     *
     * @param array[]|null $selectedDeliveryOptions
     */
    public function printEmailConfirmation(?array $selectedDeliveryOptions): void
    {
        printf($this->generateEmailConfirmation($selectedDeliveryOptions));
    }

    /**
     * @param array[]|null $options
     *
     * @return string|null
     */
    public function generateThankYouConfirmation(?array $options): ?string
    {
        if ($options) {
            $htmlHeader = "<h2 class='woocommerce-column__title'> " . __("Delivery information:", "woocommerce-myparcel") . "</h2><table>";

            foreach ($options as $key => $option) {
                if ($option) {
                    $htmlHeader .= "<tr><td>$key</td><td>" . __($option, "woocommerce-myparcel") . "</td></tr>";
                }
            }

            return $htmlHeader . "</table>";
        }

        return null;
    }

    /**
     * @param array[]|null $options
     *
     * @return string|null
     */
    public function generateEmailConfirmation(?array $options): ?string
    {
        if ($options) {
            $htmlHeader = "<h2 class='woocommerce-column__title'> " . __("Delivery information:", "woocommerce-myparcel") . "</h2>";
            $htmlHeader .= "<table cellspacing='0' style='border: 1px solid #e5e5e5; margin-bottom: 20px;>";

            foreach ($options as $key => $option) {
                if ($option) {
                    $htmlHeader .= "<tr style='border: 1px solid #d5d5d5;'>
                              <td style='border: 1px solid #e5e5e5;'>$key</td>
                              <td style='border: 1px solid #e5e5e5;'>" . __($option, "woocommerce-myparcel") . "</td>
                            </tr>";
                }
            }

            return $htmlHeader . "</table>";
        }

        return null;
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
    public static function renderAction(string $url, string $alt, string $icon, array $rawAttributes = []): void
    {
        printf(
            '<a href="%1$s" 
                class="button tips wcmp__action" 
                data-tip="%2$s" 
                %4$s>
                <img class="wcmp__action__img wcmp__m--auto" src="%3$s" alt="%2$s" />',
            wp_nonce_url($url, WCMYPA::NONCE_ACTION),
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
            $track_trace_url  = self::getTrackTraceUrl($order_id, $track_trace);
            $track_trace_link = sprintf(
                '<a href="%s" target="_blank">%s</a>',
                $track_trace_url,
                $track_trace
            );
        } elseif (isset($shipment["shipment"]) && isset($shipment["shipment"]["options"])) {
            $package_type     = WCMP_Export::getPackageTypeHuman($shipment["shipment"]["options"]["package_type"]);
            $track_trace_link = "($package_type)";
        } else {
            $track_trace_link = __("(Unknown)", "woocommerce-myparcel");
        }

        echo $track_trace_link;
    }

    /**
     * @param array $shipment
     * @param int   $order_id
     */
    public static function renderStatus(array $shipment, int $order_id): void
    {
        echo $shipment["status"] ?? "–";

        if (self::shipmentIsStatus($shipment, self::ORDER_STATUS_DELIVERED_AT_RECIPIENT)
            || self::shipmentIsStatus($shipment, self::ORDER_STATUS_DELIVERED_READY_FOR_PICKUP)
            || self::shipmentIsStatus($shipment, self::ORDER_STATUS_DELIVERED_PACKAGE_PICKED_UP)
        ) {
            $order = WCX::get_order($order_id);
//            This will be addressed in MY-24881
//            $order->update_status('wc-custom-delivered');
        }
    }

    /**
     * @param array $shipment
     * @param int   $status
     *
     * @return bool
     */
    public static function shipmentIsStatus(array $shipment, int $status): bool
    {
        return strstr($shipment['status'], (new WCMP_Export())->getShipmentStatusName($status));
    }

    /**
     * Remove options that aren't allowed and return the edited array.
     *
     * @param array  $data
     * @param string $country
     *
     * @return mixed
     */
    public static function removeDisallowedDeliveryOptions(array $data, string $country): array
    {
        $data['package_type'] = $data['package_type'] ?? AbstractConsignment::DEFAULT_PACKAGE_TYPE_NAME;
        $isHomeCountry        = WCMP_Data::isHomeCountry($country);
        $isEuCountry          = WCMP_Country_Codes::isEuCountry($country);

        $isPackage      = AbstractConsignment::PACKAGE_TYPE_PACKAGE_NAME === $data['package_type'];
        $isDigitalStamp = AbstractConsignment::PACKAGE_TYPE_DIGITAL_STAMP_NAME === $data['package_type'];

        if (! $isHomeCountry || ! $isPackage) {
            $data['shipment_options']['age_check']       = false;
            $data['shipment_options']['return_shipment'] = false;
            $data['shipment_options']['insured']         = false;
            $data['shipment_options']['insured_amount']  = 0;
        }

        if (! $isPackage || (! $isHomeCountry && ! $isEuCountry)) {
            $data['shipment_options']['large_format'] = false;
        }

        if (! $isDigitalStamp) {
            unset($data['extra_options']['weight']);
        }

        return $data;
    }
}

return new WCMYPA_Admin();
