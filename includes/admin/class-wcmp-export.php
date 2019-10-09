<?php

use MyParcelNL\Sdk\src\Exception\ApiException;
use MyParcelNL\Sdk\src\Exception\MissingFieldException;
use MyParcelNL\Sdk\src\Factory\ConsignmentFactory;
use MyParcelNL\Sdk\src\Helper\MyParcelCollection;
use MyParcelNL\Sdk\src\Model\Consignment\AbstractConsignment;
use MyParcelNL\Sdk\src\Model\Consignment\BpostConsignment;
use MyParcelNL\Sdk\src\Model\Consignment\DPDConsignment;
use MyParcelNL\Sdk\src\Model\DeliveryOptions\DeliveryOptions;
use MyParcelNL\Sdk\src\Model\MyParcelCustomsItem;
use WPO\WC\MyParcelBE\Compatibility\WC_Core as WCX;
use WPO\WC\MyParcelBE\Compatibility\Order as WCX_Order;

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
    public const INSURANCE_AMOUNT = 500;

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
                    $message = __("You have to export the orders to MyParcel before you can print the labels!",
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
     */
    public function export()
    {
        // Check the nonce
        if (! check_ajax_referer("wc_myparcelbe", "security", false)) {
            die("Ajax security check failed. Did you pass a valid nonce in \$_REQUEST['security']?");
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
            $return["error"] = __("You do not have sufficient permissions to access this page.",
                "woocommerce-myparcelbe"
            );
            $json            = json_encode($return);
            echo $json;
            die();
        }

        $dialog       = $_REQUEST["dialog"];
        $order_ids    = $_REQUEST["order_ids"];
        $print        = $_REQUEST["print"];
        $request      = $_REQUEST["request"];
        $shipment_ids = $_REQUEST["shipment_ids"];

        // make sure $order_ids is a proper array
        $order_ids = ! empty($order_ids) ? $this->sanitize_posted_array($order_ids) : [];

        include_once("class-wcmp-export-consignments.php");

        switch ($request) {
            // Creating consignments.
            case self::ADD_SHIPMENTS:
                // filter out non-myparcel destinations
                $order_ids = $this->filter_myparcelbe_destination_orders($order_ids);

                if (empty($order_ids)) {
                    $this->errors[] = __("You have not selected any orders!", "woocommerce-myparcelbe");
                    break;
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
                $offset = ! empty($offset) && is_numeric($offset) ? $offset % 4 : 0;

                if (empty($order_ids) && empty($shipment_ids)) {
                    $this->errors[] = __("You have not selected any orders!", "woocommerce-myparcelbe");
                    break;
                }
                $label_response_type = isset($label_response_type) ? $label_response_type : null;

                if (! empty($shipment_ids)) {
                    $order_ids    = ! empty($order_ids) ? $this->sanitize_posted_array($order_ids) : [];
                    $shipment_ids = $this->sanitize_posted_array($shipment_ids);
                    $return       = $this->get_shipment_labels(
                        $shipment_ids,
                        $order_ids,
                        $label_response_type,
                        $offset
                    );
                } else {
                    $order_ids = $this->filter_myparcelbe_destination_orders($order_ids);
                    $return    = $this->get_labels($order_ids, $label_response_type, $offset);
                }
                break;

            case self::MODAL_DIALOG:
                if (empty($order_ids)) {
                    $errors[] = __("You have not selected any orders!", "woocommerce-myparcelbe");
                    break;
                }
                $order_ids = $this->filter_myparcelbe_destination_orders($order_ids);
                $this->modal_dialog($order_ids, $dialog);
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

    public function sanitize_posted_array($array)
    {
        // check for JSON
        if (is_string($array) && strpos($array, "[") !== false) {
            $array = json_decode(stripslashes($array));
        }

        // cast as array for single exports
        $array = (array) $array;

        return $array;
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

        $this->log("*** Creating shipments started ***");

        /**
         * Loop over the order ids and create consignments for each order.
         */
        foreach ($order_ids as $order_id) {
            $order = WCX::get_order($order_id);

            // check collo amount
            $extra_params = WCX_Order::get_meta($order, WCMP_Admin::META_SHIPMENT_OPTIONS_EXTRA);
            $collo_amount = isset($extra_params["collo_amount"]) ? $extra_params["collo_amount"] : 1;

            /**
             * Create a real multi collo shipment if available, otherwise loop over the collo_amount and add separate
             * consignments to the collection.
             */
            if (WCMP_Data::HAS_MULTI_COLLO) {
                $consignment = (new WCMP_Export_Consignments($order_id))->getConsignment();

                $collection->addMultiCollo($consignment, $collo_amount);
            } else {
                for ($i = 0; $i < $collo_amount; $i++) {
                    $consignment = (new WCMP_Export_Consignments($order))->getConsignment();

                    $collection->addConsignment($consignment);
                }
            }

            $this->log("Shipment data for order {$order_id}.");
            $this->log(json_encode($collection->toArray()));
        }

        $collection = $collection->createConcepts();

        foreach ($order_ids as $order_id) {
            $order        = WCX::get_order($order_id);
            $consignments = $collection->getConsignmentsByReferenceIdGroup($order_id);

            foreach ($consignments->toArray() as $consignmentId) {
                $shipment['shipment_id'] = $consignmentId;
                $this->save_shipment_data($order, $shipment);

                // Get the "process directly" setting
                $processEnabled = WCMP()->setting_collection->isEnabled(WCMP_Settings::SETTING_PROCESS_DIRECTLY);

                if ($processEnabled || $process === true) {
                    // flush cache until WC issue #13439 is fixed https://github.com/woocommerce/woocommerce/issues/13439
//                    if (method_exists($order, "save")) {
//                        $order->save();
//                    }
                    $this->get_labels((array) $order_id, "url");
                    $this->get_shipment_data($consignmentId, $order);
                }

                // status automation
                if ($this->getSetting(WCMP_Settings::SETTING_ORDER_STATUS_AUTOMATION)
                    && $this->getSetting(
                        WCMP_Settings::SETTING_AUTOMATIC_ORDER_STATUS
                    )) {
                    $order->update_status(
                        $this->getSetting(WCMP_Settings::SETTING_AUTOMATIC_ORDER_STATUS),
                        __("MyParcel shipment created:", "woocommerce-myparcelbe")
                    );
                }

                $this->success[$order_id] = $consignmentId;
            }

            WCX_Order::update_meta_data(
                $order,
                WCMP_Admin::META_LAST_SHIPMENT_IDS,
                $consignments->getConsignmentIds()
            );
        }

        if (!empty($this->success)) {
            $return["success"]     = sprintf(
                __("%s shipments successfully exported to MyParcel", "woocommerce-myparcelbe"),
                count($collection->getConsignmentIds())
            );
            $return["success_ids"] = $collection->getConsignmentIds();
        }

        return $return;
    }

    /**
     * @param $myparcelbe_options
     *
     * @return array
     */
    public function add_return($myparcelbe_options)
    {
        $return = [];

        $this->log("*** Creating return shipments started ***");

        foreach ($myparcelbe_options as $order_id => $options) {
            $return_shipments = [$this->prepare_return_shipment_data($order_id, $options)];
            $this->log("Return shipment data for order {$order_id}:\n" . var_export($return_shipments, true));

            try {
                $api      = $this->init_api();
                $response = $api->add_shipments($return_shipments, "return");
                $this->log("API response (order {$order_id}):\n" . var_export($response, true));
                if (isset($response["body"]["data"]["ids"])) {
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
                    $this->errors[$order_id] = __("Unknown error", "woocommerce-myparcelbe");
                }
            } catch (Exception $e) {
                $this->errors[$order_id] = $e->getMessage();
            }
        }

        return $return;
    }

    /**
     * @param       $shipment_ids
     * @param array $order_ids
     * @param null $label_response_type
     * @param int $offset
     *
     * @return array
     */
    public function get_shipment_labels($shipment_ids, $order_ids = [], $label_response_type = null, $offset = 0)
    {
        $return = [];

        $this->log("*** Label request started ***");
        $this->log("Shipment IDs: " . implode(", ", $shipment_ids));

        try {
            $api    = $this->init_api();
            $params = [];
            if (! empty($offset) && is_numeric($offset)) {
                // positions are defined on landscape, but paper is filled portrait-wise
                $portrait_positions  = [2, 4, 1, 3];
                $params["positions"] = implode(";", array_slice($portrait_positions, $offset));
            }

            if (isset($label_response_type) && $label_response_type == "url") {
                $response = $api->get_shipment_labels($shipment_ids, $params, "link");
                $this->add_myparcelbe_note_to_shipments($shipment_ids, $order_ids);
                $this->log("API response:n" . var_export($response, true));

                if (isset($response["body"]["data"]["pdfs"]["url"])) {
                    $url           = untrailingslashit($api->apiUrl) . $response["body"]["data"]["pdfs"]["url"];
                    $return["url"] = $url;
                } else {
                    $this->errors[] = __("Unknown error", "woocommerce-myparcelbe");
                }
            } else {
                $response = $api->get_shipment_labels($shipment_ids, $params, "pdf");
                $this->add_myparcelbe_note_to_shipments($shipment_ids, $order_ids);

                if (isset($response["body"])) {
                    $this->log("PDF data received");
                    $pdf_data    = $response["body"];
                    $output_mode = $this->getSetting(WCMP_Settings::SETTING_DOWNLOAD_DISPLAY) ? $this->getSetting(
                        WCMP_Settings::SETTING_DOWNLOAD_DISPLAY
                    ) : "";
                    if ($output_mode == "display") {
                        $this->stream_pdf($pdf_data, $order_ids);
                    } else {
                        $this->download_pdf($pdf_data, $order_ids);
                    }
                } else {
                    $this->log("Unknown error, API response:n" . var_export($response, true));
                    $this->errors[] = __("Unknown error", "woocommerce-myparcelbe");
                }
            }
        } catch (Exception $e) {
            $this->errors[] = $e->getMessage();
        }

        return $return;
    }

    /**
     * @param      $order_ids
     * @param null $label_response_type
     * @param int $offset
     *
     * @return array
     */
    public function get_labels($order_ids, $label_response_type = null, $offset = 0)
    {
        $shipment_ids = $this->get_shipment_ids($order_ids, ["only_last" => true]);

        if (empty($shipment_ids)) {
            $this->log(" *** Failed label request(not exported yet) ***");
            $this->errors[] = __("The selected orders have not been exported to MyParcel yet! ",
                "woocommerce-myparcelbe"
            );

            return [];
        }

        return $this->get_shipment_labels($shipment_ids, $order_ids, $label_response_type, $offset);
    }

    /**
     * @param $order_ids
     * @param $dialog
     */
    public function modal_dialog($order_ids, $dialog)
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
            "email"   => ($this->getSetting("connect_email")) ? WCX_Order::get_prop($order, "billing_email") : "",
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
        $shipment_ids = $this->get_shipment_ids(
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
     * @param bool $connectEmail
     *
     * @return mixed|void
     */
    public static function getRecipientFromOrder(WC_Order $order, bool $connectEmail = null)
    {
        $is_using_old_fields = (string) WCX_Order::get_meta($order, "_billing_street_name") !== ""
                               || (string) WCX_Order::get_meta(
                $order,
                "_billing_house_number"
            ) !== "";

        $shipping_name =
            method_exists($order, "get_formatted_shipping_full_name") ? $order->get_formatted_shipping_full_name()
                : trim($order->get_shipping_first_name() . " " . $order->get_shipping_last_name());

        if ($connectEmail === null) {
            $connectEmail = WCMP()->setting_collection->getByName(WCMP_Settings::SETTING_CONNECT_EMAIL);
        }

        $connectPhone = WCMP()->setting_collection->getByName(WCMP_Settings::SETTING_CONNECT_PHONE);

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

                // If not using old fields
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
     * @param $selected_shipment_ids
     * @param $order_ids
     *
     * @throws ErrorException
     * @internal param $shipment_ids
     */
    public function add_myparcelbe_note_to_shipments($selected_shipment_ids, $order_ids)
    {
        if ($this->getSetting("barcode_in_note")) {
            return;
        }

        // Select the barcode text of the MyParcel settings
        $this->prefix_message = $this->getSetting("barcode_in_note_title");

        foreach ($order_ids as $order_id) {
            $order           = WCX::get_order($order_id);
            $order_shipments = WCX_Order::get_meta($order, WCMP_Admin::META_SHIPMENTS);
            foreach ($order_shipments as $shipment) {
                $shipment_id = $shipment["shipment_id"];
                $this->add_myparcelbe_note_to_shipment($selected_shipment_ids, $shipment_id, $order);
            }
        }

        return;
    }

    /**
     * @param $order
     *
     * @return array
     * @throws Exception
     * @deprecated
     */
    public function get_options($order)
    {
        $description = $this->getLabelDescription($order);

        // use shipment options from order when available
        $shipment_options = WCX_Order::get_meta($order, WCMP_Admin::META_SHIPMENT_OPTIONS);

        if (! empty($shipment_options)) {
            $empty_defaults = [
                "package_type"      => 1,
                "signature"         => 0,
                "label_description" => "",
                "insured_amount"    => 0,
            ];
            $options        = array_merge($empty_defaults, $shipment_options);
        } else {
            $insured_amount = $this->getInsuranceAmount();

            $options = [
                "package_type"      => self::PACKAGE,
                "signature"         => $this->isSignature(),
                "label_description" => $description,
                "insured_amount"    => $insured_amount,
            ];
        }

        // set insurance amount to int if already set
        if (isset($options["insurance"])) {
            $options["insurance"]["amount"] = self::INSURANCE_AMOUNT * 100;
        }

        // remove frontend insurance option values
        if (isset($options["insured_amount"])) {
            unset($options["insured_amount"]);
        }

        if (isset($options["insured"])) {
            unset($options["insured"]);
        }

        // load delivery options
        $myparcelbe_delivery_options = WCX_Order::get_meta($order, WCMP_Admin::META_DELIVERY_OPTIONS);

        // set delivery type
        $options["delivery_type"] = $this->get_delivery_type($order, $myparcelbe_delivery_options);

        // Options for Pickup and Pickup express delivery types:
        // always enable signature on receipt
        if ($this->is_pickup($order, $myparcelbe_delivery_options)) {
            $options["signature"] = 0;
        }

        // options signature & recipient only
        $myparcelbe_signature = WCX_Order::get_meta($order, WCMP_Admin::META_SIGNATURE);
        if (! empty($myparcelbe_signature)) {
            $options["signature"] = 1;
        }

        // allow prefiltering consignment data
        $options = apply_filters("wc_myparcelbe_order_shipment_options", $options, $order);

        // PREVENT ILLEGAL SETTINGS
        // convert numeric strings to int
        $int_options = ["package_type", "delivery_type", "signature", "return "];
        foreach ($options as $key => &$value) {
            if (in_array($key, $int_options)) {
                $value = (int) $value;
            }
        }

        // disable options
        if ($options["package_type"] != self::PACKAGE) {
            $illegal_options = ["delivery_type", "signature", "return ", "insurance", "delivery_date"];
            foreach ($options as $key => $option) {
                if (in_array($key, $illegal_options)) {
                    unset($options[$key]);
                }
            }
        }

        return $options;
    }

    /**
     * @param string ...$settings
     *
     * @return bool
     */
    private function isActiveSetting(string ...$settings): bool
    {
        foreach ($settings as $setting) {
            if (! $this->getSetting($setting)) {
                return false;
            }
        }

        return true;
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
     * @return int
     */
    public function getInsuranceAmount(): int
    {
        $insured_amount = 0;

        if ($this->isActiveSetting("insured", "insured_amount", "insured_amount_custom")) {
            $insured_amount = $this->getSetting("insured_amount_custom");
        } elseif ($this->isActiveSetting("insured", "insured_amount")) {
            $insured_amount = $this->getSetting("insured_amount");
        }

        return $insured_amount;
    }

    /**
     * @param int $timestamp
     *
     * @return false|string
     */
    private function get_next_delivery_day($timestamp)
    {
        $weekDay       = date("w", $timestamp);
        $new_timestamp = strtotime(" + 1 day", $timestamp);

        if ($weekDay == 0 || $weekDay == 1 || $new_timestamp < time()) {
            $new_timestamp = $this->get_next_delivery_day($new_timestamp);
        }

        return $new_timestamp;
    }

    /**
     * @param AbstractConsignment $consignment
     * @param WC_Order $order
     *
     * @return AbstractConsignment
     * @throws MissingFieldException
     */
    public function setCustomItems(AbstractConsignment $consignment, WC_Order $order): AbstractConsignment
    {
        $contents = (int) ($this->getSetting("package_contents") ? $this->getSetting("package_contents") : 1);

        $country = WC()->countries->get_base_country();

        foreach ($order->get_items() as $item_id => $item) {
            $product = $order->get_product_from_item($item);
            if (! empty($product)) {
                // Description
                $description = $item["name"];

                // GitHub issue https://github.com/myparcelnl/woocommerce/issues/190
                if (strlen($description) >= self::DESCRIPTION_MAX_LENGTH) {
                    $description = substr($item["name"], 0, 47) . "...";
                }
                // Amount
                $amount = (int) (isset($item["qty"]) ? $item["qty"] : 1);

                // Weight (total item weight in grams)
                $weight = (int) round($this->get_item_weight_kg($item, $order) * 1000);

                $myParcelItem =
                    (new MyParcelCustomsItem())->setDescription($description)
                        ->setAmount($amount)
                        ->setWeight($weight)
                        ->setItemValue(
                            (int) round(
                                ($item["line_total"] + $item["line_tax"]) * 100
                            )
                        )
                        ->setCountry($country)
                        ->setClassification($contents);

                $consignment->addItem($myParcelItem);
            }
        }

        return $consignment;
    }

    public function get_shipment_ids($order_ids, $args)
    {
        $shipment_ids = [];
        foreach ($order_ids as $order_id) {
            $order           = WCX::get_order($order_id);
            $order_shipments = WCX_Order::get_meta($order, WCMP_Admin::META_SHIPMENTS);
            if (! empty($order_shipments)) {
                $order_shipment_ids = [];
                // exclude concepts or only concepts
                foreach ($order_shipments as $key => $shipment) {
                    if (isset($args["exclude_concepts"]) && empty($shipment["tracktrace"])) {
                        continue;
                    }
                    if (isset($args["only_concepts"]) && ! empty($shipment["tracktrace"])) {
                        continue;
                    }

                    $order_shipment_ids[] = $shipment["shipment_id"];
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
        }

        return $shipment_ids;
    }

    /**
     * @param $order
     * @param $shipment
     *
     * @return bool|void
     */
    public function save_shipment_data($order, $shipment)
    {
        if (empty($shipment)) {
            return false;
        }

        $new_shipments                           = [];
        $new_shipments[$shipment["shipment_id"]] = $shipment;

        if (WCMP()->setting_collection->isEnabled(WCMP_Settings::SETTING_KEEP_SHIPMENTS)) {
            if ($old_shipments = WCX_Order::get_meta($order, WCMP_Admin::META_SHIPMENTS)) {
                $shipments = $old_shipments;
                foreach ($new_shipments as $shipment_id => $shipment) {
                    $shipments[$shipment_id] = $shipment;
                }
            }
        }

        $shipments = $shipments ?? $new_shipments;

        WCX_Order::update_meta_data($order, WCMP_Admin::META_SHIPMENTS, $shipments);

        return;
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
                    $shipping_method_instance = $shipping_method[1];
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
                $parsed_errors[] = sprintf("<strong>%s %s:</strong> %s",
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

    public function stream_pdf($pdf_data, $order_ids)
    {
        header("Content-type: application/pdf");
        header("Content-Disposition: inline; filename=\"{$this->get_filename($order_ids)}\"");
        echo $pdf_data;
        die();
    }

    public function download_pdf($pdf_data, $order_ids)
    {
        header("Content-Description: File Transfer");
        header("Content-Type: application/octet-stream");
        header("Content-Disposition: attachment; filename=\"{$this->get_filename($order_ids)}\"");
        header("Content-Transfer-Encoding: binary");
        header("Connection: Keep-Alive");
        header("Expires: 0");
        header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
        header("Pragma: public");
        echo $pdf_data;
        die();
    }

    public function get_filename($order_ids)
    {
        $filename = "MyParcelBE-" . date("Y-m-d") . ".pdf";

        return apply_filters("wcmyparcelbe_filename", $filename, $order_ids);
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
     * @param int $id
     * @param WC_Order $order
     *
     * @return array
     * @throws Exception
     */
    public function get_shipment_data(int $id, WC_Order $order)
    {
        $api      = $this->init_api();
        $response = $api->get_shipments($id);

        if (! empty($response["body"]["data"]["shipments"])) {
            $shipments = $response["body"]["data"]["shipments"];
            $shipment  = array_shift($shipments);

            if (! isset($shipment["id"]) || $shipment["id"] !== $id) {
                return [];
            }

            if ($shipment["status"] < 2) {
                throw new Exception(__("No label(s) created yet.", "woocommerce-myparcelbe"));
            }

            // if shipment id matches and status is not concept, get track trace barcode and status name
            $status        = $this->get_shipment_status_name($shipment["status"]);
            $track_trace   = $shipment["barcode"];
            $shipment_id   = $id;
            $shipment_data = compact("shipment_id", "status", "track_trace", "shipment");

            $this->save_shipment_data($order, $shipment_data);

            // If Channel Engine is active, add the created Track & Trace code and set shipping method to bpost in their meta data
            if (WC_CHANNEL_ENGINE_ACTIVE and ! WCX_Order::get_meta(
                    $order,
                    "_shipping_ce_track_and_trace"
                )) {
                WCX_Order::update_meta_data($order, "_shipping_ce_track_and_trace", $track_trace);
                WCX_Order::update_meta_data($order, "_shipping_ce_shipping_method", "Bpost");
            }

            return $shipment_data;
        } else {
            return [];
        }
    }

    /**
     * @param $description
     * @param $order
     *
     * @return mixed
     * @throws Exception
     */
    public function replace_shortcodes($description, WC_Order $order)
    {
        $deliveryOptions = WCMP_Admin::getDeliveryOptionsFromOrder($order);

        $replacements = [
            "[ORDER_NR]"      => $order->get_order_number(),
            "[DELIVERY_DATE]" => $deliveryOptions->getDate(),
        ];

        $description = str_replace(array_keys($replacements), array_values($replacements), $description);

        return $description;
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
     * @param        $order
     * @param string $myparcelbe_delivery_options
     *
     * @return array|bool|mixed|string
     */
    public function is_pickup($order, $myparcelbe_delivery_options = "")
    {
        if (empty($myparcelbe_delivery_options)) {
            $myparcelbe_delivery_options = WCX_Order::get_meta($order, WCMP_Admin::META_DELIVERY_OPTIONS);
        }

        $pickup_types = ["retail"];
        if (! empty($myparcelbe_delivery_options["price_comment"])
            && in_array(
                $myparcelbe_delivery_options["price_comment"],
                $pickup_types
            )) {
            return $myparcelbe_delivery_options;
        }

        // Backwards compatibility for pakjegemak data
        $pgaddress = WCX_Order::get_meta($order, WCMP_Admin::META_PGADDRESS);
        if (! empty($pgaddress) && ! empty($pgaddress["postcode"])) {
            return [
                "postal_code"   => $pgaddress["postcode"],
                "street"        => $pgaddress["street"],
                "city"          => $pgaddress["town"],
                "number"        => $pgaddress["house_number"],
                "location"      => $pgaddress["name"],
                "price_comment" => "retail",
            ];
        }

        // no pickup
        return false;
    }

    /**
     * @param        $order
     * @param string $deliveryOptions
     *
     * @return int|mixed|string
     * @deprecated
     */
    public function get_delivery_type($order, $myparcelbe_delivery_options = "")
    {
        // delivery types
        $delivery_types = [
            "morning"  => 1,
            "standard" => 2, // "default in JS API"
            "avond"    => 3,
            "retail"   => 4, // "pickup"
        ];

        if (empty($myparcelbe_delivery_options)) {
            $myparcelbe_delivery_options = WCX_Order::get_meta($order, WCMP_Admin::META_DELIVERY_OPTIONS);
        }

        // standard = default, overwrite if options found
        $delivery_type = "standard";
        if (! empty($myparcelbe_delivery_options)) {
            // pickup & pickup express store the delivery type in the delivery options,
            if (empty($myparcelbe_delivery_options["price_comment"])
                && ! empty($myparcelbe_delivery_options["time"])) {
                // check if we have a price_comment in the time option
                $delivery_time = array_shift($myparcelbe_delivery_options["time"]); // take first element in time array
                if (isset($delivery_time["price_comment"])) {
                    $delivery_type = $delivery_time["price_comment"];
                }
            } else {
                $delivery_type = $myparcelbe_delivery_options["price_comment"];
            }
        }

        // backwards compatibility for pakjegemak
        if ($pgaddress = WCX_Order::get_meta($order, "_myparcelbe_pgaddress")) {
            $delivery_type = "retail";
        }

        // convert to int (default to 2 = standard for unknown types)
        $delivery_type = isset($delivery_types[$delivery_type]) ? $delivery_types[$delivery_type] : 2;

        return $delivery_type;
    }

    /**
     * @param WC_Order $order
     * @param string $shipping_method_id
     *
     * @return bool
     */
    public function get_order_shipping_class(WC_Order $order, string $shipping_method_id = "")
    {
        if (empty($shipping_method_id)) {
            $order_shipping_methods = $order->get_items("shipping");

            if (! empty($order_shipping_methods)) {
                // we"re taking the first(we"re not handling multiple shipping methods as of yet)
                $order_shipping_method = array_shift($order_shipping_methods);
                $shipping_method_id    = $order_shipping_method["method_id"];
            } else {
                return false;
            }
        }

        $shipping_method = self::get_shipping_method($shipping_method_id);

        if (empty($shipping_method)) {
            return false;
        }

        // get shipping classes from order
        $found_shipping_classes = $this->find_order_shipping_classes($order);

        return $this->get_shipping_class($shipping_method, $found_shipping_classes);
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
     * @param array $args
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

    public function filter_myparcelbe_destination_orders($order_ids)
    {
        foreach ($order_ids as $key => $order_id) {
            $order            = WCX::get_order($order_id);
            $shipping_country = WCX_Order::get_prop($order, "shipping_country");
            // skip non-myparcel destination orders
            if (! $this->is_myparcelbe_destination($shipping_country)) {
                unset($order_ids[$key]);
            }
        }

        return $order_ids;
    }

    public function is_myparcelbe_destination($country_code)
    {
        return ($country_code == "BE" || $this->is_eu_country($country_code)
                || $this->is_world_shipment_country(
                $country_code
            ));
    }

    public function is_eu_country($country_code)
    {
        $euro_countries = [
            "AT",
            "NL",
            "BG",
            "CZ",
            "DK",
            "EE",
            "FI",
            "FR",
            "DE",
            "GR",
            "HU",
            "IE",
            "IT",
            "LV",
            "LT",
            "LU",
            "PL",
            "PT",
            "RO",
            "SK",
            "SI",
            "ES",
            "SE",
            "MC",
            "AL",
            "AD",
            "BA",
            "IC",
            "FO",
            "GI",
            "GL",
            "GG",
            "JE",
            "HR",
            "LI",
            "MK",
            "MD",
            "ME",
            "UA",
            "SM",
            "RS",
            "VA",
            "BY",
        ];

        return in_array($country_code, $euro_countries);
    }

    public function is_world_shipment_country($country_code)
    {
        $world_shipment_countries = [
            "AF",
            "AQ",
            "DZ",
            "VI",
            "AO",
            "AG",
            "AR",
            "AM",
            "AW",
            "AU",
            "AZ",
            "BS",
            "BH",
            "BD",
            "BB",
            "BZ",
            "BJ",
            "BM",
            "BT",
            "BO",
            "BW",
            "BR",
            "VG",
            "BN",
            "BF",
            "BI",
            "KH",
            "CA",
            "KY",
            "CF",
            "CL",
            "CN",
            "CO",
            "KM",
            "CG",
            "CD",
            "CR",
            "CU",
            "DJ",
            "DM",
            "DO",
            "EC",
            "EG",
            "SV",
            "GQ",
            "ER",
            "ET",
            "FK",
            "FJ",
            "PH",
            "GF",
            "PF",
            "GA",
            "GB",
            "GM",
            "GE",
            "GH",
            "GD",
            "GP",
            "GT",
            "GN",
            "GW",
            "GY",
            "HT",
            "HN",
            "HK",
            "IN",
            "ID",
            "IS",
            "IQ",
            "IR",
            "IL",
            "CI",
            "JM",
            "JP",
            "YE",
            "JO",
            "CV",
            "CM",
            "KZ",
            "KE",
            "KG",
            "KI",
            "KW",
            "LA",
            "LS",
            "LB",
            "LR",
            "LY",
            "MO",
            "MG",
            "MW",
            "MV",
            "MY",
            "ML",
            "MA",
            "MQ",
            "MR",
            "MU",
            "MX",
            "MN",
            "MS",
            "MZ",
            "MM",
            "NA",
            "NR",
            "NP",
            "NI",
            "NC",
            "NZ",
            "NE",
            "NG",
            "KP",
            "UZ",
            "OM",
            "TL",
            "PK",
            "PA",
            "PG",
            "PY",
            "PE",
            "PN",
            "PR",
            "QA",
            "RE",
            "RU",
            "RW",
            "KN",
            "LC",
            "VC",
            "PM",
            "WS",
            "ST",
            "SA",
            "SN",
            "SC",
            "SL",
            "SG",
            "SO",
            "LK",
            "SD",
            "SR",
            "SZ",
            "SY",
            "TJ",
            "TW",
            "TZ",
            "TH",
            "TG",
            "TO",
            "TT",
            "TD",
            "TN",
            "TM",
            "TC",
            "TV",
            "UG",
            "UY",
            "VU",
            "VE",
            "AE",
            "US",
            "VN",
            "ZM",
            "ZW",
            "ZA",
            "KR",
            "AN",
            "BQ",
            "CW",
            "SX",
            "XK",
            "IM",
            "MT",
            "CY",
            "CH",
            "TR",
            "NO",
        ];

        return in_array($country_code, $world_shipment_countries);
    }

    public function get_invoice_number($order)
    {
        return (string) apply_filters("wc_myparcelbe_invoice_number", $order->get_order_number());
    }

    public function get_item_display_name($item, $order)
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
     * Log data if the error logging setting is enabled.
     *
     * @param string $message
     */
    public function log(string $message): void
    {
        if (!WCMP()->setting_collection->isEnabled(WCMP_Settings::SETTING_ERROR_LOGGING)) {
            return;
        }

        // Starting with WooCommerce 3.0, logging can be grouped by context and severity.
        if (class_exists("WC_Logger") && version_compare(WOOCOMMERCE_VERSION, "3.0", " >= ")) {
            $logger = wc_get_logger();
            $logger->debug($message, ["source" => "wc-myparcelbe"]);

            return;
        }

        if (class_exists("WC_Logger")) {
            $wc_logger = function_exists("wc_get_logger") ? wc_get_logger() : new WC_Logger();
            $wc_logger->add("wc-myparcelbe", $message);

            return;
        }

        // Old WC versions didn't have a logger
        // log file in upload folder - wp-content/uploads
        $upload_dir        = wp_upload_dir();
        $upload_base       = trailingslashit($upload_dir["basedir"]);
        $log_file          = $upload_base . "myparcelbe_log.txt";
        $current_date_time = date("Y-m-d H:i:s");
        $message           = $current_date_time . " " . $message . "n";
        file_put_contents($log_file, $message, FILE_APPEND);

        return;
    }

    /**
     * @param $shipment_id
     *
     * @return mixed
     * @throws ErrorException
     * @throws Exception
     */
    private function get_shipment_barcode_from_myparcelbe_api($shipment_id)
    {
        $api      = $this->init_api();
        $response = $api->get_shipments($shipment_id);

        if (! isset($response["body"]["data"]["shipments"][0]["barcode"])) {
            throw new ErrorException("No MyParcel barcode found for shipment id; " . $shipment_id);
        }

        return $response["body"]["data"]["shipments"][0]["barcode"];
    }

    /**
     * @param $selected_shipment_ids
     * @param $shipment_id
     * @param $order
     *
     * @throws ErrorException
     */
    private function add_myparcelbe_note_to_shipment($selected_shipment_ids, $shipment_id, $order)
    {
        if (! in_array($shipment_id, $selected_shipment_ids)) {
            return;
        }

        $barcode = $this->get_shipment_barcode_from_myparcelbe_api($shipment_id);

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
     * @param $order
     *
     * @return mixed|string
     * @throws Exception
     */
    private function getLabelDescription($order)
    {
        $description = "";
        // parse description
        if ($this->getSetting("label_description")) {
            $description = $this->replace_shortcodes($this->getSetting("label_description"), $order);
        }

        return $description;
    }

    /**
     * @return int
     */
    private function isSignature(): int
    {
        return ($this->getSetting("signature")) ? 1 : 0;
    }

    /**
     * @param DeliveryOptions $delivery_options
     *
     * @return int
     */
    private function getPickupTypeByDeliveryOptions(DeliveryOptions $delivery_options): int
    {
        if ($delivery_options->isPickup()) {
            return AbstractConsignment::DELIVERY_TYPE_PICKUP;
        }

        return AbstractConsignment::DELIVERY_TYPE_STANDARD;
    }
}

return new WCMP_Export();
