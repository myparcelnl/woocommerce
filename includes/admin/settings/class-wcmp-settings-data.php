<?php

use MyParcelNL\Sdk\src\Model\Consignment\BpostConsignment;
use MyParcelNL\Sdk\src\Model\Consignment\DPDConsignment;
use WPO\WC\MyParcelBE\Entity\SettingsFieldArguments;

if (! defined('ABSPATH')) {
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
            WCMP_Settings::SETTINGS_GENERAL         => _wcmp("General"),
            WCMP_Settings::SETTINGS_EXPORT_DEFAULTS => _wcmp("Default export settings"),
            WCMP_Settings::SETTINGS_CHECKOUT        => _wcmp("Checkout settings"),
        ];

        $array[WCMP_Settings::SETTINGS_BPOST] = _wcmp("bpost");
        $array[WCMP_Settings::SETTINGS_DPD]   = _wcmp("DPD");

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
                    if (isset($setting["condition"]) && $prefix) {
                        if (is_array($setting["condition"])) {
                            $related                      = $setting["condition"]["name"];
                            $setting["condition"]["name"] = "{$name}_{$related}";
                        } else {
                            $related              = $setting["condition"];
                            $setting["condition"] = "{$name}_{$related}";
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
                    "label"    => _wcmp("API settings"),
                    "settings" => $this->get_section_general_api(),
                ],
                [
                    "name"     => "general",
                    "label"    => _wcmp("General settings"),
                    "settings" => $this->get_section_general_general(),
                ],
                [
                    "name"     => "diagnostics",
                    "label"    => _wcmp("Diagnostic tools"),
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
                    "label"    => _wcmp("Default export settings"),
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
                    "label"    => _wcmp("Checkout settings"),
                    "settings" => $this->get_section_checkout_main(),
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
                    "label"       => _wcmp("Default export settings"),
                    "description" => _wcmp("These settings will be applied to bpost shipments you create in the backend."),
                    "settings"    => $this->get_section_carrier_bpost_export_defaults(),
                ],
                [
                    "name"     => "delivery_options",
                    "label"    => _wcmp("bpost delivery options"),
                    "settings" => $this->get_section_carrier_bpost_delivery_options(),
                ],
                [
                    "name"     => "pickup_options",
                    "label"    => _wcmp("bpost pickup options"),
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
                    "label"    => _wcmp("dpd delivery options"),
                    "settings" => $this->get_section_carrier_dpd_delivery_options(),
                ],
                [
                    "name"     => "pickup_options",
                    "label"    => _wcmp("dpd pickup options"),
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
                "label"     => _wcmp("Key"),
                "help_text" => _wcmp("api key"),
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
                "label"   => _wcmp("Label display"),
                "type"    => "select",
                "options" => [
                    "download" => _wcmp("Download PDF"),
                    "display"  => _wcmp("Open the PDF in a new tab"),
                ],
            ],
            [
                "name"    => WCMP_Settings::SETTING_LABEL_FORMAT,
                "label"   => _wcmp("Label format"),
                "type"    => "select",
                "options" => [
                    "A4" => _wcmp("Standard printer (A4)"),
                    "A6" => _wcmp("Label Printer (A6)"),
                ],
            ],
            [
                "name"      => WCMP_Settings::SETTING_PRINT_POSITION_OFFSET,
                "label"     => _wcmp("Ask for print start position"),
                "condition" => [
                    "name"         => WCMP_Settings::SETTING_LABEL_FORMAT,
                    "type"         => "disable",
                    "parent_value" => "A4",
                    "set_value"    => self::DISABLED,
                ],
                "type"      => "toggle",
                "help_text" => _wcmp("This option enables you to continue printing where you left off last time"),
            ],
            [
                "name"      => WCMP_Settings::SETTING_EMAIL_TRACK_TRACE,
                "label"     => _wcmp("Track & Trace in email"),
                "type"      => "toggle",
                "help_text" => _wcmp(
                    "Add the Track & Trace code to emails to the customer.<br/><strong>Note!</strong> When you select this option, make sure you have not enabled the Track & Trace email in your MyParcel BE backend."
                ),
            ],
            [
                "name"      => WCMP_Settings::SETTING_MY_ACCOUNT_TRACK_TRACE,
                "label"     => _wcmp("Track & Trace in My Account"),
                "type"      => "toggle",
                "help_text" => _wcmp("Show Track & Trace trace code and link in My Account."),
            ],
            [
                "name"      => WCMP_Settings::SETTING_PROCESS_DIRECTLY,
                "label"     => _wcmp("Process shipments directly"),
                "type"      => "toggle",
                "help_text" => _wcmp(
                    "When you enable this option, shipments will be directly processed when sent to MyParcel BE."
                ),
            ],
            [
                "name"      => WCMP_Settings::SETTING_ORDER_STATUS_AUTOMATION,
                "label"     => _wcmp("Order status automation"),
                "type"      => "toggle",
                "help_text" => _wcmp(
                    "Automatically set order status to a predefined status after successful MyParcel BE export.<br/>Make sure <strong>Process shipments directly</strong> is enabled when you use this option together with the <strong>Track & Trace in email</strong> option, otherwise the Track & Trace code will not be included in the customer email."
                ),
            ],
            [
                "name"      => WCMP_Settings::SETTING_AUTOMATIC_ORDER_STATUS,
                "condition" => WCMP_Settings::SETTING_ORDER_STATUS_AUTOMATION,
                "class"     => ["wcmp__child"],
                //                    "default" => "", // todo
                "label"     => _wcmp("Automatic order status"),
                "type"      => "select",
                "options"   => $this->callbacks->get_order_status_options(),
            ],
            [
                "name"      => WCMP_Settings::SETTING_KEEP_SHIPMENTS,
                "label"     => _wcmp("Keep old shipments"),
                "type"      => "toggle",
                "help_text" => _wcmp(
                    "With this option enabled, data from previous shipments (Track & Trace links) will be kept in the order when you export more than once."
                ),
            ],
            [
                "name"      => WCMP_Settings::SETTING_BARCODE_IN_NOTE,
                "label"     => _wcmp("Place barcode inside note"),
                "type"      => "toggle",
                "help_text" => _wcmp("Place the barcode inside a note of the order"),
            ],
            [
                "name"      => WCMP_Settings::SETTING_BARCODE_IN_NOTE_TITLE,
                "condition" => WCMP_Settings::SETTING_BARCODE_IN_NOTE,
                "class"     => ["wcmp__child"],
                "label"     => _wcmp("Title before the barcode"),
                "default"   => _wcmp("Track & trace code:"),
                "help_text" => _wcmp("You can change the text before the barcode inside an note"),
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
                "label"       => _wcmp("Log API communication"),
                "type"        => "toggle",
                "description" => '<a href="' . esc_url_raw(
                        admin_url("admin.php?page=wc-status&tab=logs")
                    ) . '" target="_blank">' . _wcmp("View logs") . "</a> (wc-myparcelbe)",
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
                "label" => _wcmp("Enable bpost delivery"),
                "type"  => "toggle",
            ],
            [
                "name"      => WCMP_Settings::SETTING_CARRIER_DROP_OFF_DAYS,
                "condition" => WCMP_Settings::SETTING_CARRIER_DELIVERY_ENABLED,
                "label"     => _wcmp("Drop-off days"),
                "callback"  => [$this->callbacks, "enhanced_select"],
                "options"   => (new WP_Locale())->weekday,
                "help_text" => _wcmp("Days of the week on which you hand over parcels to bpost"),
            ],
            [
                "name"      => WCMP_Settings::SETTING_CARRIER_CUTOFF_TIME,
                "condition" => WCMP_Settings::SETTING_CARRIER_DELIVERY_ENABLED,
                "label"     => _wcmp("Cut-off time"),
                "help_text" => _wcmp("Time at which you stop processing orders for the day (format: hh:mm)"),
            ],
            [
                "name"      => WCMP_Settings::SETTING_CARRIER_DROP_OFF_DELAY,
                "condition" => WCMP_Settings::SETTING_CARRIER_DELIVERY_ENABLED,
                "label"     => _wcmp("Drop-off delay"),
                "type"      => "number",
                "step"      => 1,
                "help_text" => _wcmp("Number of days you need to process an order."),
            ],
            [
                "name"      => WCMP_Settings::SETTING_CARRIER_DELIVERY_DAYS_WINDOW,
                "condition" => WCMP_Settings::SETTING_CARRIER_DELIVERY_ENABLED,
                "label"     => _wcmp("Delivery days window"),
                "type"      => "toggle",
                "help_text" => _wcmp("Show the delivery date inside the checkout."),
            ],
            [
                "name"      => WCMP_Settings::SETTING_CARRIER_SIGNATURE_ENABLED,
                "condition" => WCMP_Settings::SETTING_CARRIER_DELIVERY_ENABLED,
                "label"     => _wcmp("Signature on delivery"),
                "type"      => "toggle",
                "has_price" => true,
                "help_text" => _wcmp(
                    "Enter an amount that is either positive or negative. For example, do you want to give a discount for using this function or do you want to charge extra for this delivery option."
                ),
            ],
            [
                "name"      => WCMP_Settings::SETTING_CARRIER_SIGNATURE_FEE,
                "condition" => WCMP_Settings::SETTING_CARRIER_SIGNATURE_ENABLED,
                "class"     => ["wcmp__child"],
                "label"     => _wcmp("Fee (optional)"),
                "type"      => "currency",
                "help_text" => _wcmp(
                    "Enter an amount that is either positive or negative. For example, do you want to give a discount for using this function or do you want to charge extra for this delivery option."
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
                "label" => _wcmp("Enable bpost pickup"),
                "type"  => "toggle",
            ],
            [
                "name"      => WCMP_Settings::SETTING_CARRIER_PICKUP_FEE,
                "condition" => WCMP_Settings::SETTING_CARRIER_PICKUP_ENABLED,
                "class"     => ["wcmp__child"],
                "label"     => _wcmp("Fee (optional)"),
                "type"      => "currency",
                "help_text" => _wcmp(
                    "Enter an amount that is either positive or negative. For example, do you want to give a discount for using this function or do you want to charge extra for this delivery option."
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
                "label" => _wcmp("Enable DPD delivery"),
                "type"  => "toggle",
            ],
            [
                "name"      => WCMP_Settings::SETTING_CARRIER_DROP_OFF_DAYS,
                "condition" => WCMP_Settings::SETTING_CARRIER_DELIVERY_ENABLED,
                "label"     => _wcmp("Drop-off days"),
                "callback"  => [$this->callbacks, "enhanced_select"],
                "options"   => (new WP_Locale())->weekday,
                "help_text" => _wcmp("Days of the week on which you hand over parcels to dpd"),
            ],
            [
                "name"        => WCMP_Settings::SETTING_CARRIER_CUTOFF_TIME,
                "condition"   => WCMP_Settings::SETTING_CARRIER_DELIVERY_ENABLED,
                "label"       => _wcmp("Cut-off time"),
                "placeholder" => "17:00",
                "help_text"   => _wcmp("Time at which you stop processing orders for the day (format: hh:mm)"),
            ],
            [
                "name"      => WCMP_Settings::SETTING_CARRIER_DROP_OFF_DELAY,
                "condition" => WCMP_Settings::SETTING_CARRIER_DELIVERY_ENABLED,
                "label"     => _wcmp("Drop-off delay"),
                "help_text" => _wcmp("Number of days you need to process an order."),
            ],
            [
                "name"      => WCMP_Settings::SETTING_CARRIER_DELIVERY_DAYS_WINDOW,
                "condition" => WCMP_Settings::SETTING_CARRIER_DELIVERY_ENABLED,
                "label"     => _wcmp("Delivery days window"),
                "type"      => "toggle",
                "help_text" => _wcmp("Show the delivery date inside the checkout."),
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
                "label"     => _wcmp("Enable dpd pickup"),
                "type"      => "toggle",
                "has_price" => true,
                "help_text" => _wcmp(
                    "Enter an amount that is either positive or negative. For example, do you want to give a discount for using this function or do you want to charge extra for this delivery option."
                ),
            ],
            [
                "name"      => WCMP_Settings::SETTING_CARRIER_PICKUP_FEE,
                "condition" => WCMP_Settings::SETTING_CARRIER_PICKUP_ENABLED,
                "label"     => _wcmp("Fee (optional)"),
                "type"      => "currency",
                "help_text" => _wcmp(
                    "Enter an amount that is either positive or negative. For example, do you want to give a discount for using this function or do you want to charge extra for this delivery option."
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
                "label"     => _wcmp("Package types"),
                "callback"  => [$this->callbacks, "enhanced_select"],
                "loop"      => WCMP_Data::getPackageTypesHuman(),
                "options"   => WCMP_Settings_Callbacks::getShippingMethods(),
                "help_text" => _wcmp("Select one or more shipping methods for each MyParcel BE package type"),
            ],
            [
                "name"      => WCMP_Settings::SETTING_CONNECT_EMAIL,
                "label"     => _wcmp("Connect customer email"),
                "type"      => "toggle",
                "help_text" => _wcmp(
                    "When you connect the customer email, MyParcel BE can send a Track & Trace email to this address. In your %sMyParcel BE backend%s you can enable or disable this email and format it in your own style."
                ),
                '<a href="https://backoffice.sendmyparcel.be/settings/account" target="_blank">',
                '</a>',
            ],
            [
                "name"      => WCMP_Settings::SETTING_CONNECT_PHONE,
                "label"     => _wcmp("Connect customer phone"),
                "type"      => "toggle",
                "help_text" => _wcmp(
                    "When you connect the customer's phone number, the courier can use this for the delivery of the parcel. This greatly increases the delivery success rate for foreign shipments."
                ),
            ],
            [
                "name"      => WCMP_Settings::SETTING_LABEL_DESCRIPTION,
                "label"     => _wcmp("Label description"),
                "help_text" => _wcmp(
                    "When you connect the customer's phone number, the courier can use this for the delivery of the parcel. This greatly increases the delivery success rate for foreign shipments."
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
                "label"     => _wcmp("MyParcel BE address fields"),
                "type"      => "toggle",
                "help_text" => _wcmp(
                    "When enabled the checkout will use the MyParcel BE address fields. This means there will be three separate fields for street name, number and suffix. Want to use the WooCommerce default fields? Leave this option unchecked."
                ),
            ],
            [
                "name"      => WCMP_Settings::SETTING_DELIVERY_OPTIONS_ENABLED,
                "label"     => _wcmp("Enable MyParcel BE delivery options"),
                "type"      => "toggle",
                "help_text" => _wcmp(
                    "The MyParcel delivery options allow your customers to select whether they want their parcel delivered at home or to a pickup point. Depending on the settings you can allow them to select a date, time and even options like requiring a signature on delivery."
                ),
            ],
            [
                "name"      => WCMP_Settings::SETTING_HEADER_DELIVERY_OPTIONS_TITLE,
                "condition" => WCMP_Settings::SETTING_DELIVERY_OPTIONS_ENABLED,
                "label"     => _wcmp("Delivery options title"),
                "title"     => "Delivery options title",
                "help_text" => _wcmp(
                    "You can place a delivery title above the MyParcel BE options. When there is no title, it will not be visible."
                ),
            ],
            [
                "name"      => WCMP_Settings::SETTING_DELIVERY_TITLE,
                "condition" => WCMP_Settings::SETTING_DELIVERY_OPTIONS_ENABLED,
                "label"     => _wcmp("Delivery options title"),
            ],
            [
                "name"      => WCMP_Settings::SETTING_STANDARD_TITLE,
                "condition" => WCMP_Settings::SETTING_DELIVERY_OPTIONS_ENABLED,
                "label"     => _wcmp("Standard delivery title"),
                "help_text" => _wcmp("When there is no title, the delivery time will automatically be visible."),
            ],
            [
                "name"      => WCMP_Settings::SETTING_SIGNATURE_TITLE,
                "condition" => WCMP_Settings::SETTING_DELIVERY_OPTIONS_ENABLED,
                "label"     => _wcmp("Signature on delivery"),
            ],
            [
                "name"      => WCMP_Settings::SETTING_PICKUP_TITLE,
                "condition" => WCMP_Settings::SETTING_DELIVERY_OPTIONS_ENABLED,
                "label"     => _wcmp("Pickup title"),
            ],
            [
                "name"      => WCMP_Settings::SETTING_DELIVERY_OPTIONS_DISPLAY,
                "condition" => WCMP_Settings::SETTING_DELIVERY_OPTIONS_ENABLED,
                "label"     => _wcmp("Display for"),
                "type"      => "select",
                "options"   => [
                    "selected_methods" => _wcmp("Shipping methods associated with Parcels"),
                    "all_methods"      => _wcmp("All shipping methods"),
                ],
                "help_text" => _wcmp(
                    "You can link the delivery options to specific shipping methods by adding them to the package types under \"Standard export settings\". The delivery options are not visible at foreign addresses."
                ),
            ],
            [
                "name"      => WCMP_Settings::SETTING_DELIVERY_OPTIONS_POSITION,
                "condition" => WCMP_Settings::SETTING_DELIVERY_OPTIONS_ENABLED,
                "label"     => _wcmp("Checkout position"),
                "type"      => "select",
                "default"   => "woocommerce_after_checkout_billing_form",
                "options"   => [
                    "woocommerce_after_checkout_billing_form"  => _wcmp(
                        "Show checkout options after billing details"
                    ),
                    "woocommerce_after_checkout_shipping_form" => _wcmp(
                        "Show checkout options after shipping details"
                    ),
                    "woocommerce_after_order_notes"            => _wcmp(
                        "Show checkout options after notes"
                    ),
                ],
                "help_text" => _wcmp(
                    "You can change the place of the checkout options on the checkout page. By default it will be placed after shipping details."
                ),
            ],
            [
                "name"              => WCMP_Settings::SETTING_DELIVERY_OPTIONS_CUSTOM_CSS,
                "condition"         => WCMP_Settings::SETTING_DELIVERY_OPTIONS_ENABLED,
                "label"             => _wcmp("Custom styles"),
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
     * @return array
     */
    private function get_section_carrier_bpost_export_defaults(): array
    {
        return [
            [
                "name"  => WCMP_Settings::SETTING_CARRIER_DEFAULT_EXPORT_INSURED,
                "label" => _wcmp("Insured shipment (to €500)"),
                "type"  => "toggle",
            ],
            [
                "name"  => WCMP_Settings::SETTING_CARRIER_DEFAULT_EXPORT_SIGNATURE,
                "label" => _wcmp("Signature on delivery"),
                "type"  => "toggle",
            ],
        ];
    }
}

new WCMP_Settings_Data();
