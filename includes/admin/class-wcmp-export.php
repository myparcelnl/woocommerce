<?php

use MyParcelNL\Sdk\src\Exception\ApiException;
use MyParcelNL\Sdk\src\Exception\MissingFieldException;
use MyParcelNL\Sdk\src\Helper\MyParcelCollection;
use MyParcelNL\Sdk\src\Model\Consignment\BpostConsignment;
use MyParcelNL\Sdk\src\Support\Arr;
use WPO\WC\MyParcelBE\Compatibility\WC_Core as WCX;
use WPO\WC\MyParcelBE\Compatibility\Order as WCX_Order;
use WPO\WC\MyParcelBE\Compatibility\WCMP_ChannelEngine_Compatibility as ChannelEngine;

if (! defined("ABSPATH")) {
    exit;
} // Exit if accessed directly

if (class_exists("WCMP_Export")) {
    return new WCMP_Export();
}

class WCMP_Export
{
    // Package types
    public const PACKAGE          = 1;

    public const EXPORT = "wcmp_export";

    public const ADD_SHIPMENTS = "add_shipments";
    public const ADD_RETURN    = "add_return";
    public const GET_LABELS    = "get_labels";
    public const MODAL_DIALOG  = "modal_dialog";

    /**
     * Maximum characters length of item description.
     */
    public const DESCRIPTION_MAX_LENGTH = 50;

    public $order_id;
    public $success;
    public $errors;
    public $myParcelCollection;

    private $prefix_message;

    public function __construct()
    {
        $this->success = [];
        $this->errors  = [];

        require("class-wcmp-rest.php");
        require("class-wcmp-api.php");

        add_action("admin_notices", [$this, "admin_notices"]);

        add_action("wp_ajax_" . self::EXPORT, [$this, "export"]);
        add_action("wp_ajax_wc_myparcelbe_frontend", [$this, "frontend_api_request"]);
        add_action("wp_ajax_nopriv_wc_myparcelbe_frontend", [$this, "frontend_api_request"]);
    }

    /**
     * Get the value of a shipment option. Check if it was set manually, through the delivery options for example,
     *  if not get the value of the default export setting for given settingName.
     *
     * @param bool|null $option      Condition to check.
     * @param string    $settingName Name of the setting to fall back to.
     *
     * @return bool
     */
    public static function getChosenOrDefaultShipmentOption($option, string $settingName): bool
    {
        if ($option !== null) {
            return $option;
        }

        return WCMP()->setting_collection->isEnabled($settingName);
    }

    public function admin_notices()
    {
        if (isset($_GET["myparcelbe_done"])) { // only do this when for the user that initiated this
            $action_return = get_option("wcmyparcelbe_admin_notices");
            $print_queue   = get_option("wcmyparcelbe_print_queue", []);
            if (! empty($action_return)) {
                foreach ($action_return as $type => $message) {
                    if (in_array($type, ["success", "error"])) {
                        if ($type == "success" && ! empty($print_queue)) {
                            $print_queue_store        = sprintf(
                                '<input type="hidden" value="%s" class="wcmp__print-queue">',
                                json_encode(array_keys($print_queue['order_ids']))
                            );
                            $print_queue_offset_store = sprintf(
                                '<input type="hidden" value="%s" class="wcmp__print-queue__offset">',
                                $print_queue['offset']
                            );
                            // dequeue
                            delete_option("wcmyparcelbe_print_queue");
                        }
                        printf(
                            '<div class="wcmp__notice notice notice-%s"><p>%s</p>%s</div>',
                            $type,
                            $message,
                            isset($print_queue_store) ? $print_queue_store . $print_queue_offset_store : ""
                        );
                    }
                }
                // destroy after reading
                delete_option("wcmyparcelbe_admin_notices");
                wp_cache_delete("wcmyparcelbe_admin_notices", "options");
            }
        }

        if (isset($_GET["myparcelbe"])) {
            switch ($_GET["myparcelbe"]) {
                case "no_consignments":
                    $message = __(
                        "You have to export the orders to MyParcel before you can print the labels!",
                        "woocommerce-myparcelbe"
                    );
                    printf('<div class="wcmp__notice notice notice-error"><p>%s</p></div>', $message);
                    break;
                default:
                    break;
            }
        }
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
        if (! check_ajax_referer(WCMP::NONCE_ACTION, "_wpnonce", false)) {
            die("Ajax security check failed. Did you pass a valid nonce in \$_REQUEST['_wpnonce']?");
        }

        if (! is_user_logged_in()) {
            wp_die(__("You do not have sufficient permissions to access this page.", "woocommerce-myparcelbe"));
        }

        $return = [];

        // Check the user privileges (maybe use order ids for filter?)
        if (apply_filters(
            "wc_myparcelbe_check_privs",
            ! current_user_can("manage_woocommerce_orders") && ! current_user_can("edit_shop_orders")
        )) {
            $return["error"] = __(
                "You do not have sufficient permissions to access this page.",
                "woocommerce-myparcelbe"
            );
            $json            = json_encode($return);
            echo $json;
            die();
        }

        $dialog  = $_REQUEST["dialog"] ?? null;
        $print   = $_REQUEST["print"] ?? null;
        $request = $_REQUEST["request"];

        $order_ids    = $this->sanitize_posted_array($_REQUEST["order_ids"] ?? []);
        $shipment_ids = $this->sanitize_posted_array($_REQUEST["shipment_ids"] ?? []);

        include_once("class-wcmp-export-consignments.php");

        switch ($request) {
            // Creating consignments.
            case self::ADD_SHIPMENTS:
                $this->addShipments($order_ids, $shipment_ids, $_REQUEST["offset"] ?? null, $print);
                break;

            // Creating a return shipment.
            case self::ADD_RETURN:
                if (empty($myparcelbe_options)) {
                    $this->errors[] = __("You have not selected any orders!", "woocommerce-myparcelbe");
                    break;
                }
                $return = $this->add_return($myparcelbe_options);
                break;

            // Downloading labels.
            case self::GET_LABELS:
                if (empty($shipment_ids) && empty($order_ids)) {
                    $this->errors[] = __("You have not selected any orders!", "woocommerce-myparcelbe");
                    break;
                }

                $return = $this->printLabels($order_ids, $shipment_ids, $_REQUEST["offset"] ?? null);
                break;

            case self::MODAL_DIALOG:
                $order_ids = $this->filterOrderDestinations($order_ids);
                $this->modal_dialog($order_ids);
                break;
        }

        // display errors directly if PDF requested or modal
        if (in_array($request, [self::ADD_RETURN, self::GET_LABELS, self::MODAL_DIALOG]) && ! empty($this->errors)) {
            echo $this->parse_errors($this->errors);
            die();
        }

        // format errors for html output
        if (! empty($this->errors)) {
            $return["error"] = $this->parse_errors($this->errors);
        }

        // if we're directed here from modal, show proper result page
        if (isset($modal)) {
            $this->modal_success_page($request, $return);
        } else {
            // return JSON response
            echo json_encode($return);
        }
    }

    /**
     * @param string|array $array
     *
     * @return array
     */
    public function sanitize_posted_array($array): array
    {
        if (is_array($array)) {
            return $array;
        }

        // check for JSON
        if (is_string($array) && strpos($array, "[") !== false) {
            $array = json_decode(stripslashes($array));
        }

        return (array) $array;
    }

    /**
     * @param $order_ids
     * @param $process
     *
     * @return array
     * @throws ApiException
     * @throws MissingFieldException
     * @throws Exception
     */
    public function add_shipments(array $order_ids, bool $process)
    {
        $return = [];
        $collection = new MyParcelCollection();
        $processDirectly = WCMP()->setting_collection->isEnabled(WCMP_Settings::SETTING_PROCESS_DIRECTLY)
            || $process === true;
        $keepOldShipments = WCMP()->setting_collection->isEnabled(WCMP_Settings::SETTING_KEEP_SHIPMENTS);

        WCMP_Log::add("*** Creating shipments started ***");

        $order_ids_with_new_shipments = [];
        /**
         * Loop over the order ids and create consignments for each order.
         */
        foreach ($order_ids as $order_id) {
            $order = WCX::get_order($order_id);
            $order_shipments = WCX_Order::get_meta($order, WCMP_Admin::META_SHIPMENTS);

            // check collo amount
            $extra_params = WCX_Order::get_meta($order, WCMP_Admin::META_SHIPMENT_OPTIONS_EXTRA);
            $collo_amount = isset($extra_params["collo_amount"]) ? $extra_params["collo_amount"] : 1;

            /**
             * Create a real multi collo shipment if available, otherwise loop over the collo_amount and add separate
             * consignments to the collection.
             */
            if (WCMP_Data::HAS_MULTI_COLLO) {
                $consignment = (new WCMP_Export_Consignments($order))->getConsignment();

                $collection->addMultiCollo($consignment, $collo_amount);
            } else {
                for ($i = 0; $i < $collo_amount; $i++) {
                    $consignment = (new WCMP_Export_Consignments($order))->getConsignment();

                    $collection->addConsignment($consignment);
                }
            }

            WCMP_Log::add("Shipment data for order {$order_id}.");
        }

        $collection = $collection->createConcepts();

        if ($processDirectly) {
            $collection->setLinkOfLabels();
        }

        foreach ($order_ids as $order_id) {
            $order          = WCX::get_order($order_id);
            $consignmentIds = ($collection->getConsignmentsByReferenceIdGroup($order_id))->getConsignmentIds();

            foreach ($consignmentIds as $consignmentId) {
                $shipment["shipment_id"] = $consignmentId;

                $this->save_shipment_data($order, $shipment, $keepOldShipments);

                if ($processDirectly) {
                    $this->getOrderLabels((array) $order_id, "url");
                }

                $this->updateOrderStatus($order);

                $this->success[$order_id] = $consignmentId;
            }

            if ($processDirectly) {
                $this->get_shipment_data($consignmentIds, $order);
            }

            WCX_Order::update_meta_data(
                $order,
                WCMP_Admin::META_LAST_SHIPMENT_IDS,
                $consignmentIds
            );
        }

        if (! empty($this->success)) {
            $return["success"]     = sprintf(
                __("%s shipments successfully exported to MyParcel", "woocommerce-myparcelbe"),
                count($collection->getConsignmentIds())
            );
            $return["success_ids"] = $collection->getConsignmentIds();

            WCMP_Log::add($return["success"]);
            WCMP_Log::add("ids: " . implode(", ", $return["success_ids"]));
        }

        return $return;
    }

    /**
     * @param $orders
     *
     * @return array
     * @throws Exception
     */
    public function add_return($orders)
    {
        $return = [];

        WCMP_Log::add("*** Creating return shipments started ***");

        foreach ($orders as $order_id => $options) {
            $return_shipments = [$this->prepare_return_shipment_data($order_id, $options)];
            WCMP_Log::add("Return shipment data for order {$order_id}:", print_r($return_shipments, true));

            try {
                $api      = $this->init_api();
                $response = $api->add_shipments($return_shipments, "return");
                WCMP_Log::add("API response (order {$order_id}):\n" . print_r($response, true));
                if (Arr::get($response, "body.data.ids")) {
                    $order                    = WCX::get_order($order_id);
                    $ids                      = array_shift($response["body"]["data"]["ids"]);
                    $shipment_id              = $ids["id"];
                    $this->success[$order_id] = $shipment_id;

                    $shipment = [
                        "shipment_id" => $shipment_id,
                    ];

                    // save shipment data in order meta
                    $this->save_shipment_data($order, $shipment);
                } else {
                    WCMP_Log::add("\$response\[\"body.data.ids\"] not found.", print_r($response, true));
                    $this->errors[$order_id] = "\$response\[\"body.data.ids\"] not found.";
                }
            } catch (Exception $e) {
                $this->errors[$order_id] = $e->getMessage();
            }
        }

        return $return;
    }

    /**
     * @param array $shipment_ids
     * @param array $order_ids
     * @param null  $label_response_type
     * @param int   $offset
     *
     * @return array
     */
    public function getShipmentLabels(
        array $shipment_ids,
        array $order_ids = [],
        $label_response_type = null,
        int $offset = 0
    ) {
        $return = [];

        WCMP_Log::add("*** getShipmentLabels() ***");
        WCMP_Log::add("getShipmentLabels(" . print_r(func_get_args(), true) . "):");
        WCMP_Log::add("Shipment IDs: " . implode(", ", $shipment_ids));

        try {
            $api    = $this->init_api();
            $params = [];

            if (! empty($offset) && is_numeric($offset)) {
                // positions are defined on landscape, but paper is filled portrait-wise
                $portrait_positions  = [2, 4, 1, 3];
                $params["positions"] = implode(";", array_slice($portrait_positions, $offset));
            }

            $download = WCMP()->setting_collection->getByName(WCMP_Settings::SETTING_DELIVERY_OPTIONS_DISPLAY) === "download";
            $response = $api->get_shipment_labels($shipment_ids, $params);

            if (Arr::get($response, "body")) {
                $this->addNoteToShipments($shipment_ids, $order_ids);
                new WCMP_Export_Pdf($response);
            } else {
                $this->errors[] = __("No PDF present in response", "woocommerce-myparcelbe");
            }

        } catch (Exception $e) {
            $this->errors[] = $e->getMessage();
        }

        return $return;
    }

    /**
     * @param      $order_ids
     * @param null $label_response_type
     * @param int  $offset
     *
     * @return array
     */
    public function getOrderLabels(array $order_ids, $label_response_type = null, int $offset = 0)
    {
        $shipment_ids = $this->getShipmentIds($order_ids, ["only_last" => true]);

        if (empty($shipment_ids)) {
            WCMP_Log::add(" *** Failed label request(not exported yet) ***");
            $this->errors[] = __(
                "The selected orders have not been exported to MyParcel yet! ",
                "woocommerce-myparcelbe"
            );

            return [];
        }

        return $this->getShipmentLabels(
            $shipment_ids,
            $order_ids,
            $label_response_type,
            $offset
        );
    }

    /**
     * @param $order_ids
     * @param $dialog
     */
    public function modal_dialog($order_ids)
    {
        // check for JSON
        if (is_string($order_ids) && strpos($order_ids, "[") !== false) {
            $order_ids = json_decode(stripslashes($order_ids));
        }

        // cast as array for single exports
        $order_ids = (array) $order_ids;
        require("views/html-bulk-options-form.php");
        die();
    }

    /**
     * @param $request
     * @param $result
     */
    public function modal_success_page($request, $result)
    {
        require("views/html-modal-result-page.php");
        die();
    }

    /**
     * @throws Exception
     */
    public function frontend_api_request()
    {
        // TODO: check nonce
        $params = $_REQUEST;

        // filter non API params
        $api_params = [
            "cc"                    => "",
            "postal_code"           => "",
            "number"                => "",
            "carrier"               => "",
            "delivery_time"         => "",
            "delivery_date"         => "",
            "cutoff_time"           => "",
            "dropoff_days"          => "",
            "dropoff_delay"         => "",
            "deliverydays_window"   => "",
            "exclude_delivery_type" => "",
        ];
        $params     = array_intersect_key($params, $api_params);

        $api = $this->init_api();

        try {
            $response = $api->get_delivery_options($params, true);

            @header("Content - type: application / json; charset = utf - 8");

            echo $response["body"];
        } catch (Exception $e) {
            @header("HTTP / 1.1 503 service unavailable");
        }
        die();
    }

    /**
     * @return WCMP_API
     * @throws Exception
     */
    public function init_api()
    {
        $key = $this->getSetting(WCMP_Settings::SETTING_API_KEY);

        if (! ($key)) {
            throw new ErrorException(__("No API key found in MyParcel BE settings", "woocommerce-myparcelbe"));
        }

        return new WCMP_API($key);
    }

    /**
     * @param $order_id
     * @param $options
     *
     * @return array
     * @throws Exception
     */
    public function prepare_return_shipment_data($order_id, $options)
    {
        $order = WCX::get_order($order_id);

        $shipping_name =
            method_exists($order, "get_formatted_shipping_full_name") ? $order->get_formatted_shipping_full_name()
                : trim($order->shipping_first_name . " " . $order->shipping_last_name);

        // set name & email
        $return_shipment_data = [
            "name"    => $shipping_name,
            "email"   => ($this->getSetting(WCMP_Settings::SETTING_CONNECT_EMAIL)) ? WCX_Order::get_prop(
                $order,
                "billing_email"
            ) : "",
            "carrier" => BpostConsignment::CARRIER_ID, // default to Bpost for now
        ];

        // add options if available
        if (! empty($options)) {
            // convert insurance option
            if (! isset($options["insurance"]) && isset($options["insured_amount"])) {
                if ($options["insured_amount"] > 0) {
                    $options["insurance"] = [
                        "amount"   => (int) $options["insured_amount"] * 100,
                        "currency" => "EUR",
                    ];
                }
                unset($options["insured_amount"]);
                unset($options["insured"]);
            }
            // PREVENT ILLEGAL SETTINGS
            // convert numeric strings to int
            $int_options = ["package_type", "delivery_type", "signature", "return "];
            foreach ($options as $key => &$value) {
                if (in_array($key, $int_options)) {
                    $value = (int) $value;
                }
            }
            // remove frontend insurance option values
            if (isset($options["insured_amount"])) {
                unset($options["insured_amount"]);
            }
            if (isset($options["insured"])) {
                unset($options["insured"]);
            }
            $return_shipment_data["options"] = $options;
        }

        // get parent
        $shipment_ids = $this->getShipmentIds(
            (array) $order_id,
            [
                "exclude_concepts" => true,
                "only_last"        => true,
            ]
        );
        if (! empty($shipment_ids)) {
            $return_shipment_data["parent"] = (int) array_pop($shipment_ids);
        }

        return $return_shipment_data;
    }

    /**
     * @param WC_Order $order
     * @param bool     $connectEmail
     *
     * @return mixed|void
     * @throws Exception
     */
    public static function getRecipientFromOrder(WC_Order $order, bool $connectEmail = null)
    {
        $is_using_old_fields = WCX_Order::has_meta($order, "_billing_street_name")
            || WCX_Order::has_meta($order, "_billing_house_number");

        $shipping_name =
            method_exists($order, "get_formatted_shipping_full_name") ? $order->get_formatted_shipping_full_name()
                : trim($order->get_shipping_first_name() . " " . $order->get_shipping_last_name());

        if ($connectEmail === null) {
            $connectEmail = WCMP()->setting_collection->isEnabled(WCMP_Settings::SETTING_CONNECT_EMAIL);
        }

        $connectPhone = WCMP()->setting_collection->isEnabled(WCMP_Settings::SETTING_CONNECT_PHONE);

        $address = [
            "cc"                     => (string) WCX_Order::get_prop($order, "shipping_country"),
            "city"                   => (string) WCX_Order::get_prop($order, "shipping_city"),
            "person"                 => $shipping_name,
            "company"                => (string) WCX_Order::get_prop($order, "shipping_company"),
            "email"                  => $connectEmail ? WCX_Order::get_prop($order, "billing_email") : "",
            "phone"                  => $connectPhone ? WCX_Order::get_prop($order, "billing_phone") : "",
            "street_additional_info" => WCX_Order::get_prop($order, "shipping_address_2"),
        ];

        $shipping_country = WCX_Order::get_prop($order, "shipping_country");
        if ($shipping_country === "BE") {
            // use billing address if old "pakjegemak" (1.5.6 and older)
            $pgAddress = WCX_Order::get_meta($order, WCMP_Admin::META_PGADDRESS);

            if ($pgAddress) {
                $billing_name = method_exists($order, "get_formatted_billing_full_name")
                    ? $order->get_formatted_billing_full_name()
                    : trim(
                        $order->get_billing_first_name() . " " . $order->get_billing_last_name()
                    );
                $address_intl = [
                    "city"        => (string) WCX_Order::get_prop($order, "billing_city"),
                    "person"      => $billing_name,
                    "company"     => (string) WCX_Order::get_prop($order, "billing_company"),
                    "postal_code" => (string) WCX_Order::get_prop($order, "billing_postcode"),
                ];

                if ($is_using_old_fields) {
                    $address_intl["street"]        = (string) WCX_Order::get_meta($order, "_billing_street_name");
                    $address_intl["number"]        = (string) WCX_Order::get_meta($order, "_billing_house_number");
                    $address_intl["number_suffix"] =
                        (string) WCX_Order::get_meta($order, "_billing_house_number_suffix");
                } else {
                    // Split the address line 1 into three parts
                    preg_match(
                        WCMP_BE_Postcode_Fields::SPLIT_STREET_REGEX,
                        WCX_Order::get_prop($order, "billing_address_1"),
                        $address_parts
                    );
                    $address_intl["street"]                 = (string) $address_parts["street"];
                    $address_intl["number"]                 = (string) $address_parts["number"];
                    $address_intl["number_suffix"]          =
                        array_key_exists("number_suffix", $address_parts) // optional
                            ? (string) $address_parts["number_suffix"] : "";
                    $address_intl["street_additional_info"] = WCX_Order::get_prop($order, "billing_address_2");
                }
            } else {
                $address_intl = [
                    "postal_code" => (string) WCX_Order::get_prop($order, "shipping_postcode"),
                ];
                // If not using old fields
                if ($is_using_old_fields) {
                    $address_intl["street"]        = (string) WCX_Order::get_meta($order, "_shipping_street_name");
                    $address_intl["number"]        = (string) WCX_Order::get_meta($order, "_shipping_house_number");
                    $address_intl["number_suffix"] =
                        (string) WCX_Order::get_meta($order, "_shipping_house_number_suffix");
                } else {
                    // Split the address line 1 into three parts
                    preg_match(
                        WCMP_BE_Postcode_Fields::SPLIT_STREET_REGEX,
                        WCX_Order::get_prop($order, "shipping_address_1"),
                        $address_parts
                    );

                    $address_intl["street"]        = (string) $address_parts["street"];
                    $address_intl["number"]        = (string) $address_parts["number"];
                    $address_intl["number_suffix"] = array_key_exists("number_suffix", $address_parts) // optional
                        ? (string) $address_parts["number_suffix"] : "";
                }
            }
        } else {
            $address_intl = [
                "postal_code"            => (string) WCX_Order::get_prop($order, "shipping_postcode"),
                "street"                 => (string) WCX_Order::get_prop($order, "shipping_address_1"),
                "street_additional_info" => (string) WCX_Order::get_prop($order, "shipping_address_2"),
                "region"                 => (string) WCX_Order::get_prop($order, "shipping_state"),
            ];
        }

        $address = array_merge($address, $address_intl);

        return apply_filters("wc_myparcelbe_recipient", $address, $order);
    }

    /**
     * @param array $selected_shipment_ids
     * @param array $order_ids
     *
     * @throws ErrorException
     * @internal param $shipment_ids
     */
    public function addNoteToShipments(array $selected_shipment_ids, array $order_ids)
    {
        if (! WCMP()->setting_collection->isEnabled(WCMP_Settings::SETTING_BARCODE_IN_NOTE)) {
            return;
        }

        // Select the barcode text of the MyParcel settings
        $this->prefix_message = $this->getSetting(WCMP_Settings::SETTING_BARCODE_IN_NOTE_TITLE);

        foreach ($order_ids as $order_id) {
            $order           = WCX::get_order($order_id);
            $order_shipments = WCX_Order::get_meta($order, WCMP_Admin::META_SHIPMENTS);

            foreach ($order_shipments as $shipment_id => $shipment) {
                $this->addNoteToShipment($selected_shipment_ids, $shipment_id, $order);
            }
        }

        return;
    }

    /**
     * @param string $name
     *
     * @return mixed
     */
    private function getSetting(string $name)
    {
        return WCMP()->setting_collection->getByName($name);
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
            $order_shipments = WCX_Order::get_meta($order, WCMP_Admin::META_SHIPMENTS);

            if (empty($order_shipments)) {
                continue;
            }

            $order_shipment_ids = [];
            // exclude concepts or only concepts
            foreach ($order_shipments as $shipment_id => $shipment) {
                if (isset($args["exclude_concepts"]) && empty($shipment["tracktrace"])) {
                    continue;
                }
                if (isset($args["only_concepts"]) && ! empty($shipment["tracktrace"])) {
                    continue;
                }

                $order_shipment_ids[] = $shipment_id;
            }

            if (isset($args["only_last"])) {
                $last_shipment_ids = WCX_Order::get_meta($order, WCMP_Admin::META_LAST_SHIPMENT_IDS);

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
     * @param WC_Order $order
     * @param array    $shipment
     * @param bool     $keepOld
     *
     * @return bool|void
     */
    public function save_shipment_data(WC_Order $order, array $shipment, bool $keepOld = true)
    {
        if (empty($shipment)) {
            return false;
        }

        $new_shipments                           = [];
        $new_shipments[$shipment["shipment_id"]] = $shipment;

        $old_shipments = $keepOld ? WCX_Order::get_meta($order, WCMP_Admin::META_SHIPMENTS) : [];
        $new_shipments = array_replace_recursive((array) $old_shipments, $new_shipments);

        WCX_Order::update_meta_data($order, WCMP_Admin::META_SHIPMENTS, $new_shipments);
    }

    /**
     * TODO: For MyParcel NL, currently not necessary for BE.
     *
     * @param $shipping_method
     * @param $shipping_class
     * @param $shipping_country
     *
     * @return int|string
     */
    public function get_package_type_from_shipping_method($shipping_method, $shipping_class, $shipping_country)
    {
        $packageTypes = WCMP()->setting_collection->getByName(WCMP_Settings::SETTING_SHIPPING_METHODS_PACKAGE_TYPES);

        $package_type             = self::PACKAGE;
        $shipping_method_id_class = "";

        if ($packageTypes) {
            if (strpos($shipping_method, "table_rate:") === 0 && class_exists("WC_Table_Rate_Shipping")) {
                // Automattic / WooCommerce table rate
                // use full method = method_id:instance_id:rate_id
                $shipping_method_id = $shipping_method;
            } else { // non table rates

                if (strpos($shipping_method, ":") !== false) {
                    // means we have method_id:instance_id
                    $shipping_method          = explode(":", $shipping_method);
                    $shipping_method_id       = $shipping_method[0];
                } else {
                    $shipping_method_id = $shipping_method;
                }

                // add class if we have one
                if (! empty($shipping_class)) {
                    $shipping_method_id_class = "{$shipping_method_id}:{$shipping_class}";
                }
            }

            foreach ($packageTypes as $packageType => $shippingMethods) {
                if ($this->isActiveMethod(
                    $shipping_method_id,
                    $shippingMethods,
                    $shipping_method_id_class,
                    $shipping_class
                )) {
                    $package_type = $packageType;
                    break;
                }
            }
        }

        return $package_type;
    }

    /**
     * @param string $package_type
     *
     * @return string
     */
    public function get_package_type(string $package_type): string
    {
        return WCMP_Data::getPackageTypes()[$package_type] ?? __("Unknown", "woocommerce-myparcelbe");
    }

    /**
     * @param $errors
     *
     * @return mixed|string
     */
    public function parse_errors($errors)
    {
        $parsed_errors = [];
        foreach ($errors as $key => $error) {
            // check if we have an order_id
            if ($key > 10) {
                $parsed_errors[] = sprintf(
                    "<strong>%s %s:</strong> %s",
                    __("Order", "woocommerce-myparcelbe"),
                    $key,
                    $error
                );
            } else {
                $parsed_errors[] = $error;
            }
        }

        if (count($parsed_errors) == 1) {
            $html = array_shift($parsed_errors);
        } else {
            foreach ($parsed_errors as &$parsed_error) {
                $parsed_error = "<li>{$parsed_error}</li>";
            }
            $html = sprintf("<ul>%s</ul>", implode("\n", $parsed_errors));
        }

        return $html;
    }

    public function get_shipment_status_name($status_code)
    {
        $shipment_statuses = [
            1  => __("pending - concept", "woocommerce-myparcelbe"),
            2  => __("pending - registered", "woocommerce-myparcelbe"),
            3  => __("enroute - handed to carrier", "woocommerce-myparcelbe"),
            4  => __("enroute - sorting", "woocommerce-myparcelbe"),
            5  => __("enroute - distribution", "woocommerce-myparcelbe"),
            6  => __("enroute - customs", "woocommerce-myparcelbe"),
            7  => __("delivered - at recipient", "woocommerce-myparcelbe"),
            8  => __("delivered - ready for pickup", "woocommerce-myparcelbe"),
            9  => __("delivered - package picked up", "woocommerce-myparcelbe"),
            30 => __("inactive - concept", "woocommerce-myparcelbe"),
            31 => __("inactive - registered", "woocommerce-myparcelbe"),
            32 => __("inactive - enroute - handed to carrier", "woocommerce-myparcelbe"),
            33 => __("inactive - enroute - sorting", "woocommerce-myparcelbe"),
            34 => __("inactive - enroute - distribution", "woocommerce-myparcelbe"),
            35 => __("inactive - enroute - customs", "woocommerce-myparcelbe"),
            36 => __("inactive - delivered - at recipient", "woocommerce-myparcelbe"),
            37 => __("inactive - delivered - ready for pickup", "woocommerce-myparcelbe"),
            38 => __("inactive - delivered - package picked up", "woocommerce-myparcelbe"),
            99 => __("inactive - unknown", "woocommerce-myparcelbe"),
        ];

        if (isset($shipment_statuses[$status_code])) {
            return $shipment_statuses[$status_code];
        } else {
            return __("Unknown status", "woocommerce-myparcelbe");
        }
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
    public function get_shipment_data(array $ids, WC_Order $order): array
    {
        $data = [];
        $api      = $this->init_api();
        $response = $api->get_shipments($ids);

        $shipments = Arr::get($response, "body.data.shipments");

        if (! $shipments) {
            return [];
        }

        foreach ($shipments as $shipment) {
            if (! isset($shipment["id"])) {
                return [];
            }

            // if shipment id matches and status is not concept, get track trace barcode and status name
            $status        = $this->get_shipment_status_name($shipment["status"]);
            $track_trace   = $shipment["barcode"];
            $shipment_id   = $shipment["id"];
            $shipment_data = compact("shipment_id", "status", "track_trace", "shipment");
            $this->save_shipment_data($order, $shipment_data);

            ChannelEngine::updateMetaOnExport($order, $track_trace);

            $data[$shipment_id] = $shipment_data;
        }

        return $data;
    }

    /**
     * @param $item
     * @param $order
     *
     * @return float
     */
    public static function get_item_weight_kg($item, WC_Order $order): float
    {
        $product = $order->get_product_from_item($item);

        if (empty($product)) {
            return 0;
        }

        $weight      = (int) $product->get_weight();
        $weight_unit = get_option("woocommerce_weight_unit");
        switch ($weight_unit) {
            case "g":
                $product_weight = $weight / 1000;
                break;
            case "lbs":
                $product_weight = $weight * 0.45359237;
                break;
            case "oz":
                $product_weight = $weight * 0.0283495231;
                break;
            default:
                $product_weight = $weight;
                break;
        }

        $item_weight = (float) $product_weight * (int) $item["qty"];

        return (float) $item_weight;
    }

    /**
     * @param $chosen_method
     *
     * @return bool|WC_Shipping_Method
     */
    public static function get_shipping_method($chosen_method)
    {
        if (version_compare(WOOCOMMERCE_VERSION, "2.6", " >= ") && $chosen_method !== "legacy_flat_rate") {
            $chosen_method = explode(":", $chosen_method); // slug:instance
            // only for flat rate
            if ($chosen_method[0] !== "flat_rate") {
                return false;
            }
            if (empty($chosen_method[1])) {
                return false; // no instance known (=probably manual order)
            }

            $method_slug     = $chosen_method[0];
            $method_instance = $chosen_method[1];

            $shipping_method = WC_Shipping_Zones::get_shipping_method($method_instance);
        } else {
            // only for flat rate or legacy flat rate
            if (! in_array($chosen_method, ["flat_rate", "legacy_flat_rate"])) {
                return false;
            }
            $shipping_methods = WC()->shipping()->load_shipping_methods();

            if (! isset($shipping_methods[$chosen_method])) {
                return false;
            }
            $shipping_method = $shipping_methods[$chosen_method];
        }

        return $shipping_method;
    }

    /**
     * @param $shipping_method
     * @param $found_shipping_classes
     *
     * @return bool|int
     */
    public function get_shipping_class($shipping_method, $found_shipping_classes)
    {
        // get most expensive class
        // adapted from $shipping_method->calculate_shipping()
        $highest_class_cost = 0;
        $highest_class      = false;
        foreach ($found_shipping_classes as $shipping_class => $products) {
            // Also handles BW compatibility when slugs were used instead of ids
            $shipping_class_term    = get_term_by("slug", $shipping_class, "product_shipping_class");
            $shipping_class_term_id = "";

            if ($shipping_class_term != null) {
                $shipping_class_term_id = $shipping_class_term->term_id;
            }

            $class_cost_string = $shipping_class_term && $shipping_class_term_id ? $shipping_method->get_option(
                "class_cost_" . $shipping_class_term_id,
                $shipping_method->get_option("class_cost_" . $shipping_class, "")
            ) : $shipping_method->get_option("no_class_cost", "");

            if ($class_cost_string === "") {
                continue;
            }

            $has_costs  = true;
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
     * @param $order
     *
     * @return array
     */
    public function find_order_shipping_classes(WC_Order $order)
    {
        $found_shipping_classes = [];
        $order_items            = $order->get_items();
        foreach ($order_items as $item_id => $item) {
            $product = $order->get_product_from_item($item);
            if ($product && $product->needs_shipping()) {
                $found_class = $product->get_shipping_class();

                if (! isset($found_shipping_classes[$found_class])) {
                    $found_shipping_classes[$found_class] = [];
                }
                // normally this should pass the $product object, but only in the checkout this contains
                // quantity & line_total (which is all we need), so we pass data from the $item instead
                $item_product                                   = new stdClass();
                $item_product->quantity                         = $item["qty"];
                $item_product->line_total                       = $item["line_total"];
                $found_shipping_classes[$found_class][$item_id] = $item_product;
            }
        }

        return $found_shipping_classes;
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
     * @param array $atts
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
     * @param $order_ids
     *
     * @return mixed
     * @throws Exception
     */
    public function filterOrderDestinations(array $order_ids): array
    {
        foreach ($order_ids as $key => $order_id) {
            $order            = WCX::get_order($order_id);
            $shipping_country = WCX_Order::get_prop($order, "shipping_country");

            if (! WCMP_Country_Codes::isAllowedDestination($shipping_country)) {
                unset($order_ids[$key]);
            }
        }

        return $order_ids;
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

        if (! isset($response["body"]["data"]["shipments"][0]["barcode"])) {
            throw new ErrorException("No MyParcel barcode found for shipment id; " . $shipment_id);
        }

        return $response["body"]["data"]["shipments"][0]["barcode"];
    }

    /**
     * @param array    $selected_shipment_ids
     * @param int      $shipment_id
     * @param WC_Order $order
     *
     * @throws ErrorException
     */
    private function addNoteToShipment(array $selected_shipment_ids, int $shipment_id, WC_Order $order)
    {
        if (! in_array($shipment_id, $selected_shipment_ids)) {
            return;
        }

        $barcode = $this->getShipmentBarcodeFromApi($shipment_id);

        $order->add_order_note($this->prefix_message . sprintf($barcode));
    }

    /**
     * @param $shipping_method_id
     * @param $package_type_shipping_methods
     * @param $shipping_method_id_class
     * @param $shipping_class
     *
     * @return bool
     */
    private function isActiveMethod(
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
        $newShippingClass = str_replace(":", "_", $shipping_class);
        if (! empty($shipping_class) && in_array($newShippingClass, $package_type_shipping_methods)) {
            return true;
        }

        return false;
    }

    /**
     * Update the status of given order based on the automatic order status settings.
     *
     * @param WC_Order $order
     */
    private function updateOrderStatus(WC_Order $order): void
    {
        if (WCMP()->setting_collection->isEnabled(WCMP_Settings::SETTING_ORDER_STATUS_AUTOMATION)) {
            $order->update_status(
                $this->getSetting(WCMP_Settings::SETTING_AUTOMATIC_ORDER_STATUS),
                __("MyParcel shipment created:", "woocommerce-myparcelbe")
            );
        }
    }

    /**
     * @param $order_ids
     * @param $shipment_ids
     * @param $offset
     *
     * @return array
     * @throws Exception
     */
    private function printLabels($order_ids, $shipment_ids, $offset)
    {
        $offset = ! empty($offset) && is_numeric($offset) ? $offset % 4 : 0;

        $label_response_type = isset($label_response_type) ? $label_response_type : null;

        if (! empty($shipment_ids)) {
            $return = $this->getShipmentLabels(
                $shipment_ids,
                $order_ids,
                $label_response_type,
                $offset
            );
        } else {
            $order_ids = $this->filterOrderDestinations($order_ids);
            $return    = $this->getOrderLabels($order_ids, $label_response_type, $offset);
        }

        return $return;
    }

    /**
     * @param $order_ids
     * @param $shipment_ids
     *
     * @return array|void
     * @throws ApiException
     * @throws MissingFieldException
     * @throws Exception
     */
    private function addShipments($order_ids, $shipment_ids, $offset, $print)
    {
        $order_ids = $this->filterOrderDestinations($order_ids);

        if (empty($order_ids)) {
            $this->errors[] =
                __(
                    "The order(s) you have selected have invalid shipping countries.",
                    "woocommerce-myparcelbe"
                );
            return;
        }

        // if we're going to print directly, we need to process the orders first, regardless of the settings
        $process = $print === "yes" ? true : false;
        $return  = $this->add_shipments($order_ids, $process);

        // When adding shipments, store $return for use in admin_notice
        // This way we can refresh the page (JS) to show all new buttons
        if ($print === "no" || $print === "after_reload") {
            update_option("wcmyparcelbe_admin_notices", $return);
            if ($print === "after_reload") {
                $print_queue = [
                    "order_ids" => $return["success_ids"],
                    "offset"    => isset($offset) && is_numeric($offset) ? $offset % 4 : 0,
                ];
                update_option("wcmyparcelbe_print_queue", $print_queue);
            }
        }

        return $return;
    }
}

return new WCMP_Export();
