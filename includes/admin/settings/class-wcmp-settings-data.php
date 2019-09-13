<?php

use MyParcelNL\Sdk\src\Model\Consignment\BpostConsignment;
use MyParcelNL\Sdk\src\Model\Consignment\DPDConsignment;

if (! defined('ABSPATH')) {
    exit;
}

if (! class_exists('WCMP_Settings_Data')) :

    /**
     * This class contains all data for the admin settings screens created by the plugin.
     */
    class WCMP_Settings_Data
    {
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
            $this->generate_settings($this->get_general_sections(), WCMP_Settings::SETTINGS_GENERAL);
            $this->generate_settings($this->get_export_defaults_sections(), WCMP_Settings::SETTINGS_EXPORT_DEFAULTS);

            if (WCMP()->setting_collection->isEnabled("delivery_options_enabled")) {
                $this->generate_settings($this->get_carrier_bpost_sections(), 'bpost', true);
                $this->generate_settings($this->get_carrier_dpd_sections(), 'dpd', true);
            }
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
            $optionIdentifier = "woocommerce_myparcelbe_{$optionName}_settings";

            // Register settings.
            register_setting($optionIdentifier, $optionIdentifier, [$this->callbacks, 'validate']);

            // Create option in wp_options with default settings if the option doesn't exist yet.
            if (false === get_option($optionIdentifier)) {
                $this->set_default_settings($optionName, $optionIdentifier);
            }

            foreach ($settingsArray as $name => $array) {
                foreach ($array as $section) {
                    $sectionName = "{$name}_{$section["name"]}";

                    add_settings_section(
                        $sectionName,
                        $section["label"],
                        null,
                        $optionIdentifier
                    );

                    foreach ($section["settings"] as $setting) {
                        $settingName = $prefix ? "{$name}_{$setting["name"]}" : $setting["name"];

                        $class = [$sectionName];

                        if (array_key_exists("args", $setting)) {
                            if (array_key_exists("class", $setting["args"])) {
                                if (is_array($setting["args"]["class"])) {
                                    array_merge($class, $setting["args"]["class"]);
                                } else {
                                    array_push($class, $setting["args"]["class"]);
                                }
                            }
                        }

                        $callback = $setting["callback"] ?? [$this->callbacks, "renderField"];

                        unset($setting["callback"]);
                        $args       = $setting;
                        $args["id"] = $settingName;

                        add_settings_field(
                            $settingName,
                            $setting["label"],
                            $callback,
                            $optionIdentifier,
                            $sectionName,
                            $args
                        );
                    }
                }
            }
        }

        /**
         * Get a default string from the translations.
         *
         * @param $key
         *
         * @return string
         */
        public static function get_default_string($key): string
        {
            return _wcmp(self::get_default_strings()[$key]);
        }

        /**
         * Set default settings.
         *
         * @param string $option
         * @param string $optionIdentifier
         *
         * @return void.
         */
        public static function set_default_settings(string $option, string $optionIdentifier): void
        {
            switch ($option) {
                case WCMP_Settings::SETTINGS_GENERAL:
                    $default = self::get_default_general_settings();
                    break;
                case WCMP_Settings::SETTINGS_EXPORT_DEFAULTS:
                    $default = self::get_default_export_defaults_settings();
                    break;
                case WCMP_Settings::SETTINGS_BPOST:
                case WCMP_Settings::SETTINGS_DPD:
                    $default = self::get_default_carrier_settings($option);
                    break;
                default:
                    $default = [];
                    break;
            }

            // Add the option if it doesn't exist yet, otherwise update it.
            if (false === get_option($optionIdentifier)) {
                add_option($optionIdentifier, $default);
            } else {
                update_option($optionIdentifier, $default);
            }
        }

        /**
         * These are the unprefixed settings for bpost.
         * After the settings are generated every name will be prefixed with "bpost_"
         * Example: delivery_enabled => bpost_delivery_enabled
         *
         * @return array
         */
        private function get_bpost_section_delivery_options(): array
        {
            return [
                [
                    "name"  => "delivery_enabled",
                    "label" => _wcmp("Enable bpost delivery"),
                    "type"  => "toggle",
                ],
                [
                    "name"        => "drop_off_days",
                    "condition"   => "delivery_enabled",
                    "label"       => _wcmp("Drop-off days"),
                    "type"        => "enhanced_select",
                    "options"     => (new WP_Locale())->weekday,
                    "description" =>  _wcmp("Days of the week on which you hand over parcels to bpost"),
                ],
                [
                    "name"        => "cutoff_time",
                    "condition"   => "delivery_enabled",
                    "label"       => _wcmp("Cut-off time"),
                    "description" =>  _wcmp("Time at which you stop processing orders for the day (format: hh:mm)"),
                ],
                [
                    "name"        => "drop_off_delay",
                    "condition"   => "delivery_enabled",
                    "label"       => _wcmp("Drop-off delay"),
                    "type"        => "number",
                    "step"        => 1,
                    "description" =>  _wcmp("Number of days you need to process an order."),
                ],
                [
                    "name"        => "delivery_days_window",
                    "condition"   => "delivery_enabled",
                    "label"       => _wcmp("Delivery days window"),
                    "type"        => "toggle",
                    "description" =>  _wcmp("Show the delivery date inside the checkout."),
                ],
                [
                    "name"        => "signature",
                    "condition"   => "delivery_enabled",
                    "label"       => _wcmp("Signature on delivery"),
                    "type"        => "delivery_option_enable",
                    "has_title"   => false,
                    "has_price"   => true,
                    "description" => sprintf(
                         _wcmp("Enter an amount that is either positive or negative. For example, do you want to give a discount for using this function or do you want to charge extra for this delivery option.")
                    ),
                ],
            ];
        }

        /**
         * @return array
         */
        private function get_bpost_section_pickup_options(): array
        {
            return [
                [
                    "name"        => "pickup",
                    "label"       => _wcmp("Enable bpost pickup"),
                    "type"        => "delivery_option_enable",
                    "has_title"   => false,
                    "has_price"   => true,
                    "description" =>  _wcmp("Enter an amount that is either positive or negative. For example, do you want to give a discount for using this function or do you want to charge extra for this delivery option."),
                ],
            ];
        }

        /**
         * @return array
         */
        private function get_general_sections()
        {
            return [
                WCMP_Settings::SETTINGS_GENERAL => [
                    [
                        "name"     => "api",
                        "label"    => _wcmp("API settings"),
                        "settings" => $this->get_general_section_api(),
                    ],
                    [
                        "name"     => "general",
                        "label"    => _wcmp("General settings"),
                        "settings" => $this->get_general_section_general(),
                    ],
                    [
                        "name"     => "checkout_options",
                        "label"    => _wcmp("Checkout options"),
                        "settings" => $this->get_general_section_checkout_options(),
                    ],
                    [
                        "name"     => "diagnostics",
                        "label"    => _wcmp("Diagnostic tools"),
                        "settings" => $this->get_general_section_diagnostics(),
                    ],
                ],
            ];
        }

        private function get_export_defaults_sections()
        {
            return [
                WCMP_Settings::SETTINGS_EXPORT_DEFAULTS => [
                    [
                        "name"     => "export_defaults",
                        "label"    => _wcmp('Default export settings'),
                        "settings" => $this->get_export_defaults_section_defaults(),
                    ],
                ],
            ];
        }

        /**
         * Get the array of bpost sections and their settings to be added to WordPress.
         *
         * @return array
         */
        private function get_carrier_bpost_sections()
        {
            return [
                BpostConsignment::CARRIER_NAME => [
                    [
                        "name"     => "delivery_options",
                        "label"    => _wcmp("bpost delivery options"),
                        "settings" => $this->get_bpost_section_delivery_options(),
                    ],
                    [
                        "name"     => "pickup_options",
                        "label"    => _wcmp("bpost pickup options"),
                        "settings" => $this->get_bpost_section_pickup_options(),
                    ],
                ],
            ];
        }

        /**
         * Get the array of dpd sections and their settings to be added to WordPress.
         *
         * @return array
         */
        private function get_carrier_dpd_sections()
        {
            return [
                DPDConsignment::CARRIER_NAME => [
                    [
                        "name"     => "delivery_options",
                        "label"    => _wcmp("dpd delivery options"),
                        "settings" => $this->get_dpd_section_delivery_options(),
                    ],
                    [
                        "name"     => "pickup_options",
                        "label"    => _wcmp("dpd pickup options"),
                        "settings" => $this->get_dpd_section_pickup_options(),
                    ],
                ],
            ];
        }

        private function carrier_defaults()
        {
            return [
                BpostConsignment::CARRIER_NAME => [
                    // settings
                    "delivery_enabled"     => "",
                    "drop_off_days"        => "",
                    "cutoff_time"          => "",
                    "drop_off_delay"       => "",
                    "delivery_days_window" => "",

                    // delivery_options
                    "signature"            => "",
                    "pickup"               => "",
                ],
                DPDConsignment::CARRIER_NAME   => [
                    // settings
                    "delivery_enabled"     => "",
                    "drop_off_days"        => "",
                    "cutoff_time"          => "",
                    "drop_off_delay"       => "",
                    "delivery_days_window" => "",

                    // delivery_options
                    "pickup"               => "",
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
        private function get_dpd_section_delivery_options(): array
        {
            return [
                [
                    "name"  => "delivery_enabled",
                    "label" => _wcmp("Enable DPD delivery"),
                    "type"  => "toggle",
                ],
                [
                    "name"        => "drop_off_days",
                    "label"       => _wcmp("Drop-off days"),
                    "type"        => "enhanced_select",
                    "options"     => (new WP_Locale())->weekday,
                    "description" =>  _wcmp("Days of the week on which you hand over parcels to dpd"),
                ],
                [
                    "name"        => "cutoff_time",
                    "label"       => _wcmp("Cut-off time"),
                    "placeholder" => "17:00",
                    "description" =>  _wcmp("Time at which you stop processing orders for the day (format: hh:mm)"),
                ],
                [
                    "name"        => "drop_off_delay",
                    "label"       => _wcmp("Drop-off delay"),
                    "description" =>  _wcmp("Number of days you need to process an order."),
                ],
                [
                    "name"        => "delivery_days_window",
                    "label"       => _wcmp("Delivery days window"),
                    "type"        => "toggle",
                    "description" =>  _wcmp("Show the delivery date inside the checkout."),
                ],
            ];
        }

        /**
         * @return array
         */
        private function get_dpd_section_pickup_options(): array
        {
            return [
                [
                    "name"        => "pickup",
                    "label"       => _wcmp("Enable dpd pickup"),
                    "type"        => "delivery_option_enable",
                    "has_title"   => false,
                    "has_price"   => true,
                    "description" =>  _wcmp("Enter an amount that is either positive or negative. For example, do you want to give a discount for using this function or do you want to charge extra for this delivery option."),
                ],
            ];
        }

        /**
         * @return array
         */
        private function get_general_section_api(): array
        {
            return [
                [
                    "name"  => "api_key",
                    "label" => _wcmp("Key"),
                ],
            ];
        }

        /**
         * @return array
         */
        private function get_general_section_general()
        {
            return [
                [
                    "name"    => "download_display",
                    "label"   => _wcmp("Label display"),
                    "type"    => "select",
                    "options" => [
                        "download" => _wcmp("Download PDF"),
                        "display"  => _wcmp("Open the PDF in a new tab"),
                    ],
                ],
                [
                    "name"    => "label_format",
                    "label"   => _wcmp("Label format"),
                    "type"    => "select",
                    "options" => [
                        "A4" => _wcmp("Standard printer (A4)"),
                        "A6" => _wcmp("Label Printer (A6)"),
                    ],
                ],
                [
                    "name"        => "print_position_offset",
                    "label"       => _wcmp("Ask for print start position"),
                    "condition"   => [
                        "name"         => "label_format",
                        "type"         => "disable",
                        "parent_value" => "A4",
                        "set_value"    => "0",
                    ],
                    "type"        => "toggle",
                    "description" =>  _wcmp("This option enables you to continue printing where you left off last time"),
                ],
                [
                    "name"        => "email_tracktrace",
                    "label"       => _wcmp("Track & Trace in email"),
                    "type"        => "toggle",
                    "description" =>  _wcmp("Add the Track & Trace code to emails to the customer.<br/><strong>Note!</strong> When you select this option, make sure you have not enabled the Track & Trace email in your MyParcel BE backend."),
                ],
                [
                    "name"        => "myaccount_tracktrace",
                    "label"       => _wcmp("Track & Trace in My Account"),
                    "type"        => "toggle",
                    "description" =>  _wcmp("Show Track & Trace trace code and link in My Account."),
                ],
                [
                    "name"        => "process_directly",
                    "label"       => _wcmp("Process shipments directly"),
                    "type"        => "toggle",
                    "description" =>  _wcmp("When you enable this option, shipments will be directly processed when sent to MyParcel BE."),
                ],
                [
                    "name"        => "order_status_automation",
                    "label"       => _wcmp("Order status automation"),
                    "type"        => "toggle",
                    "description" =>  _wcmp("Automatically set order status to a predefined status after successful MyParcel BE export.<br/>Make sure <strong>Process shipments directly</strong> is enabled when you use this option together with the <strong>Track & Trace in email</strong> option, otherwise the Track & Trace code will not be included in the customer email."),
                ],
                [
                    "name"  => "automatic_order_status",
                    "label" => _wcmp("Automatic order status"),
                    "type"  => "order_status_select",
                    "class" => "automatic_order_status",
                ],
                [
                    "name"        => "keep_shipments",
                    "label"       => _wcmp("Keep old shipments"),
                    "type"        => "toggle",
                    "default"     => 0,
                    "description" =>  _wcmp("With this option enabled, data from previous shipments (Track & Trace links) will be kept in the order when you export more than once."),
                ],
                [
                    "name"        => "barcode_in_note",
                    "label"       => _wcmp("Place barcode inside note"),
                    "type"        => "toggle",
                    "class"       => "barcode_in_note",
                    "description" => _wcmp("Place the barcode inside a note of the order"),
                ],
                [
                    "name"        => "barcode_in_note_title",
                    "label"       => _wcmp("Title before the barcode"),
                    "class"       => "barcode_in_note_title",
                    "default"     => "Tracking code:",
                    "description" =>  _wcmp("You can change the text before the barcode inside an note"),
                ],
            ];
        }

        /**
         * @return array
         */
        private function get_general_section_checkout_options()
        {
            return [
                [
                    "name"        => "use_split_address_fields",
                    "label"       => _wcmp("MyParcel BE address fields"),
                    "type"        => "toggle",
                    "class"       => "use_split_address_fields",
                    "description" =>  _wcmp("When enabled the checkout will use the MyParcel BE address fields. This means there will be three separate fields for street name, number and suffix. Want to use the WooCommerce default fields? Leave this option unchecked."),
                ],
                [
                    "name"        => "delivery_options_enabled",
                    "label"       => _wcmp("Enable MyParcel BE delivery options"),
                    "type"        => "toggle",
                    "description" =>  _wcmp("The MyParcel delivery options allow your customers to select whether they want their parcel delivered at home or to a pickup point. Depending on the settings you can allow them to select a date, time and even options like requiring a signature on delivery."),
                ],
                [
                    "name"        => "header_delivery_options_title",
                    "condition"   => "delivery_options_enabled",
                    "label"       => _wcmp("Delivery options title"),
                    "title"       => "Delivery options title",
                    "description" =>  _wcmp("You can place a delivery title above the MyParcel BE options. When there is no title, it will not be visible."),
                ],
                [
                    "name"      => "at_home_delivery",
                    "condition" => "delivery_options_enabled",
                    "label"     => _wcmp("Home delivery title"),
                    "title"     => "Delivered at home or at work",
                    "current"   => self::get_default_string("at_home_delivery_title"),
                ],
                [
                    "name"        => "standard_title",
                    "condition"   => "delivery_options_enabled",
                    "label"       => _wcmp("Standard delivery title"),
                    "title"       => "Standard delivery",
                    "current"     => self::get_default_string("standard_title"),
                    "description" =>  _wcmp("When there is no title, the delivery time will automatically be visible."),
                ],
                [
                    "name"      => "signature_title",
                    "condition" => "delivery_options_enabled",
                    "label"     => _wcmp("Signature on delivery"),
                    "has_title" => true,
                    "title"     => "Signature on delivery",
                    "current"   => self::get_default_string("signature_title"),
                ],
                [
                    "name"      => "pickup_title",
                    "condition" => "delivery_options_enabled",
                    "label"     => _wcmp("Pickup"),
                    "has_title" => true,
                    "title"     => "Pickup",
                    "current"   => self::get_default_string("pickup_title"),
                ],
                [
                    "name"        => "delivery_options_display",
                    "condition"   => "delivery_options_enabled",
                    "label"       => _wcmp("Display for"),
                    "type"        => "select",
                    "options"     => [
                        "selected_methods" =>  _wcmp("Shipping methods associated with Parcels"),
                        "all_methods"      => _wcmp("All shipping methods"),
                    ],
                    "description" =>  _wcmp("You can link the delivery options to specific shipping methods by adding them to the package types under \"Standard export settings\". The delivery options are not visible at foreign addresses."),
                ],
                [
                    "name"        => "checkout_position",
                    "condition"   => "delivery_options_enabled",
                    "label"       => _wcmp("Checkout position"),
                    "type"        => "select",
                    "options"     => [
                        "woocommerce_after_checkout_billing_form"  =>  _wcmp("Show checkout options after billing details"),
                        "woocommerce_after_checkout_shipping_form" =>  _wcmp("Show checkout options after shipping details"),
                        "woocommerce_after_order_notes"            =>  _wcmp("Show checkout options after notes"),
                    ],
                    "description" =>  _wcmp("You can change the place of the checkout options on the checkout page. By default it will be placed after shipping details."),
                ],
                [
                    "name"              => "delivery_options_custom_css",
                    "condition"         => "delivery_options_enabled",
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
        private function get_general_section_diagnostics()
        {
            return [
                [
                    "name"        => "error_logging",
                    "label"       => _wcmp("Log API communication"),
                    "type"        => "toggle",
                    "description" => '<a href="' . esc_url_raw(
                            admin_url("admin.php?page=wc-status&tab=logs")
                        ) . '" target="_blank">' .  _wcmp("View logs") . "</a> (wc-myparcelbe)",
                ],
            ];
        }

        private function get_export_defaults_section_defaults()
        {
            return [
                [
                    "name"          => "shipping_methods_package_types",
                    "label"         => _wcmp("Package types"),
                    "callback"      => [$this->callbacks, "shipping_methods_package_types"],
                    "package_types" => WCMP()->export->get_package_types(),
                    "description"   =>  _wcmp("Select one or more shipping methods for each MyParcel BE package type"),
                ],
                [
                    "name"        => "connect_email",
                    "label"       => _wcmp("Connect customer email"),
                    "type"        => "toggle",
                    "description" =>  _wcmp("When you connect the customer email, MyParcel BE can send a Track & Trace email to this address. In your %sMyParcel BE backend%s you can enable or disable this email and format it in your own style."),
                    '<a href="https://backoffice.sendmyparcel.be/settings/account" target="_blank">',
                    '</a>',
                ],
                [
                    "name"        => "connect_phone",
                    "label"       => _wcmp("Connect customer phone"),
                    "type"        => "toggle",
                    "description" =>  _wcmp("When you connect the customer's phone number, the courier can use this for the delivery of the parcel. This greatly increases the delivery success rate for foreign shipments."),
                ],
                [
                    "name"        => "label_description",
                    "label"       => _wcmp("Label description"),
                    "description" =>  _wcmp("When you connect the customer's phone number, the courier can use this for the delivery of the parcel. This greatly increases the delivery success rate for foreign shipments."),
                ],
                [
                    "name"        => "connect_phone",
                    "label"       => _wcmp("Connect customer phone"),
                    "type"        => "toggle",
                    "description" =>  _wcmp("When you connect the customer's phone number, the courier can use this for the delivery of the parcel. This greatly increases the delivery success rate for foreign shipments."),
                ],
            ];
        }

        /**
         * Get the defaults for each carrier.
         *
         * @param string $carrier
         *
         * @return array
         */
        public static function get_default_carrier_settings(string $carrier): array
        {
            return [
                "{$carrier}_delivery_enabled"     => 1,
                "{$carrier}_pickup_enabled"       => 0,
                "{$carrier}_drop_off_days"        => [1, 2, 3, 4, 5],
                "{$carrier}_drop_off_delay"       => 0,
                "{$carrier}_delivery_days_window" => 1,
            ];
        }

        /**
         * @return array
         */
        private static function get_default_general_settings(): array
        {
            return [
                "download_display" => "download",
                "label_format"     => "A4",
            ];
        }

        /**
         * @return array
         */
        private static function get_default_export_defaults_settings(): array
        {
            return [];
        }

        /**
         * @return array
         */
        private static function get_default_strings()
        {
            return [
                "at_home_delivery_title" => "Delivered at home or at work",
                "standard_title"         => "Standard delivery",
                "signature_title"        => "Signature on delivery",
                "pickup_title"           => "Pickup",
            ];
        }
    }

endif;

new WCMP_Settings_Data();
