<?php

declare(strict_types=1);

use MyParcelNL\Pdk\Facade\Pdk;
use MyParcelNL\Pdk\Fulfilment\Collection\OrderCollection;
use MyParcelNL\Pdk\Fulfilment\Repository\OrderRepository;
use MyParcelNL\Pdk\Plugin\Collection\PdkOrderCollection;
use MyParcelNL\Pdk\Shipment\Collection\ShipmentCollection;
use MyParcelNL\Pdk\Shipment\Model\Shipment;
use MyParcelNL\Pdk\Shipment\Repository\ShipmentRepository;
use MyParcelNL\Sdk\src\Adapter\DeliveryOptions\AbstractDeliveryOptionsAdapter;
use MyParcelNL\Sdk\src\Exception\ApiException;
use MyParcelNL\Sdk\src\Exception\MissingFieldException;
use MyParcelNL\Sdk\src\Helper\MyParcelCollection;
use MyParcelNL\Sdk\src\Model\Consignment\AbstractConsignment;
use MyParcelNL\Sdk\src\Model\Consignment\PostNLConsignment;
use MyParcelNL\Sdk\src\Model\Fulfilment\AbstractOrder;
use MyParcelNL\Sdk\src\Support\Arr;
use MyParcelNL\Sdk\src\Support\Str;
use MyParcelNL\WooCommerce\Helper\ExportRow;
use MyParcelNL\WooCommerce\includes\adapter\PdkOrderCollectionFromWCOrdersAdapter;
use MyParcelNL\WooCommerce\includes\adapter\PdkOrderFromWCOrderAdapter;
use MyParcelNL\WooCommerce\includes\admin\Messages;
use WPO\WC\MyParcel\Compatibility\Order as WCX_Order;
use WPO\WC\MyParcel\Compatibility\WC_Core as WCX;
use WPO\WC\MyParcel\Compatibility\WCMP_ChannelEngine_Compatibility as ChannelEngine;

if (! defined("ABSPATH")) {
    exit;
} // Exit if accessed directly

if (class_exists("WCMP_Export")) {
    return new WCMP_Export();
}

class WCMP_Export
{
    public const EXPORT = 'wcmp_export';
    public const EXPORT_ORDER  = 'export_order';
    public const EXPORT_RETURN = 'export_return';
    public const GET_LABELS    = 'get_labels';
    public const MODAL_DIALOG  = 'modal_dialog';
    /**
     * Maximum characters length of item description.
     */
    public const ITEM_DESCRIPTION_MAX_LENGTH  = 50;
    public const ORDER_DESCRIPTION_MAX_LENGTH = 45;
    public const COOKIE_EXPIRE_TIME = 20;
    public const DEFAULT_POSITIONS = [2, 4, 1, 3];
    public const SUFFIX_CHECK_REG  = "~^([a-z]{1}\d{1,3}|-\d{1,4}\d{2}\w{1,2}|[a-z]{1}[a-z\s]{0,3})(?:\W|$)~i";
    /**
     * Shipping methods that can never have delivery options.
     */
    public const DISALLOWED_SHIPPING_METHODS = [
        WCMP_Shipping_Methods::LOCAL_PICKUP,
    ];
    public const COUNTRY_CODE_NL = 'NL';
    public const COUNTRY_CODE_BE = 'BE';
    public const NO              = 'no';
    public const YES             = 'yes';

    public $success;

    /**
     * @var mixed
     */
    private $logger;

    public function __construct()
    {
        //$this->logger  = Pdk::get(PdkLogger::class);
        $this->success = [];
        require_once("class-wcmp-rest.php");
        require_once("class-wcmp-api.php");

        add_action("wp_ajax_" . self::EXPORT, [$this, "export"]);
    }

    /**
     * @param  int $orderId
     *
     * @throws \JsonException
     * @throws \MyParcelNL\Pdk\Base\Exception\InvalidCastException
     */
    public function exportByOrderId(int $orderId): void
    {
        if (! $orderId) {
            return;
        }

        $pdkOrderCollection = (new PdkOrderFromWCOrderAdapter([$orderId]))->convert();
        $return             = $this->exportAccordingToMode($pdkOrderCollection, [(string) $orderId], self::NO);

        if (isset($return['success'])) {
            $order = WCX::get_order($orderId);
            $order->add_order_note($return['success']);
        }
    }

    /**
     * @param $item
     * @param $order
     *
     * @return mixed|string
     */
    public static function get_item_display_name($item, $order)
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
     * @throws ApiException
     * @throws MissingFieldException
     * @throws Exception
     */
    public function export()
    {
        // Check the nonce
        if (! check_ajax_referer(WCMYPA::NONCE_ACTION, "_wpnonce", false)) {
            die("Ajax security check failed. Did you pass a valid nonce in \$_REQUEST['_wpnonce']?");
        }

        if (! is_user_logged_in()) {
            wp_die(__("You do not have sufficient permissions to access this page.", "woocommerce-myparcel"));
        }

        $return = [];

        // Check the user privileges (maybe use order ids for filter?)
        if (apply_filters(
            "wc_myparcel_check_privs",
            ! current_user_can("manage_woocommerce_orders") && ! current_user_can("edit_shop_orders")
        )) {
            $return["error"] = __(
                "You do not have sufficient permissions to access this page.",
                "woocommerce-myparcel"
            );
            echo json_encode($return);
            die();
        }

        $print   = $_REQUEST['print'] ?? null;
        $offset  = (int) ($_REQUEST['offset'] ?? 0);
        $request = $_REQUEST['request'];

        /**
         * @var $orderIds
         */
        $orderIds    = $this->sanitize_posted_array($_REQUEST['order_ids'] ?? []);
        $shipmentIds = $this->sanitize_posted_array($_REQUEST['shipment_ids'] ?? []);

        foreach ($orderIds as $key => $id) {
            $order    = WCX::get_order($id);
            $pdkOrder = (new PdkOrderFromWCOrderAdapter($order));

            if ($pdkOrder->hasLocalPickup()) {
                unset($orderIds[$key]);
            }
        }
        $pdkOrderCollection = (new PdkOrderCollectionFromWCOrdersAdapter($orderIds))->convert();

        if (empty($shipmentIds) && empty($orderIds)) {
            Messages::showAdminNotice(__('You have not selected any orders!', 'woocommerce-myparcel'));
        } else {
            try {
                switch ($request) {
                    case self::EXPORT_ORDER:
                        $return = $this->exportAccordingToMode($pdkOrderCollection, $orderIds, $print);
                        break;
                    case self::EXPORT_RETURN:
                        $return = $this->exportReturn($pdkOrderCollection);
                        break;
                    case self::GET_LABELS:
                        $return = $this->printLabels($pdkOrderCollection, $offset);
                        break;
                    case self::MODAL_DIALOG:
                        $orderIds = $this->filterOrderDestinations($orderIds);
                        $this->modal_dialog($orderIds);
                        break;
                }
            } catch (Exception $e) {
                $errorMessage = $e->getMessage();
                WCMP_Log::add("$request: {$errorMessage}");
                Messages::showAdminNotice($errorMessage, Messages::NOTICE_LEVEL_ERROR);
            }
        }

        // if we're directed here from modal, show proper result page
        if (isset($_REQUEST['modal'])) {
            $this->modal_success_page($request);
        } else {
            // return JSON response
            echo json_encode($return);
            die();
        }
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
        if (is_string($array) && strpos($array, "[")!==false) {
            $array = json_decode(stripslashes($array));
        }

        return (array) $array;
    }

    /**
     * @param  \MyParcelNL\Pdk\Plugin\Collection\PdkOrderCollection $pdkOrderCollection
     * @param  array                                                $orderIds
     * @param  bool                                                 $process
     *
     * @return array
     * @throws \MyParcelNL\Pdk\Base\Exception\InvalidCastException
     */
    public function addShipments(PdkOrderCollection $pdkOrderCollection, array $orderIds, bool $process): array
    {
        $return          = [];
        $processDirectly = $process
            || WCMYPA()->settingCollection->isEnabled(
                WCMYPA_Settings::SETTING_PROCESS_DIRECTLY
            );

        //$this->logger->log(WCMP_Log::LOG_LEVELS['alert'], '*** Creating shipments started ***');

        $shipmentCollection = $pdkOrderCollection->generateShipments();
        $repository         = Pdk::get(ShipmentRepository::class);
        $concepts           = $repository->createConcepts($shipmentCollection);

        if ($processDirectly) {
            $labelFormat   = WCMP_Settings_Data::getSetting(WCMYPA_Settings::SETTING_LABEL_FORMAT);
            $labelPosition = WCMP_Settings_Data::getSetting(WCMYPA_Settings::SETTING_ASK_FOR_PRINT_POSITION);
            $repository->fetchLabelLink($shipmentCollection, $labelFormat, $labelPosition = null);
        }

        foreach ($orderIds as $order_id) {

            $order       = WCX::get_order($order_id);
            $shipmentIds = $shipmentCollection->pluck('id');
            if (! $shipmentIds) {
                continue;
            }

            $savedShipmentData = $shipmentIds->map(function ($shipmentId) use ($order, $shipmentCollection){
                $shipment = $shipmentCollection->where('id', $shipmentId)->toArray();
                $this->saveShipmentData($order, $shipment);
                return $shipmentId;
            });

            $this->success[$order_id] = $savedShipmentData;

            if ($processDirectly) {
                $this->getShipmentData($shipmentCollection, $order);
            }

            WCMP_API::updateOrderStatus($order, WCMP_Settings_Data::CHANGE_STATUS_AFTER_EXPORT);


            WCX_Order::update_meta_data(
                $order,
                WCMYPA_Admin::META_LAST_SHIPMENT_IDS,
                $shipmentIds->all()
            );
        }

        if (! empty($this->success)) {
            $return["success"]     = sprintf(
                __("%s shipments successfully exported to MyParcel", "woocommerce-myparcel"),
                count($shipmentIds)
            );
            $return["success_ids"] = $shipmentIds->all();

            // do action on successfully exporting the label
            do_action("wcmp_labels_exported", $orderIds);

            WCMP_Log::add($return["success"]);
            WCMP_Log::add("ids: " . implode(", ", $return["success_ids"]));
        }

        return $return;
    }

    /**
     * @param  \MyParcelNL\Pdk\Plugin\Collection\PdkOrderCollection $pdkOrderCollection
     *
     * @return array
     * @throws \Exception
     */
    public function exportReturn(PdkOrderCollection $pdkOrderCollection): array
    {
        WCMP_Log::add('*** Creating return shipments started ***');

        $repository         = Pdk::get(ShipmentRepository::class);
        $shipmentCollection = $pdkOrderCollection->generateShipments();
        $returnShipments    = $repository->createReturnShipments($shipmentCollection);

        $returnShipments->each(function (Shipment $returnShipment) {
            $orderId    = $returnShipment->externalIdentifier;
            $shipmentId = $returnShipment->id;
            $order      = WCX::get_order($orderId);
            $shipment   = ['id' => $shipmentId,];

            $this->saveShipmentData($order, $shipment);
        });

        return [];
    }

    /**
     * @param  \MyParcelNL\Pdk\Shipment\Collection\ShipmentCollection $shipments
     * @param  int                                                    $offset
     *
     * @return array
     */
    public function downloadOrGetUrlOfLabels(
        ShipmentCollection $shipments,
        int                $offset = 0
    ): array {
        WCMP_Log::add('*** downloadOrGetUrlOfLabels() ***');
        WCMP_Log::add('Shipment IDs: ' . implode(', ', $shipments));

        $positions = array_slice(self::DEFAULT_POSITIONS, $offset % 4);
        $displayOverride = WCMYPA()->settingCollection->getByName(WCMYPA_Settings::SETTING_DOWNLOAD_DISPLAY);

        return Pdk::get(ShipmentRepository::class)
            ->fetchLabelLink($shipments, $displayOverride, $positions);
    }

    /**
     * @param  array       $order_ids
     * @param  int         $offset
     * @param  string|null $display
     *
     * @return array
     * @throws Exception
     */
    public function getOrderLabels(array $order_ids, int $offset = 0, string $display = null)
    {
        $shipment_ids = $this->getShipmentIds($order_ids, ["only_last" => true]);

        if (empty($shipment_ids)) {
            WCMP_Log::add(" *** Failed label request(not exported yet) ***");

            throw new Exception(
                __(
                    "The selected orders have not been exported to MyParcel yet! ",
                    "woocommerce-myparcel"
                )
            );
        }

        return $this->downloadOrGetUrlOfLabels(
            $shipment_ids,
            $order_ids,
            $offset,
            $display
        );
    }

    /**
     * @param $order_ids
     */
    public function modal_dialog($order_ids): void
    {
        // check for JSON
        if (is_string($order_ids) && strpos($order_ids, '[')!==false) {
            $order_ids = json_decode(stripslashes($order_ids), false);
        }

        // cast as array for single exports
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
     * @return WCMP_API
     * @throws Exception
     */
    public function init_api()
    {
        $key = WCMP_Settings_Data::getSetting(WCMYPA_Settings::SETTING_API_KEY);

        if (! ($key)) {
            throw new ErrorException(__("No API key found in MyParcel settings", "woocommerce-myparcel"));
        }

        return new WCMP_API($key);
    }

    /**
     * TODO: There are no options being passed right now but these will be necessary for NL.
     *
     * @param             $order_id
     * @param  array|null $options
     *
     * @return array
     * @throws Exception
     */
    public function prepareReturnShipmentData($order_id, ?array $options = []): array
    {
        $order = WCX::get_order($order_id);

        $shipping_name =
            method_exists($order, 'get_formatted_shipping_full_name') ? $order->get_formatted_shipping_full_name()
                : trim($order->get_shipping_first_name() . ' ' . $order->get_shipping_last_name());

        // set name & email
        $return_shipment_data = [
            'parent'  => (int) $order->get_order_number(),
            'name'    => $shipping_name,
            'email'   => WCX_Order::get_prop($order, 'billing_email'),
            'carrier' => PostNLConsignment::CARRIER_ID, // default to PostNL for now
        ];

        if (! Arr::get($return_shipment_data, 'email')) {
            throw new Exception(__('No e-mail address found in order.', 'woocommerce-myparcel'));
        }

        // add options if available
        if (! empty($options)) {
            // convert insurance option
            if (! isset($options['insurance']) && isset($options['insured_amount'])) {
                if ($options['insured_amount'] > 0) {
                    $options['insurance'] = [
                        'amount'   => (int) $options['insured_amount'] * 100,
                        'currency' => ExportRow::CURRENCY_EURO,
                    ];
                }
                unset($options['insured_amount']);
                unset($options['insured']);
            }
            // PREVENT ILLEGAL SETTINGS
            // convert numeric strings to int
            $int_options = ['package_type', 'delivery_type', 'signature', 'return '];
            foreach ($options as $key => &$value) {
                if (in_array($key, $int_options)) {
                    $value = (int) $value;
                }
            }
            // remove frontend insurance option values
            if (isset($options['insured_amount'])) {
                unset($options['insured_amount']);
            }
            if (isset($options['insured'])) {
                unset($options['insured']);
            }

            $return_shipment_data['options'] = $options;
        }

        // get parent
        $shipment_ids = $this->getShipmentIds(
            (array) $order_id,
            [
                'exclude_concepts' => true,
                'only_last'        => true,
            ]
        );

        if (! empty($shipment_ids)) {
            $return_shipment_data['parent'] = (int) array_pop($shipment_ids);
        }

        return $return_shipment_data;
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
        $prefix_message = $prefix_message ? $prefix_message . " " : "";

        $order = WCX::get_order($order_id);
        $order->add_order_note($prefix_message . implode(", ", $track_traces));
    }

    /**
     * @param  array $order_ids
     * @param  array $args
     *
     * @return array
     * @throws \JsonException
     */
    public function getShipmentIds(array $order_ids, array $args): array
    {
        $shipment_ids = [];

        foreach ($order_ids as $order_id) {
            $order           = WCX::get_order($order_id);
            $order_shipments = WCX_Order::get_meta($order, WCMYPA_Admin::META_SHIPMENTS);

            if (empty($order_shipments)) {
                continue;
            }

            $order_shipment_ids = [];
            // exclude concepts or only concepts
            foreach ($order_shipments as $shipment_id => $shipment) {
                if (isset($args["exclude_concepts"]) && empty($shipment["track_trace"])) {
                    continue;
                }
                if (isset($args["only_concepts"]) && ! empty($shipment["track_trace"])) {
                    continue;
                }

                $order_shipment_ids[] = $shipment_id;
            }

            if (isset($args["only_last"])) {
                $last_shipment_ids = WCX_Order::get_meta($order, WCMYPA_Admin::META_LAST_SHIPMENT_IDS);

                if (! empty($last_shipment_ids) && is_array($last_shipment_ids)) {
                    foreach ($order_shipment_ids as $order_shipment_id) {
                        if (in_array($order_shipment_id, $last_shipment_ids)) {
                            $shipment_ids[] = $order_shipment_id;
                        }
                    }
                } else {
                    $shipment_ids[] = array_pop($order_shipment_ids);
                }
            } else {
                $shipment_ids[] = array_merge($shipment_ids, $order_shipment_ids);
            }
        }

        return $shipment_ids;
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
            throw new Exception('save_shipment_data requires a valid shipment');
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

        return (string) $shippingMethodId;
    }

    /**
     * Determine appropriate package type for this order.
     *
     * @param  WC_Order                            $order
     * @param  AbstractDeliveryOptionsAdapter|null $deliveryOptions
     *
     * @return string
     * @throws Exception
     */
    public function getPackageTypeFromOrder(WC_Order                       $order,
                                            $deliveryOptions = null
    ): string {
        $packageTypeFromDeliveryOptions = $deliveryOptions ? $deliveryOptions->getPackageType() : null;
        $allowedPackageType             = $this->getAllowedPackageType($order, $packageTypeFromDeliveryOptions);

        if ($allowedPackageType) {
            return apply_filters("wc_myparcel_order_package_type", $allowedPackageType, $order, $this);
        }

        // Get pre 4.0.0 package type if it exists.
        if (WCX_Order::has_meta($order, WCMYPA_Admin::META_SHIPMENT_OPTIONS_LT_4_0_0)) {
            $shipmentOptions = WCX_Order::get_meta($order, WCMYPA_Admin::META_SHIPMENT_OPTIONS_LT_4_0_0);

            if (isset($shipmentOptions['package_type'])) {
                $packageType = Data::getPackageTypeId($shipmentOptions['package_type']);
            }

            return (string) apply_filters(
                "wc_myparcel_order_package_type",
                ($packageType ?? AbstractConsignment::DEFAULT_PACKAGE_TYPE),
                $order,
                $this
            );
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
            "wc_myparcel_order_package_type",
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

        if (Str::startsWith($shippingMethod, 'table_rate:') && class_exists('WC_Table_Rate_Shipping')) {
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

        return $packageType ?? __("Unknown", "woocommerce-myparcel");
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
            WCMP_Log::add(new Exception("Tried to convert invalid value to package type: $packageType ($type)"));

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
        $shippingCountry      = WCX_Order::get_prop($order, "shipping_country");
        $isMailbox            = AbstractConsignment::PACKAGE_TYPE_MAILBOX_NAME===$packageType;
        $isDigitalStamp       = AbstractConsignment::PACKAGE_TYPE_DIGITAL_STAMP_NAME===$packageType;
        $isDefaultPackageType = AbstractConsignment::CC_NL!==$shippingCountry && ($isMailbox || $isDigitalStamp);

        if ($isDefaultPackageType) {
            $packageType = AbstractConsignment::DEFAULT_PACKAGE_TYPE_NAME;
        }

        return $packageType;
    }

    public function getShipmentStatusName($status_code)
    {
        $shipment_statuses = [
            1  => __("pending - concept", "woocommerce-myparcel"),
            2  => __("pending - registered", "woocommerce-myparcel"),
            3  => __("enroute - handed to carrier", "woocommerce-myparcel"),
            4  => __("enroute - sorting", "woocommerce-myparcel"),
            5  => __("enroute - distribution", "woocommerce-myparcel"),
            6  => __("enroute - customs", "woocommerce-myparcel"),
            7  => __("delivered - at recipient", "woocommerce-myparcel"),
            8  => __("delivered - ready for pickup", "woocommerce-myparcel"),
            9  => __("delivered - package picked up", "woocommerce-myparcel"),
            12 => __("printed - letter", "woocommerce-myparcel"),
            14 => __("printed - digital stamp", "woocommerce-myparcel"),
            30 => __("inactive - concept", "woocommerce-myparcel"),
            31 => __("inactive - registered", "woocommerce-myparcel"),
            32 => __("inactive - enroute - handed to carrier", "woocommerce-myparcel"),
            33 => __("inactive - enroute - sorting", "woocommerce-myparcel"),
            34 => __("inactive - enroute - distribution", "woocommerce-myparcel"),
            35 => __("inactive - enroute - customs", "woocommerce-myparcel"),
            36 => __("inactive - delivered - at recipient", "woocommerce-myparcel"),
            37 => __("inactive - delivered - ready for pickup", "woocommerce-myparcel"),
            38 => __("inactive - delivered - package picked up", "woocommerce-myparcel"),
            99 => __("inactive - unknown", "woocommerce-myparcel"),
        ];

        if (isset($shipment_statuses[$status_code])) {
            return $shipment_statuses[$status_code];
        }

        return __("Unknown status", "woocommerce-myparcel");
    }

    /**
     * Retrieves, updates and returns shipment data for given id.
     *
     * @param  \MyParcelNL\Pdk\Shipment\Collection\ShipmentCollection $shipmentCollection
     * @param  WC_Order                                               $order
     *
     * @return array
     * @throws \MyParcelNL\Pdk\Base\Exception\InvalidCastException
     * @throws \Exception
     */
    public function getShipmentData(ShipmentCollection $shipmentCollection, WC_Order $order): array
    {
        if ($shipmentCollection->isEmpty()) {
            return [];
        }

        foreach ($shipmentCollection->all() as $shipment) {
            $this->saveShipmentData($order, $shipment->toArray());
            ChannelEngine::updateMetaOnExport($order, $shipment->getAttribute('barcode') ?: $shipment->getAttribute('external_identifier'));
        }

        return $shipmentCollection->toArray();
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
            $shipping_class_term    = get_term_by("slug", $shipping_class, "product_shipping_class");
            $shipping_class_term_id = "";

            if ($shipping_class_term!=null) {
                $shipping_class_term_id = $shipping_class_term->term_id;
            }

            $class_cost_string = $shipping_class_term && $shipping_class_term_id ? $shipping_method->get_option(
                "class_cost_" . $shipping_class_term_id,
                $shipping_method->get_option("class_cost_" . $shipping_class, "")
            ) : $shipping_method->get_option("no_class_cost", "");

            if ($class_cost_string==="") {
                continue;
            }

            $class_cost = $this->wc_flat_rate_evaluate_cost(
                $class_cost_string,
                [
                    "qty"  => array_sum(wp_list_pluck($products, "quantity")),
                    "cost" => array_sum(wp_list_pluck($products, "line_total")),
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
        if (version_compare(WOOCOMMERCE_VERSION, "2.6", ">=")) {
            include_once(WC()->plugin_path() . "/includes/libraries/class-wc-eval-math.php");
        } else {
            include_once(WC()->plugin_path() . "/includes/shipping/flat-rate/includes/class-wc-eval-math.php");
        }

        // Allow 3rd parties to process shipping cost arguments
        $args           = apply_filters("woocommerce_evaluate_shipping_cost_args", $args, $sum, $flat_rate_method);
        $locale         = localeconv();
        $decimals       = [
            wc_get_price_decimal_separator(),
            $locale["decimal_point"],
            $locale["mon_decimal_point"],
            ",",
        ];
        $this->fee_cost = $args["cost"];

        // Expand shortcodes
        add_shortcode("fee", [$this, "wc_flat_rate_fee"]);

        $sum = do_shortcode(
            str_replace(
                ["[qty]", "[cost]"],
                [$args["qty"], $args["cost"]],
                $sum
            )
        );

        remove_shortcode("fee");

        // Remove whitespace from string
        $sum = preg_replace("/\s+/", "", $sum);

        // Remove locale from string
        $sum = str_replace($decimals, ".", $sum);

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
    public function wc_flat_rate_fee($atts)
    {
        $atts = shortcode_atts(
            [
                "percent" => "",
                "min_fee" => "",
                "max_fee" => "",
            ],
            $atts
        );

        $calculated_fee = 0;

        if ($atts["percent"]) {
            $calculated_fee = $this->fee_cost * (floatval($atts["percent"]) / 100);
        }

        if ($atts["min_fee"] && $calculated_fee < $atts["min_fee"]) {
            $calculated_fee = $atts["min_fee"];
        }

        if ($atts["max_fee"] && $calculated_fee > $atts["max_fee"]) {
            $calculated_fee = $atts["max_fee"];
        }

        return $calculated_fee;
    }

    /**
     * Filter out orders shipping to country codes that are not in the allowed list.
     *
     * @param  array $order_ids
     *
     * @return array
     * @throws Exception
     */
    public function filterOrderDestinations(array $order_ids): array
    {
        foreach ($order_ids as $key => $order_id) {
            $order            = WCX::get_order($order_id);
            $shipping_country = WCX_Order::get_prop($order, 'shipping_country');

            if (! CountryCodes::isAllowedDestination($shipping_country)) {
                unset($order_ids[$key]);
                Messages::showAdminNotice(
                    sprintf(__('error_order_has_invalid_shipment_country', 'woocommerce-myparcel'), $order_id),
                    Messages::NOTICE_LEVEL_ERROR
                );
            }
        }

        return $order_ids;
    }

    /**
     * @param  int                 $colloAmount
     * @param  MyParcelCollection  $collection
     * @param  AbstractConsignment $consignment
     *
     * @throws MissingFieldException
     */
    public function addFakeMultiCollo(int                 $colloAmount,
                                      MyParcelCollection  $collection,
                                      AbstractConsignment $consignment
    ): void {
        for ($i = 1; $i <= $colloAmount; $i++) {
            $collection->addConsignment($consignment);
        }
    }

    /**
     * @param  \MyParcelNL\WooCommerce\includes\adapter\PdkOrderFromWCOrderAdapter $pdkOrderAdapter
     * @param  MyParcelCollection                                                  $collection
     * @param  AbstractConsignment                                                 $consignment
     *
     * @throws \JsonException
     * @throws \MyParcelNL\Sdk\src\Exception\MissingFieldException
     */
    public function addMultiCollo(
        PdkOrderFromWCOrderAdapter $pdkOrderAdapter,
        MyParcelCollection         $collection,
        AbstractConsignment        $consignment
    ): void {
        $pdkOrder             = $pdkOrderAdapter->getPdkOrder();
        $isPackage            = AbstractConsignment::PACKAGE_TYPE_PACKAGE_NAME === $pdkOrder->deliveryOptions->packageType;
        $hasCarrierMultiCollo = $consignment->canHaveExtraOption(AbstractConsignment::EXTRA_OPTION_MULTI_COLLO);
        $isMultiColloCountry  = in_array(
            $pdkOrder->recipient->cc,
            [self::COUNTRY_CODE_NL, self::COUNTRY_CODE_BE],
            true
        );

        if ($isMultiColloCountry && $hasCarrierMultiCollo && $isPackage) {
            $collection->addMultiCollo($consignment, $pdkOrderAdapter->getColloAmount());
            return;
        }

        $this->addFakeMultiCollo($pdkOrderAdapter->getColloAmount(), $collection, $consignment);
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
    ) {
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
        $newShippingClass = str_replace(':', '_', $shipping_class);
        if (! empty($shipping_class) && in_array($newShippingClass, $package_type_shipping_methods, true)) {
            return true;
        }

        return false;
    }

    /**
     * @param  \MyParcelNL\Pdk\Plugin\Collection\PdkOrderCollection $pdkOrderCollection
     * @param  int                                                  $offset
     *
     * @return array
     * @throws \Exception
     */
    private function printLabels(
        PdkOrderCollection $pdkOrderCollection,
        int                $offset
    ): array {
        $pdkOrderCollection->generateShipments();
        return $this->downloadOrGetUrlOfLabels(
            $pdkOrderCollection->getAllShipments(),
            $offset
        );
    }

    /**
     * @param  \MyParcelNL\Pdk\Plugin\Collection\PdkOrderCollection $pdkOrderCollection
     * @param  array                                                $orderIds
     * @param  string | null                                        $print
     *
     * @return array
     * @throws \MyParcelNL\Pdk\Base\Exception\InvalidCastException
     */
    private function exportAccordingToMode(PdkOrderCollection $pdkOrderCollection, array $orderIds, ?string $print): array
    {
        $exportMode = WCMP_Settings_Data::getSetting(WCMYPA_Settings::SETTING_EXPORT_MODE);
        $orderIds   = $this->filterOrderDestinations($orderIds);
        $print      = $print ?? self::NO;

        if (WCMP_Settings_Data::EXPORT_MODE_PPS === $exportMode) {
            $return = $this->saveOrderCollection($pdkOrderCollection, $orderIds);
        } else {
            // if we're going to print directly, we need to process the orders first, regardless of the settings
            $process = (self::YES === $print);
            $return  = $this->addShipments($pdkOrderCollection, $orderIds, $process);
        }

        return $this->setFeedbackForClient($return ?? []);
    }

    /**
     * @param  \MyParcelNL\Pdk\Plugin\Collection\PdkOrderCollection $pdkOrderCollection
     * @param  array                                                $orderIds
     *
     * @return array
     * @throws \Exception
     */
    private function saveOrderCollection(PdkOrderCollection $pdkOrderCollection, array $orderIds): array
    {
        $repository                = Pdk::get(OrderRepository::class);
        $pdkOrderCollection->generateShipments();
        $fulfilmentOrderCollection = $pdkOrderCollection->getOrderCollection();

        try {
            $savedOrderCollection = $repository->saveOrder($fulfilmentOrderCollection);

            return $this->updateOrderMetaByCollection($savedOrderCollection);
        } catch (Exception $e) {
            Messages::showAdminNotice($e->getMessage());
            return [];
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

        $orderCollection->each(static function ($order) use ($currentDateTime){
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

            WCMP_API::updateOrderStatus($wcOrder, WCMP_Settings_Data::CHANGE_STATUS_AFTER_EXPORT);
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
     * @param  array $return
     *
     * @return array
     */
    private function setFeedbackForClient(array $return): array
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

return new WCMP_Export();
