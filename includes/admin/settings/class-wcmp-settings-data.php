<?php

use MyParcelNL\Sdk\src\Model\Consignment\BpostConsignment;
use MyParcelNL\Sdk\src\Model\Consignment\DPDConsignment;
use WPO\WC\MyParcelBE\Entity\SettingsFieldArguments;

if (!defined('ABSPATH')) {
    exit;
}

if (class_exists('WCMP_Settings_Data')) {
    return new WCMP_Settings_Data();
}

/**
 * This class contains all data for the admin settings screens created by the plugin.
 */
class WCMP_Settings_Data
{
    public const ENABLED  = "1";
    public const DISABLED = "0";

    public const DISPLAY_FOR_SELECTED_METHODS = "selected_methods";
    public const DISPLAY_FOR_ALL_METHODS      = "all_methods";

    /**
     * @var WCMP_Settings_Callbacks
     */
    private $callbacks;

    public function __construct()
    {
        $this->callbacks = require 'class-wcmp-settings-callbacks.php';

        // Create the MyParcel settings with the admin_init hook.
        add_action("admin_init", [$this, "create_all_settings"]);
    }

    /**
     * Create all settings sections.
     */
    public function create_all_settings(): void
    {
        $this->generate_settings(
            $this->get_sections_general(),
            WCMP_Settings::SETTINGS_GENERAL
        );

        $this->generate_settings(
            $this->get_sections_export_defaults(),
            WCMP_Settings::SETTINGS_EXPORT_DEFAULTS
        );

        $this->generate_settings(
            $this->get_sections_checkout(),
            WCMP_Settings::SETTINGS_CHECKOUT
        );

        $this->generate_settings(
            $this->get_sections_carrier_bpost(),
            WCMP_Settings::SETTINGS_BPOST,
            true
        );

        $this->generate_settings(
            $this->get_sections_carrier_dpd(),
            WCMP_Settings::SETTINGS_DPD,
            true
        );
    }

    public static function getTabs()
    {
        $array = [
            WCMP_Settings::SETTINGS_GENERAL         => __("General", "woocommerce-myparcelbe"),
            WCMP_Settings::SETTINGS_EXPORT_DEFAULTS => __("Default export settings", "woocommerce-myparcelbe"),
            WCMP_Settings::SETTINGS_CHECKOUT        => __("Checkout settings", "woocommerce-myparcelbe"),
        ];

        $array[WCMP_Settings::SETTINGS_BPOST] = __("bpost", "woocommerce-myparcelbe");
        $array[WCMP_Settings::SETTINGS_DPD]   = __("DPD", "woocommerce-myparcelbe");

        return $array;
    }

    /**
     * Generate settings sections and fields by the given $settingsArray.
     *
     * @param array  $settingsArray - Array of settings to loop through.
     * @param string $optionName    - Name to use in the identifier.
     * @param bool   $prefix        - Add the key of the top level settings as prefix before every setting or not.
     */
    private function generate_settings(array $settingsArray, string $optionName, bool $prefix = false): void
    {
        $optionIdentifier = WCMP_Settings::getOptionId($optionName);
        $defaults         = [];

        // Register settings.
        register_setting($optionIdentifier, $optionIdentifier, [$this->callbacks, 'validate']);

        foreach ($settingsArray as $name => $array) {
            foreach ($array as $section) {
                $sectionName = "{$name}_{$section["name"]}";

                add_settings_section(
                    $sectionName,
                    $section["label"],
                    function () use ($section) {
                        // Allows a description to be shown with a section title.
                        /** @noinspection PhpVoidFunctionResultUsedInspection */
                        return $this->callbacks->renderSection($section);
                    },
                    $optionIdentifier
                );

                foreach ($section["settings"] as $setting) {
                    $setting["id"] = $prefix ? "{$name}_{$setting["name"]}" : $setting["name"];

                    // Add the prefix to the name in the condition array
                    if (isset($setting["condition"])) {
                        if (is_array($setting["condition"])) {
                            $related                      = $setting["condition"]["name"];
                            $related                      = $prefix ? "{$name}_{$related}" : $related;
                            $setting["condition"]["name"] = "{$optionIdentifier}[$related]";
                        } else {
                            $related              = $setting["condition"];
                            $related              = $prefix ? "{$name}_{$related}" : $related;
                            $setting["condition"] = "{$optionIdentifier}[$related]";
                        }
                    }

                    $class = new SettingsFieldArguments($setting);

                    // Add the setting's default value to the defaults array.
                    $defaults[$setting["id"]] = $class->getDefault();

                    $defaultCallback = function () use ($class, $optionIdentifier) {
                        $this->callbacks->renderField($class, $optionIdentifier);
                    };

                    $callback = $setting["callback"] ?? $defaultCallback;

                    add_settings_field(
                        $setting["id"],
                        $setting["label"],
                        $callback,
                        $optionIdentifier,
                        $sectionName,
                        // If a custom callback is used, send the $setting as arguments. Otherwise use the created
                        // arguments from the class.
                        isset($setting["callback"]) ? $setting : $class->getArguments()
                    );
                }
            }
        }

        // Create option in wp_options with default settings if the option doesn't exist yet.
        if (false === get_option($optionIdentifier)) {
            add_option($optionIdentifier, $defaults);
        }

        // Merge any missing values into the settings
        update_option(
            $optionIdentifier,
            array_replace_recursive(
                $defaults,
                get_option($optionIdentifier)
            )
        );
    }

    /**
     * @return array
     */
    private function get_sections_general()
    {
        return [
            WCMP_Settings::SETTINGS_GENERAL => [
                [
                    "name"     => "api",
                    "label"    => __("API settings", "woocommerce-myparcelbe"),
                    "settings" => $this->get_section_general_api(),
                ],
                [
                    "name"     => "general",
                    "label"    => __("General settings", "woocommerce-myparcelbe"),
                    "settings" => $this->get_section_general_general(),
                ],
                [
                    "name"     => "diagnostics",
                    "label"    => __("Diagnostic tools", "woocommerce-myparcelbe"),
                    "settings" => $this->get_section_general_diagnostics(),
                ],
            ],
        ];
    }

    /**
     * @return array
     */
    private function get_sections_export_defaults()
    {
        return [
            WCMP_Settings::SETTINGS_EXPORT_DEFAULTS => [
                [
                    "name"     => "main",
                    "label"    => __("Default export settings", "woocommerce-myparcelbe"),
                    "settings" => $this->get_section_export_defaults_main(),
                ],
            ],
        ];
    }

    private function get_sections_checkout()
    {
        return [
            WCMP_Settings::SETTINGS_CHECKOUT => [
                [
                    "name"     => "main",
                    "label"    => __("Checkout settings", "woocommerce-myparcelbe"),
                    "settings" => $this->get_section_checkout_main(),
                ],
                [
                    "name"     => "strings",
                    "label"    => __("Titles", "woocommerce-myparcelbe"),
                    "condition" => WCMP_Settings::SETTING_DELIVERY_OPTIONS_ENABLED,
                    "settings" => $this->get_section_checkout_strings(),
                ],
            ],
        ];
    }

    /**
     * Get the array of bpost sections and their settings to be added to WordPress.
     *
     * @return array
     */
    private function get_sections_carrier_bpost()
    {
        return [
            BpostConsignment::CARRIER_NAME => [
                [
                    "name"        => "export_defaults",
                    "label"       => __("Default export settings", "woocommerce-myparcelbe"),
                    "description" => __(
                        "These settings will be applied to bpost shipments you create in the backend.",
                        "woocommerce-myparcelbe"
                    ),
                    "settings"    => $this->get_section_carrier_bpost_export_defaults(),
                ],
                [
                    "name"     => "delivery_options",
                    "label"    => __("bpost delivery options", "woocommerce-myparcelbe"),
                    "settings" => $this->get_section_carrier_bpost_delivery_options(),
                ],
                [
                    "name"     => "pickup_options",
                    "label"    => __("bpost pickup options", "woocommerce-myparcelbe"),
                    "settings" => $this->get_section_carrier_bpost_pickup_options(),
                ],
            ],
        ];
    }

    /**
     * Get the array of dpd sections and their settings to be added to WordPress.
     *
     * @return array
     */
    private function get_sections_carrier_dpd()
    {
        return [
            DPDConsignment::CARRIER_NAME => [
                [
                    "name"     => "delivery_options",
                    "label"    => __("DPD delivery options", "woocommerce-myparcelbe"),
                    "settings" => $this->get_section_carrier_dpd_delivery_options(),
                ],
                [
                    "name"     => "pickup_options",
                    "label"    => __("DPD pickup options", "woocommerce-myparcelbe"),
                    "settings" => $this->get_section_carrier_dpd_pickup_options(),
                ],
            ],
        ];
    }

    /**
     * @return array
     */
    private function get_section_general_api(): array
    {
        return [
            [
                "name"      => WCMP_Settings::SETTING_API_KEY,
                "label"     => __("Key", "woocommerce-myparcelbe"),
                "help_text" => __("api key", "woocommerce-myparcelbe"),
            ],
        ];
    }

    /**
     * @return array
     */
    private function get_section_general_general(): array
    {
        return [
            [
                "name"    => WCMP_Settings::SETTING_DOWNLOAD_DISPLAY,
                "label"   => __("Label display", "woocommerce-myparcelbe"),
                "type"    => "select",
                "options" => [
                    "download" => __("Download PDF", "woocommerce-myparcelbe"),
                    "display"  => __("Open the PDF in a new tab", "woocommerce-myparcelbe"),
                ],
            ],
            [
                "name"    => WCMP_Settings::SETTING_LABEL_FORMAT,
                "label"   => __("Label format", "woocommerce-myparcelbe"),
                "type"    => "select",
                "options" => [
                    "A4" => __("Standard printer (A4)", "woocommerce-myparcelbe"),
                    "A6" => __("Label Printer (A6)", "woocommerce-myparcelbe"),
                ],
            ],
            [
                "name"      => WCMP_Settings::SETTING_ASK_FOR_PRINT_POSITION,
                "label"     => __("Ask for print start position", "woocommerce-myparcelbe"),
                "condition" => [
                    "name"         => WCMP_Settings::SETTING_LABEL_FORMAT,
                    "type"         => "disable",
                    "parent_value" => "A4",
                    "set_value"    => self::DISABLED,
                ],
                "type"      => "toggle",
                "help_text" => __(
                    "This option enables you to continue printing where you left off last time",
                    "woocommerce-myparcelbe"
                ),
            ],
            [
                "name"      => WCMP_Settings::SETTING_TRACK_TRACE_EMAIL,
                "label"     => __("Track & Trace in email", "woocommerce-myparcelbe"),
                "type"      => "toggle",
                "help_text" => __(
                    "Add the Track & Trace code to emails to the customer.<br/><strong>Note!</strong> When you select this option, make sure you have not enabled the Track & Trace email in your MyParcel BE backend.",
                    "woocommerce-myparcelbe"
                ),
            ],
            [
                "name"      => WCMP_Settings::SETTING_TRACK_TRACE_MY_ACCOUNT,
                "label"     => __("Track & Trace in My Account", "woocommerce-myparcelbe"),
                "type"      => "toggle",
                "help_text" => __("Show Track & Trace trace code and link in My Account.", "woocommerce-myparcelbe"),
            ],
            [
                "name"      => WCMP_Settings::SETTING_PROCESS_DIRECTLY,
                "label"     => __("Process shipments directly", "woocommerce-myparcelbe"),
                "type"      => "toggle",
                "help_text" => __(
                    "When you enable this option, shipments will be directly processed when sent to MyParcel BE.",
                    "woocommerce-myparcelbe"
                ),
            ],
            [
                "name"      => WCMP_Settings::SETTING_ORDER_STATUS_AUTOMATION,
                "label"     => __("Order status automation", "woocommerce-myparcelbe"),
                "type"      => "toggle",
                "help_text" => __(
                    "Automatically set order status to a predefined status after successful MyParcel BE export.<br/>Make sure <strong>Process shipments directly</strong> is enabled when you use this option together with the <strong>Track & Trace in email</strong> option, otherwise the Track & Trace code will not be included in the customer email.",
                    "woocommerce-myparcelbe"
                ),
            ],
            [
                "name"      => WCMP_Settings::SETTING_AUTOMATIC_ORDER_STATUS,
                "condition" => WCMP_Settings::SETTING_ORDER_STATUS_AUTOMATION,
                "class"     => ["wcmp__child"],
                "label"     => __("Automatic order status", "woocommerce-myparcelbe"),
                "type"      => "select",
                "options"   => $this->callbacks->get_order_status_options(),
            ],
            [
                "name"      => WCMP_Settings::SETTING_KEEP_SHIPMENTS,
                "label"     => __("Keep old shipments", "woocommerce-myparcelbe"),
                "type"      => "toggle",
                "help_text" => __(
                    "With this option enabled, data from previous shipments (Track & Trace links) will be kept in the order when you export more than once.",
                    "woocommerce-myparcelbe"
                ),
            ],
            [
                "name"      => WCMP_Settings::SETTING_BARCODE_IN_NOTE,
                "label"     => __("Place barcode inside note", "woocommerce-myparcelbe"),
                "type"      => "toggle",
                "help_text" => __("Place the barcode inside a note of the order", "woocommerce-myparcelbe"),
            ],
            [
                "name"      => WCMP_Settings::SETTING_BARCODE_IN_NOTE_TITLE,
                "condition" => WCMP_Settings::SETTING_BARCODE_IN_NOTE,
                "class"     => ["wcmp__child"],
                "label"     => __("Title before the barcode", "woocommerce-myparcelbe"),
                "default"   => __("Track & trace code:", "woocommerce-myparcelbe"),
                "help_text" => __(
                    "You can change the text before the barcode inside an note",
                    "woocommerce-myparcelbe"
                ),
            ],
        ];
    }

    /**
     * @return array
     */
    private function get_section_general_diagnostics(): array
    {
        return [
            [
                "name"        => WCMP_Settings::SETTING_ERROR_LOGGING,
                "label"       => __("Log API communication", "woocommerce-myparcelbe"),
                "type"        => "toggle",
                "description" => '<a href="' . esc_url_raw(
                        admin_url("admin.php?page=wc-status&tab=logs")
                    ) . '" target="_blank">' . __("View logs", "woocommerce-myparcelbe") . "</a> (wc-myparcelbe)",
            ],
        ];
    }

    /**
     * Export defaults specifically for bpost.
     *
     * @return array
     */
    private function get_section_carrier_bpost_export_defaults(): array
    {
        return [
            [
                "name"  => WCMP_Settings::SETTING_CARRIER_DEFAULT_EXPORT_INSURED,
                "label" => __("Insured shipment (to €500)", "woocommerce-myparcelbe"),
                "type"  => "toggle",
            ],
            [
                "name"  => WCMP_Settings::SETTING_CARRIER_DEFAULT_EXPORT_SIGNATURE,
                "label" => __("Signature on delivery", "woocommerce-myparcelbe"),
                "type"  => "toggle",
            ],
        ];
    }

    /**
     * These are the unprefixed settings for bpost.
     * After the settings are generated every name will be prefixed with "bpost_"
     * Example: delivery_enabled => bpost_delivery_enabled
     *
     * @return array
     */
    private function get_section_carrier_bpost_delivery_options(): array
    {
        return [
            [
                "name"  => WCMP_Settings::SETTING_CARRIER_DELIVERY_ENABLED,
                "label" => __("Enable bpost delivery", "woocommerce-myparcelbe"),
                "type"  => "toggle",
            ],
            [
                "name"      => WCMP_Settings::SETTING_CARRIER_DROP_OFF_DAYS,
                "condition" => WCMP_Settings::SETTING_CARRIER_DELIVERY_ENABLED,
                "label"     => __("Drop-off days", "woocommerce-myparcelbe"),
                "callback"  => [$this->callbacks, "enhanced_select"],
                "options"   => $this->getWeekdays(),
                "default"   => [1, 2, 3, 4, 5],
                "help_text" => __("Days of the week on which you hand over parcels to bpost", "woocommerce-myparcelbe"),
            ],
            [
                "name"      => WCMP_Settings::SETTING_CARRIER_CUTOFF_TIME,
                "condition" => WCMP_Settings::SETTING_CARRIER_DELIVERY_ENABLED,
                "label"     => __("Cut-off time", "woocommerce-myparcelbe"),
                "help_text" => __(
                    "Time at which you stop processing orders for the day (format: hh:mm)",
                    "woocommerce-myparcelbe"
                ),
                "default"   => "17:00",
            ],
            [
                "name"      => WCMP_Settings::SETTING_CARRIER_DROP_OFF_DELAY,
                "condition" => WCMP_Settings::SETTING_CARRIER_DELIVERY_ENABLED,
                "label"     => __("Drop-off delay", "woocommerce-myparcelbe"),
                "type"      => "number",
                "max"       => 14,
                "help_text" => __("Number of days you need to process an order.", "woocommerce-myparcelbe"),
            ],
            [
                "name"      => WCMP_Settings::SETTING_CARRIER_DELIVERY_DAYS_WINDOW,
                "condition" => WCMP_Settings::SETTING_CARRIER_DELIVERY_ENABLED,
                "label"     => __("Show delivery date", "woocommerce-myparcelbe"),
                "type"      => "toggle",
                "default"   => self::ENABLED,
                "help_text" => __("Show the delivery date inside the delivery options.", "woocommerce-myparcelbe"),
            ],
            [
                "name"      => WCMP_Settings::SETTING_CARRIER_SIGNATURE_ENABLED,
                "condition" => WCMP_Settings::SETTING_CARRIER_DELIVERY_ENABLED,
                "label"     => __("Signature on delivery", "woocommerce-myparcelbe"),
                "type"      => "toggle",
                "help_text" => __(
                    "Enter an amount that is either positive or negative. For example, do you want to give a discount for using this function or do you want to charge extra for this delivery option.",
                    "woocommerce-myparcelbe"
                ),
            ],
            [
                "name"      => WCMP_Settings::SETTING_CARRIER_SIGNATURE_FEE,
                "condition" => WCMP_Settings::SETTING_CARRIER_SIGNATURE_ENABLED,
                "class"     => ["wcmp__child"],
                "label"     => __("Fee (optional)", "woocommerce-myparcelbe"),
                "type"      => "currency",
                "help_text" => __(
                    "Enter an amount that is either positive or negative. For example, do you want to give a discount for using this function or do you want to charge extra for this delivery option.",
                    "woocommerce-myparcelbe"
                ),
            ],
        ];
    }

    /**
     * @return array
     */
    private function get_section_carrier_bpost_pickup_options(): array
    {
        return [
            [
                "name"  => WCMP_Settings::SETTING_CARRIER_PICKUP_ENABLED,
                "label" => __("Enable bpost pickup", "woocommerce-myparcelbe"),
                "type"  => "toggle",
            ],
            [
                "name"      => WCMP_Settings::SETTING_CARRIER_PICKUP_FEE,
                "condition" => WCMP_Settings::SETTING_CARRIER_PICKUP_ENABLED,
                "class"     => ["wcmp__child"],
                "label"     => __("Fee (optional)", "woocommerce-myparcelbe"),
                "type"      => "currency",
                "help_text" => __(
                    "Enter an amount that is either positive or negative. For example, do you want to give a discount for using this function or do you want to charge extra for this delivery option.",
                    "woocommerce-myparcelbe"
                ),
            ],
        ];
    }

    /**
     * These are the unprefixed settings for dpd.
     * After the settings are generated every name will be prefixed with "dpd_"
     * Example: delivery_enabled => dpd_delivery_enabled
     *
     * @return array
     */
    private function get_section_carrier_dpd_delivery_options(): array
    {
        return [
            [
                "name"  => WCMP_Settings::SETTING_CARRIER_DELIVERY_ENABLED,
                "label" => __("Enable DPD delivery", "woocommerce-myparcelbe"),
                "type"  => "toggle",
            ],
            [
                "name"      => WCMP_Settings::SETTING_CARRIER_DROP_OFF_DAYS,
                "condition" => WCMP_Settings::SETTING_CARRIER_DELIVERY_ENABLED,
                "label"     => __("Drop-off days", "woocommerce-myparcelbe"),
                "callback"  => [$this->callbacks, "enhanced_select"],
                "options"   => $this->getWeekdays(),
                "default"   => [1, 2, 3, 4, 5],
                "help_text" => __("Days of the week on which you hand over parcels to DPD", "woocommerce-myparcelbe"),
            ],
            [
                "name"        => WCMP_Settings::SETTING_CARRIER_CUTOFF_TIME,
                "condition"   => WCMP_Settings::SETTING_CARRIER_DELIVERY_ENABLED,
                "label"       => __("Cut-off time", "woocommerce-myparcelbe"),
                "placeholder" => "17:00",
                "default"     => "17:00",
                "help_text"   => __(
                    "Time at which you stop processing orders for the day (format: hh:mm)",
                    "woocommerce-myparcelbe"
                ),
            ],
            [
                "name"      => WCMP_Settings::SETTING_CARRIER_DROP_OFF_DELAY,
                "condition" => WCMP_Settings::SETTING_CARRIER_DELIVERY_ENABLED,
                "label"     => __("Drop-off delay", "woocommerce-myparcelbe"),
                "type"      => "number",
                "max"       => 14,
                "help_text" => __("Number of days you need to process an order.", "woocommerce-myparcelbe"),
            ],
            [
                "name"      => WCMP_Settings::SETTING_CARRIER_DELIVERY_DAYS_WINDOW,
                "condition" => WCMP_Settings::SETTING_CARRIER_DELIVERY_ENABLED,
                "label"     => __("Show delivery date", "woocommerce-myparcelbe"),
                "type"      => "toggle",
                "default"   => self::ENABLED,
                "help_text" => __("Show the delivery date inside the delivery options.", "woocommerce-myparcelbe"),
            ],
        ];
    }

    /**
     * @return array
     */
    private function get_section_carrier_dpd_pickup_options(): array
    {
        return [
            [
                "name"      => WCMP_Settings::SETTING_CARRIER_PICKUP_ENABLED,
                "label"     => __("Enable DPD pickup", "woocommerce-myparcelbe"),
                "type"      => "toggle",
                "help_text" => __(
                    "Enter an amount that is either positive or negative. For example, do you want to give a discount for using this function or do you want to charge extra for this delivery option.",
                    "woocommerce-myparcelbe"
                ),
            ],
            [
                "name"      => WCMP_Settings::SETTING_CARRIER_PICKUP_FEE,
                "condition" => WCMP_Settings::SETTING_CARRIER_PICKUP_ENABLED,
                "class"     => ["wcmp__child"],
                "label"     => __("Fee (optional)", "woocommerce-myparcelbe"),
                "type"      => "currency",
                "help_text" => __(
                    "Enter an amount that is either positive or negative. For example, do you want to give a discount for using this function or do you want to charge extra for this delivery option.",
                    "woocommerce-myparcelbe"
                ),
            ],
        ];
    }

    /**
     * @return array
     */
    private function get_section_export_defaults_main()
    {
        return [
            [
                "name"      => WCMP_Settings::SETTING_SHIPPING_METHODS_PACKAGE_TYPES,
                "label"     => __("Package types", "woocommerce-myparcelbe"),
                "callback"  => [$this->callbacks, "enhanced_select"],
                "loop"      => WCMP_Data::getPackageTypesHuman(),
                "options"   => WCMP_Settings_Callbacks::getShippingMethods(),
                "default"   => [],
                "help_text" => __(
                    "Select one or more shipping methods for each MyParcel BE package type",
                    "woocommerce-myparcelbe"
                ),
            ],
            [
                "name"      => WCMP_Settings::SETTING_CONNECT_EMAIL,
                "label"     => __("Connect customer email", "woocommerce-myparcelbe"),
                "type"      => "toggle",
                "help_text" => __(
                    "When you connect the customer email, MyParcel BE can send a Track & Trace email to this address. In your %sMyParcel BE backend%s you can enable or disable this email and format it in your own style.",
                    "woocommerce-myparcelbe"
                ),
                '<a href="https://backoffice.sendmyparcel.be/settings/account" target="_blank">',
                '</a>',
            ],
            [
                "name"      => WCMP_Settings::SETTING_CONNECT_PHONE,
                "label"     => __("Connect customer phone", "woocommerce-myparcelbe"),
                "type"      => "toggle",
                "help_text" => __(
                    "When you connect the customer's phone number, the courier can use this for the delivery of the parcel. This greatly increases the delivery success rate for foreign shipments.",
                    "woocommerce-myparcelbe"
                ),
            ],
            [
                "name"      => WCMP_Settings::SETTING_LABEL_DESCRIPTION,
                "label"     => __("Label description", "woocommerce-myparcelbe"),
                "help_text" => __(
                    "When you connect the customer's phone number, the courier can use this for the delivery of the parcel. This greatly increases the delivery success rate for foreign shipments.",
                    "woocommerce-myparcelbe"
                ),
            ],
        ];
    }

    /**
     * @return array
     */
    private function get_section_checkout_main(): array
    {
        return [
            [
                "name"      => WCMP_Settings::SETTING_USE_SPLIT_ADDRESS_FIELDS,
                "label"     => __("MyParcel BE address fields", "woocommerce-myparcelbe"),
                "type"      => "toggle",
                "help_text" => __(
                    "When enabled the checkout will use the MyParcel BE address fields. This means there will be three separate fields for street name, number and suffix. Want to use the WooCommerce default fields? Leave this option unchecked.",
                    "woocommerce-myparcelbe"
                ),
            ],
            [
                "name"      => WCMP_Settings::SETTING_DELIVERY_OPTIONS_ENABLED,
                "label"     => __("Enable MyParcel BE delivery options", "woocommerce-myparcelbe"),
                "type"      => "toggle",
                "help_text" => __(
                    "The MyParcel delivery options allow your customers to select whether they want their parcel delivered at home or to a pickup point. Depending on the settings you can allow them to select a date, time and even options like requiring a signature on delivery.",
                    "woocommerce-myparcelbe"
                ),
            ],
            [
                "name"      => WCMP_Settings::SETTING_DELIVERY_OPTIONS_DISPLAY,
                "condition" => WCMP_Settings::SETTING_DELIVERY_OPTIONS_ENABLED,
                "label"     => __("Display for", "woocommerce-myparcelbe"),
                "type"      => "select",
                "help_text" => __(
                    "You can link the delivery options to specific shipping methods by adding them to the package types under \"Standard export settings\". The delivery options are not visible at foreign addresses.",
                    "woocommerce-myparcelbe"
                ),
                "options"   => [
                    self::DISPLAY_FOR_SELECTED_METHODS => __(
                        "Shipping methods associated with Parcels",
                        "woocommerce-myparcelbe"
                    ),
                    self::DISPLAY_FOR_ALL_METHODS      => __("All shipping methods", "woocommerce-myparcelbe"),
                ],
            ],
            [
                "name"      => WCMP_Settings::SETTING_DELIVERY_OPTIONS_POSITION,
                "condition" => WCMP_Settings::SETTING_DELIVERY_OPTIONS_ENABLED,
                "label"     => __("Checkout position", "woocommerce-myparcelbe"),
                "type"      => "select",
                "default"   => "woocommerce_after_checkout_billing_form",
                "options"   => [
                    "woocommerce_after_checkout_billing_form"  => __(
                        "Show checkout options after billing details",
                        "woocommerce-myparcelbe"
                    ),
                    "woocommerce_after_checkout_shipping_form" => __(
                        "Show checkout options after shipping details",
                        "woocommerce-myparcelbe"
                    ),
                    "woocommerce_after_order_notes"            => __(
                        "Show checkout options after notes",
                        "woocommerce-myparcelbe"
                    ),
                ],
                "help_text" => __(
                    "You can change the place of the checkout options on the checkout page. By default it will be placed after shipping details.",
                    "woocommerce-myparcelbe"
                ),
            ],
            [
                "name"              => WCMP_Settings::SETTING_DELIVERY_OPTIONS_CUSTOM_CSS,
                "condition"         => WCMP_Settings::SETTING_DELIVERY_OPTIONS_ENABLED,
                "label"             => __("Custom styles", "woocommerce-myparcelbe"),
                "type"              => "textarea",
                "custom_attributes" => [
                    "style" => "font-family: monospace;",
                    "rows"  => "8",
                    "cols"  => "12",
                ],
            ],
        ];
    }

    /**
     * Get the weekdays from WP_Locale and remove any entries. Sunday is removed by default unless `null` is passed.
     *
     * @param int|null ...$remove
     *
     * @return array
     */
    private function getWeekdays(...$remove): array
    {
        $weekdays = (new WP_Locale())->weekday;

        if ($remove !== null) {
            $remove = count($remove) ? $remove : [0];
            foreach ($remove as $index) {
                unset($weekdays[$index]);
            }
        }

        return $weekdays;
    }

    private function get_section_checkout_strings(): array
    {
        return [
            [
                "name"      => WCMP_Settings::SETTING_HEADER_DELIVERY_OPTIONS_TITLE,
                "condition" => WCMP_Settings::SETTING_DELIVERY_OPTIONS_ENABLED,
                "label"     => __("Delivery options title", "woocommerce-myparcelbe"),
                "title"     => "Delivery options title",
                "help_text" => __(
                    "You can place a delivery title above the MyParcel BE options. When there is no title, it will not be visible.",
                    "woocommerce-myparcelbe"
                ),
            ],
            [
                "name"      => WCMP_Settings::SETTING_DELIVERY_TITLE,
                "condition" => WCMP_Settings::SETTING_DELIVERY_OPTIONS_ENABLED,
                "label"     => __("Delivery title", "woocommerce-myparcelbe"),
                "default"   => __("Delivered at home or at work", "woocommerce-myparcelbe"),
            ],
            [
                "name"      => WCMP_Settings::SETTING_STANDARD_TITLE,
                "condition" => WCMP_Settings::SETTING_DELIVERY_OPTIONS_ENABLED,
                "label"     => __("Standard delivery title", "woocommerce-myparcelbe"),
                "help_text" => __(
                    "When there is no title, the delivery time will automatically be visible.",
                    "woocommerce-myparcelbe"
                ),
                "default"   => __("Standard delivery", "woocommerce-myparcelbe"),
            ],
            [
                "name"      => WCMP_Settings::SETTING_SIGNATURE_TITLE,
                "condition" => WCMP_Settings::SETTING_DELIVERY_OPTIONS_ENABLED,
                "label"     => __("Signature on delivery", "woocommerce-myparcelbe"),
                "default"   => __("Signature on delivery", "woocommerce-myparcelbe"),
            ],
            [
                "name"      => WCMP_Settings::SETTING_PICKUP_TITLE,
                "condition" => WCMP_Settings::SETTING_DELIVERY_OPTIONS_ENABLED,
                "label"     => __("Pickup title", "woocommerce-myparcelbe"),
                "default"   => __("Pickup", "woocommerce-myparcelbe"),
            ],
        ];
    }
}

new WCMP_Settings_Data();
