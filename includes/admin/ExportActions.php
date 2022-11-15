<?php

declare(strict_types=1);

use MyParcelNL\Pdk\Shipment\Collection\ShipmentCollection;
use MyParcelNL\Pdk\Shipment\Repository\ShipmentRepository;
use MyParcelNL\WooCommerce\includes\admin\OrderStatus;
use MyParcelNL\Pdk\Base\PdkActions;
use MyParcelNL\Pdk\Base\PdkEndpoint;
use MyParcelNL\Pdk\Facade\Pdk;
use MyParcelNL\Pdk\Fulfilment\Collection\OrderCollection;
use MyParcelNL\Sdk\src\Adapter\DeliveryOptions\AbstractDeliveryOptionsAdapter;
use MyParcelNL\Sdk\src\Model\Consignment\AbstractConsignment;
use MyParcelNL\Sdk\src\Model\Fulfilment\AbstractOrder;
use MyParcelNL\Sdk\src\Support\Str;
use MyParcelNL\WooCommerce\includes\admin\Messages;
use MyParcelNL\WooCommerce\PdkOrderRepository;
use Symfony\Component\HttpFoundation\Response;
use WPO\WC\MyParcel\Compatibility\Order as WCX_Order;
use WPO\WC\MyParcel\Compatibility\WC_Core as WCX;
use WPO\WC\MyParcel\Compatibility\WCMP_ChannelEngine_Compatibility as ChannelEngine;

if (! defined('ABSPATH')) {
    exit;
}

if (class_exists('ExportActions')) {
    return new ExportActions();
}

class ExportActions
{
    public const ACTION_NAME = 'MyParcelPdk';
    /**
     * @deprecated GEBRUIK PDK
     */
    public const EXPORT_ORDER = '';
    /**
     * @deprecated GEBRUIK PDK
     */
    public const EXPORT_RETURN = '';
    /**
     * @deprecated GEBRUIK PDK
     */
    public const GET_LABELS = '';
    /**
     * @deprecated GEBRUIK PDK
     */
    public const        MODAL_DIALOG                 = '';
    public const        ITEM_DESCRIPTION_MAX_LENGTH  = 50;
    public const        DEFAULT_POSITIONS            = [2, 4, 1, 3];
    public const        ORDER_DESCRIPTION_MAX_LENGTH = 45;
    public const        DISALLOWED_SHIPPING_METHODS  = [
        WCMP_Shipping_Methods::LOCAL_PICKUP,
    ];

    /**
     * @var array
     */
    public $success;

    public function __construct()
    {
        $this->success = [];

        add_action('wp_ajax_' . self::ACTION_NAME, [$this, 'handlePdkAction']);
    }

    /**
     * @param $item
     * @param $order
     *
     * @return mixed|string
     */
    public static function getItemDisplayName($item, $order)
    {
        // set base name
        $name = $item['name'];

        // add variation name if available
        $product = $order->get_product_from_item($item);
        if ($product && isset($item['variation_id']) && $item['variation_id'] > 0
            && method_exists(
                $product,
                'get_variation_attributes'
            )) {
            $name .= woocommerce_get_formatted_variation($product->get_variation_attributes());
        }

        return $name;
    }

    /**
     * Export selected orders.
     *
     * @access public
     * @return void
     * @throws Exception
     */
    public function handlePdkAction(): void
    {
        $this->permissionChecks();

        $action = $_REQUEST['pdkAction'];

        $_GET['orderIds'] = $_GET['orderIds'] ?: $_REQUEST['order_ids'];

        $orderIds = (array) $_GET['orderIds'];

        try {
            $response = $this->callAction($action, $orderIds);
        } catch (Exception $e) {
            echo json_encode(
                $this->setFeedbackForClient([
                    'error' => $e->getMessage(),
                ])
            );
        }

        $return = [];
        switch ($action) {
            case PdkActions::EXPORT_ORDER:
                $return = [
                    'success' => sprintf(__('successfully_exported', 'woocommerce-myparcel'), implode(', ', $orderIds)),
                ];
                break;
            case PdkActions::PRINT_ORDER:
                if (isset($response)) {
                    (new OrderStatus())->updateOrderBarcode($orderIds);
                    $displaySetting = WCMYPA()->settingCollection->getByName('download_display');
                    $display        = $displaySetting === 'display';
                    $parsedResponse = json_decode($response->getContent(), true)['data'];

                    if ($display) {
                        $pdf = base64_decode(json_decode($response->getContent(), true)['data']['pdf']);

                        header('Content-Type: application/pdf');
                        header('Content-Length: ' . strlen($pdf));
                        header('Content-disposition: inline; filename="pietje' . gmdate('Y-M-d H-i-s') . '.pdf"');
                        header('Cache-Control: public, must-revalidate, max-age=0');
                        header('Pragma: public');
                        header('Expires: Sat, 26 Jul 1997 05:00:00 GMT');
                        header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');

                        $return = $pdf;
                    } else {
                        $return = [
                            'success' => sprintf(
                                __('successfully_printed', 'woocommerce-myparcel'),
                                implode(', ', $orderIds)
                            ),
                            'link'     => $parsedResponse['link'],
                        ];
                    }
                }
                break;
        }

        echo json_encode($this->setFeedbackForClient($return));
        die();
    }

    /**
     * @param $action
     * @param $orderIds
     *
     * @return null|
     */
    public function callAction($action, $orderIds): ?Response
    {
        try {
            /** @var \MyParcelNL\Pdk\Base\PdkEndpoint $endpoint */
            $endpoint = Pdk::get(PdkEndpoint::class);
            $response = $endpoint->call($action);

            $this->getShipmentData($orderIds);
        } catch (Exception $e) {
            $errorMessage = $e->getMessage();
            //WCMP_Log::add("$request: {$errorMessage}");
            Messages::showAdminNotice($errorMessage, Messages::NOTICE_LEVEL_ERROR);
        }

        return $response ?? null;
    }

    /**
     * @param  string|array $array
     *
     * @return array
     */
    public function sanitize_posted_array($array): array
    {
        if (is_array($array)) {
            return $array;
        }

        // check for JSON
        if (is_string($array) && strpos($array, '[') !== false) {
            $array = json_decode(stripslashes($array), false);
        }

        return (array) $array;
    }

    /**
     * @param $order_ids
     *
     * @throws \JsonException
     */
    public function modal_dialog($order_ids): void
    {
        if (is_string($order_ids) && strpos($order_ids, '[') !== false) {
            $order_ids = json_decode(stripslashes($order_ids), false);
        }

        $order_ids = (array) $order_ids;
        require('views/html-send-return-email-form.php');
        die();
    }

    /**
     * @param $request
     */
    public function modal_success_page($request): void
    {
        require('views/html-modal-result-page.php');
        die();
    }

    /**
     * @param  int   $order_id
     * @param  array $track_traces
     *
     * @internal param $shipment_ids
     */
    public static function addTrackTraceNoteToOrder(int $order_id, array $track_traces): void
    {
        if (! WCMYPA()->settingCollection->isEnabled(WCMYPA_Settings::SETTING_BARCODE_IN_NOTE)) {
            return;
        }

        $prefix_message = WCMYPA()->settingCollection->getByName(WCMYPA_Settings::SETTING_BARCODE_IN_NOTE_TITLE);

        // Select the barcode text of the MyParcel settings
        $prefix_message = $prefix_message ? $prefix_message . ' ' : '';

        $order = WCX::get_order($order_id);
        $order->add_order_note($prefix_message . implode(', ', $track_traces));
    }

    /**
     * @param  WC_Order $order
     * @param  array    $shipment
     *
     * @return void
     * @throws Exception
     */
    public function saveShipmentData(WC_Order $order, array $shipment): void
    {
        if (empty($shipment)) {
            throw new \RuntimeException('save_shipment_data requires a valid shipment');
        }

        $old_shipments                           = [];
        $new_shipments                           = [];
        $new_shipments[$shipment['shipment_id']] = $shipment;

        if (WCX_Order::has_meta($order, WCMYPA_Admin::META_SHIPMENTS)) {
            $old_shipments = WCX_Order::get_meta($order, WCMYPA_Admin::META_SHIPMENTS);
        }

        $new_shipments = array_replace_recursive($old_shipments, $new_shipments);

        WCX_Order::update_meta_data($order, WCMYPA_Admin::META_SHIPMENTS, $new_shipments);
    }

    /**
     * @param  WC_Order $order
     * @param  string   $shippingMethodId
     *
     * @return string|null
     * @throws Exception
     */
    public function getOrderShippingClass(WC_Order $order, string $shippingMethodId = ''): ?string
    {
        if (empty($shippingMethodId)) {
            $orderShippingMethods = $order->get_items('shipping');

            if (! empty($orderShippingMethods)) {
                // we're taking the first (we're not handling multiple shipping methods as of yet)
                $orderShippingMethod = array_shift($orderShippingMethods);
                $shippingMethodId    = $orderShippingMethod['method_id'];
            } else {
                return null;
            }
        }

        $shippingMethod = self::getShippingMethod($shippingMethodId);

        if (! $shippingMethod) {
            return null;
        }

        return $shippingMethodId;
    }

    /**
     * Determine appropriate package type for this order.
     *
     * @param  WC_Order                            $order
     * @param  null|AbstractDeliveryOptionsAdapter $deliveryOptions
     *
     * @return string
     * @throws Exception
     */
    public function getPackageTypeFromOrder(
        WC_Order                       $order,
        AbstractDeliveryOptionsAdapter $deliveryOptions = null
    ): string {
        $packageTypeFromDeliveryOptions = $deliveryOptions ? $deliveryOptions->getPackageType() : null;
        $allowedPackageType             = $this->getAllowedPackageType($order, $packageTypeFromDeliveryOptions);

        if ($allowedPackageType) {
            return apply_filters('wc_myparcel_order_package_type', $allowedPackageType, $order, $this);
        }

        $packageType = AbstractConsignment::DEFAULT_PACKAGE_TYPE_NAME;

        // get shipping methods from order
        $orderShippingMethods = $order->get_items('shipping');

        if (! empty($orderShippingMethods)) {
            // we're taking the first (we're not handling multiple shipping methods as of yet)
            $orderShippingMethod = array_shift($orderShippingMethods);
            $orderShippingMethod = $orderShippingMethod['method_id'];

            $orderShippingClass = WCX_Order::get_meta($order, WCMYPA_Admin::META_HIGHEST_SHIPPING_CLASS);
            if (empty($orderShippingClass)) {
                $orderShippingClass = $this->getOrderShippingClass($order, $orderShippingMethod);
            }

            $packageType = self::getPackageTypeFromShippingMethod(
                $orderShippingMethod,
                $orderShippingClass
            );
        }

        return apply_filters(
            'wc_myparcel_order_package_type',
            $this->getAllowedPackageType($order, $packageType),
            $order,
            $this
        );
    }

    /**
     * @param $shippingMethod
     * @param $shippingClass
     *
     * @return string
     */
    public static function getPackageTypeFromShippingMethod($shippingMethod, $shippingClass): string
    {
        $packageType           = AbstractConsignment::PACKAGE_TYPE_PACKAGE_NAME;
        $shippingMethodIdClass = $shippingMethod;

        if (class_exists('WC_Table_Rate_Shipping') && Str::startsWith($shippingMethod, 'table_rate:')) {
            // Automattic / WooCommerce table rate
            // use full method = method_id:instance_id:rate_id
            $shippingMethodId = $shippingMethod;
        } else {
            // non table rates
            if (Str::contains($shippingMethodIdClass, ':')) {
                // means we have method_id:instance_id
                $shippingMethod   = explode(':', $shippingMethod);
                $shippingMethodId = $shippingMethod[0];
            } else {
                $shippingMethodId = $shippingMethod;
            }

            // add class if we have one
            if (! empty($shippingClass)) {
                $shippingMethodIdClass = "{$shippingMethodId}:{$shippingClass}";
            }
        }

        $packageTypes = WCMYPA()->settingCollection->getByName(
            WCMYPA_Settings::SETTING_SHIPPING_METHODS_PACKAGE_TYPES
        );
        foreach ($packageTypes as $packageTypeKey => $packageTypeShippingMethods) {
            if (self::isActiveMethod(
                $shippingMethodId,
                $packageTypeShippingMethods,
                $shippingMethodIdClass,
                $shippingClass
            )) {
                $packageType = $packageTypeKey;
                break;
            }
        }

        return self::getPackageTypeAsString($packageType);
    }

    /**
     * @param  string|null $packageType
     *
     * @return string
     */
    public static function getPackageTypeHuman(?string $packageType): string
    {
        if ($packageType) {
            $packageType = Data::getPackageTypeHuman($packageType);
        }

        return $packageType ?? __('Unknown', 'woocommerce-myparcel');
    }

    /**
     * Will convert any package type to a valid string package type.
     *
     * @param  mixed $packageType
     *
     * @return string
     */
    public static function getPackageTypeAsString($packageType): string
    {
        if (is_numeric($packageType)) {
            $packageType = Data::getPackageTypeName($packageType);
        }

        if (! is_string($packageType) || ! in_array($packageType, AbstractConsignment::PACKAGE_TYPES_NAMES)) {
            // Log data when this occurs but don't actually throw an exception.
            $type = gettype($packageType);
            WCMP_Log::add(
                (string) new Exception("Tried to convert invalid value to package type: $packageType ($type)")
            );

            $packageType = null;
        }

        return $packageType ?? AbstractConsignment::DEFAULT_PACKAGE_TYPE_NAME;
    }

    /**
     * @param  WC_Order    $order
     * @param  string|null $packageType
     *
     * @return string|null
     * @throws Exception
     */
    public function getAllowedPackageType(WC_Order $order, ?string $packageType): ?string
    {
        $shippingCountry      = WCX_Order::get_prop($order, 'shipping_country');
        $isMailbox            = AbstractConsignment::PACKAGE_TYPE_MAILBOX_NAME === $packageType;
        $isDigitalStamp       = AbstractConsignment::PACKAGE_TYPE_DIGITAL_STAMP_NAME === $packageType;
        $isDefaultPackageType = AbstractConsignment::CC_NL !== $shippingCountry && ($isMailbox || $isDigitalStamp);

        if ($isDefaultPackageType) {
            $packageType = AbstractConsignment::DEFAULT_PACKAGE_TYPE_NAME;
        }

        return $packageType;
    }

    /**
     * @param $status_code
     *
     * @return mixed|string|void
     * TODO: Move to SDK
     */
    public function getShipmentStatusName($status_code)
    {
        $shipment_statuses = [
            1  => __('pending - concept', 'woocommerce-myparcel'),
            2  => __('pending - registered', 'woocommerce-myparcel'),
            3  => __('enroute - handed to carrier', 'woocommerce-myparcel'),
            4  => __('enroute - sorting', 'woocommerce-myparcel'),
            5  => __('enroute - distribution', 'woocommerce-myparcel'),
            6  => __('enroute - customs', 'woocommerce-myparcel'),
            7  => __('delivered - at recipient', 'woocommerce-myparcel'),
            8  => __('delivered - ready for pickup', 'woocommerce-myparcel'),
            9  => __('delivered - package picked up', 'woocommerce-myparcel'),
            12 => __('printed - letter', 'woocommerce-myparcel'),
            14 => __('printed - digital stamp', 'woocommerce-myparcel'),
            30 => __('inactive - concept', 'woocommerce-myparcel'),
            31 => __('inactive - registered', 'woocommerce-myparcel'),
            32 => __('inactive - enroute - handed to carrier', 'woocommerce-myparcel'),
            33 => __('inactive - enroute - sorting', 'woocommerce-myparcel'),
            34 => __('inactive - enroute - distribution', 'woocommerce-myparcel'),
            35 => __('inactive - enroute - customs', 'woocommerce-myparcel'),
            36 => __('inactive - delivered - at recipient', 'woocommerce-myparcel'),
            37 => __('inactive - delivered - ready for pickup', 'woocommerce-myparcel'),
            38 => __('inactive - delivered - package picked up', 'woocommerce-myparcel'),
            99 => __('inactive - unknown', 'woocommerce-myparcel'),
        ];

        return $shipment_statuses[$status_code] ?? __('Unknown status', 'woocommerce-myparcel');
    }

    /**
     * Retrieves, updates and returns shipment data for given id.
     *
     * @param  array    $orderIds
     * @param  WC_Order $order
     *
     * @return array
     * @throws Exception
     */
    public function getShipmentData(array $orderIds): array
    {
        if (empty($orderIds)) {
            return [];
        }

        $data            = [];
        $orderRepository = Pdk::get(PdkOrderRepository::class);
        $orders          = $orderRepository->getMany($orderIds);

        $shipments = $orders->getAllShipments()
            ->groupBy('orderId')
            ->reduce(static function (ShipmentCollection $acc, $shipments) {
                $acc->push($shipments->last());
                return $acc;
            }, new ShipmentCollection());

        $shipmentRepository = Pdk::get(ShipmentRepository::class);
        $fetchedShipments   = $shipmentRepository->getShipments(
            $shipments->pluck('id')
                ->all()
        );

        if (! $shipments) {
            return [];
        }

        foreach ($fetchedShipments as $shipment) {
            if (! $shipment->id) {
                return [];
            }

            $trackTrace   = $shipment->barcode ?: $shipment->externalIdentifier;
            $shipmentData = [
                'shipment_id' => $shipment->id,
                'status'      => $this->getShipmentStatusName($shipment->status),
                'track_trace' => $trackTrace,
                'shipment'    => $shipment->toArray(),
            ];

            $order = wc_get_order($shipment->referenceIdentifier);

            $this->saveShipmentData($order, $shipmentData);

            ChannelEngine::updateMetaOnExport($order, $trackTrace);

            $data[$shipment->id] = $shipmentData;
        }

        return $data;
    }

    /**
     * @param  string $chosenMethod
     *
     * @return null|bool|\WC_Shipping_Method
     */
    public static function getShippingMethod(string $chosenMethod)
    {
        [$methodSlug, $methodInstance] = WCMP_Checkout::splitShippingMethodString($chosenMethod);

        $isDisallowedShippingMethod = in_array($methodSlug, self::DISALLOWED_SHIPPING_METHODS, true);
        $isManualOrder              = empty($methodInstance);

        if ($isDisallowedShippingMethod || $isManualOrder) {
            return null;
        }

        return WC_Shipping_Zones::get_shipping_method($methodInstance);
    }

    /**
     * @param $shipping_method
     * @param $found_shipping_classes
     *
     * @return int|null
     */
    public function getShippingClass($shipping_method, $found_shipping_classes): ?int
    {
        // get most expensive class
        // adapted from $shipping_method->calculate_shipping()
        $highest_class_cost = 0;
        $highest_class      = null;
        foreach ($found_shipping_classes as $shipping_class => $products) {
            // Also handles BW compatibility when slugs were used instead of ids
            $shipping_class_term    = get_term_by('slug', $shipping_class, 'product_shipping_class');
            $shipping_class_term_id = $shipping_class_term->term_id ?? '';

            $class_cost_string = $shipping_class_term && $shipping_class_term_id ? $shipping_method->get_option(
                'class_cost_' . $shipping_class_term_id,
                $shipping_method->get_option('class_cost_' . $shipping_class, '')
            ) : $shipping_method->get_option('no_class_cost', '');

            if ('' === $class_cost_string) {
                continue;
            }

            $class_cost = $this->wc_flat_rate_evaluate_cost(
                $class_cost_string,
                [
                    'qty'  => array_sum(wp_list_pluck($products, 'quantity')),
                    'cost' => array_sum(wp_list_pluck($products, 'line_total')),
                ],
                $shipping_method
            );
            if ($class_cost > $highest_class_cost && ! empty($shipping_class_term_id)) {
                $highest_class_cost = $class_cost;
                $highest_class      = $shipping_class_term->term_id;
            }
        }

        return $highest_class;
    }

    /**
     * Adapted from WC_Shipping_Flat_Rate - Protected method
     * Evaluate a cost from a sum/string.
     *
     * @param  string $sum
     * @param  array  $args
     * @param         $flat_rate_method
     *
     * @return string
     */
    public function wc_flat_rate_evaluate_cost(string $sum, array $args, $flat_rate_method)
    {
        include_once(WC()->plugin_path() . '/includes/libraries/class-wc-eval-math.php');

        // Allow 3rd parties to process shipping cost arguments
        $args           = apply_filters('woocommerce_evaluate_shipping_cost_args', $args, $sum, $flat_rate_method);
        $locale         = localeconv();
        $decimals       = [
            wc_get_price_decimal_separator(),
            $locale['decimal_point'],
            $locale['mon_decimal_point'],
            ',',
        ];
        $this->fee_cost = $args['cost'];

        // Expand shortcodes
        add_shortcode('fee', [$this, 'wc_flat_rate_fee']);

        $sum = do_shortcode(
            str_replace(
                ['[qty]', '[cost]'],
                [$args['qty'], $args['cost']],
                $sum
            )
        );

        remove_shortcode('fee');

        // Remove whitespace from string
        $sum = preg_replace('/\s+/', '', $sum);

        // Remove locale from string
        $sum = str_replace($decimals, '.', $sum);

        // Trim invalid start/end characters
        $sum = rtrim(ltrim($sum, "\t\n\r\0\x0B+*/"), "\t\n\r\0\x0B+-*/");

        // Do the math
        return $sum ? WC_Eval_Math::evaluate($sum) : 0;
    }

    /**
     * Adapted from WC_Shipping_Flat_Rate - Protected method
     * Work out fee (shortcode).
     *
     * @param  array $atts
     *
     * @return string
     */
    public function wc_flat_rate_fee(array $atts)
    {
        $atts = shortcode_atts(
            [
                'percent' => '',
                'min_fee' => '',
                'max_fee' => '',
            ],
            $atts
        );

        $calculated_fee = 0;

        if ($atts['percent']) {
            $calculated_fee = $this->fee_cost * ((float) $atts['percent'] / 100);
        }

        if ($atts['min_fee'] && $calculated_fee < $atts['min_fee']) {
            $calculated_fee = $atts['min_fee'];
        }

        if ($atts['max_fee'] && $calculated_fee > $atts['max_fee']) {
            $calculated_fee = $atts['max_fee'];
        }

        return $calculated_fee;
    }

    /**
     * @param $shipping_method_id
     * @param $package_type_shipping_methods
     * @param $shipping_method_id_class
     * @param $shipping_class
     *
     * @return bool
     */
    private static function isActiveMethod(
        $shipping_method_id,
        $package_type_shipping_methods,
        $shipping_method_id_class,
        $shipping_class
    ): bool {
        //support WooCommerce flat rate
        // check if we have a match with the predefined methods
        if (in_array($shipping_method_id, $package_type_shipping_methods, true)) {
            return true;
        }

        if (in_array($shipping_method_id_class, $package_type_shipping_methods, true)) {
            return true;
        }

        // fallback to bare method (without class) (if bare method also defined in settings)
        if (! empty($shipping_method_id_class)
            && in_array($shipping_method_id_class, $package_type_shipping_methods, true)) {
            return true;
        }

        // support WooCommerce Table Rate Shipping by WooCommerce
        if (! empty($shipping_class) && in_array($shipping_class, $package_type_shipping_methods, true)) {
            return true;
        }

        // support WooCommerce Table Rate Shipping by Bolder Elements
        $newShippingClass = str_replace(':', '_', (string) $shipping_class);
        return ! empty($shipping_class) && in_array($newShippingClass, $package_type_shipping_methods, true);
    }

    /**
     * @throws \JsonException
     */
    private function permissionChecks(): void
    {
        if (! check_ajax_referer(WCMYPA::NONCE_ACTION, '_wpnonce', false)) {
            die("Ajax security check failed. Did you pass a valid nonce in \$_REQUEST['_wpnonce']?");
        }

        if (! is_user_logged_in()) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'woocommerce-myparcel'));
        }

        if (apply_filters(
            'wc_myparcel_check_privs',
            ! current_user_can('manage_woocommerce_orders') && ! current_user_can('edit_shop_orders')
        )) {
            $return['error'] = __(
                'You do not have sufficient permissions to access this page.',
                'woocommerce-myparcel'
            );
            echo json_encode($return);
            die();
        }
    }

    /**
     * @param  \MyParcelNL\Pdk\Fulfilment\Collection\OrderCollection $orderCollection
     *
     * @return array
     */
    private function updateOrderMetaByCollection(OrderCollection $orderCollection): array
    {
        $currentDateTime = (new DateTime())->format(AbstractOrder::DATE_FORMAT_FULL);

        $orderCollection->each(static function ($order) use ($currentDateTime) {
            $orderId = $order->externalIdentifier;
            $wcOrder = WCX::get_order($order->externalIdentifier);
            $value   = [
                WCMYPA_Admin::META_PPS_EXPORTED    => true,
                WCMYPA_Admin::META_PPS_UUID        => $order->getUuid(),
                WCMYPA_Admin::META_PPS_EXPORT_DATE => $currentDateTime,
            ];

            if (! add_post_meta($orderId, WCMYPA_Admin::META_PPS, $value)) {
                Messages::showAdminNotice(
                    sprintf(__('error_pps_export_feedback', 'woocommerce-myparcel'), $orderId),
                    Messages::NOTICE_LEVEL_ERROR
                );
            }

            OrderStatus::updateOrderStatus($wcOrder, WCMP_Settings_Data::CHANGE_STATUS_AFTER_EXPORT);
        });

        return [
            'success' => sprintf(
                __('message_orders_exported_successfully', 'woocommerce-myparcel'),
                $orderCollection->count()
            ),
        ];
    }

    /**
     * When adding shipments, store $return for use in admin_notice
     * This way we can refresh the page (JS) to show all new buttons
     *
     * @param $return
     *
     * @return mixed
     */
    private function setFeedbackForClient($return)
    {
        if ($return['success'] ?? null) {
            Messages::showAdminNotice($return['success'], Messages::NOTICE_LEVEL_SUCCESS);
        }

        if ($return['error'] ?? null) {
            Messages::showAdminNotice($return['error'], Messages::NOTICE_LEVEL_ERROR);
        }

        return $return;
    }
}

return new ExportActions();
