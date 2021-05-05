<?php

use MyParcelNL\Sdk\src\Model\Consignment\BpostConsignment;
use MyParcelNL\Sdk\src\Model\Consignment\DPDConsignment;
use MyParcelNL\Sdk\src\Model\Consignment\PostNLConsignment;
use WPO\WC\MyParcelBE\Entity\SettingsFieldArguments;

if (! defined('ABSPATH')) {
    exit;
}

if (class_exists('WCMPBE_Settings_Data')) {
    return new WCMPBE_Settings_Data();
}

/**
 * This class contains all data for the admin settings screens created by the plugin.
 */
class WCMPBE_Settings_Data
{
    public const ENABLED  = "1";
    public const DISABLED = "0";

    public const DISPLAY_FOR_SELECTED_METHODS = "selected_methods";
    public const DISPLAY_FOR_ALL_METHODS      = "all_methods";

    public const DISPLAY_TOTAL_PRICE     = "total_price";
    public const DISPLAY_SURCHARGE_PRICE = "surcharge";

    public const PICKUP_LOCATIONS_VIEW_MAP  = "map";
    public const PICKUP_LOCATIONS_VIEW_LIST = "list";

    public const CHANGE_STATUS_AFTER_PRINTING = "after_printing";
    public const CHANGE_STATUS_AFTER_EXPORT   = "after_export";

    public const NOT_ACTIVE        = "notActive";
    public const NO_OPTIONS        = "noOptions";
    public const EQUAL_TO_SHIPMENT = "equalToShipment";

    /**
     * @var WCMPBE_Settings_Callbacks
     */
    private $callbacks;

    public function __construct()
    {
        $this->callbacks = require 'class-wcmpbe-settings-callbacks.php';

        // Create the MyParcel settings with the admin_init hook.
        add_action("admin_init", [$this, "create_all_settings"]);
    }

    /**
     * Create all settings sections.
     *
     * @throws \Exception
     */
    public function create_all_settings(): void
    {
        $this->generate_settings(
            $this->get_sections_general(),
            WCMPBE_Settings::SETTINGS_GENERAL
        );

        $this->generate_settings(
            $this->get_sections_export_defaults(),
            WCMPBE_Settings::SETTINGS_EXPORT_DEFAULTS
        );

        $this->generate_settings(
            $this->get_sections_checkout(),
            WCMPBE_Settings::SETTINGS_CHECKOUT
        );

        $this->generate_settings(
            $this->get_sections_carrier_postnl(),
            WCMPBE_Settings::SETTINGS_POSTNL,
            true
        );

        $this->generate_settings(
            $this->get_sections_carrier_bpost(),
            WCMPBE_Settings::SETTINGS_BPOST,
            true
        );

        $this->generate_settings(
            $this->get_sections_carrier_dpd(),
            WCMPBE_Settings::SETTINGS_DPD,
            true
        );
    }

    /**
     * @return array
     */
    public static function getTabs(): array
    {
        $array = [
            WCMPBE_Settings::SETTINGS_GENERAL         => __("General", "woocommerce-myparcelbe"),
            WCMPBE_Settings::SETTINGS_EXPORT_DEFAULTS => __("Default export settings", "woocommerce-myparcelbe"),
            WCMPBE_Settings::SETTINGS_CHECKOUT        => __("Checkout settings", "woocommerce-myparcelbe"),
        ];

        $array[WCMPBE_Settings::SETTINGS_BPOST]  = __("bpost", "woocommerce-myparcelbe");
        $array[WCMPBE_Settings::SETTINGS_DPD]    = __("DPD", "woocommerce-myparcelbe");
        $array[WCMPBE_Settings::SETTINGS_POSTNL] = __("PostNL", "woocommerce-myparcelbe");

        return $array;
    }

    /**
     * Generate settings sections and fields by the given $settingsArray.
     *
     * @param array  $settingsArray - Array of settings to loop through.
     * @param string $optionName    - Name to use in the identifier.
     * @param bool   $prefix        - Add the key of the top level settings as prefix before every setting or not.
     *
     * @throws \Exception
     */
    private function generate_settings(array $settingsArray, string $optionName, bool $prefix = false): void
    {
        $optionIdentifier = WCMPBE_Settings::getOptionId($optionName);
        $defaults         = [];

        // Register settings.
        register_setting($optionIdentifier, $optionIdentifier, [$this->callbacks, 'validate']);

        foreach ($settingsArray as $name => $array) {
            foreach ($array as $section) {
                $sectionName = "{$name}_{$section["name"]}";

                add_settings_section(
                    $sectionName,
                    $section["label"],
                    /**
                     * Allows a description to be shown with a section title.
                     */
                    static function() use ($section) {
                        WCMPBE_Settings_Callbacks::renderSection($section);
                    },
                    $optionIdentifier
                );

                foreach ($section["settings"] as $setting) {
                    $namePrefix            = $prefix ? "{$name}_" : '';
                    $setting["id"]        = $prefix ? "{$name}_{$setting["name"]}" : $setting["name"];
                    $setting["option_id"] = $optionIdentifier;

                    $class = new SettingsFieldArguments($setting, "{$optionIdentifier}[{$namePrefix}", ']');

                    // Add the setting's default value to the defaults array.
                    $defaults[$setting["id"]] = $class->getDefault();

                    if (isset(get_option($optionIdentifier)[$class->getId()])) {
                        $class->setValue(get_option($optionIdentifier)[$class->getId()]);
                    }

                    // Default callback
                    $callback = static function() use ($class) {
                        WCMPBE_Settings_Callbacks::renderField($class);
                    };

                    // Pass the class to custom callbacks as well.
                    if (isset($setting['callback'])) {
                        $callback = static function () use ($setting, $class) {
                            call_user_func($setting["callback"], $class);
                        };
                    }

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
            WCMPBE_Settings::SETTINGS_GENERAL => [
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
            WCMPBE_Settings::SETTINGS_EXPORT_DEFAULTS => [
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
            WCMPBE_Settings::SETTINGS_CHECKOUT => [
                [
                    "name"     => "main",
                    "label"    => __("Checkout settings", "woocommerce-myparcelbe"),
                    "settings" => $this->get_section_checkout_main(),
                ],
                [
                    "name"      => "strings",
                    "label"     => __("Titles", "woocommerce-myparcelbe"),
                    "condition" => WCMPBE_Settings::SETTING_DELIVERY_OPTIONS_ENABLED,
                    "settings"  => $this->get_section_checkout_strings(),
                ],
            ],
        ];
    }

    /**
     * Get the array of PostNL sections and their settings to be added to WordPress.
     *
     * @return array
     */
    private function get_sections_carrier_postnl()
    {
        return [
            PostNLConsignment::CARRIER_NAME => [
                [
                    "name"        => "export_defaults",
                    "label"       => __("PostNL export settings", "woocommerce-myparcelbe"),
                    "description" => __(
                        "These settings will be applied to PostNL shipments you create in the backend.",
                        "woocommerce-myparcelbe"
                    ),
                    "settings"    => $this->get_section_carrier_postnl_export_defaults(),
                ],
                [
                    "name"     => "delivery_options",
                    "label"    => __("PostNL delivery options", "woocommerce-myparcelbe"),
                    "settings" => $this->get_section_carrier_postnl_delivery_options(),
                ],
                [
                    "name"     => "pickup_options",
                    "label"    => __("PostNL pickup options", "woocommerce-myparcelbe"),
                    "settings" => $this->get_section_carrier_postnl_pickup_options(),
                ],
            ],
        ];
    }

    /**
     * Get the array of PostNL sections and their settings to be added to WordPress.
     *
     * @return array
     */
    private function get_sections_carrier_bpost()
    {
        return [
            BpostConsignment::CARRIER_NAME => [
                [
                    "name"        => "export_defaults",
                    "label"       => __("bpost export settings", "woocommerce-myparcelbe"),
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
     * Get the array of PostNL sections and their settings to be added to WordPress.
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
                "name"      => WCMPBE_Settings::SETTING_API_KEY,
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
                "name"    => WCMPBE_Settings::SETTING_DOWNLOAD_DISPLAY,
                "label"   => __("Label display", "woocommerce-myparcelbe"),
                "type"    => "select",
                "options" => [
                    "download" => __("Download PDF", "woocommerce-myparcelbe"),
                    "display"  => __("Open the PDF in a new tab", "woocommerce-myparcelbe"),
                ],
            ],
            [
                "name"    => WCMPBE_Settings::SETTING_LABEL_FORMAT,
                "label"   => __("Label format", "woocommerce-myparcelbe"),
                "type"    => "select",
                "options" => [
                    "A4" => __("Standard printer (A4)", "woocommerce-myparcelbe"),
                    "A6" => __("Label Printer (A6)", "woocommerce-myparcelbe"),
                ],
            ],
            [
                "name"       => WCMPBE_Settings::SETTING_ASK_FOR_PRINT_POSITION,
                "label"      => __("Ask for print start position", "woocommerce-myparcelbe"),
                "condition" => [
                    "parent_name"  => WCMPBE_Settings::SETTING_LABEL_FORMAT,
                    "type"         => "disable",
                    "parent_value" => "A4",
                    "set_value"    => self::DISABLED,
                ],
                "type"       => "toggle",
                "help_text"  => __(
                    "This option enables you to continue printing where you left off last time",
                    "woocommerce-myparcelbe"
                ),
            ],
            [
                "name"      => WCMPBE_Settings::SETTING_TRACK_TRACE_EMAIL,
                "label"     => __("Track & Trace in email", "woocommerce-myparcelbe"),
                "type"      => "toggle",
                "help_text" => __(
                    "Add the Track & Trace code to emails to the customer.<br/><strong>Note!</strong> When you select this option, make sure you have not enabled the Track & Trace email in your MyParcel backend.",
                    "woocommerce-myparcelbe"
                ),
            ],
            [
                "name"      => WCMPBE_Settings::SETTING_TRACK_TRACE_MY_ACCOUNT,
                "label"     => __("Track & Trace in My Account", "woocommerce-myparcelbe"),
                "type"      => "toggle",
                "help_text" => __("Show Track & Trace trace code and link in My Account.", "woocommerce-myparcelbe"),
            ],
            [
                "name"      => WCMPBE_Settings::SETTING_PROCESS_DIRECTLY,
                "label"     => __("Process shipments directly", "woocommerce-myparcelbe"),
                "type"      => "toggle",
                "help_text" => __(
                    "When you enable this option, shipments will be directly processed when sent to MyParcel.",
                    "woocommerce-myparcelbe"
                ),
            ],
            [
                "name"      => WCMPBE_Settings::SETTING_ORDER_STATUS_AUTOMATION,
                "label"     => __("Order status automation", "woocommerce-myparcelbe"),
                "type"      => "toggle",
                "help_text" => __(
                    "Automatically set order status to a predefined status after successful MyParcel export.<br/>Make sure <strong>Process shipments directly</strong> is enabled when you use this option together with the <strong>Track & Trace in email</strong> option, otherwise the Track & Trace code will not be included in the customer email.",
                    "woocommerce-myparcelbe"
                ),
            ],
            [
                "name"      => WCMPBE_Settings::SETTING_CHANGE_ORDER_STATUS_AFTER,
                "condition" => WCMPBE_Settings::SETTING_ORDER_STATUS_AUTOMATION,
                "class"     => ["wcmpbe__child"],
                "label"     => __("setting_change_order_status_after", "woocommerce-myparcelbe"),
                "type"      => "select",
                "default"   => self::CHANGE_STATUS_AFTER_PRINTING,
                "options"   => [
                    self::CHANGE_STATUS_AFTER_PRINTING => __("setting_change_status_after_printing", "woocommerce-myparcelbe"),
                    self::CHANGE_STATUS_AFTER_EXPORT   => __("setting_change_status_after_export", "woocommerce-myparcelbe"),
                ],
                "help_text" => __(
                    "setting_change_status_after_help_text",
                    "woocommerce-myparcelbe"
                ),
            ],
            [
                "name"      => WCMPBE_Settings::SETTING_AUTOMATIC_ORDER_STATUS,
                "condition" => WCMPBE_Settings::SETTING_ORDER_STATUS_AUTOMATION,
                "class"     => ["wcmpbe__child"],
                "label"     => __("setting_automatic_order_status", "woocommerce-myparcelbe"),
                "type"      => "select",
                "options"   => WCMPBE_Settings_Callbacks::get_order_status_options(),
            ],
            [
                "name"      => WCMPBE_Settings::SETTING_BARCODE_IN_NOTE,
                "label"     => __("Place barcode inside note", "woocommerce-myparcelbe"),
                "type"      => "toggle",
                "help_text" => __("Place the barcode inside a note of the order", "woocommerce-myparcelbe"),
            ],
            [
                "name"      => WCMPBE_Settings::SETTING_BARCODE_IN_NOTE_TITLE,
                "condition" => WCMPBE_Settings::SETTING_BARCODE_IN_NOTE,
                "class"     => ["wcmpbe__child"],
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
                "name"        => WCMPBE_Settings::SETTING_ERROR_LOGGING,
                "label"       => __("Log API communication", "woocommerce-myparcelbe"),
                "type"        => "toggle",
                "description" => '<a href="' . esc_url_raw(
                        admin_url("admin.php?page=wc-status&tab=logs")
                    ) . '" target="_blank">' . __("View logs", "woocommerce-myparcelbe") . "</a> (wc-myparcelbe)",
            ],
        ];
    }

    /**
     * Export defaults specifically for postnl.
     *
     * @return array
     */
    private function get_section_carrier_postnl_export_defaults(): array
    {
        return [
            [
                "name"      => WCMPBE_Settings::SETTING_CARRIER_DEFAULT_EXPORT_ONLY_RECIPIENT,
                "label"     => __("shipment_options_only_recipient", "woocommerce-myparcelbe"),
                "help_text" => __("shipment_options_only_recipient_help_text", "woocommerce-myparcelbe"),
                "type"      => "toggle",
            ],
            [
                "name"      => WCMPBE_Settings::SETTING_CARRIER_DEFAULT_EXPORT_SIGNATURE,
                "label"     => __("shipment_options_signature", "woocommerce-myparcelbe"),
                "help_text" => __("shipment_options_signature_help_text", "woocommerce-myparcelbe"),
                "type"      => "toggle",
            ],
            [
                "name"      => WCMPBE_Settings::SETTING_CARRIER_DEFAULT_EXPORT_INSURED,
                "label"     => __("shipment_options_insured", "woocommerce-myparcelbe"),
                "help_text" => __("shipment_options_insured_help_text", "woocommerce-myparcelbe"),
                "type"      => "toggle",
            ],
            [
                "name"      => WCMPBE_Settings::SETTING_CARRIER_DEFAULT_EXPORT_INSURED_FROM_PRICE,
                "condition" => WCMPBE_Settings::SETTING_CARRIER_DEFAULT_EXPORT_INSURED,
                "label"     => __("shipment_options_insured_from_price", "woocommerce-myparcelbe"),
                "help_text" => __("shipment_options_insured_from_price_help_text", "woocommerce-myparcelbe"),
                "type"      => "number",
            ],
        ];
    }

    /**
     * These are the unprefixed settings for postnl.
     * After the settings are generated every name will be prefixed with "postnl_"
     * Example: delivery_enabled => postnl_delivery_enabled
     *
     * @return array
     */
    private function get_section_carrier_postnl_delivery_options(): array
    {
        return [
            [
                "name"  => WCMPBE_Settings::SETTING_CARRIER_DELIVERY_ENABLED,
                "label" => __("Enable PostNL delivery", "woocommerce-myparcelbe"),
                "type"  => "toggle",
            ],
            [
                "name"      => WCMPBE_Settings::SETTING_CARRIER_DROP_OFF_DAYS,
                "condition" => WCMPBE_Settings::SETTING_CARRIER_DELIVERY_ENABLED,
                "label"     => __("Drop-off days", "woocommerce-myparcelbe"),
                "callback"  => [WCMPBE_Settings_Callbacks::class, "enhanced_select"],
                "options"   => $this->getWeekdays(null),
                "default"   => [2],
                "help_text" => __("Days of the week on which you hand over parcels to PostNL", "woocommerce-myparcelbe"),
            ],
            [
                "name"      => WCMPBE_Settings::SETTING_CARRIER_CUTOFF_TIME,
                "condition" => WCMPBE_Settings::SETTING_CARRIER_DELIVERY_ENABLED,
                "label"     => __("Cut-off time", "woocommerce-myparcelbe"),
                "help_text" => __(
                    "Time at which you stop processing orders for the day (format: hh:mm)",
                    "woocommerce-myparcelbe"
                ),
                "default"   => "17:00",
            ],
            [
                "name"      => WCMPBE_Settings::SETTING_CARRIER_DROP_OFF_DELAY,
                "condition" => WCMPBE_Settings::SETTING_CARRIER_DELIVERY_ENABLED,
                "label"     => __("Drop-off delay", "woocommerce-myparcelbe"),
                "type"      => "number",
                "max"       => 14,
                "help_text" => __("Number of days you need to process an order.", "woocommerce-myparcelbe"),
            ],
            [
                "name"      => WCMPBE_Settings::SETTING_CARRIER_DELIVERY_DAYS_WINDOW,
                "condition" => WCMPBE_Settings::SETTING_CARRIER_DELIVERY_ENABLED,
                "label"     => __("Show delivery date", "woocommerce-myparcelbe"),
                "type"      => "toggle",
                "default"   => self::ENABLED,
                "help_text" => __("Show the delivery date inside the delivery options.", "woocommerce-myparcelbe"),
            ],
            [
                "name"      => WCMPBE_Settings::SETTING_CARRIER_ONLY_RECIPIENT_ENABLED,
                "condition" => WCMPBE_Settings::SETTING_CARRIER_DELIVERY_ENABLED,
                "label"     => __("shipment_options_only_recipient", "woocommerce-myparcelbe"),
                "type"      => "toggle",
            ],
            self::getFeeField(
                WCMPBE_Settings::SETTING_CARRIER_ONLY_RECIPIENT_FEE,
                [
                    WCMPBE_Settings::SETTING_CARRIER_DELIVERY_ENABLED,
                    WCMPBE_Settings::SETTING_CARRIER_ONLY_RECIPIENT_ENABLED,
                ]
            ),
            [
                "name"      => WCMPBE_Settings::SETTING_CARRIER_SIGNATURE_ENABLED,
                "condition" => WCMPBE_Settings::SETTING_CARRIER_DELIVERY_ENABLED,
                "label"     => __("shipment_options_signature", "woocommerce-myparcelbe"),
                "type"      => "toggle",
                "help_text" => __(
                    "Enter an amount that is either positive or negative. For example, do you want to give a discount for using this function or do you want to charge extra for this delivery option.",
                    "woocommerce-myparcelbe"
                ),
            ],
            self::getFeeField(
                WCMPBE_Settings::SETTING_CARRIER_SIGNATURE_FEE,
                [
                    WCMPBE_Settings::SETTING_CARRIER_DELIVERY_ENABLED,
                    WCMPBE_Settings::SETTING_CARRIER_SIGNATURE_ENABLED,
                ]
            ),
        ];
    }

    /**
     * @return array
     */
    private function get_section_carrier_postnl_pickup_options(): array
    {
        return [
            [
                "name"  => WCMPBE_Settings::SETTING_CARRIER_PICKUP_ENABLED,
                "label" => __("Enable PostNL pickup", "woocommerce-myparcelbe"),
                "type"  => "toggle",
            ],
            self::getFeeField(
                WCMPBE_Settings::SETTING_CARRIER_PICKUP_FEE,
                [
                    WCMPBE_Settings::SETTING_CARRIER_PICKUP_ENABLED,
                ]
            ),
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
                "name"      => WCMPBE_Settings::SETTING_CARRIER_DEFAULT_EXPORT_INSURED,
                "label"     => __("shipment_options_insured", "woocommerce-myparcelbe"),
                "help_text" => __("shipment_options_insured_help_text", "woocommerce-myparcelbe"),
                "type"      => "toggle",
            ],
            [
                "name"      => WCMPBE_Settings::SETTING_CARRIER_DEFAULT_EXPORT_INSURED_FROM_PRICE,
                "condition" => WCMPBE_Settings::SETTING_CARRIER_DEFAULT_EXPORT_INSURED,
                "label"     => __("shipment_options_insured_from_price", "woocommerce-myparcelbe"),
                "help_text" => __("shipment_options_insured_from_price_help_text", "woocommerce-myparcelbe"),
                "type"      => "number",
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
                "name"  => WCMPBE_Settings::SETTING_CARRIER_DELIVERY_ENABLED,
                "label" => __("Enable bpost delivery", "woocommerce-myparcelbe"),
                "type"  => "toggle",
            ],
            [
                "name"      => WCMPBE_Settings::SETTING_CARRIER_DROP_OFF_DAYS,
                "condition" => WCMPBE_Settings::SETTING_CARRIER_DELIVERY_ENABLED,
                "label"     => __("Drop-off days", "woocommerce-myparcelbe"),
                "callback"  => [WCMPBE_Settings_Callbacks::class, "enhanced_select"],
                "options"   => $this->getWeekdays(null),
                "default"   => [2],
                "help_text" => __("Days of the week on which you hand over parcels to bpost", "woocommerce-myparcelbe"),
            ],
            [
                "name"      => WCMPBE_Settings::SETTING_CARRIER_CUTOFF_TIME,
                "condition" => WCMPBE_Settings::SETTING_CARRIER_DELIVERY_ENABLED,
                "label"     => __("Cut-off time", "woocommerce-myparcelbe"),
                "help_text" => __(
                    "Time at which you stop processing orders for the day (format: hh:mm)",
                    "woocommerce-myparcelbe"
                ),
                "default"   => "17:00",
            ],
            [
                "name"      => WCMPBE_Settings::SETTING_CARRIER_DROP_OFF_DELAY,
                "condition" => WCMPBE_Settings::SETTING_CARRIER_DELIVERY_ENABLED,
                "label"     => __("Drop-off delay", "woocommerce-myparcelbe"),
                "type"      => "number",
                "max"       => 14,
                "help_text" => __("Number of days you need to process an order.", "woocommerce-myparcelbe"),
            ],
            [
                "name"      => WCMPBE_Settings::SETTING_CARRIER_DELIVERY_DAYS_WINDOW,
                "condition" => WCMPBE_Settings::SETTING_CARRIER_DELIVERY_ENABLED,
                "label"     => __("Show delivery date", "woocommerce-myparcelbe"),
                "type"      => "toggle",
                "default"   => self::ENABLED,
                "help_text" => __("Show the delivery date inside the delivery options.", "woocommerce-myparcelbe"),
            ],
            [
                "name"      => WCMPBE_Settings::SETTING_CARRIER_SIGNATURE_ENABLED,
                "condition" => WCMPBE_Settings::SETTING_CARRIER_DELIVERY_ENABLED,
                "label"     => __("shipment_options_signature", "woocommerce-myparcelbe"),
                "type"      => "toggle",
                "help_text" => __(
                    "Enter an amount that is either positive or negative. For example, do you want to give a discount for using this function or do you want to charge extra for this delivery option.",
                    "woocommerce-myparcelbe"
                ),
            ],
            self::getFeeField(
                WCMPBE_Settings::SETTING_CARRIER_SIGNATURE_FEE,
                [
                    WCMPBE_Settings::SETTING_CARRIER_DELIVERY_ENABLED,
                    WCMPBE_Settings::SETTING_CARRIER_SIGNATURE_ENABLED,
                ]
            ),
        ];
    }

    /**
     * @return array
     */
    private function get_section_carrier_bpost_pickup_options(): array
    {
        return [
            [
                "name"  => WCMPBE_Settings::SETTING_CARRIER_PICKUP_ENABLED,
                "label" => __("Enable bpost pickup", "woocommerce-myparcelbe"),
                "type"  => "toggle",
            ],
            self::getFeeField(
                WCMPBE_Settings::SETTING_CARRIER_PICKUP_FEE,
                [
                    WCMPBE_Settings::SETTING_CARRIER_PICKUP_ENABLED,
                ]
            ),
        ];
    }


    /**
     * These are the unprefixed settings for DPD.
     * After the settings are generated every name will be prefixed with "dpd_"
     * Example: delivery_enabled => dpd_delivery_enabled
     *
     * @return array
     */
    private function get_section_carrier_dpd_delivery_options(): array
    {
        return [
            [
                "name"  => WCMPBE_Settings::SETTING_CARRIER_DELIVERY_ENABLED,
                "label" => __("Enable DPD delivery", "woocommerce-myparcelbe"),
                "type"  => "toggle",
            ],
            [
                "name"      => WCMPBE_Settings::SETTING_CARRIER_DROP_OFF_DAYS,
                "condition" => WCMPBE_Settings::SETTING_CARRIER_DELIVERY_ENABLED,
                "label"     => __("Drop-off days", "woocommerce-myparcelbe"),
                "callback"  => [WCMPBE_Settings_Callbacks::class, "enhanced_select"],
                "options"   => $this->getWeekdays(null),
                "default"   => [2],
                "help_text" => __("Days of the week on which you hand over parcels to DPD", "woocommerce-myparcelbe"),
            ],
            [
                "name"      => WCMPBE_Settings::SETTING_CARRIER_CUTOFF_TIME,
                "condition" => WCMPBE_Settings::SETTING_CARRIER_DELIVERY_ENABLED,
                "label"     => __("Cut-off time", "woocommerce-myparcelbe"),
                "help_text" => __(
                    "Time at which you stop processing orders for the day (format: hh:mm)",
                    "woocommerce-myparcelbe"
                ),
                "default"   => "17:00",
            ],
            [
                "name"      => WCMPBE_Settings::SETTING_CARRIER_DROP_OFF_DELAY,
                "condition" => WCMPBE_Settings::SETTING_CARRIER_DELIVERY_ENABLED,
                "label"     => __("Drop-off delay", "woocommerce-myparcelbe"),
                "type"      => "number",
                "max"       => 14,
                "help_text" => __("Number of days you need to process an order.", "woocommerce-myparcelbe"),
            ],
            [
                "name"      => WCMPBE_Settings::SETTING_CARRIER_DELIVERY_DAYS_WINDOW,
                "condition" => WCMPBE_Settings::SETTING_CARRIER_DELIVERY_ENABLED,
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
                "name"  => WCMPBE_Settings::SETTING_CARRIER_PICKUP_ENABLED,
                "label" => __("Enable dpd pickup", "woocommerce-myparcelbe"),
                "type"  => "toggle",
            ],
            self::getFeeField(
                WCMPBE_Settings::SETTING_CARRIER_PICKUP_FEE,
                [
                    WCMPBE_Settings::SETTING_CARRIER_PICKUP_ENABLED,
                ]
            ),
        ];
    }

    /**
     * @return array
     */
    private function get_section_export_defaults_main(): array
    {
        return [
            [
                "name"      => WCMPBE_Settings::SETTING_SHIPPING_METHODS_PACKAGE_TYPES,
                "label"     => __("Package types", "woocommerce-myparcelbe"),
                "callback"  => [WCMPBE_Settings_Callbacks::class, "enhanced_select"],
                "loop"      => WCMPBE_Data::getPackageTypesHuman(),
                "options"   => (new WCMPBE_Shipping_Methods())->getShippingMethods(),
                "default"   => [],
                "help_text" => __(
                    "Select one or more shipping methods for each MyParcel package type",
                    "woocommerce-myparcelbe"
                ),
            ],
            [
                "name"      => WCMPBE_Settings::SETTING_CONNECT_EMAIL,
                "label"     => __("Connect customer email", "woocommerce-myparcelbe"),
                "type"      => "toggle",
                "help_text" => __(
                    "When you connect the customer's email, MyParcel can send a Track & Trace email to this address. In your MyParcel backend you can enable or disable this email and format it in your own style.",
                    "woocommerce-myparcelbe"
                ),
            ],
            [
                "name"      => WCMPBE_Settings::SETTING_CONNECT_PHONE,
                "label"     => __("Connect customer phone", "woocommerce-myparcelbe"),
                "type"      => "toggle",
                "help_text" => __(
                    "When you connect the customer's phone number, the courier can use this for the delivery of the parcel. This greatly increases the delivery success rate for foreign shipments.",
                    "woocommerce-myparcelbe"
                ),
            ],
            [
                "name"      => WCMPBE_Settings::SETTING_LABEL_DESCRIPTION,
                "label"     => __("Label description", "woocommerce-myparcelbe"),
                "help_text" => __(
                    "With this option you can add a description to the shipment. This will be printed on the top left of the label, and you can use this to search or sort shipments in your backoffice. Because of limited space on the label which varies per package type, we recommend that you keep the label description as short as possible.",
                    "woocommerce-myparcelbe"
                ),
                "append"  => $this->getLabelDescriptionAddition(),
            ],
            [
                "name"      => WCMPBE_Settings::SETTING_EMPTY_PARCEL_WEIGHT,
                "type"      => "number",
                "default"   => 0,
                "label"     => sprintf(
                    "%s (%s)",
                    __("Empty parcel weight", "woocommerce-myparcelbe"),
                    get_option('woocommerce_weight_unit')
                ),
                "help_text" => __(
                    "Default weight of your empty parcel.",
                    "woocommerce-myparcelbe"
                ),
            ],
            [
                "name"      => WCMPBE_Settings::SETTING_HS_CODE,
                "label"     => __("Default HS Code", "woocommerce-myparcelbe"),
                "help_text" => __(
                    "HS Codes are used for MyParcel world shipments, you can find the appropriate code on the site of the Dutch Customs.",
                    "woocommerce-myparcelbe"
                ),
            ],
            [
                "name"    => WCMPBE_Settings::SETTING_PACKAGE_CONTENT,
                "label"   => __("Customs shipment type", "woocommerce-myparcelbe"),
                "type"    => "select",
                "options" => [
                    1 => __("Commercial goods", "woocommerce-myparcelbe"),
                    2 => __("Commercial samples", "woocommerce-myparcelbe"),
                    3 => __("Documents", "woocommerce-myparcelbe"),
                    4 => __("Gifts", "woocommerce-myparcelbe"),
                    5 => __("Return shipment", "woocommerce-myparcelbe"),
                ],
            ],
            [
                'name'      => WCMPBE_Settings::SETTING_COUNTRY_OF_ORIGIN,
                'label'     => __('setting_country_of_origin', 'woocommerce-myparcelbe'),
                'type'      => 'select',
                'options'   => (new WC_Countries())->get_countries(),
                'default'   => (new WC_Countries())->get_base_country(),
                'help-text' => __(
                    'setting_country_of_origin_help_text',
                    'woocommerce-myparcelbe'
                ),
            ],
//            [
//                "name"      => WCMPBE_Settings::SETTING_AUTOMATIC_EXPORT,
//                "label"     => __("Automatic export", "woocommerce-myparcelbe"),
//                "type"      => "toggle",
//                "help_text" => __(
//                    "With this setting enabled orders are exported to MyParcel automatically after payment.",
//                    "woocommerce-myparcelbe"
//                ),
//            ],
            [
                "name"      => WCMPBE_Settings::SETTING_DEFAULT_CARRIER,
                "label"     => __("Default carrier", "woocommerce-myparcelbe"),
                "help-text" => __(
                    "You can set a default carrier here which will be used when no other option overrides it.", "woocommerce-myparcelbe"
                ),
                "type"      => "select",
                "options"   => WCMPBE_Data::getCarriersHuman(),
            ],
//            [
//                "name"      => WCMPBE_Settings::SETTING_RETURN_IN_THE_BOX,
//                "label"     => __("Print return label directly", "woocommerce-myparcelbe"),
//                "type"      => "select",
//                "options"   => [
//                    self::NOT_ACTIVE        => __("No", "woocommerce-myparcelbe"),
//                    self::NO_OPTIONS        => __("Without options", "woocommerce-myparcelbe"),
//                    self::EQUAL_TO_SHIPMENT => __("Options equal to shipment", "woocommerce-myparcelbe"),
//                ],
//                "help_text" => __(
//                    "Enabling this setting automatically creates a related return shipment for any shipment you export. When downloading the shipment labels the corresponding return shipment labels will be included.",
//                    "woocommerce-myparcelbe"
//                ),
//            ],
        ];
    }

    /**
     * @return array
     */
    private function get_section_checkout_main(): array
    {
        return [
            [
                "name"      => WCMPBE_Settings::SETTING_USE_SPLIT_ADDRESS_FIELDS,
                "label"     => __("MyParcel address fields", "woocommerce-myparcelbe"),
                "type"      => "toggle",
                "help_text" => __(
                    "When enabled the checkout will use the MyParcel address fields. This means there will be three separate fields for street name, number and suffix. Want to use the WooCommerce default fields? Leave this option unchecked.",
                    "woocommerce-myparcelbe"
                ),
            ],
            [
                "name"      => WCMPBE_Settings::SETTING_SHOW_DELIVERY_DAY,
                "label"     => __("Show delivery date", "woocommerce-myparcelbe"),
                "type"      => "toggle",
                "help_text" => __(
                    "Show delivery day options allow your customers to see the delivery day in order confirmation and My Account.",
                    "woocommerce-myparcelbe"
                ),
            ],
            [
                "name"      => WCMPBE_Settings::SETTING_DELIVERY_OPTIONS_ENABLED,
                "label"     => __("Enable MyParcel delivery options", "woocommerce-myparcelbe"),
                "type"      => "toggle",
                "help_text" => __(
                    "The MyParcel delivery options allow your customers to select whether they want their parcel delivered at home or to a pickup point. Depending on the settings you can allow them to select a date, time and even options like requiring a signature on delivery.",
                    "woocommerce-myparcelbe"
                ),
            ],
            [
                "name"      => WCMPBE_Settings::SETTINGS_SHOW_DELIVERY_OPTIONS_FOR_BACKORDERS,
                "condition" => WCMPBE_Settings::SETTING_DELIVERY_OPTIONS_ENABLED,
                "label"     => __("Enable delivery options for backorders", "woocommerce-myparcelbe"),
                "type"      => "toggle",
                "help_text" => __(
                    "When this option is enabled, delivery options and delivery day will be also shown for backorders.",
                    "woocommerce-myparcelbe"
                ),
            ],
            [
                "name"      => WCMPBE_Settings::SETTING_DELIVERY_OPTIONS_DISPLAY,
                "condition" => WCMPBE_Settings::SETTING_DELIVERY_OPTIONS_ENABLED,
                "label"     => __("settings_checkout_display_for", "woocommerce-myparcelbe"),
                "type"      => "select",
                "help_text" => __("settings_checkout_display_for_help_text", "woocommerce-myparcelbe"),
                "options"   => [
                    self::DISPLAY_FOR_SELECTED_METHODS => __("settings_checkout_display_for_selected_methods", "woocommerce-myparcelbe"),
                    self::DISPLAY_FOR_ALL_METHODS      => __("settings_checkout_display_for_all_methods", "woocommerce-myparcelbe"),
                ],
            ],
            [
                "name"      => WCMPBE_Settings::SETTING_DELIVERY_OPTIONS_POSITION,
                "condition" => WCMPBE_Settings::SETTING_DELIVERY_OPTIONS_ENABLED,
                "label"     => __("Checkout position", "woocommerce-myparcelbe"),
                "type"      => "select",
                "default"   => "woocommerce_after_checkout_billing_form",
                "options"   => [
                    "woocommerce_after_checkout_billing_form"     => __(
                        "Show after billing details",
                        "woocommerce-myparcelbe"
                    ),
                    "woocommerce_after_checkout_shipping_form"    => __(
                        "Show after shipping details",
                        "woocommerce-myparcelbe"
                    ),
                    "woocommerce_checkout_after_customer_details" => __(
                        "Show after customer details",
                        "woocommerce-myparcelbe"
                    ),
                    "woocommerce_after_order_notes"               => __(
                        "Show after notes",
                        "woocommerce-myparcelbe"
                    ),
                    "woocommerce_review_order_before_payment"     => __(
                        "Show after subtotal",
                        "woocommerce-myparcelbe"
                    ),
                ],
                "help_text" => __(
                    "You can change the place of the delivery options on the checkout page. By default it will be placed after shipping details.",
                    "woocommerce-myparcelbe"
                ),
            ],
            [
                "name"      => WCMPBE_Settings::SETTING_DELIVERY_OPTIONS_PRICE_FORMAT,
                "condition" => WCMPBE_Settings::SETTING_DELIVERY_OPTIONS_ENABLED,
                "label"     => __("settings_checkout_price_format", "woocommerce-myparcelbe"),
                "type"      => "select",
                "default"   => self::DISPLAY_TOTAL_PRICE,
                "options"   => [
                    self::DISPLAY_TOTAL_PRICE     => __(
                        "settings_checkout_total_price",
                        "woocommerce-myparcelbe"
                    ),
                    self::DISPLAY_SURCHARGE_PRICE => __(
                        "settings_checkout_surcharge",
                        "woocommerce-myparcelbe"
                    ),
                ],
            ],
            [
                "name"      => WCMPBE_Settings::SETTING_PICKUP_LOCATIONS_DEFAULT_VIEW,
                "condition" => WCMPBE_Settings::SETTING_DELIVERY_OPTIONS_ENABLED,
                "label"     => __("settings_pickup_locations_default_view", "woocommerce-myparcelbe"),
                "type"      => "select",
                "default"   => self::PICKUP_LOCATIONS_VIEW_MAP,
                "options"   => [
                    self::PICKUP_LOCATIONS_VIEW_MAP  => __(
                        "settings_pickup_locations_default_view_map",
                        "woocommerce-myparcelbe"
                    ),
                    self::PICKUP_LOCATIONS_VIEW_LIST => __(
                        "settings_pickup_locations_default_view_list",
                        "woocommerce-myparcelbe"
                    ),
                ],
            ],
            [
                "name"              => WCMPBE_Settings::SETTING_DELIVERY_OPTIONS_CUSTOM_CSS,
                "condition"         => WCMPBE_Settings::SETTING_DELIVERY_OPTIONS_ENABLED,
                "label"             => __("Custom styles", "woocommerce-myparcelbe"),
                "type"              => "textarea",
                "append"            => $this->getCustomCssAddition(),
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
                "name"      => WCMPBE_Settings::SETTING_HEADER_DELIVERY_OPTIONS_TITLE,
                "condition" => WCMPBE_Settings::SETTING_DELIVERY_OPTIONS_ENABLED,
                "label"     => __("Delivery options title", "woocommerce-myparcelbe"),
                "title"     => "Delivery options title",
                "help_text" => __(
                    "You can place a delivery title above the MyParcel options. When there is no title, it will not be visible.",
                    "woocommerce-myparcelbe"
                ),
            ],
            [
                "name"      => WCMPBE_Settings::SETTING_DELIVERY_TITLE,
                "condition" => WCMPBE_Settings::SETTING_DELIVERY_OPTIONS_ENABLED,
                "label"     => __("Delivery title", "woocommerce-myparcelbe"),
                "default"   => __("Delivered at home or at work", "woocommerce-myparcelbe"),
            ],
            [
                "name"      => WCMPBE_Settings::SETTING_STANDARD_TITLE,
                "condition" => WCMPBE_Settings::SETTING_DELIVERY_OPTIONS_ENABLED,
                "label"     => __("Standard delivery title", "woocommerce-myparcelbe"),
                "help_text" => __(
                    "When there is no title, the delivery time will automatically be visible.",
                    "woocommerce-myparcelbe"
                ),
                "default"   => __("Standard delivery", "woocommerce-myparcelbe"),
            ],
            [
                "name"      => WCMPBE_Settings::SETTING_ONLY_RECIPIENT_TITLE,
                "condition" => WCMPBE_Settings::SETTING_DELIVERY_OPTIONS_ENABLED,
                "label"     => __("Home address only title", "woocommerce-myparcelbe"),
                "default"   => __("shipment_options_only_recipient", "woocommerce-myparcelbe"),
            ],
            [
                "name"      => WCMPBE_Settings::SETTING_SIGNATURE_TITLE,
                "condition" => WCMPBE_Settings::SETTING_DELIVERY_OPTIONS_ENABLED,
                "label"     => __("Signature on delivery title", "woocommerce-myparcelbe"),
                "default"   => __("shipment_options_signature", "woocommerce-myparcelbe"),
            ],
            [
                "name"      => WCMPBE_Settings::SETTING_PICKUP_TITLE,
                "condition" => WCMPBE_Settings::SETTING_DELIVERY_OPTIONS_ENABLED,
                "label"     => __("Pickup title", "woocommerce-myparcelbe"),
                "default"   => __("Pickup", "woocommerce-myparcelbe"),
            ],
            [
                "name"      => WCMPBE_Settings::PICKUP_LOCATIONS_LIST_BUTTON,
                "condition" => WCMPBE_Settings::SETTING_DELIVERY_OPTIONS_ENABLED,
                "help_text" => __(
                    "The text on the \"list\" button when viewing pickup locations.",
                    "woocommerce-myparcelbe"
                ),
                "label"     => __("Pickup list button text", "woocommerce-myparcelbe"),
                "default"   => __("List", "woocommerce-myparcelbe"),
            ],
            [
                "name"      => WCMPBE_Settings::PICKUP_LOCATIONS_MAP_BUTTON,
                "condition" => WCMPBE_Settings::SETTING_DELIVERY_OPTIONS_ENABLED,
                "help_text" => __(
                    "The text on the \"map\" button when viewing pickup locations.",
                    "woocommerce-myparcelbe"
                ),
                "label"     => __("Pickup map button text", "woocommerce-myparcelbe"),
                "default"   => __("Map", "woocommerce-myparcelbe"),
            ],
        ];
    }

    /**
     * Get the html string to render after the custom css select.
     *
     * @return string
     */
    private function getCustomCssAddition(): string
    {
        $currentTheme = wp_get_theme();

        $preset  = sanitize_title($currentTheme);
        $cssPath = WCMYPABE()->plugin_path() . "/assets/css/delivery-options/delivery-options-preset-$preset.css";

        if (! file_exists($cssPath)) {
            return "";
        }

        return sprintf(
            '<p>%s <a class="" href="#" onclick="document.querySelector(`#delivery_options_custom_css`).value = `%s`">%s</a></p>',
            sprintf(__('Theme "%s" detected.', "woocommerce-myparcelbe"), $currentTheme),
            file_get_contents($cssPath),
            __("Apply preset.", "woocommerce-myparcelbe")
        );
    }

    /**
     * Created html for clickable hints for the variables that can be used in the label description.
     *
     * @return string
     */
    private function getLabelDescriptionAddition(): string
    {
        $output = '';
        $variables = [
            '[DELIVERY_DATE]' => __('Delivery date', 'woocommerce-myparcelbe'),
            '[ORDER_NR]'      => __('Order number', 'woocommerce-myparcelbe'),
            '[PRODUCT_ID]'    => __('Product id', 'woocommerce-myparcelbe'),
            '[PRODUCT_NAME]'  => __('Product name', 'woocommerce-myparcelbe'),
            '[PRODUCT_QTY]'   => __('Product quantity', 'woocommerce-myparcelbe'),
            '[PRODUCT_SKU]'   => __('Product SKU', 'woocommerce-myparcelbe'),
            '[CUSTOMER_NOTE]' => __('Customer note', 'woocommerce-myparcelbe'),
        ];

        foreach ($variables as $variable => $description) {
            $output .= "<br><a onclick=\"var el = document.querySelector('#label_description_field input');el.value += '$variable';el.focus();\">$variable</a>: $description";
        }

        return sprintf("<div class=\"label-description-variables\"><p>Available variables: %s</p>", $output);
    }

    /**
     * @param string $name
     * @param string|array  $conditions
     *
     * @return array
     */
    private static function getFeeField(string $name, array $conditions): array
    {
        return [
            "name"       => $name,
            "condition" => $conditions,
            "class"      => ["wcmpbe__child"],
            "label"      => __("Fee (optional)", "woocommerce-myparcelbe"),
            "type"       => "currency",
            "help_text"  => __(
                "Enter an amount that is either positive or negative. For example, do you want to give a discount for using this function or do you want to charge extra for this delivery option.",
                "woocommerce-myparcelbe"
            ),
        ];
    }
}

new WCMPBE_Settings_Data();
