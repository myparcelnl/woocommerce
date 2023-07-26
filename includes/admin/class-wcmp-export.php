<?php

declare(strict_types=1);

use MyParcelNL\Sdk\src\Adapter\DeliveryOptions\AbstractDeliveryOptionsAdapter;
use MyParcelNL\Sdk\src\Collection\Fulfilment\OrderCollection;
use MyParcelNL\Sdk\src\Collection\Fulfilment\OrderNotesCollection;
use MyParcelNL\Sdk\src\Exception\ApiException;
use MyParcelNL\Sdk\src\Exception\MissingFieldException;
use MyParcelNL\Sdk\src\Helper\MyParcelCollection;
use MyParcelNL\Sdk\src\Model\Carrier\AbstractCarrier;
use MyParcelNL\Sdk\src\Model\Carrier\CarrierFactory;
use MyParcelNL\Sdk\src\Model\Consignment\AbstractConsignment;
use MyParcelNL\Sdk\src\Model\Consignment\DropOffPoint;
use MyParcelNL\Sdk\src\Model\CustomsDeclaration;
use MyParcelNL\Sdk\src\Model\Fulfilment\AbstractOrder;
use MyParcelNL\Sdk\src\Model\Fulfilment\Order;
use MyParcelNL\Sdk\src\Model\Fulfilment\OrderNote;
use MyParcelNL\Sdk\src\Model\MyParcelCustomsItem;
use MyParcelNL\Sdk\src\Support\Arr;
use MyParcelNL\Sdk\src\Support\Collection;
use MyParcelNL\Sdk\src\Support\Str;
use MyParcelNL\WooCommerce\Helper\ExportRow;
use MyParcelNL\WooCommerce\Helper\LabelDescriptionFormat;
use MyParcelNL\WooCommerce\Includes\Adapter\OrderLineFromWooCommerce;
use MyParcelNL\WooCommerce\includes\admin\Messages;
use MyParcelNL\WooCommerce\includes\admin\OrderSettings;
use MyParcelNL\WooCommerce\includes\Settings\Api\AccountSettings;
use WPO\WC\MyParcel\Compatibility\Order as WCX_Order;
use WPO\WC\MyParcel\Compatibility\WC_Core as WCX;
use WPO\WC\MyParcel\Compatibility\WCMP_ChannelEngine_Compatibility as ChannelEngine;

if (! defined('ABSPATH')) {
    exit;
} // Exit if accessed directly

if (class_exists('WCMP_Export')) {
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
    public const SUFFIX_CHECK_REG  = '~^([a-z]{1}\d{1,3}|-\d{1,4}\d{2}\w{1,2}|[a-z]{1}[a-z\s]{0,3})(?:\W|$)~i';

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

    /**
     * @var MyParcelCollection
     */
    public $myParcelCollection;

    public $order_id;

    public $success;

    /**
     * @var \MyParcelNL\Sdk\src\Collection\Fulfilment\OrderCollection
     */
    private $orderCollection;

    private $prefix_message;

    public function __construct()
    {
        $this->success = [];

        require_once('class-wcmp-rest.php');
        require_once('class-wcmp-api.php');

        add_action('wp_ajax_' . self::EXPORT, [$this, 'export']);
    }

    /**
     * @param int $order_id
     * @param array $track_traces
     *
     * @internal param $shipment_ids
     */
    public static function addTrackTraceNoteToOrder(int $order_id, array $track_traces): void
    {
        if (! WCMYPA()->setting_collection->isEnabled(WCMYPA_Settings::SETTING_BARCODE_IN_NOTE)) {
            return;
        }

        $prefix_message = WCMYPA()->setting_collection->getByName(WCMYPA_Settings::SETTING_BARCODE_IN_NOTE_TITLE);

        // Select the barcode text of the MyParcel settings
        $prefix_message = $prefix_message ? $prefix_message . ' ' : '';

        $order = WCX::get_order($order_id);
        $order->add_order_note($prefix_message . implode(', ', $track_traces));
    }

    /**
     * @param  int $size
     *
     * @return int
     */
    public static function convertSizeToCm(int $size): int
    {
        $sizeUnit = get_option('woocommerce_dimension_unit');
        $floatSize = (float) $size;

        switch ($sizeUnit) {
            case 'm':
                $result = $floatSize * 100;
                break;
            case 'mm':
                $result = $floatSize / 10;
                break;
            case 'inch':
                $result = $floatSize * 2.54;
                break;
            case 'yd':
                $result = $floatSize * 91.44;
                break;
            default:
                $result = $floatSize;
                break;
        }

        return (int) ceil($result);
    }

    /**
     * Returns the weight in grams.
     *
     * @param int|float $weight
     *
     * @return int
     */
    public static function convertWeightToGrams($weight): int
    {
        $weightUnit  = get_option('woocommerce_weight_unit');
        $floatWeight = (float) $weight;

        switch ($weightUnit) {
            case 'kg':
                $weight = $floatWeight * 1000;
                break;
            case 'lbs':
                $weight = $floatWeight * 453.59237;
                break;
            case 'oz':
                $weight = $floatWeight * 28.34952;
                break;
            default:
                $weight = $floatWeight;
                break;
        }

        return (int) ceil($weight);
    }

    /**
     * Get the value of a shipment option. Check if it was set manually, through the delivery options for example,
     *  if not get the value of the default export setting for given settingName.
     *
     * @param mixed  $option      Chosen value.
     * @param string $settingName Name of the setting to fall back to if there is no chosen value.
     *
     * @return mixed
     */
    public static function getChosenOrDefaultShipmentOption($option, string $settingName, ?string $carrierName = null)
    {
        return $option ?? WCMYPA()->setting_collection->where('carrier', $carrierName)->getByName($settingName);
    }

    /**
     * @return array
     */
    public static function getDigitalStampRangeOptions(): array
    {
        $options = [];

        foreach (WCMP_Data::getDigitalStampRanges() as $key => $tierRange) {
            $options[$tierRange['average']] = $tierRange['min'] . ' - ' . $tierRange['max'] . ' gram';
        }

        return $options;
    }

    /**
     * Will convert any package type to a valid string package type.
     *
     * @param mixed $packageType
     *
     * @return string
     */
    public static function getPackageTypeAsString($packageType): string
    {
        if (is_numeric($packageType)) {
            $packageType = WCMP_Data::getPackageTypeName($packageType);
        }

        if (! is_string($packageType) || ! in_array($packageType, WCMP_Data::getPackageTypes())) {
            // Log data when this occurs but don't actually throw an exception.
            $type = gettype($packageType);
            WCMP_Log::add("Tried to convert invalid value to package type: $packageType ($type)");

            $packageType = null;
        }

        return $packageType ?? AbstractConsignment::DEFAULT_PACKAGE_TYPE_NAME;
    }

    /**
     * @param $shippingMethod
     * @param $shippingClass
     *
     * @return string
     */
    public static function getPackageTypeFromShippingMethod($shippingMethod, $shippingClass): string
    {
        $shippingMethod        = (string) $shippingMethod;
        $shippingClass         = (string) $shippingClass;
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

        $packageTypes = WCMYPA()->setting_collection->getByName(WCMYPA_Settings::SETTING_SHIPPING_METHODS_PACKAGE_TYPES);

        if (! $packageTypes) {
            return $packageType;
        }

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
     * @param string|int|null $packageType
     *
     * @return string
     */
    public static function getPackageTypeHuman($packageType): string
    {
        if ($packageType) {
            $packageType = WCMP_Data::getPackageTypeHuman($packageType);
        }

        return $packageType ?? __('Unknown', 'woocommerce-myparcel');
    }

    /**
     * @param  string $chosenMethod
     *
     * @return null|bool|\WC_Shipping_Method
     */
    public static function getShippingMethod(string $chosenMethod)
    {
        if ($chosenMethod === WCMP_Shipping_Methods::LEGACY_FLAT_RATE) {
            return self::getLegacyShippingMethod($chosenMethod);
        }

        [$methodSlug, $methodInstance] = WCMP_Checkout::splitShippingMethodString($chosenMethod);

        $isDisallowedShippingMethod = in_array($methodSlug, self::DISALLOWED_SHIPPING_METHODS, true);
        $isManualOrder              = empty($methodInstance);

        if ($isDisallowedShippingMethod || $isManualOrder) {
            return null;
        }

        return WC_Shipping_Zones::get_shipping_method($methodInstance) ?? null;
    }

    /**
     * @return array
     */
    public static function getUserAgents(): array
    {
        return [
            'MyParcelNL-WooCommerce' => WCMYPA::getInstance()->version,
            'WooCommerce'            => WooCommerce::instance()->version,
            'Wordpress'              => get_bloginfo('version'),
        ];
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
        if ($product && isset($item['variation_id']) && $item['variation_id'] > 0 && method_exists($product, 'get_variation_attributes')) {
            $name .= woocommerce_get_formatted_variation($product->get_variation_attributes());
        }

        return $name;
    }

    /**
     * @param $value
     *
     * @return int|string|array
     */
    public static function sanitizeOptions($value)
    {
        if (is_string($value)) {
            return sanitize_text_field($value);
        }

        if (is_array($value)) {
            return array_map(['self', 'sanitizeOptions'], $value);
        }

        return (int) $value;
    }

    /**
     * @param string $chosen_method
     *
     * @return null|WC_Shipping_Method
     */
    private static function getLegacyShippingMethod(string $chosen_method): ?WC_Shipping_Method
    {
        // only for flat rate or legacy flat rate
        if (! in_array(
            $chosen_method,
            [
                WCMP_Shipping_Methods::FLAT_RATE,
                WCMP_Shipping_Methods::LEGACY_FLAT_RATE,
            ]
        )) {
            return null;
        }

        $shipping_methods = WC()->shipping()->load_shipping_methods();

        if (! isset($shipping_methods[$chosen_method])) {
            return null;
        }

        return $shipping_methods[$chosen_method];
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
        if (in_array($shipping_method_id, $package_type_shipping_methods)) {
            return true;
        }

        if (in_array($shipping_method_id_class, $package_type_shipping_methods)) {
            return true;
        }

        // fallback to bare method (without class) (if bare method also defined in settings)
        if (! empty($shipping_method_id_class)
            && in_array(
                $shipping_method_id_class,
                $package_type_shipping_methods
            )) {
            return true;
        }

        // support WooCommerce Table Rate Shipping by WooCommerce
        if (! empty($shipping_class) && in_array($shipping_class, $package_type_shipping_methods)) {
            return true;
        }

        // support WooCommerce Table Rate Shipping by Bolder Elements
        $newShippingClass = str_replace(':', '_', $shipping_class);
        if (! empty($shipping_class) && in_array($newShippingClass, $package_type_shipping_methods)) {
            return true;
        }

        return false;
    }

    /**
     * @param int                 $colloAmount
     * @param MyParcelCollection  $collection
     * @param AbstractConsignment $consignment
     *
     * @throws MissingFieldException
     */
    public function addFakeMultiCollo(int $colloAmount, MyParcelCollection $collection, AbstractConsignment $consignment): void
    {
        for ($i = 1; $i <= $colloAmount; $i++) {
            $collection->addConsignment($consignment);
        }
    }

    /**
     * @param  \MyParcelNL\WooCommerce\includes\admin\OrderSettings $orderSettings
     * @param  MyParcelCollection                                   $collection
     * @param  AbstractConsignment                                  $consignment
     *
     * @throws \MyParcelNL\Sdk\src\Exception\MissingFieldException
     */
    public function addMultiCollo(
        OrderSettings $orderSettings,
        MyParcelCollection $collection,
        AbstractConsignment $consignment
    ): void
    {
        $isPackage            = AbstractConsignment::PACKAGE_TYPE_PACKAGE_NAME === $orderSettings->getPackageType();
        $hasCarrierMultiCollo = $consignment->canHaveExtraOption(AbstractConsignment::EXTRA_OPTION_MULTI_COLLO);
        $isMultiColloCountry  = in_array(
            $orderSettings->getShippingCountry(),
            [self::COUNTRY_CODE_NL, self::COUNTRY_CODE_BE]
        );

        if ($isMultiColloCountry && $hasCarrierMultiCollo && $isPackage) {
            $collection->addMultiCollo($consignment, $orderSettings->getColloAmount());
            return;
        }

        $this->addFakeMultiCollo($orderSettings->getColloAmount(), $collection, $consignment);
    }

    /**
     * @param  \MyParcelNL\Sdk\src\Helper\MyParcelCollection  $collection
     *
     * @throws \MyParcelNL\Sdk\src\Exception\AccountNotActiveException
     * @throws \MyParcelNL\Sdk\src\Exception\ApiException
     * @throws \MyParcelNL\Sdk\src\Exception\MissingFieldException
     */
    public function addReturnInTheBox(MyParcelCollection $collection): void
    {
        $returnOptions = WCMYPA()->setting_collection->getByName(WCMYPA_Settings::SETTING_RETURN_IN_THE_BOX);

        if (! $returnOptions || WCMP_Settings_Data::NOT_ACTIVE === $returnOptions) {
            return;
        }

        $collection
            ->generateReturnConsignments(
                false,
                function (
                    AbstractConsignment $returnConsignment,
                    AbstractConsignment $parent
                ) use ($returnOptions): AbstractConsignment {
                    $returnConsignment->setLabelDescription(
                        'Return: ' . $parent->getLabelDescription() .
                        ' This label is valid until: ' . date('d-m-Y', strtotime('+ 28 days'))
                    );

                    if (WCMP_Settings_Data::NO_OPTIONS === $returnOptions) {
                        $returnConsignment->setOnlyRecipient(false);
                        $returnConsignment->setSignature(false);
                        $returnConsignment->setAgeCheck(false);
                        $returnConsignment->setReturn(false);
                        $returnConsignment->setLargeFormat(false);
                        $returnConsignment->setInsurance(0);
                    }

                    return $returnConsignment;
                }
            );
    }

    /**
     * @param  array $order_ids
     * @param  bool  $process
     *
     * @return array
     * @throws ApiException
     * @throws MissingFieldException
     * @throws \MyParcelNL\Sdk\src\Exception\AccountNotActiveException
     * @throws \Exception
     */
    public function addShipments(array $order_ids, bool $process): array
    {
        $return          = [];
        $collection      = new MyParcelCollection();
        $processDirectly = $process || WCMYPA()->setting_collection->isEnabled(WCMYPA_Settings::SETTING_PROCESS_DIRECTLY);

        WCMP_Log::add('*** Creating shipments started ***');

        /**
         * Loop over the order ids and create consignments for each order.
         */
        foreach ($order_ids as $order_id) {
            $order = WCX::get_order($order_id);

            try {
                $exportConsignments = new WCMP_Export_Consignments($order);
                $exportConsignments->validate();
                $consignment        = $exportConsignments->getConsignment();
            } catch (Exception $ex) {
                $errorMessage = sprintf(
                    __('error_export_order_id_failed_because', 'woocommerce-myparcel'),
                    $order_id, __($ex->getMessage(), 'woocommerce-myparcel')
                );
                Messages::showAdminNotice($errorMessage, Messages::NOTICE_LEVEL_ERROR);
                WCMP_Log::add($errorMessage);
                unset($order_ids[$order_id]);

                continue;
            }

            $this->addConsignments($exportConsignments->getOrderSettings(), $collection, $consignment);
            WCMP_Log::add("Shipment data for order {$order_id}.");
        }

        $this->addReturnInTheBox($collection);

        if (0 === count($collection)) {
            WCMP_Log::add('No shipments exported to MyParcel.');

            return ['error' => __(
                'error_no_shipments_created',
                'woocommerce-myparcel'
            )];
        }

        $collection = $collection
            ->setUserAgents(self::getUserAgents())
            ->createConcepts();

        if ($processDirectly) {
            $collection->setLinkOfLabels();
        }

        foreach ($order_ids as $order_id) {
            $order          = WCX::get_order($order_id);
            $consignmentIds = ($collection->getConsignmentsByReferenceIdGroup($order_id))->getConsignmentIds();
            if (! $consignmentIds) {
                continue;
            }

            foreach ($consignmentIds as $consignmentId) {
                $shipment['shipment_id'] = $consignmentId;
                $this->saveShipmentData($order, $shipment);
                $this->success[$order_id] = $consignmentId;
            }

            if ($processDirectly) {
                $this->getShipmentData($consignmentIds, $order);
            }

            WCMP_API::updateOrderStatus($order, WCMP_Settings_Data::CHANGE_STATUS_AFTER_EXPORT);

            WCX_Order::update_meta_data(
                $order,
                WCMYPA_Admin::META_LAST_SHIPMENT_IDS,
                $consignmentIds
            );
        }

        if (! empty($this->success)) {
            $return['success']     = sprintf(
                __('%s shipments successfully exported to MyParcel', 'woocommerce-myparcel'),
                count($collection->getConsignmentIds())
            );
            $return['success_ids'] = $collection->getConsignmentIds();

            // do action on successfully exporting the label
            do_action('wcmp_labels_exported', $order_ids);

            WCMP_Log::add($return['success']);
            WCMP_Log::add('ids: ' . implode(', ', $return['success_ids']));
        }

        return $return;
    }

    /**
     * @param array       $shipment_ids
     * @param array       $order_ids
     * @param int         $offset
     * @param string|null $displayOverride - Overrides display setting.
     *
     * @return array
     * @throws Exception
     */
    public function downloadOrGetUrlOfLabels(
        array $shipment_ids,
        array $order_ids = [],
        int $offset = 0,
        string $displayOverride = null
    ) {
        $return = [];

        WCMP_Log::add('*** downloadOrGetUrlOfLabels() ***');
        WCMP_Log::add('Shipment IDs: ' . implode(', ', $shipment_ids));

        try {
            $api = $this->init_api();

            // positions are defined on landscape, but paper is filled portrait-wise
            $positions = array_slice(self::DEFAULT_POSITIONS, $offset % 4);

            $displaySetting = WCMYPA()->setting_collection->getByName(WCMYPA_Settings::SETTING_DOWNLOAD_DISPLAY);
            $display        = ($displayOverride ?? $displaySetting) === 'display';
            $api->getShipmentLabels($shipment_ids, $order_ids, $positions, $display);
        } catch (Exception $e) {
            Messages::showAdminNotice($e->getMessage());
            throw new RuntimeException($e->getMessage());
        }

        return $return;
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
        if (! check_ajax_referer(WCMYPA::NONCE_ACTION, '_wpnonce', false)) {
            die("Ajax security check failed. Did you pass a valid nonce in \$_REQUEST['_wpnonce']?");
        }

        if (! is_user_logged_in()) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'woocommerce-myparcel'));
        }

        $return = [];

        // Check the user privileges (maybe use order ids for filter?)
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

        $requestVars = array_merge(
            filter_input_array(INPUT_GET) ?? [],
            filter_input_array(INPUT_POST) ?? []
        );
        $print       = sanitize_text_field($requestVars['print'] ?? null);
        $offset      = (int) ($requestVars['offset'] ?? 0);
        $request     = sanitize_text_field($requestVars['request']);

        /**
         * @var $order_ids
         */
        $order_ids    = $this->onlyIntegersInArray($requestVars['order_ids'] ?? []);
        $shipment_ids = $this->onlyIntegersInArray($requestVars['shipment_ids'] ?? []);

        foreach ($order_ids as $key => $id) {
            $order         = WCX::get_order($id);
            $orderSettings = new OrderSettings($order);

            if ($orderSettings->hasLocalPickup()) {
                unset($order_ids[$key]);
            }
        }

        if (empty($shipment_ids) && empty($order_ids)) {
            Messages::showAdminNotice(__('You have not selected any orders!', 'woocommerce-myparcel'));
        } else {
            try {
                switch ($request) {
                    // Creating consignments.
                    case self::EXPORT_ORDER:
                        $return = $this->exportAccordingToMode($order_ids, $offset, $print);
                        break;

                    // Creating a return shipment.
                    case self::EXPORT_RETURN:
                        $options = array_map([$this, 'sanitizeOptions'], $requestVars['myparcel_options'] ?? []);
                        $return = $this->exportReturn($order_ids, $options);
                        break;

                    // Downloading labels.
                    case self::GET_LABELS:
                        $return = $this->printLabels($order_ids, $shipment_ids, $offset);
                        break;

                    case self::MODAL_DIALOG:
                        $order_ids = $this->filterOrderDestinations($order_ids);
                        $this->modal_dialog($order_ids);
                        break;
                }
            } catch (Exception $e) {
                $errorMessage = $e->getMessage();
                WCMP_Log::add("$request: {$errorMessage}");
                Messages::showAdminNotice($errorMessage, Messages::NOTICE_LEVEL_ERROR);
            }
        }

        // if we're directed here from modal, show proper result page
        if (isset($requestVars['modal'])) {
            $this->modal_success_page($request, $return);
        } else {
            // return JSON response
            echo json_encode($return);
            die();
        }
    }

    /**
     * @param int $orderId
     *
     * @throws ApiException
     * @throws ErrorException
     * @throws MissingFieldException
     */
    public function exportByOrderId(int $orderId): void
    {
        if (! $orderId) {
            return;
        }

        $return = $this->exportAccordingToMode([(string) $orderId], 0, self::NO);

        if (isset($return['success'])) {
            $order = WCX::get_order($orderId);
            $order->add_order_note($return['success']);
        }
    }

    /**
     * @param array      $order_ids
     * @param array|null $options
     *
     * @return array
     */
    public function exportReturn(array $order_ids, ?array $options = []): array
    {
        $return = [];

        WCMP_Log::add('*** Creating return shipments started ***');

        foreach ($order_ids as $order_id) {
            try {
                $return_shipments = [
                    $this->prepareReturnShipmentData(
                        $order_id,
                        $options
                            ? $options[$order_id]
                            : null
                    ),
                ];

                WCMP_Log::add("Return shipment data for order {$order_id}:", print_r($return_shipments, true));

                $api      = $this->init_api();
                $response = $api->add_shipments($return_shipments, 'return');

                WCMP_Log::add("API response (order {$order_id}):\n" . print_r($response, true));

                $ids = Arr::get($response, 'body.data.ids');

                if ($ids) {
                    $order                    = WCX::get_order($order_id);
                    $ids                      = array_shift($response['body']['data']['ids']);
                    $shipment_id              = $ids['id'];
                    $this->success[$order_id] = $shipment_id;

                    $shipment = [
                        'shipment_id' => $shipment_id,
                    ];

                    // save shipment data in order meta
                    $this->saveShipmentData($order, $shipment);
                } else {
                    WCMP_Log::add("\$response\[\"body.data.ids\"] empty or not found.", print_r($response, true));
                    throw new Exception("\$response\[\"body.data.ids\"] empty or not found.");
                }
            } catch (Exception $e) {
                $errorMessage = $e->getMessage();
                Messages::showAdminNotice($errorMessage, Messages::NOTICE_LEVEL_ERROR);
            }
        }

        return $return;
    }

    /**
     * Filter out orders shipping to country codes that are not in the allowed list.
     *
     * @param array $order_ids
     *
     * @return array
     * @throws Exception
     */
    public function filterOrderDestinations(array $order_ids): array
    {
        foreach ($order_ids as $key => $order_id) {
            $order            = WCX::get_order($order_id);
            $shipping_country = WCX_Order::get_prop($order, 'shipping_country');

            if (! WCMP_Country_Codes::isAllowedDestination($shipping_country)) {
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
     * @throws \MyParcelNL\Sdk\src\Exception\MissingFieldException
     * @throws \ErrorException
     * @throws \JsonException
     */
    public function generateCustomsDeclaration(WC_Order $wcOrder): CustomsDeclaration
    {
        $customsDeclaration = new CustomsDeclaration();
        $contents           = (int) ($this->getSetting('package_contents') ?? AbstractConsignment::PACKAGE_CONTENTS_COMMERCIAL_GOODS);
        $orderSettings      = new OrderSettings($wcOrder);
        $totalWeight        = WCMP_Export::convertWeightToGrams($orderSettings->getWeight());

        $customsDeclaration
            ->setContents($contents)
            ->setInvoice((string) $wcOrder->get_id())
            ->setWeight($totalWeight);

        foreach ($wcOrder->get_items() as $item) {
            $product = $item->get_product();
            if (! $product) {
                Messages::showAdminNotice(
                    sprintf(
                        __('warning_product_missing_check_backoffice', 'woocommerce-myparcel'),
                        $wcOrder->get_id()
                    ),
                    Messages::NOTICE_LEVEL_WARNING
                );
                $product = new WC_Product();
                $product->set_weight(1);
            }

            if (! $product || $product->is_virtual()) {
                continue;
            }

            $productHelper = new ExportRow($wcOrder, $product);
            $customsItem   = (new MyParcelCustomsItem())
                ->setDescription($productHelper->getItemDescription())
                ->setAmount($productHelper->getItemAmount($item))
                ->setWeight($productHelper->getItemWeight())
                ->setItemValueArray($productHelper->getValueOfItem())
                ->setCountry($productHelper->getCountryOfOrigin())
                ->setClassification($productHelper->getHsCode());

            $customsDeclaration->addCustomsItem($customsItem);
        }

        return $customsDeclaration;
    }

    /**
     * @param WC_Order    $order
     * @param string|null $packageType
     *
     * @return string|null
     *
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
     * @param array       $order_ids
     * @param int         $offset
     * @param string|null $display
     *
     * @return array
     * @throws Exception
     */
    public function getOrderLabels(array $order_ids, int $offset = 0, string $display = null)
    {
        $shipment_ids = $this->getShipmentIds($order_ids, ['only_last' => true]);

        if (empty($shipment_ids)) {
            WCMP_Log::add(' *** Failed label request(not exported yet) ***');

            throw new Exception(__(
                'The selected orders have not been exported to MyParcel yet! ',
                'woocommerce-myparcel'
            ));
        }

        return $this->downloadOrGetUrlOfLabels(
            $shipment_ids,
            $order_ids,
            $offset,
            $display
        );
    }

    /**
     * @param WC_Order $order
     * @param string   $shippingMethodId
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
     * @param WC_Order                            $order
     * @param AbstractDeliveryOptionsAdapter|null $deliveryOptions
     *
     * @return string
     * @throws Exception
     */
    public function getPackageTypeFromOrder(WC_Order $order, AbstractDeliveryOptionsAdapter $deliveryOptions = null): string
    {
        $packageTypeFromDeliveryOptions = $deliveryOptions ? $deliveryOptions->getPackageType() : null;
        $allowedPackageType             = $this->getAllowedPackageType($order, $packageTypeFromDeliveryOptions);

        if ($allowedPackageType) {
            return apply_filters('wc_myparcel_order_package_type', $allowedPackageType, $order, $this);
        }

        // Get pre 4.0.0 package type if it exists.
        if (WCX_Order::has_meta($order, WCMYPA_Admin::META_SHIPMENT_OPTIONS_LT_4_0_0)) {
            $shipmentOptions = WCX_Order::get_meta($order, WCMYPA_Admin::META_SHIPMENT_OPTIONS_LT_4_0_0);

            if (isset($shipmentOptions['package_type'])) {
                $packageType = WCMP_Data::getPackageTypeId($shipmentOptions['package_type']);
            }

            return (string) apply_filters('wc_myparcel_order_package_type', ($packageType ?? AbstractConsignment::DEFAULT_PACKAGE_TYPE), $order, $this);
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

        return apply_filters('wc_myparcel_order_package_type', $this->getAllowedPackageType($order, $packageType), $order, $this);
    }

    /**
     * Retrieves, updates and returns shipment data for given id.
     *
     * @param array    $ids
     * @param WC_Order $order
     *
     * @return array
     * @throws Exception
     */
    public function getShipmentData(array $ids, WC_Order $order): array
    {
        if (empty($ids)) {
            return [];
        }

        $data     = [];
        $api      = $this->init_api();
        $response = $api->get_shipments($ids);

        $shipments = Arr::get($response, 'body.data.shipments');

        if (! $shipments) {
            return [];
        }

        foreach ($shipments as $shipment) {
            if (! isset($shipment['id'])) {
                return [];
            }

            // if shipment id matches and status is not concept, get track trace barcode and status name
            $status        = $this->getShipmentStatusName($shipment['status']);
            $track_trace   = $shipment['barcode'] ?: $shipment['external_identifier'];
            $shipment_id   = $shipment['id'];
            $shipment_data = compact('shipment_id', 'status', 'track_trace', 'shipment');
            $this->saveShipmentData($order, $shipment_data);

            ChannelEngine::updateMetaOnExport($order, $track_trace);

            $data[$shipment_id] = $shipment_data;
        }

        return $data;
    }

    /**
     * @param array $order_ids
     * @param array $args
     *
     * @return array
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
                if (isset($args['exclude_concepts']) && empty($shipment['track_trace'])) {
                    continue;
                }
                if (isset($args['only_concepts']) && ! empty($shipment['track_trace'])) {
                    continue;
                }

                $order_shipment_ids[] = $shipment_id;
            }

            if (isset($args['only_last'])) {
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

        if (isset($shipment_statuses[$status_code])) {
            return $shipment_statuses[$status_code];
        } else {
            return __('Unknown status', 'woocommerce-myparcel');
        }
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
            $shipping_class_term_id = '';

            if ($shipping_class_term instanceof WP_Term) {
                $shipping_class_term_id = $shipping_class_term->term_id;
            }
            if (is_array($shipping_class_term)) {
                $shipping_class_term_id = $shipping_class_term['term_id'] ?? '';
            }

            $class_cost_string = $shipping_class_term && $shipping_class_term_id ?
                $shipping_method->get_option(
                "class_cost_$shipping_class_term_id",
                $shipping_method->get_option("class_cost_$shipping_class", '')
            ) : $shipping_method->get_option('no_class_cost', '');

            $class_cost_string = (string) $class_cost_string;

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
                $highest_class      = $shipping_class_term_id;
            }
        }

        return $highest_class;
    }

    /**
     * @param  \MyParcelNL\Sdk\src\Collection\Fulfilment\OrderNotesCollection $orderNotes
     * @param  \MyParcelNL\Sdk\src\Model\Fulfilment\OrderNote                 $note
     *
     * @return \MyParcelNL\Sdk\src\Collection\Fulfilment\OrderNotesCollection
     */
    public static function addValidOrderNote(OrderNotesCollection $orderNotes, OrderNote $note): OrderNotesCollection
    {
        try {
            $note->validate();
            $orderNotes->push($note);
        } catch (Exception $e) {
            WCMP_Log::add(
                sprintf(
                    'Note `%s` not exported. %s',
                    Str::limit($note->getNote(), 30),
                    $e->getMessage()
                )
            );
        }

        return $orderNotes;
    }

    /**
     * @param  \MyParcelNL\Sdk\src\Model\Fulfilment\Order $order
     *
     * @return \MyParcelNL\Sdk\src\Collection\Fulfilment\OrderNotesCollection
     */
    private function getAllNotesForOrder(Order $order): OrderNotesCollection
    {
        $wcOrder              = WCX::get_order($order->getExternalIdentifier());
        $orderNotesCollection = new OrderNotesCollection();
        $orderUuid            = $order->getUuid();

        $customerNote           = new stdClass();
        $customerNote->content  = $wcOrder->get_customer_note();
        $customerNote->added_by = 'customer';
        $notes                  = wc_get_order_notes(['order_id' => $wcOrder->get_id()]);
        $notes[]                = $customerNote;

        foreach ($notes as $note) {
            if ('system' === $note->added_by) {
                continue;
            }

            $orderNotesCollection->push(
                new OrderNote([
                    'orderUuid' => $orderUuid,
                    'note'      => $note->content,
                    'author'    => 'customer' === $note->added_by ? 'customer' : 'webshop',
                ])
            );
        }

        return $orderNotesCollection;
    }

    /**
     * @param  \MyParcelNL\Sdk\src\Collection\Fulfilment\OrderCollection $savedOrderCollection
     *
     * @return void
     */
    private function saveOrderNotes(OrderCollection $savedOrderCollection)
    {
        $orderNotes = (new OrderNotesCollection())->setApiKey($this->getSetting(WCMYPA_Settings::SETTING_API_KEY));

        $savedOrderCollection->each(function (Order $order) use ($orderNotes) {
            $this->getAllNotesForOrder($order)
                ->each(function (OrderNote $note) use ($orderNotes) {
                    self::addValidOrderNote($orderNotes, $note);
                });
        });

        $orderNotes->save();
    }


    /**
     * @return WCMP_API
     * @throws Exception
     */
    public function init_api()
    {
        $key = $this->getSetting(WCMYPA_Settings::SETTING_API_KEY);

        if (! ($key)) {
            throw new ErrorException(__('No API key found in MyParcel settings', 'woocommerce-myparcel'));
        }

        return new WCMP_API($key);
    }

    /**
     * @param $order_ids
     */
    public function modal_dialog($order_ids): void
    {
        // check for JSON
        if (is_string($order_ids) && strpos($order_ids, '[') !== false) {
            $order_ids = json_decode(stripslashes($order_ids));
        }

        // cast as array for single exports
        $order_ids = (array) $order_ids;
        require('views/html-send-return-email-form.php');
        die();
    }

    /**
     * @param $request
     * @param $result
     */
    public function modal_success_page($request, $result)
    {
        require('views/html-modal-result-page.php');
        die();
    }

    /**
     * @param string|array $array
     *
     * @return array
     */
    public function onlyIntegersInArray($array): array
    {
        // check for JSON
        if (is_string($array) && false !== strpos($array, '[')) {
            $array = json_decode(stripslashes($array), false);
        }

        return array_map(static function ($value) { return (int) $value; }, (array) $array);
    }

    /**
     * TODO: There are no options being passed right now but these will be necessary for NL.
     *
     * @param $order_id
     * @param array|null $options
     *
     * @return array
     * @throws Exception
     */
    public function prepareReturnShipmentData($order_id, ?array $options = []): array
    {
        $order           = WCX::get_order($order_id);
        $deliveryOptions = json_decode($order->get_meta('_myparcel_delivery_options'), true);
        $lastShipmentIds = json_decode($order->get_meta('_myparcel_last_shipment_ids'), true);
        $carrier         = CarrierFactory::createFromName($deliveryOptions['carrier']);
        $shipping_name   =
            method_exists($order, 'get_formatted_shipping_full_name') ? $order->get_formatted_shipping_full_name()
                : trim($order->get_shipping_first_name() . ' ' . $order->get_shipping_last_name());

        // set name & email
        $return_shipment_data = [
            'parent'  => (int) array_pop($lastShipmentIds),
            'name'    => $shipping_name,
            'email'   => WCX_Order::get_prop($order, 'billing_email'),
            'carrier' => $carrier->getId(),
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
     * @param WC_Order $order
     * @param array    $shipment
     *
     * @return void
     * @throws Exception
     */
    public function saveShipmentData(WC_Order $order, array $shipment): void
    {
        if (empty($shipment)) {
            throw new Exception("save_shipment_data requires a valid \$shipment.");
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
     * Adapted from WC_Shipping_Flat_Rate - Protected method
     * Evaluate a cost from a sum/string.
     *
     * @param string $sum
     * @param array  $args
     * @param        $flat_rate_method
     *
     * @return string
     */
    public function wc_flat_rate_evaluate_cost(string $sum, array $args, $flat_rate_method)
    {
        if (version_compare(WOOCOMMERCE_VERSION, '2.6', '>=')) {
            include_once(WC()->plugin_path() . '/includes/libraries/class-wc-eval-math.php');
        } else {
            include_once(WC()->plugin_path() . '/includes/shipping/flat-rate/includes/class-wc-eval-math.php');
        }

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
     * @param array $atts
     *
     * @return string
     */
    public function wc_flat_rate_fee($atts)
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
            $calculated_fee = $this->fee_cost * (floatval($atts['percent']) / 100);
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
     * Adds one or more consignments to the collection, depending on the collo amount.
     *
     * @param  \MyParcelNL\WooCommerce\includes\admin\OrderSettings      $orderSettings
     * @param  \MyParcelNL\Sdk\src\Helper\MyParcelCollection             $collection
     * @param  \MyParcelNL\Sdk\src\Model\Consignment\AbstractConsignment $consignment
     *
     * @throws \MyParcelNL\Sdk\src\Exception\MissingFieldException
     */
    private function addConsignments(
        OrderSettings       $orderSettings,
        MyParcelCollection  $collection,
        AbstractConsignment $consignment
    ): void {
        $colloAmount = $orderSettings->getColloAmount();

        if ($colloAmount > 1) {
            $this->addMultiCollo($orderSettings, $collection, $consignment);
            return;
        }

        $collection->addConsignment($consignment);
    }

    /**
     * @param array         $orderIds
     * @param int           $offset
     * @param string | null $print
     *
     * @return array
     * @throws ApiException
     * @throws MissingFieldException
     * @throws Exception
     */
    private function exportAccordingToMode(array $orderIds, int $offset, ?string $print): array
    {
        $exportMode = WCMYPA()->setting_collection->getByName(WCMYPA_Settings::SETTING_EXPORT_MODE);
        $orderIds   = array_map(static function ($value) { return (string) $value; },
            $this->filterOrderDestinations($orderIds)
        );
        $print      = $print ?? self::NO;

        if (WCMP_Settings_Data::EXPORT_MODE_PPS === $exportMode) {
            $return = $this->saveOrderCollection($orderIds);
        } else {
            // if we're going to print directly, we need to process the orders first, regardless of the settings
            $process = (self::YES === $print);
            $return  = $this->addShipments($orderIds, $process);
        }

        return $this->setFeedbackForClient($print, $offset, $orderIds, $return ?? []);
    }

    /**
     * @param  null|\MyParcelNL\Sdk\src\Model\Carrier\AbstractCarrier $carrier
     *
     * @return null|\MyParcelNL\Sdk\src\Model\Consignment\DropOffPoint
     */
    private function getDropOffPoint(?AbstractCarrier $carrier): ?DropOffPoint
    {
        if (! $carrier) {
            return null;
        }

        $configuration = AccountSettings::getInstance()->getCarrierConfigurationByCarrierId($carrier->getId());

        return $configuration ? $configuration->getDefaultDropOffPoint() : null;
    }

    /**
     * @param string $name
     *
     * @return mixed
     */
    private function getSetting(string $name)
    {
        return WCMYPA()->setting_collection->getByName($name);
    }

    /**
     * @param $shipment_id
     *
     * @return mixed
     * @throws ErrorException
     * @throws Exception
     */
    private function getShipmentBarcodeFromApi($shipment_id)
    {
        $api      = $this->init_api();
        $response = $api->get_shipments($shipment_id);

        if (! isset($response['body']['data']['shipments'][0]['barcode'])) {
            throw new ErrorException('No MyParcel barcode found for shipment id; ' . $shipment_id);
        }

        return $response['body']['data']['shipments'][0]['barcode'];
    }

    /**
     * @param array $order_ids
     * @param array $shipment_ids
     * @param int   $offset
     *
     * @return array
     * @throws Exception
     */
    private function printLabels(array $order_ids, array $shipment_ids, int $offset)
    {
        if (! empty($shipment_ids)) {
            $return = $this->downloadOrGetUrlOfLabels(
                $shipment_ids,
                $order_ids,
                $offset
            );
        } else {
            $order_ids = $this->filterOrderDestinations($order_ids);
            $return    = $this->getOrderLabels($order_ids, $offset);
        }

        return $return;
    }

    /**
     * @param array $orderIds
     *
     * @return array
     *
     * @throws \Exception
     */
    private function saveOrderCollection(array $orderIds): array
    {
        $apiKey                = $this->getSetting(WCMYPA_Settings::SETTING_API_KEY);
        $this->orderCollection = (new OrderCollection())->setApiKey($apiKey);

        foreach ($orderIds as $orderId) {
            $wcOrder                = WCX::get_order($orderId);
            $orderSettings          = new OrderSettings($wcOrder);
            $deliveryOptions        = $orderSettings->getDeliveryOptions();
            $carrier                = CarrierFactory::createFromName($deliveryOptions->getCarrier());
            $labelDescriptionFormat = new LabelDescriptionFormat($wcOrder, $orderSettings, $deliveryOptions);
            $shipmentOptions        = $deliveryOptions->getShipmentOptions();

            $shipmentOptions->setSignature($orderSettings->hasSignature());
            $shipmentOptions->setInsurance($orderSettings->getInsuranceAmount());
            $shipmentOptions->setAgeCheck($orderSettings->hasAgeCheck());
            $shipmentOptions->setOnlyRecipient($orderSettings->hasOnlyRecipient());
            $shipmentOptions->setReturn($orderSettings->hasReturnShipment());
            $shipmentOptions->setSameDayDelivery($orderSettings->isSameDayDelivery());
            $shipmentOptions->setLargeFormat($orderSettings->hasLargeFormat());
            $shipmentOptions->setLabelDescription($labelDescriptionFormat->getFormattedLabelDescription());
            $shipmentOptions->setHideSender($orderSettings->hasHideSender());
            $shipmentOptions->setExtraAssurance($orderSettings->hasExtraAssurance());

            $order = (new Order())
                ->setStatus($wcOrder->get_status())
                ->setDeliveryOptions($deliveryOptions)
                ->setInvoiceAddress($orderSettings->getBillingRecipient())
                ->setRecipient($orderSettings->getShippingRecipient())
                ->setOrderDate($wcOrder->get_date_created() ?? new DateTime())
                ->setPickupLocation($orderSettings->getPickupLocation())
                ->setExternalIdentifier($orderId)
                ->setWeight($orderSettings->getColloWeight())
                ->setDropOffPoint($this->getDropOffPoint($carrier));

            $orderLines = new Collection();

            foreach ($wcOrder->get_items() as $wcOrderItem) {
                $orderLine = new OrderLineFromWooCommerce($wcOrderItem);

                $orderLines->push($orderLine);
            }

            $isToRowCountry = ! in_array($order->getRecipient()->getCc(), AbstractConsignment::EURO_COUNTRIES, true);

            if ($isToRowCountry) {
                $order->setCustomsDeclaration($this->generateCustomsDeclaration($wcOrder));
            }

            $order->setOrderLines($orderLines);
            $this->orderCollection->push($order);
        }

        try {
            $savedOrderCollection = $this->orderCollection->save();
            $this->saveOrderNotes($savedOrderCollection);

            return $this->updateOrderMetaByCollection($savedOrderCollection);
        } catch (Exception $e) {
            Messages::showAdminNotice($e->getMessage());
            return [];
        }
    }

    /**
     * When adding shipments, store $return for use in admin_notice
     * This way we can refresh the page (JS) to show all new buttons
     *
     * @param string $print
     * @param int    $offset
     * @param array  $orderIds
     * @param array  $return
     *
     * @return array
     */
    private function setFeedbackForClient(string $print, int $offset, array $orderIds, array $return): array
    {
        if ($return['success'] ?? null) {
            Messages::showAdminNotice($return['success'], Messages::NOTICE_LEVEL_SUCCESS);
        }
        if ($return['error'] ?? null) {
            Messages::showAdminNotice($return['error'], Messages::NOTICE_LEVEL_ERROR);
        }

        return $return;
    }

    /**
     * @param \MyParcelNL\Sdk\src\Collection\Fulfilment\OrderCollection $orderCollection
     *
     * @return array
     */
    private function updateOrderMetaByCollection(OrderCollection $orderCollection): array
    {
        $currentDateTime = (new DateTime())->format(AbstractOrder::DATE_FORMAT_FULL);

        foreach ($orderCollection as $order) {
            $orderId = $order->getExternalIdentifier();
            $wcOrder = WCX::get_order($orderId);
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
        }

        return [
            'success' => sprintf(
                __('message_orders_exported_successfully', 'woocommerce-myparcel'),
                count($orderCollection)
            ),
        ];
    }
}

return new WCMP_Export();
