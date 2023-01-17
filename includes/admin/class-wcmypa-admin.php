<?php

use MyParcelNL\Pdk\Base\PdkActions;
use MyParcelNL\Pdk\Base\Service\CountryService;
use MyParcelNL\Pdk\Facade\Pdk;
use MyParcelNL\Pdk\Plugin\Model\PdkOrder;
use MyParcelNL\Pdk\Shipment\Model\DeliveryOptions;
use MyParcelNL\Sdk\src\Adapter\DeliveryOptions\AbstractDeliveryOptionsAdapter as DeliveryOptionsAdapter;
use MyParcelNL\Sdk\src\Adapter\DeliveryOptions\AbstractShipmentOptionsAdapter;
use MyParcelNL\Sdk\src\Factory\DeliveryOptionsAdapterFactory;
use MyParcelNL\Sdk\src\Model\Carrier\CarrierPostNL;
use MyParcelNL\WooCommerce\includes\Settings\Api\AccountSettings;
use MyParcelNL\WooCommerce\includes\Validators\WebhookCallbackUrlValidator;
use MyParcelNL\WooCommerce\Pdk\Plugin\Repository\PdkOrderRepository;
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
 *
 * @deprecated@deprecated
 */
class WCMYPA_Admin
{
    public const META_CONSIGNMENTS                = '_myparcel_consignments';
    public const META_CONSIGNMENT_ID              = '_myparcel_consignment_id';
    public const META_DELIVERY_OPTIONS            = '_myparcel_delivery_options';
    public const META_HIGHEST_SHIPPING_CLASS      = '_myparcel_highest_shipping_class';
    public const META_LAST_SHIPMENT_IDS           = '_myparcel_last_shipment_ids';
    public const META_RETURN_SHIPMENT_IDS         = '_myparcel_return_shipment_ids';
    public const META_ORDER_VERSION               = '_myparcel_order_version';
    public const META_DELIVERY_DATE               = '_myparcel_delivery_date';
    public const META_SHIPMENTS                   = '_myparcel_shipments';
    public const META_SHIPMENT_OPTIONS_EXTRA      = '_myparcel_shipment_options_extra';
    public const META_TRACK_TRACE                 = '_myparcel_tracktrace';
    public const META_HS_CODE                     = '_myparcel_hs_code';
    public const META_HS_CODE_VARIATION           = '_myparcel_hs_code_variation';
    public const META_COUNTRY_OF_ORIGIN_VARIATION = '_myparcel_country_of_origin_variation';
    public const META_COUNTRY_OF_ORIGIN           = '_myparcel_country_of_origin';
    public const META_AGE_CHECK                   = '_myparcel_age_check';
    public const META_PPS                         = '_myparcel_pps';
    public const META_PPS_EXPORTED                = 'pps_exported';
    public const META_PPS_EXPORT_DATE             = 'pps_export_date';
    public const META_PPS_UUID                    = 'pps_uuid';
    public const OLD_RED_JE_PAKKETJE_NAME         = 'redjepakketje';
    /**
     * @deprecated use weight property in META_SHIPMENT_OPTIONS_EXTRA.
     *             @deprecated
     */
    public const META_ORDER_WEIGHT = '_myparcel_order_weight';
    // Ids referring to shipment statuses.
    public const ORDER_STATUS_DELIVERED_AT_RECIPIENT      = 7;
    public const ORDER_STATUS_DELIVERED_READY_FOR_PICKUP  = 8;
    public const ORDER_STATUS_DELIVERED_PACKAGE_PICKED_UP = 9;
    public const ORDER_STATUS_PRINTED_LETTER              = 12;
    public const ORDER_STATUS_PRINTED_DIGITAL_STAMP       = 14;
    public const SHIPMENT_OPTIONS_FORM_NAME               = 'myparcel_options';
    public const PRODUCT_OPTIONS_ENABLED                  = 'yes';
    public const PRODUCT_OPTIONS_DISABLED                 = 'no';

    public function __construct()
    {
        if (is_wp_version_compatible('4.7.0')) {
            add_action('bulk_actions-edit-shop_order', [$this, 'addBulkActions'], 100);
        } else {
            add_action('admin_footer', [$this, 'bulk_actions']);
        }

        add_action('admin_footer', [$this, 'renderOffsetDialog']);
        add_action('admin_footer', [$this, 'renderShipmentOptionsForm']);

        /**
         * Orders page
         * --
         * showMyParcelSettings is on the woocommerce_admin_order_actions_end hook because there is no hook to put it
         * in the shipping address column... It is put in the right place after loading using JavaScript.
         *
         * @see wcmp-admin.js -> runTriggers()
         *                    @deprecated
         */
//        add_action('woocommerce_admin_order_actions_end', [$this, 'showMyParcelSettings'], 9999);
//        add_action('woocommerce_admin_order_actions_end', [$this, 'showOrderActions'], 20);

        /*
         * Single order page
         * @deprecated
         */
        add_action('add_meta_boxes_shop_order', [$this, 'add_order_meta_box']);
        add_action('woocommerce_admin_order_data_after_shipping_address', [$this, 'single_order_shipment_options']);

        add_action('wp_ajax_wcmp_save_shipment_options', [$this, 'save_shipment_options_ajax']);
        add_action('wp_ajax_wcmp_get_shipment_summary_status', [$this, 'order_list_ajax_get_shipment_summary']);
        add_action('wp_ajax_wcmp_get_shipment_options', [$this, 'ajaxGetShipmentOptions']);

        // Add barcode in order grid
        add_filter('manage_edit-shop_order_columns', [$this, 'barcode_add_new_order_admin_list_column'], 10, 1);
        add_action('manage_shop_order_posts_custom_column', [$this, 'addBarcodeToOrderColumn'], 10, 2);
        add_action('restrict_manage_posts', [$this, 'addDeliveryDayFilterToOrdergrid'], 10, 1);
        add_filter('request', [$this, 'getDeliveryDateFromOrder'], 10, 1);
        add_action('woocommerce_payment_complete', [$this, 'automaticExportOrder'], 1000);
        add_action('woocommerce_order_status_changed', [$this, 'automaticExportOrder'], 1000, 3);
        add_action('woocommerce_product_after_variable_attributes', [$this, 'variation_hs_code_field'], 10, 3);
        add_action('woocommerce_save_product_variation', [$this, 'save_variation_hs_code_field'], 10, 2);
        add_filter('woocommerce_available_variation', [$this, 'load_variation_hs_code_field'], 10, 1);
        add_action('woocommerce_product_options_shipping', [$this, 'productOptionsFields']);
        add_action('woocommerce_process_product_meta', [$this, 'productOptionsFieldSave']);
        add_action(
            'woocommerce_product_after_variable_attributes',
            [$this, 'renderVariationCountryOfOriginField'],
            10,
            3
        );
        add_action('woocommerce_save_product_variation', [$this, 'saveVariationCountryOfOriginField'], 10, 2);
        add_filter('woocommerce_available_variation', [$this, 'loadVariationCountryOfOriginField'], 10, 1);
    }

    /**
     * @throws \Exception
     *                   @deprecated
     */
    public function addDeliveryDayFilterToOrdergrid(): void
    {
        global $typenow;

        if ((apply_filters('deliveryDayFilter', true))
            && in_array(
                $typenow,
                wc_get_order_types('order-meta-boxes'),
                true
            )) {
            $this->deliveryDayFilter();
        }
    }

    /**
     * @throws \Exception
     *                   @deprecated
     */
    public function deliveryDayFilter(): void
    {
        if (! empty($_GET['post_type']) == 'shop_order' && is_admin() && $this->anyActiveCarrierHasShowDeliveryDate()) {
            $selected = (isset($_GET['deliveryDate'])
                ? sanitize_text_field($_GET['deliveryDate'])
                : false);
            ?>

          <select name="deliveryDate">
            <option value=""><?php
                _e('all_delivery_days', 'woocommerce-myparcel'); ?></option>
              <?php
              $carrierName       = 'postnl';
              $deliveryDayWindow = (int) WCMYPA()->settingCollection->where('carrier', $carrierName)
                  ->getByName(
                      'delivery_days_window'

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
     * @param  array $deliveryDate
     *
     * @return array
     *              @deprecated
     */
    public function getDeliveryDateFromOrder(array $deliveryDate): array
    {
        global $typenow;

        $hasDeliveryDate = isset($_GET['deliveryDate']) && ! empty($_GET['deliveryDate']);

        if ($hasDeliveryDate && in_array($typenow, wc_get_order_types('order-meta-boxes'), true)) {
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
     * @param  \MyParcelNL\Sdk\src\Adapter\DeliveryOptions\AbstractDeliveryOptionsAdapter $deliveryOptions
     *                                                                                                    @deprecated
     */
    public static function renderPickupLocation(DeliveryOptionsAdapter $deliveryOptions): void
    {
        $pickup = $deliveryOptions->getPickupLocation();

        if (! $pickup || ! $deliveryOptions->isPickup()) {
            return;
        }

        printf(
            "<div class=\"pickup-location\"><strong>%s:</strong><br /> %s<br />%s %s<br />%s %s</div>",
            __('Pickup location', 'woocommerce-myparcel'),
            $pickup->getLocationName(),
            $pickup->getStreet(),
            $pickup->getNumber(),
            $pickup->getPostalCode(),
            $pickup->getCity()
        );

        echo '<hr>';
    }

    /**
     * @param  int    $loop
     * @param  array  $variationData
     * @param  object $variation
     *                          @deprecated
     */
    public function renderVariationCountryOfOriginField(int $loop, array $variationData, object $variation): void
    {
        woocommerce_wp_select(
            [
                'id'            => self::META_COUNTRY_OF_ORIGIN_VARIATION . "[{$loop}]",
                'name'          => self::META_COUNTRY_OF_ORIGIN_VARIATION . "[{$loop}]",
                'type'          => 'select',
                'options'       => array_merge(
                    [
                        null => __('Default', 'woocommerce-myparcel'),
                    ],
                    (new WC_Countries())->get_countries()
                ),
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
     * @param  int $variationId
     * @param  int $loop
     *                  @deprecated
     */
    public function saveVariationCountryOfOriginField(int $variationId, int $loop): void
    {
        $countryOfOriginValue = $_POST[self::META_COUNTRY_OF_ORIGIN_VARIATION][$loop];

        if (! empty($countryOfOriginValue)) {
            update_post_meta($variationId, self::META_COUNTRY_OF_ORIGIN_VARIATION, esc_attr($countryOfOriginValue));
        }
    }

    /**
     * @param  array $variation
     *
     * @return array
     *              @deprecated
     */
    public function loadVariationCountryOfOriginField(array $variation): array
    {
        $variation[self::META_COUNTRY_OF_ORIGIN_VARIATION] = get_post_meta(
            $variation['variation_id'],
            self::META_COUNTRY_OF_ORIGIN_VARIATION,
            true
        );

        return $variation;
    }

    /**
     * @param $loop
     * @param $variationData
     * @param $variation
     *                  @deprecated
     */
    public function variation_hs_code_field($loop, $variationData, $variation): void
    {
        woocommerce_wp_text_input(
            [
                'id'            => sprintf('%s[%s]', self::META_HS_CODE_VARIATION, $loop),
                'name'          => sprintf('%s[%s]', self::META_HS_CODE_VARIATION, $loop),
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
     *             @deprecated
     */
    public function save_variation_hs_code_field($variationId, $loop): void
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
     *              @deprecated
     */
    public function load_variation_hs_code_field($variation)
    {
        $variation[self::META_HS_CODE_VARIATION] = get_post_meta(
            $variation['variation_id'],
            self::META_HS_CODE_VARIATION,
            true
        );

        return $variation;
    }

    /**
     * @param              $orderId
     * @param  string|null $oldStatus
     * @param  string|null $newStatus will be passed when order status change triggers this method
     *                                @deprecated
     */
    public function automaticExportOrder($orderId, ?string $oldStatus = null, ?string $newStatus = null): void
    {
        if (! WCMYPA()->settingCollection->isEnabled('export_automatic')) {
            return;
        }

        $order         = WCX::get_order($orderId);
        $orderRepository = Pdk::get(PdkOrderRepository::class);

        if ($orderRepository->hasLocalPickup()) {
            return;
        }

        $newStatus             = $newStatus ?? WCMP_Settings_Data::NOT_ACTIVE;
        $automaticExportStatus = WCMYPA()->settingCollection->getByName(
            'export_automatic_status'
        );

        if ($automaticExportStatus === $newStatus) {
            try {
                $_GET['orderIds'] = $orderId;
                (new ExportActions())->callAction(PdkActions::EXPORT_ORDER, (array) $orderId);
            } catch (Exception $e) {
            }
        }
    }

    /**
     * @param  WC_Order $order
     *
     * @throws Exception
     *                  @deprecated
     */
    public function showMyParcelSettings(WC_Order $order): void
    {
        try {
            $orderRepository = (Pdk::get(PdkOrderRepository::class));
            $pdkOrder        = $orderRepository->get($order);
        } catch (Exception $exception) {
            WCMP_Log::add(sprintf('Could not get OrderSettings for order %d', $order->get_id()), $exception);
            printf(
                '<div class="wcmp__shipment-settings-wrapper">⚠ %s</div>',
                __('warning_faulty_order_settings', 'woocommerce-myparcel')
            );

            return;
        }

        $isAllowedDestination = CountryCodes::isAllowedDestination($pdkOrder->recipient->cc ?? 'NL');

        if (! $isAllowedDestination || $orderRepository->hasLocalPickup()) {
            return;
        }

        echo '<div class="wcmp__shipment-settings-wrapper" style="display: none;">';
        $this->printDeliveryDate($orderRepository->getDeliveryOptions());

        $consignments       = self::get_order_shipments($order);
        // if we have shipments, then we show status & link to Track & Trace, settings under i
        if (! empty($consignments)) :
            // only use last shipment
            $lastShipment = array_pop($consignments);
            $lastShipmentId = $lastShipment['shipment_id'] ?? null;

            ?>
          <a class="wcmp__shipment-summary__show">
            <span class="wcmp__encircle wcmp__shipment-summary__show">i</span>
          </a>
          <div
            class="wcmp__box wcmp__modal wcmp__shipment-summary__list"
            data-loaded=""
            data-shipment_id="<?php
            echo $lastShipmentId; ?>"
            data-order_id="<?php
            echo $order->get_id(); ?>"
            style="display: none;">
              <?php
              self::renderSpinner(); ?>
          </div>
        <?php
        endif;

        printf(
            '<a href="#" class="wcmp__shipment-options__show" data-order-id="%d"><span class="wcmp__shipment-options__package-type">%s</span> &#x25BE;</a>',
            $order->get_id(),
            Data::getPackageTypeHuman(
                (new ExportActions())->getAllowedPackageType($order, $pdkOrder->deliveryOptions->packageType)
            )
        );

        echo '</div>';
    }

    /**
     * Get shipment status + Track & Trace link via AJAX
     *
     * @throws Exception
     *                  @deprecated
     */
    public function order_list_ajax_get_shipment_summary(): void
    {
        check_ajax_referer(WCMYPA::NONCE_ACTION, 'security');

        include('views/html-order-shipment-summary.php');
        die();
    }

    /**
     * @return array
     *              @deprecated
     */
    public function getMyParcelBulkActions(): array
    {
        $exportMode  = WCMYPA()->settingCollection->getByName('export_mode');
        $returnValue = [
            PdkActions::EXPORT_ORDER => __('myparcel_bulk_action_export', 'woocommerce-myparcel'),
        ];

        if (WCMP_Settings_Data::EXPORT_MODE_SHIPMENTS === $exportMode) {
            $returnValue[PdkActions::PRINT_ORDER]            = __('myparcel_bulk_action_print', 'woocommerce-myparcel');
            $returnValue[PdkActions::EXPORT_AND_PRINT_ORDER] = __(
                'myparcel_bulk_action_export_print',
                'woocommerce-myparcel'
            );
        }

        return $returnValue;
    }

    /**
     * Add export option to bulk action drop down menu.
     *
     * @param  array $actions
     *
     * @return array
     * @since WordPress 4.7.0
     *        @deprecated
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
     * Used pre WordPress 4.7.0
     *
     * @access public
     * @return void
     *             @deprecated
     */
    public function bulk_actions()
    {
        global $post_type;

        $bulk_actions = $this->getMyParcelBulkActions();

        if ('shop_order' === $post_type) {
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
     * @throws \Exception
     *                   @deprecated
     */
    public function renderOffsetDialog(): void
    {
        if (! WCMYPA()->settingCollection->isEnabled('ask_for_print_position')) {
            return;
        }

        $field = [
            'name'              => 'offset',
            'class'             => ['wcmp__d--inline-block'],
            'input_class'       => ['wcmp__offset-dialog__offset'],
            'type'              => 'number',
            'custom_attributes' => [
                'step' => '1',
                'min'  => '0',
                'max'  => '4',
                'size' => '2',
            ],
        ];

        $fieldDisplay = [
            'name' => 'display',
            'class'             => ['wcmp__d--inline-block'],
            'input_class'       => ['wcmp__offset-dialog__display'],
            'type'              => 'select',
            'options' => [
                'A4' => 'A4',
                'A6' => 'A6',
            ],
        ];
        $class = new SettingsFieldArguments($field, false);
        $classDisplay = new SettingsFieldArguments($fieldDisplay, false);
        ?>

      <div
        class="wcmp wcmp__box wcmp__modal wcmp__offset-dialog wcmp__ws--nowrap"
        style="display: none;">
        <div class="wcmp__offset-dialog__inner wcmp__d--flex">
          <div>
            <div class="wcmp__pb--2">
                <?php
                printf(
                    '<label for="%s">%s</label>',
                    $class->getId(),
                    __('Labels to skip', 'woocommerce-myparcel')
                ); ?>
            </div>
            <div class="wcmp__d--flex wcmp__pb--2">
                <?php
                woocommerce_form_field($field['name'], $class->getArguments(false), ''); ?>
              <img
                src="<?php
                echo WCMYPA()->plugin_url() . '/assets/img/offset.svg'; ?>"
                class="wcmp__offset-dialog__icon wcmp__pl--1" />
            </div>
            <div class="wcmp__d--flex wcmp__pb--2">
                <?php
                woocommerce_form_field($fieldDisplay['name'], $classDisplay->getArguments(false), '');?>
            </div>
            <div>
              <a
                href="#"
                class="wcmp__offset-dialog__button button"
                style="display: none;"
                target="_blank">
                  <?php
                  _e('Print', 'woocommerce-myparcel'); ?><?php
                  self::renderSpinner(); ?>
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
     * @deprecated
     */
    public function renderShipmentOptionsForm(): void
    {
        echo '<div class="wcmp__box wcmp__modal wcmp__shipment-options-dialog" style="display: none; position: absolute;"></div>';
    }

    /**
     * Get the new html content for the shipment options form based on the passed order id.
     * @deprecated
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
     *                  @deprecated
     */
    public function showOrderActions($order): void
    {
        if (empty($order)) {
            return;
        }

        $orderRepository = (Pdk::get(PdkOrderRepository::class));
        $pdkOrder        = $orderRepository->get($order);
        $shippingCountry = WCX_Order::get_prop($order, 'shipping_country');

        if (! CountryCodes::isAllowedDestination($shippingCountry)) {
            return;
        }

        $listingActions = self::getListingActions($pdkOrder, $orderRepository);
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
     * @param  PdkOrder                                                         $pdkOrder
     * @param  \MyParcelNL\WooCommerce\Pdk\Plugin\Repository\PdkOrderRepository $orderRepository
     *
     * @return array|array[]
     * @throws \JsonException
     *                       @deprecated
     */
    public static function getListingActions(PdkOrder $pdkOrder, PdkOrderRepository $orderRepository): array
    {
        $shippingCountry = $pdkOrder->recipient->cc;
        $wcOrderId       = $pdkOrder->externalIdentifier;
        $exportMode      = WCMYPA()->settingCollection->getByName('export_mode');
        $consignments    = self::get_order_shipments(wc_get_order($wcOrderId));
        $listingActions  = self::getDefaultListingActions($wcOrderId);

        if (WCMP_Settings_Data::EXPORT_MODE_PPS === $exportMode) {
            $metaPps        = get_post_meta($wcOrderId, self::META_PPS);
            $listingActions = self::updateExportButtonForPps($listingActions, $metaPps);
        }

        if (empty($consignments) || WCMP_Settings_Data::EXPORT_MODE_PPS === $exportMode) {
            unset($listingActions[PdkActions::PRINT_ORDER]);
        }

        if (empty($consignments) || Data::DEFAULT_COUNTRY_CODE !== $shippingCountry) {
            unset($listingActions[ExportActions::EXPORT_RETURN]);
        }

        if ($orderRepository->hasLocalPickup()) {
            unset($listingActions[PdkActions::PRINT_ORDER], $listingActions[PdkActions::EXPORT_ORDER]);
        }

        return $listingActions;
    }

    /**
     * @param  \WC_Order $order
     *
     * @return array
     *              @deprecated
     */
    public static function getListingAttributes(WC_Order $order): array
    {
        $downloadDisplay = WCMYPA()->settingCollection->getByName('download_display');
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
     * @param  int $orderId
     *
     * @return array[]
     *                @deprecated
     */
    public static function getDefaultListingActions(int $orderId): array
    {
        $exportOrder = PdkActions::EXPORT_ORDER;
        $printOrder  = PdkActions::PRINT_ORDER;
        $addReturn   = PdkActions::EXPORT_RETURN;
        $pluginUrl   = WCMYPA()->plugin_url();
        $baseUrl     = 'admin-ajax.php?action=' . ExportActions::ACTION_NAME;

        return [
            $exportOrder => [
                'url' => admin_url("$baseUrl&pdkAction=$exportOrder&orderIds=$orderId"),
                'img' => "{$pluginUrl}/assets/img/export.svg",
                'alt' => __('action_export_to_myparcel', 'woocommerce-myparcel'),
            ],
            $printOrder  => [
                'url' => admin_url("$baseUrl&pdkAction=$printOrder&orderIds=$orderId"),
                'img' => "{$pluginUrl}/assets/img/print.svg",
                'alt' => __('action_print_myparcel_label', 'woocommerce-myparcel'),
            ],
            $addReturn   => [
                'url' => admin_url("$baseUrl&pdkAction=$addReturn&orderIds=$orderId"),
                'img' => "{$pluginUrl}/assets/img/return.svg",
                'alt' => __('action_email_return_label', 'woocommerce-myparcel'),
            ],
        ];
    }

    /**
     * @param  array $listingActions
     * @param  array $metaPps
     *
     * @return array
     *              @deprecated
     */
    public static function updateExportButtonForPps(array $listingActions, array $metaPps): array
    {
        if (! $metaPps) {
            return $listingActions;
        }

        $pluginUrl                                       = WCMYPA()->plugin_url();
        $listingActions[PdkActions::EXPORT_ORDER]['img'] = "{$pluginUrl}/assets/img/myparcel.svg";

        foreach ($metaPps as $metaPpsFeedback) {
            if (is_array($metaPpsFeedback) && $metaPpsFeedback[self::META_PPS_EXPORTED]) {
                $listingActions[PdkActions::EXPORT_ORDER]['alt'] = __(
                    'export_hint_already_exported',
                    'woocommerce-myparcel'
                );

                break;
            }
        }

        return $listingActions;
    }

    /**
     * @param  \WC_Order $order
     * @param  bool      $exclude_concepts
     *
     * @return array
     * @throws \JsonException
     *                       @deprecated
     */
    public static function get_order_shipments(WC_Order $order, bool $exclude_concepts = false): array
    {
        $shipments = WCX_Order::get_meta($order, self::META_SHIPMENTS);

        // fallback to legacy consignment data (v1.X)
        if (empty($shipments)) {
            if ($shipmentId = WCX_Order::get_meta($order, self::META_CONSIGNMENT_ID)) {
                $shipments = [
                    [
                        'shipment_id' => $shipmentId,
                        'trace_trace' => WCX_Order::get_meta($order, self::META_TRACK_TRACE),
                    ],
                ];
            } elseif ($legacy_consignments = WCX_Order::get_meta($order, self::META_CONSIGNMENTS)) {
                $shipments = [];
                foreach ($legacy_consignments as $consignment) {
                    if (isset($consignment['consignment_id'])) {
                        $shipments[] = [
                            'shipment_id' => $consignment['consignment_id'],
                            'track_trace' => $consignment['track_trace'],
                        ];
                    }
                }
            }
        }

        if (empty($shipments) || ! is_array($shipments)) {
            return [];
        }

        /**
         * Filter out concepts.
         * @deprecated
         */
        if ($exclude_concepts) {
            $shipments = array_filter(
                $shipments,
                static function ($shipment) {
                    return isset($shipment['track_trace']);
                }
            );
        }

        return $shipments;
    }

    /**
     * On saving shipment options from the bulk options form.
     *
     * @throws Exception
     * @see admin/views/html-order-shipment-options.php
     *                                                 @deprecated
     */
    public function save_shipment_options_ajax(): void
    {
        parse_str($_POST['form_data'], $form_data);

        foreach ($form_data[self::SHIPMENT_OPTIONS_FORM_NAME] as $order_id => $data) {
            $order = WCX::get_order($order_id);
            $data  = self::removeDisallowedDeliveryOptions($data, $order->get_shipping_country());

            WCX_Order::update_meta_data(
                $order,
                self::META_DELIVERY_OPTIONS,
                $data
            );

            // Save extra options
            WCX_Order::update_meta_data(
                $order,
                self::META_SHIPMENT_OPTIONS_EXTRA,
                array_merge(
                    self::getExtraOptionsFromOrder($order),
                    $data['extra_options']
                )
            );
        }

        die();
    }

    /**
     * Add the meta box on the single order page
     *
     * @return void
     *             @deprecated
     */
    public function add_order_meta_box(): void
    {
        add_meta_box(
            'myparcel',
            __('MyParcel', 'woocommerce-myparcel'),
            [$this, 'createMetaBox'],
            'shop_order',
            'side'
        );
    }

    /**
     * Callback: Create the meta box content on the single order page
     *
     * @throws Exception
     *                  @deprecated
     */
    public function createMetaBox(): void
    {
        global $post_id;

        // get order
        $order = WCX::get_order($post_id);

        if (! $order) {
            return;
        }

        $orderId = WCX_Order::get_id($order);

        $shipping_country = WCX_Order::get_prop($order, 'shipping_country');
        if (! CountryCodes::isAllowedDestination($shipping_country)) {
            return;
        }

        $class = version_compare(WOOCOMMERCE_VERSION, '3.3.0', '>=') ? 'single_wc_actions' : 'single_order_actions';
        // show buttons and check if WooCommerce > 3.3.0 is used and select the correct function and class
        echo "<div class=\"$class\">";
        $this->showOrderActions($order);
        echo '</div>';

        $downloadDisplay = WCMYPA()->settingCollection->getByName(
                'download_display'
            ) === 'display';

        $shipmentIds = self::get_order_shipments($order);

        // show shipments if available
        if (! $shipmentIds) {
            return;
        }

        include('views/html-order-track-trace-table.php');
    }

    /**
     * @param  \WC_Order $order
     *
     * @throws \Exception
     *                   @deprecated
     */
    public function single_order_shipment_options(WC_Order $order): void
    {
        $shipping_country = WCX_Order::get_prop($order, 'shipping_country');

        if (! CountryCodes::isAllowedDestination($shipping_country)) {
            return;
        }

        $this->showMyParcelSettings($order);
    }

    /**
     * @param  \WC_Order $order
     * @param  bool      $isEmail
     *
     * @throws \Exception
     *                   @deprecated
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
     * @deprecated
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
     * @param  int $postId
     *                    @deprecated
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
     *                  @deprecated
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
     *              @deprecated
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
     *              @deprecated
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
     *                  @deprecated
     */
    public function addBarcodeToOrderColumn($column): void
    {
        global $post;

        if ('barcode' === $column) {
            $this->renderBarcodes(WCX::get_order($post->ID));
        }
    }

    /**
     * @param  WC_Order $order
     *
     * @return void
     * @throws Exception
     *                  @deprecated
     */
    public function renderBarcodes(WC_Order $order): void
    {
        $shipments  = self::get_order_shipments($order, false);
        $exportMode = WCMYPA()->settingCollection->getByName('export_mode');

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
            $shipmentStatusId = $shipment['shipment']['status'] ?? null;
            $printedStatuses  = [
                WCMYPA_Admin::ORDER_STATUS_PRINTED_DIGITAL_STAMP,
                WCMYPA_Admin::ORDER_STATUS_PRINTED_LETTER,
            ];

            if (in_array($shipmentStatusId, $printedStatuses, true)) {
                echo __('The label has been printed.', 'woocommerce-myparcel') . '<br/>';
                continue;
            }

            if (empty($shipment['track_trace'])) {
                echo __('Concept created but not printed.', 'woocommerce-myparcel') . '<br/>';
                continue;
            }

            printf(
                '<a target="_blank" class="wcmp__barcode-link" title="%2$s" href="%1$s">%2$s</a><br>',
                self::getTrackTraceUrl($order, $shipment['track_trace']),
                $shipment['track_trace']
            );
        }
        echo '</div>';
    }

    /**
     * Get DeliveryOptions object from the given order's meta data. Uses legacy delivery options if found, if that
     * data is invalid it falls back to defaults.
     *
     * @param  WC_Order $order
     * @param  array    $inputData
     *
     * @return DeliveryOptionsAdapter
     * @throws \Exception
     * @see \WCMP_Checkout::save_delivery_options
     *                                           @deprecated
     */
    public static function getDeliveryOptionsFromOrder(WC_Order $order, array $inputData = []): DeliveryOptionsAdapter
    {
        $meta = WCX_Order::get_meta($order, self::META_DELIVERY_OPTIONS) ?: null;

        // $meta is a json string, create an instance
        if (! empty($meta) && ! $meta instanceof DeliveryOptionsAdapter) {
            if (is_string($meta)) {
                $meta = json_decode(stripslashes($meta), true);
            }

            if (! $meta['carrier']
                || ! AccountSettings::getInstance()
                    ->isEnabledCarrier($meta['carrier'])) {
                $meta['carrier'] = (Data::DEFAULT_CARRIER_CLASS)::NAME;
            }

            $meta['date'] = $meta['date'] ?? '';

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

        return apply_filters('wc_myparcel_order_delivery_options', $meta, $order);
    }

    /**
     * @param  WC_Order $order
     *
     * @return array
     * @throws JsonException
     *                      @deprecated
     */
    public static function getExtraOptionsFromOrder(WC_Order $order): array
    {
        $meta = WCX_Order::get_meta($order, self::META_SHIPMENT_OPTIONS_EXTRA) ?: null;

        if (empty($meta)) {
            $meta['collo_amount'] = 1;
        }

        return (array) $meta;
    }

    /**
     * @return bool
     *             @deprecated
     */
    private function anyActiveCarrierHasShowDeliveryDate(): bool
    {
        $enabledCarriers = AccountSettings::getInstance()
            ->getEnabledCarriers();

        foreach ($enabledCarriers->all() as $carrier) {
            if (WCMYPA()->settingCollection->where('carrier', $carrier->getName())
                ->getByName('allow_show_delivery_date')) {
                return true;
            }
        }

        return false;
    }

    /**
     * Output the delivery date if there is a date and the show delivery day setting is enabled.
     *
     * @param  \MyParcelNL\Pdk\Shipment\Model\DeliveryOptions $deliveryOptions
     *
     * @throws \Exception
     *                   @deprecated
     */
    private function printDeliveryDate(DeliveryOptions $deliveryOptions): void
    {
        $deliveryDate = $deliveryOptions->date;
        $deliveryType = $deliveryOptions->deliveryType;

        if ($deliveryDate || DeliveryOptions::PACKAGE_TYPE_PACKAGE_NAME === $deliveryOptions->packageType) {
            printf(
                '<div class="delivery-date"><strong>%s</strong><br />%s, %s</div>',
                __('MyParcel shipment:', 'woocommerce-myparcel'),
                Data::getDeliveryTypesHuman()[$deliveryType],
                null === $deliveryDate || $deliveryType === DeliveryOptions::DELIVERY_TYPE_PICKUP_NAME ? ''
                    : wc_format_datetime($deliveryDate, 'D d-m')
            );
        }
    }

    /**
     * Output the chosen delivery options or the chosen pickup options.
     *
     * @param  DeliveryOptionsAdapter $deliveryOptions
     *
     * @return array[]|null
     * @throws \Exception
     *                   @deprecated
     */
    private function getConfirmationData(DeliveryOptionsAdapter $deliveryOptions): ?array
    {
        $deliveryOptionsEnabled = WCMYPA()->settingCollection->isEnabled(
            'delivery_options_enabled'
        );

        if (! $deliveryOptionsEnabled || ! $deliveryOptions->getCarrier()) {
            return null;
        }

        $deliveryType = $this->getDeliveryTypeOptions($deliveryOptions);

        if (DeliveryOptions::DELIVERY_TYPE_PICKUP_NAME === $deliveryOptions->getDeliveryType()) {
            $pickupLocation = $deliveryOptions->getPickupLocation();

            return [
                __('delivery_type', 'woocommerce-myparcel')   => $deliveryType,
                __('pickup_location', 'woocommerce-myparcel') => sprintf(
                    '%s<br>%s %s<br>%s %s',
                    $pickupLocation->getLocationName(),
                    $pickupLocation->getStreet(),
                    $pickupLocation->getNumber(),
                    $pickupLocation->getPostalCode(),
                    $pickupLocation->getCity()
                ),
            ];
        }

        $confirmationData = [
            __('delivery_type', 'woocommerce-myparcel') => $deliveryType,
        ];

        if (WCMYPA()->settingCollection->isEnabled('show_delivery_day')) {
            $confirmationData[__('Date:', 'woocommerce')] = wc_format_datetime(
                new WC_DateTime($deliveryOptions->getDate())
            );
        }

        $extraOptions = $this->getExtraOptions($deliveryOptions->getShipmentOptions());
        if ($extraOptions) {
            $confirmationData[__('extra_options', 'woocommerce-myparcel')] = implode('<br/>', $extraOptions);
        }

        return $confirmationData;
    }

    /**
     * @param  DeliveryOptionsAdapter $deliveryOptions
     *
     * @return string
     *               @deprecated
     */
    private function getDeliveryTypeOptions(DeliveryOptionsAdapter $deliveryOptions): string
    {
        $deliveryType  = $deliveryOptions->getDeliveryType();
        $deliveryTitle = null;

        switch ($deliveryType) {
            case DeliveryOptions::DELIVERY_TYPE_STANDARD_NAME:
                $deliveryTitle = WCMP_Checkout::getDeliveryOptionsTitle('standard_title');
                break;
            case DeliveryOptions::DELIVERY_TYPE_MORNING_NAME:
                $deliveryTitle = WCMP_Checkout::getDeliveryOptionsTitle(
                    'morning_title'
                );
                break;
            case DeliveryOptions::DELIVERY_TYPE_EVENING_NAME:
                $deliveryTitle = WCMP_Checkout::getDeliveryOptionsTitle(
                    'evening_title'
                );
                break;
            case DeliveryOptions::DELIVERY_TYPE_PICKUP_NAME:
                $deliveryTitle = WCMP_Checkout::getDeliveryOptionsTitle('pickup_title');
                break;
        }

        return $deliveryTitle ?: Data::getDeliveryTypesHuman()[$deliveryType];
    }

    /**
     * @param  \MyParcelNL\Sdk\src\Adapter\DeliveryOptions\AbstractShipmentOptionsAdapter|null $shipmentOptions
     *
     * @return array indexed array of strings holding the extra options, may be empty
     *               @deprecated
     */
    private function getExtraOptions(?AbstractShipmentOptionsAdapter $shipmentOptions): array
    {
        $returnValue = [];

        if (! $shipmentOptions) {
            return $returnValue;
        }

        if ($shipmentOptions->hasSignature()) {
            $returnValue[] = WCMP_Checkout::getDeliveryOptionsTitle('signature_title');
        }
        if ($shipmentOptions->hasOnlyRecipient()) {
            $returnValue[] = WCMP_Checkout::getDeliveryOptionsTitle('only_recipient_title');
        }

        return $returnValue;
    }

    /**
     * Print a table with the chosen delivery options on the confirmation page.
     *
     * @param  array[]|null $selectedDeliveryOptions
     *                                              @deprecated
     */
    public function printThankYouConfirmation(?array $selectedDeliveryOptions): void
    {
        printf($this->generateThankYouConfirmation($selectedDeliveryOptions));
    }

    /**
     * Print a table with the chosen delivery options in the confirmation email.
     *
     * @param  array[]|null $selectedDeliveryOptions
     *                                              @deprecated
     */
    public function printEmailConfirmation(?array $selectedDeliveryOptions): void
    {
        printf($this->generateEmailConfirmation($selectedDeliveryOptions));
    }

    /**
     * @param  array[]|null $options
     *
     * @return string|null
     *                    @deprecated
     */
    public function generateThankYouConfirmation(?array $options): ?string
    {
        if ($options) {
            $htmlHeader = "<h2 class='woocommerce-column__title'> " . __(
                    'Delivery information:',
                    'woocommerce-myparcel'
                ) . '</h2><table>';

            foreach ($options as $key => $option) {
                if ($option) {
                    $htmlHeader .= "<tr><td>$key</td><td>" . __($option, 'woocommerce-myparcel') . '</td></tr>';
                }
            }

            return $htmlHeader . '</table>';
        }

        return null;
    }

    /**
     * @param  array[]|null $options
     *
     * @return string|null
     *                    @deprecated
     */
    public function generateEmailConfirmation(?array $options): ?string
    {
        if ($options) {
            $htmlHeader = "<h2 class='woocommerce-column__title'> " . __(
                    'Delivery information:',
                    'woocommerce-myparcel'
                ) . '</h2>';
            $htmlHeader .= "<table cellspacing='0' style='border: 1px solid #e5e5e5; margin-bottom: 20px;>";

            foreach ($options as $key => $option) {
                if ($option) {
                    $htmlHeader .= "<tr style='border: 1px solid #d5d5d5;'>
                              <td style='border: 1px solid #e5e5e5;'>$key</td>
                              <td style='border: 1px solid #e5e5e5;'>" . __($option, 'woocommerce-myparcel') . '</td>
                            </tr>';
                }
            }

            return $htmlHeader . '</table>';
        }

        return null;
    }

    /**
     * Output a spinner.
     *
     * @param  string $state
     * @param  array  $args
     *                     @deprecated
     */
    public static function renderSpinner(string $state = '', array $args = []): void
    {
        $spinners = [
            'loading' => get_site_url() . '/wp-admin/images/spinner.gif',
            'success' => get_site_url() . '/wp-admin/images/yes.png',
            'failed'  => get_site_url() . '/wp-admin/images/no.png',
        ];

        $arguments = [];

        $args['class'][] = 'wcmp__spinner';

        if ($state) {
            $args['class'][] = "wcmp__spinner--$state";
        }

        foreach ($args as $arg => $value) {
            if (is_array($value)) {
                $value = implode(' ', $value);
            }
            $arguments[] = "$arg=\"$value\"";
        }

        $attributes = implode(' ', $arguments);

        echo "<span $attributes>";
        foreach ($spinners as $spinnerState => $icon) {
            printf(
                '<img class="wcmp__spinner__%1$s" alt="%1$s" src="%2$s" style="display: %3$s;" />',
                $spinnerState,
                $icon,
                $state === $spinnerState ? 'block' : 'none'
            );
        }
        echo '</span>';
    }

    /**
     * @param  string $url
     * @param  string $alt
     * @param  string $icon
     * @param  array  $rawAttributes
     *                              @deprecated
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
        echo '</a>';
    }

    /**
     * @param  array $shipment
     * @param  int   $order_id
     *
     * @throws Exception
     *                  @deprecated
     */
    public static function renderTrackTraceLink(array $shipment, int $order_id): void
    {
        $track_trace = $shipment['track_trace'] ?? null;

        if ($track_trace) {
            $track_trace_url  = self::getTrackTraceUrl($order_id, $track_trace);
            $track_trace_link = sprintf(
                '<a href="%s" target="_blank">%s</a>',
                $track_trace_url,
                $track_trace
            );
        } elseif (isset($shipment['shipment']) && isset($shipment['shipment']['options'])) {
            $package_type     = ExportActions::getPackageTypeHuman($shipment['shipment']['options']['package_type']);
            $track_trace_link = "($package_type)";
        } else {
            $track_trace_link = __('(Unknown)', 'woocommerce-myparcel');
        }

        echo $track_trace_link;
    }

    /**
     * @param  array $shipment
     * @param  int   $order_id
     *                        @deprecated
     */
    public static function renderStatus(array $shipment, int $order_id): void
    {
        echo $shipment['status'] ?? '–';

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
     * @param  array $shipment
     * @param  int   $status
     *
     * @return bool
     *             @deprecated
     */
    public static function shipmentIsStatus(array $shipment, int $status): bool
    {
        return strstr($shipment['status'], (new ExportActions())->getShipmentStatusName($status));
    }

    /**
     * Remove options that aren't allowed and return the edited array.
     *
     * @param  array  $data
     * @param  string $country
     *
     * @return mixed
     *              @deprecated
     */
    public static function removeDisallowedDeliveryOptions(array $data, string $country): array
    {
        $data['package_type'] = $data['package_type'] ?? DeliveryOptions::DEFAULT_PACKAGE_TYPE_NAME;
        $isHomeCountry        = Data::isHomeCountry($country);
        $isEuCountry          = CountryCodes::isEuCountry($country);
        $isBelgium            = CountryService::CC_BE === $country;
        $isPackage            = DeliveryOptions::PACKAGE_TYPE_PACKAGE_NAME === $data['package_type'];
        $isDigitalStamp       = DeliveryOptions::PACKAGE_TYPE_DIGITAL_STAMP_NAME === $data['package_type'];

        if (! $isHomeCountry || ! $isPackage) {
            $data['shipment_options']['age_check'] = false;
            $data['shipment_options']['return']    = false;

            if (! $isBelgium) {
                $data['shipment_options']['insured']        = false;
                $data['shipment_options']['insured_amount'] = 0;
            }
        }

        if (! $isPackage || (! $isHomeCountry && ! $isEuCountry)) {
            $data['shipment_options']['large_format'] = false;
        }

        if (! $isDigitalStamp) {
            unset($data['extra_options']['weight']);
        }

        return $data;
    }

    /**
     * Whether the current site is capable of receiving webhooks. Checks for SSL connection.
     *
     * @return bool
     *             @deprecated
     */
    public static function canUseWebhooks(): bool
    {
        $validator = new WebhookCallbackUrlValidator();
        $validator->validateAll(get_rest_url());

        try {
            $validator->report();
            return true;
        } catch (Exception $e) {
            // return false implies exception.
        }

        return false;
    }
}

return new WCMYPA_Admin();
