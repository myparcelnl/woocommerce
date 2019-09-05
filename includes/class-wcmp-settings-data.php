<?php

use MyParcelNL\Sdk\src\Model\Consignment\BpostConsignment;
use MyParcelNL\Sdk\src\Model\Consignment\DPDConsignment;

if (! defined('ABSPATH')) {
    exit;
}

if (! class_exists('wcmp_settings_data')) :

    /**
     * This class contains all data for the admin settings screens created by the plugin.
     */
    class wcmp_settings_data
    {
        const CARRIERS = [
            BpostConsignment::CARRIER_NAME,
            DPDConsignment::CARRIER_NAME
        ];

        /**
         * @var wcmp_settings_callbacks
         */
        private $callbacks;

        public function __construct()
        {
            $this->callbacks = require 'class-wcmp-settings-callbacks.php';

            $this->createSettings();
        }

        /**
         * Create the MyParcel settings with the admin_init hook.
         */
        private function createSettings()
        {
            add_action("admin_init", [$this, "create_general_settings"]);
            add_action("admin_init", [$this, "create_export_defaults_settings"]);
            add_action("admin_init", [$this, "create_carrier_settings"]);
        }

        /**
         * @return array
         */
        public static function get_default_carrier_settings()
        {
            return [
                'delivery_enabled'       => '1',
                'pickup_enabled'         => '0',
                'dropoff_days'           => [1, 2, 3, 4, 5],
                'dropoff_delay'          => '0',
                'deliverydays_window'    => '1',
                'at_home_delivery_title' => 'Delivered at home or at work',
                'standard_title'         => 'Standard delivery',
                'signature_title'        => 'Signature on delivery',
                'pickup_title'           => 'Pickup',
            ];
        }

        /**
         * @return array
         */
        private static function get_default_general_settings(): array
        {
            return array(
                "download_display" => "download",
                "label_format"     => "A4",
            );
        }

        /**
         * @param $key
         *
         * @return string
         */
        public static function get_checkout_setting_title($key)
        {
            $checkout_settings = self::get_default_carrier_settings();
            $setting           = $checkout_settings[$key];

            return __($setting, 'woocommerce-myparcelbe');
        }

        /**
         * Set default settings.
         * @return void.
         */
        public static function set_default_settings($option)
        {
            switch ($option) {
                case "general":
                    $default = self::get_default_general_settings();
                    break;
                case BpostConsignment::CARRIER_NAME:
                case DPDConsignment::CARRIER_NAME:
                    $default = self::get_default_carrier_settings();
                    break;
                case "export_defaults":
                default:
                    $default = [];
                    break;
            }

            if (false === get_option($option)) {
                add_option($option, $default);
            } else {
                update_option($option, $default);
            }
        }

        /**
         * @return array
         */
        private function get_dpd_section_delivery_options(): array
        {
            return [
                [
                    "name" => "pickup",
                    __("dpd pickup", "woocommerce-myparcelbe"),
                    "type" => "delivery_option_enable",
                    "args" => [
                        "has_title"          => false,
                        "has_price"          => true,
                        "size"               => 3,
                        "option_description" => sprintf(
                            __("Enter an amount that is either positive or negative. For example, do you want to give a discount for using this function or do you want to charge extra for this delivery option.",
                                "woocommerce-myparcelbe"
                            )
                        ),
                    ]
                ]
            ];
        }

        /**
         * @return array
         */
        private function get_bpost_section_settings(): array
        {
            return [
                [
                    "name"  => "delivery_enabled",
                    "label" => __("Enable bpost delivery", "woocommerce-myparcelbe"),
                    "type"  => "checkbox",
                ],
                [
                    "name"  => "dropoff_days",
                    "label" => __("Drop-off days", "woocommerce-myparcelbe"),
                    "type"  => "enhanced_select",
                    "args"  => [
                        "options"     => self::get_weekdays(),
                        "description" => __("Days of the week on which you hand over parcels to bpost",
                            "woocommerce-myparcelbe"
                        ),
                    ]
                ],
                [
                    "name"  => "cutoff_time",
                    "label" => __("Cut-off time", "woocommerce-myparcelbe"),
                    "type"  => "text_input",
                    "args"  => [
                        "type"        => "text",
                        "size"        => "5",
                        "description" => __("Time at which you stop processing orders for the day (format: hh:mm)",
                            "woocommerce-myparcelbe"
                        ),
                    ]
                ],
                [
                    "name"  => "dropoff_delay",
                    "label" => __("Drop-off delay", "woocommerce-myparcelbe"),
                    "type"  => "text_input",
                    "args"  => [
                        "type"        => "text",
                        "size"        => "5",
                        "description" => __("Number of days you need to process an order.",
                            "woocommerce-myparcelbe"
                        ),
                    ]
                ],
                [
                    "name"  => "deliverydays_window",
                    "label" => __("Delivery days window", "woocommerce-myparcelbe"),
                    "type"  => "checkbox",
                    "args"  => [
                        "description" => __("Show the delivery date inside the checkout.",
                            "woocommerce-myparcelbe"
                        ),
                    ]
                ],
            ];
        }

        /**
         * Create an array of translated weekdays.
         *
         * @return array
         */
        public static function get_weekdays()
        {
            return [
                '0' => __('Sunday', 'woocommerce-myparcelbe'),
                '1' => __('Monday', 'woocommerce-myparcelbe'),
                '2' => __('Tuesday', 'woocommerce-myparcelbe'),
                '3' => __('Wednesday', 'woocommerce-myparcelbe'),
                '4' => __('Thursday', 'woocommerce-myparcelbe'),
                '5' => __('Friday', 'woocommerce-myparcelbe'),
                '6' => __('Saturday', 'woocommerce-myparcelbe'),
            ];
        }

        /**
         * @return array
         */
        private function get_bpost_section_delivery_options(): array
        {
            return [
                [
                    "name" => "signature",
                    __("Signature on delivery", "woocommerce-myparcelbe"),
                    "type" => "delivery_option_enable",
                    "args" => [
                        "has_title"          => false,
                        "has_price"          => true,
                        "size"               => 3,
                        "option_description" => sprintf(
                            __("Enter an amount that is either positive or negative. For example, do you want to give a discount for using this function or do you want to charge extra for this delivery option.",
                                "woocommerce-myparcelbe"
                            )
                        ),
                    ]
                ],
                [
                    "name" => "pickup",
                    __("dpd pickup", "woocommerce-myparcelbe"),
                    "type" => "delivery_option_enable",
                    "args" => [
                        "has_title"          => false,
                        "has_price"          => true,
                        "size"               => 3,
                        "option_description" => sprintf(
                            __("Enter an amount that is either positive or negative. For example, do you want to give a discount for using this function or do you want to charge extra for this delivery option.",
                                "woocommerce-myparcelbe"
                            )
                        ),
                    ]
                ]
            ];
        }

        /**
         * Create the carrier specific settings sections.
         */
        public function create_carrier_settings()
        {
            foreach (self::CARRIERS as $carrier) {
                $this->generate_settings($this->get_carrier_sections(), $carrier, true);
            }
        }

        /**
         * Create the general settings sections.
         */
        public function create_general_settings()
        {
            $this->generate_settings($this->get_general_sections(), "general");
        }

        /**
         * Register Export defaults settings
         */
        public function export_defaults_settings()
        {
//            var_dump("export_defaults_settings");
            $option_group = 'woocommerce_myparcelbe_export_defaults_settings';

            // Register settings.
            $option_name = 'woocommerce_myparcelbe_export_defaults_settings';
            register_setting($option_group, $option_name, array($this->callbacks, 'validate'));

            // Create option in wp_options.
            if (false === get_option($option_name)) {
                $this->set_default_settings($option_name);
            }

            // API section.
            add_settings_section(
                'defaults',
                __('Default export settings', 'woocommerce-myparcelbe'),
                array($this->callbacks, 'section'),
                $option_group
            );

            if (version_compare(WOOCOMMERCE_VERSION, '2.1', '>=')) {
                add_settings_field(
                    'shipping_methods_package_types',
                    __('Package types', 'woocommerce-myparcelbe'),
                    array($this->callbacks, 'shipping_methods_package_types'),
                    $option_group,
                    'defaults',
                    array(
                        'option_name'   => $option_name,
                        'id'            => 'shipping_methods_package_types',
                        'package_types' => WooCommerce_MyParcelBE()->export->get_package_types(),
                        'description'   => __('Select one or more shipping methods for each MyParcel BE package type',
                            'woocommerce-myparcelbe'
                        ),
                    )
                );
            } else {
                add_settings_field(
                    'package_type',
                    __('Shipment type', 'woocommerce-myparcelbe'),
                    array($this->callbacks, 'select'),
                    $option_group,
                    'defaults',
                    array(
                        'option_name' => $option_name,
                        'id'          => 'package_type',
                        'default'     => (string) wcmp_export::PACKAGE,
                        'options'     => WooCommerce_MyParcelBE()->export->get_package_types(),
                    )
                );
            }

            add_settings_field(
                'connect_email',
                __('Connect customer email', 'woocommerce-myparcelbe'),
                array($this->callbacks, 'checkbox'),
                $option_group,
                'defaults',
                array(
                    'option_name' => $option_name,
                    'id'          => 'connect_email',
                    'description' => sprintf(__('When you connect the customer email, MyParcel BE can send a Track & Trace email to this address. In your %sMyParcel BE backend%s you can enable or disable this email and format it in your own style.',
                        'woocommerce-myparcelbe'
                    ),
                        '<a href="https://backoffice.sendmyparcel.be/settings/account" target="_blank">',
                        '</a>'
                    )
                )
            );

            add_settings_field(
                'connect_phone',
                __('Connect customer phone', 'woocommerce-myparcelbe'),
                array($this->callbacks, 'checkbox'),
                $option_group,
                'defaults',
                array(
                    'option_name' => $option_name,
                    'id'          => 'connect_phone',
                    'description' => __("When you connect the customer's phone number, the courier can use this for the delivery of the parcel. This greatly increases the delivery success rate for foreign shipments.",
                        'woocommerce-myparcelbe'
                    )
                )
            );

            add_settings_field(
                'label_description',
                __('Label description', 'woocommerce-myparcelbe'),
                array($this->callbacks, 'text_input'),
                $option_group,
                'defaults',
                array(
                    'option_name' => $option_name,
                    'id'          => 'label_description',
                    'size'        => '25',
                    'description' => __("With this option, you can add a description to the shipment. This will be printed on the top left of the label, and you can use this to search or sort shipments in the MyParcel BE Backend. Use <strong>[ORDER_NR]</strong> to include the order number, <strong>[DELIVERY_DATE]</strong> to include the delivery date.",
                        'woocommerce-myparcelbe'
                    ),
                )
            );

            add_settings_field(
                'package_contents',
                __('Customs shipment type', 'woocommerce-myparcelbe'),
                array($this->callbacks, 'select'),
                $option_group,
                'defaults',
                array(
                    'option_name' => $option_name,
                    'id'          => 'package_contents',
                    'options'     => array(
                        1 => __('Commercial goods', 'woocommerce-myparcelbe'),
                        2 => __('Commercial samples', 'woocommerce-myparcelbe'),
                        3 => __('Documents', 'woocommerce-myparcelbe'),
                        4 => __('Gifts', 'woocommerce-myparcelbe'),
                        5 => __('Return shipment', 'woocommerce-myparcelbe'),
                    ),
                )
            );
        }

        /**
         * Register General settings
         */
        public function general_settings()
        {
//            var_dump("general_settings");
            $option_group = 'woocommerce_myparcelbe_general_settings';

            // Register settings.
            $option_name = 'woocommerce_myparcelbe_general_settings';
            register_setting($option_group, $option_name, array($this->callbacks, 'validate'));

            // Create option in wp_options.
            if (false === get_option($option_name)) {
                $this->set_default_settings($option_name);
            }

            // API section.
            add_settings_section(
                'api',
                __('API settings', 'woocommerce-myparcelbe'),
                array($this->callbacks, 'section'),
                $option_group
            );

            add_settings_field(
                'api_key',
                __('Key', 'woocommerce-myparcelbe'),
                array($this->callbacks, 'text_input'),
                $option_group,
                'api',
                array(
                    'option_name' => $option_name,
                    'id'          => 'api_key',
                    'size'        => 50,
                )
            );

            // General section.
            add_settings_section(
                'general',
                __('General settings', 'woocommerce-myparcelbe'),
                array($this->callbacks, 'section'),
                $option_group
            );

            add_settings_field(
                'download_display',
                __('Label display', 'woocommerce-myparcelbe'),
                array($this->callbacks, 'radio_button'),
                $option_group,
                'general',
                array(
                    'option_name' => $option_name,
                    'id'          => 'download_display',
                    'options'     => array(
                        'download' => __('Download PDF', 'woocommerce-myparcelbe'),
                        'display'  => __('Open the PDF in a new tab', 'woocommerce-myparcelbe'),
                    ),
                )
            );
            add_settings_field(
                'label_format',
                __('Label format', 'woocommerce-myparcelbe'),
                array($this->callbacks, 'radio_button'),
                $option_group,
                'general',
                array(
                    'option_name' => $option_name,
                    'id'          => 'label_format',
                    'options'     => array(
                        'A4' => __('Standard printer (A4)', 'woocommerce-myparcelbe'),
                        'A6' => __('Label Printer (A6)', 'woocommerce-myparcelbe'),
                    ),
                )
            );

            add_settings_field(
                'print_position_offset',
                __('Ask for print start position', 'woocommerce-myparcelbe'),
                array($this->callbacks, 'checkbox'),
                $option_group,
                'general',
                array(
                    'option_name' => $option_name,
                    'id'          => 'print_position_offset',
                    'description' => __('This option enables you to continue printing where you left off last time',
                        'woocommerce-myparcelbe'
                    )
                )
            );

            add_settings_field(
                'email_tracktrace',
                __('Track & Trace in email', 'woocommerce-myparcelbe'),
                array($this->callbacks, 'checkbox'),
                $option_group,
                'general',
                array(
                    'option_name' => $option_name,
                    'id'          => 'email_tracktrace',
                    'description' => __('Add the Track & Trace code to emails to the customer.<br/><strong>Note!</strong> When you select this option, make sure you have not enabled the Track & Trace email in your MyParcel BE backend.',
                        'woocommerce-myparcelbe'
                    )
                )
            );

            add_settings_field(
                'myaccount_tracktrace',
                __('Track & Trace in My Account', 'woocommerce-myparcelbe'),
                array($this->callbacks, 'checkbox'),
                $option_group,
                'general',
                array(
                    'option_name' => $option_name,
                    'id'          => 'myaccount_tracktrace',
                    'description' => __('Show Track & Trace trace code and link in My Account.',
                        'woocommerce-myparcelbe'
                    )
                )
            );

            add_settings_field(
                'process_directly',
                __('Process shipments directly', 'woocommerce-myparcelbe'),
                array($this->callbacks, 'checkbox'),
                $option_group,
                'general',
                array(
                    'option_name' => $option_name,
                    'id'          => 'process_directly',
                    'description' => __('When you enable this option, shipments will be directly processed when sent to MyParcel BE.',
                        'woocommerce-myparcelbe'
                    )
                )
            );

            add_settings_field(
                'order_status_automation',
                __('Order status automation', 'woocommerce-myparcelbe'),
                array($this->callbacks, 'checkbox'),
                $option_group,
                'general',
                array(
                    'option_name' => $option_name,
                    'id'          => 'order_status_automation',
                    'description' => __('Automatically set order status to a predefined status after successful MyParcel BE export.<br/>Make sure <strong>Process shipments directly</strong> is enabled when you use this option together with the <strong>Track & Trace in email</strong> option, otherwise the Track & Trace code will not be included in the customer email.',
                        'woocommerce-myparcelbe'
                    )
                )
            );

            add_settings_field(
                'automatic_order_status',
                __('Automatic order status', 'woocommerce-myparcelbe'),
                array($this->callbacks, 'order_status_select'),
                $option_group,
                'general',
                array(
                    'option_name' => $option_name,
                    'id'          => 'automatic_order_status',
                    'class'       => 'automatic_order_status',
                )
            );

            add_settings_field(
                'keep_shipments',
                __('Keep old shipments', 'woocommerce-myparcelbe'),
                array($this->callbacks, 'checkbox'),
                $option_group,
                'general',
                array(
                    'option_name' => $option_name,
                    'id'          => 'keep_shipments',
                    'default'     => 0,
                    'description' => __('With this option enabled, data from previous shipments (Track & Trace links) will be kept in the order when you export more than once.',
                        'woocommerce-myparcelbe'
                    )
                )
            );

            add_settings_field(
                'barcode_in_note',
                __('Place barcode inside note', 'woocommerce-myparcelbe'),
                array($this->callbacks, 'checkbox'),
                $option_group,
                'general',
                array(
                    'option_name' => $option_name,
                    'id'          => 'barcode_in_note',
                    'class'       => 'barcode_in_note',
                    'description' => __('Place the barcode inside a note of the order', 'woocommerce-myparcelbe')
                )
            );

            add_settings_field(
                'barcode_in_note_title',
                __('Title before the barcode', 'woocommerce-myparcelbe'),
                array($this->callbacks, 'text_input'),
                $option_group,
                'general',
                array(
                    'option_name' => $option_name,
                    'id'          => 'barcode_in_note_title',
                    'class'       => 'barcode_in_note_title',
                    'default'     => 'Tracking code:',
                    'size'        => 25,
                    'description' => __('You can change the text before the barcode inside an note',
                        'woocommerce-myparcelbe'
                    ),
                )
            );

            // Checkout options section.
            add_settings_section(
                'checkout_options',
                __('Checkout options', 'woocommerce-myparcelbe'),
                array($this->callbacks, 'section'),
                $option_group
            );

            add_settings_field(
                'use_split_address_fields',
                __('MyParcel BE address fields', 'woocommerce-myparcelbe'),
                array($this->callbacks, 'checkbox'),
                $option_group,
                'checkout_options',
                array(
                    'option_name' => $option_name,
                    'id'          => 'use_split_address_fields',
                    'class'       => 'use_split_address_fields',
                    'description' => __('When enabled the checkout will use the MyParcel BE address fields. This means there will be three separate fields for street name, number and suffix. Want to use the WooCommerce default fields? Leave this option unchecked.',
                        'woocommerce-myparcelbe'
                    ),
                )
            );

            add_settings_field(
                'delivery_options_enabled',
                __('Enable MyParcel BE delivery options', 'woocommerce-myparcelbe'),
                array($this->callbacks, 'checkbox'),
                $option_group,
                'checkout_options',
                array(
                    'option_name' => $option_name,
                    'id'          => 'delivery_options_enabled',
                )
            );

            add_settings_field(
                'header_delivery_options_title',
                __('Delivery options title', 'woocommerce-myparcelbe'),
                array(
                    $this->callbacks,
                    'text_input'
                ),
                $option_group,
                'checkout_options',
                array(
                    'option_name' => $option_name,
                    'id'          => 'header_delivery_options_title',
                    'size'        => '53',
                    'title'       => 'Delivery options title',
                    'description' => __('You can place a delivery title above the MyParcel BE options. When there is no title, it will not be visible.',
                        'woocommerce-myparcelbe'
                    ),
                )
            );

            add_settings_field(
                'at_home_delivery',
                __('Home delivery title', 'woocommerce-myparcelbe'),
                array(
                    $this->callbacks,
                    'text_input'
                ),
                $option_group,
                'checkout_options',
                array(
                    'option_name' => $option_name,
                    'id'          => 'at_home_delivery',
                    'size'        => '53',
                    'title'       => 'Delivered at home or at work',
                    'current'     => self::get_checkout_setting_title('at_home_delivery_title'),
                )
            );

            add_settings_field(
                'standard_title',
                __('Standard delivery title', 'woocommerce-myparcelbe'),
                array(
                    $this->callbacks,
                    'text_input'
                ),
                $option_group,
                'checkout_options',
                array(
                    'option_name' => $option_name,
                    'id'          => 'standard_title',
                    'size'        => '53',
                    'title'       => 'Standard delivery',
                    'current'     => self::get_checkout_setting_title('standard_title'),
                    'description' => __('When there is no title, the delivery time will automatically be visible.',
                        'woocommerce-myparcelbe'
                    ),
                )
            );

            add_settings_field(
                'signature_title',
                __('Signature on delivery', 'woocommerce-myparcelbe'),
                array(
                    $this->callbacks,
                    'text_input'
                ),
                $option_group,
                'checkout_options',
                array(
                    'option_name' => $option_name,
                    'id'          => 'signature_title',
                    'has_title'   => true,
                    'title'       => 'Signature on delivery',
                    'current'     => self::get_checkout_setting_title('signature_title'),
                    'size'        => '30',
                )
            );

            add_settings_field(
                'pickup_title',
                __('Pickup', 'woocommerce-myparcelbe'),
                array(
                    $this->callbacks,
                    'text_input'
                ),
                $option_group,
                'checkout_options',
                array(
                    'option_name' => $option_name,
                    'id'          => 'pickup_title',
                    'has_title'   => true,
                    'title'       => 'Pickup',
                    'current'     => self::get_checkout_setting_title('pickup_title'),
                    'size'        => '30',
                )
            );

            add_settings_field(
                'checkout_display',
                __('Display for', 'woocommerce-myparcelbe'),
                array($this->callbacks, 'select'),
                $option_group,
                'checkout_options',
                array(
                    'option_name' => $option_name,
                    'id'          => 'checkout_display',
                    'options'     => array(
                        'selected_methods' => __('Shipping methods associated with Parcels', 'woocommerce-myparcelbe'),
                        'all_methods'      => __('All shipping methods', 'woocommerce-myparcelbe'),
                    ),
                    'description' => __('You can link the delivery options to specific shipping methods by adding them to the package types under \'Standard export settings\'. The delivery options are not visible at foreign addresses.',
                        'woocommerce-myparcelbe'
                    ),
                )
            );

            // Place of the checkout
            add_settings_field(
                'checkout_position',
                __('Checkout position', 'woocommerce-myparcelbe'),
                array(
                    $this->callbacks,
                    'select'
                ),
                $option_group,
                'checkout_options',
                array(
                    'option_name' => $option_name,
                    'id'          => 'checkout_position',
                    'options'     => array(
                        'woocommerce_after_checkout_billing_form'  => __('Show checkout options after billing details',
                            'woocommerce-myparcelbe'
                        ),
                        'woocommerce_after_checkout_shipping_form' => __('Show checkout options after shipping details',
                            'woocommerce-myparcelbe'
                        ),
                        'woocommerce_after_order_notes'            => __('Show checkout options after notes',
                            'woocommerce-myparcelbe'
                        ),
                    ),
                    'description' => __('You can change the place of the checkout options on the checkout page. By default it will be placed after shipping details.',
                        'woocommerce-myparcelbe'
                    ),
                )
            );

            // Customizations section
            add_settings_section(
                'customizations',
                __('Customizations', 'woocommerce-myparcelbe'),
                array(
                    $this->callbacks,
                    'section'
                ),
                $option_group
            );

            add_settings_field(
                'custom_css',
                __('Custom styles', 'woocommerce-myparcelbe'),
                [
                    $this->callbacks,
                    'textarea',
                ],
                $option_group,
                'customizations',
                [
                    'option_name' => $option_name,
                    'id'          => 'custom_css',
                    'width'       => '80',
                    'height'      => '8',
                ]
            );

            // Diagnostics section.
            add_settings_section(
                'diagnostics',
                __('Diagnostic tools', 'woocommerce-myparcelbe'),
                array($this->callbacks, 'section'),
                $option_group
            );

            add_settings_field(
                'error_logging',
                __('Log API communication', 'woocommerce-myparcelbe'),
                array($this->callbacks, 'checkbox'),
                $option_group,
                'diagnostics',
                array(
                    'option_name' => $option_name,
                    'id'          => 'error_logging',
                    'description' => '<a href="' . esc_url_raw(admin_url('admin.php?page=wc-status&tab=logs')
                        ) . '" target="_blank">' . __('View logs', 'woocommerce-myparcelbe') . '</a> (wc-myparcelbe)',
                )
            );
        }

        private function get_general_sections()
        {
            return [
                "general" => [
                    [
                        "name"     => "api",
                        "label"    => __("API settings", "woocommerce-myparcelbe"),
                        "settings" => $this->get_general_section_api()
                    ],
                    [
                        "name"     => "general",
                        "label"    => __("General settings", "woocommerce-myparcelbe"),
                        "settings" => $this->get_general_section_general()
                    ],
                    [
                        "name"     => "checkout_options",
                        "label"    => __("Checkout options", "woocommerce-myparcelbe"),
                        "settings" => $this->get_general_section_checkout_options()
                    ],
                    [
                        "name"     => "customizations",
                        "label"    => __("Customizations", "woocommerce-myparcelbe"),
                        "settings" => $this->get_general_section_customizations()
                    ],
                    [
                        "name"     => "diagnostics",
                        "label"    => __("Diagnostic tools", "woocommerce-myparcelbe"),
                        "settings" => $this->get_general_section_diagnostics()
                    ]
                ]
            ];
        }

        /**
         * Get the array of all carrier sections and their settings to be added to WordPress.
         *
         * @return array
         */
        private function get_carrier_sections()
        {
            return [
                BpostConsignment::CARRIER_NAME => [
                    // sections
                    [
                        "name"     => "settings",
                        "label"    => __("bpost settings", "woocommerce-myparcelbe"),
                        // settings fields
                        "settings" => $this->get_bpost_section_settings()
                    ],
                    [
                        "name"     => "delivery_options",
                        "label"    => __("bpost delivery options", "woocommerce-myparcelbe"),
                        "settings" => $this->get_bpost_section_delivery_options()
                    ]
                ],
                DPDConsignment::CARRIER_NAME   => [
                    [
                        "name"     => "settings",
                        "label"    => __("dpd settings", "woocommerce-myparcelbe"),
                        "settings" => $this->get_dpd_section_settings()
                    ],
                    [
                        "name"     => "delivery_options",
                        "label"    => __("dpd delivery options", "woocommerce-myparcelbe"),
                        "settings" => $this->get_dpd_section_delivery_options()
                    ]
                ]
            ];
        }

        private function carrier_defaults()
        {
            return [
                BpostConsignment::CARRIER_NAME => [
                    // settings
                    "delivery_enabled"    => "",
                    "dropoff_days"        => "",
                    "cutoff_time"         => "",
                    "dropoff_delay"       => "",
                    "deliverydays_window" => "",

                    // delivery_options
                    "signature"           => "",
                    "pickup"              => "",
                ],
                DPDConsignment::CARRIER_NAME   => [
                    // settings
                    "delivery_enabled"    => "",
                    "dropoff_days"        => "",
                    "cutoff_time"         => "",
                    "dropoff_delay"       => "",
                    "deliverydays_window" => "",

                    // delivery_options
                    "pickup"              => ""
                ]
            ];
        }

        /**
         * @return array
         */
        private function get_dpd_section_settings(): array
        {
            return [
                [
                    "name"  => "delivery_enabled",
                    "label" => __("Enable DPD delivery", "woocommerce-myparcelbe"),
                    "type"  => "checkbox",
                ],
                [
                    "name"  => "dropoff_days",
                    "label" => __("Drop-off days", "woocommerce-myparcelbe"),
                    "type"  => "enhanced_select",
                    "args"  => [
                        "options"     => self::get_weekdays(),
                        "description" => __("Days of the week on which you hand over parcels to dpd",
                            "woocommerce-myparcelbe"
                        ),
                    ]
                ],
                [
                    "name"  => "cutoff_time",
                    "label" => __("Cut-off time", "woocommerce-myparcelbe"),
                    "type"  => "text_input",
                    "args"  => [
                        "type"        => "text",
                        "size"        => "5",
                        "description" => __("Time at which you stop processing orders for the day (format: hh:mm)",
                            "woocommerce-myparcelbe"
                        ),
                    ]
                ],
                [
                    "name"  => "dropoff_delay",
                    "label" => __("Drop-off delay", "woocommerce-myparcelbe"),
                    "type"  => "text_input",
                    "args"  => [
                        "type"        => "text",
                        "size"        => "5",
                        "description" => __("Number of days you need to process an order.",
                            "woocommerce-myparcelbe"
                        ),
                    ]
                ],
                [
                    "name"  => "deliverydays_window",
                    "label" => __("Delivery days window", "woocommerce-myparcelbe"),
                    "type"  => "checkbox",
                    "args"  => [
                        "description" => __("Show the delivery date inside the checkout.",
                            "woocommerce-myparcelbe"
                        ),
                    ]
                ],
            ];
        }

        /**
         * @param array $settingsArray - Array of settings to loop through.
         * @param string $optionName - Name to use in the identifier.
         * @param bool $prefix - Add the key of the top level settings as prefix before every setting or not.
         */
        private function generate_settings(array $settingsArray, string $optionName, bool $prefix = false)
        {
            $optionIdentifier = "woocommerce_myparcelbe_{$optionName}_settings";

            // Register settings.
            register_setting($optionIdentifier, $optionIdentifier, array($this->callbacks, 'validate'));

            // Create option in wp_options with default settings if the option doesn't exist yet.
            if (false === get_option($optionIdentifier)) {
                $this->set_default_settings($optionName);
            }

            foreach ($settingsArray as $name => $section) {
                $section     = $section[0];
                $sectionName = "{$name}_{$section["name"]}";

                add_settings_section(
                    $sectionName,
                    $section["label"],
                    [$this->callbacks, 'section'],
                    $optionIdentifier
                );

                foreach ($section["settings"] as $setting) {
                    $settingName = $prefix ? "{$name}_{$setting["name"]}" : $setting["name"];
                    add_settings_field(
                        $settingName,
                        $setting["label"],
                        [$this->callbacks, $setting["type"]],
                        $optionIdentifier,
                        $sectionName,
                        array_merge([
                            'option_name' => $optionIdentifier,
                            'id'          => $settingName
                        ],
                            $setting["args"] ?? []
                        )
                    );
                }
            }
        }

        private function get_general_section_api(): array
        {
            return [
                [
                    "name"  => "api_key",
                    "label" => __("Key", "woocommerce-myparcelbe"),
                    "type"  => "text_input",
                    "args"  => [
                        "size" => 50,
                    ]
                ],
            ];
        }

        private function get_general_section_general()
        {
            return [
                [
                    "name"  => "download_display",
                    "label" => __("Label display", "woocommerce-myparcelbe"),
                    "type"  => "radio_button",
                    "args"  => [
                        "options" => [
                            "download" => __("Download PDF", "woocommerce-myparcelbe"),
                            "display"  => __("Open the PDF in a new tab", "woocommerce-myparcelbe"),
                        ],
                    ]
                ],
                [
                    "name"  => "label_format",
                    "label" => __("Label format", "woocommerce-myparcelbe"),
                    "type"  => "radio_button",
                    "args"  => [
                        "options" => [
                            "A4" => __("Standard printer (A4)", "woocommerce-myparcelbe"),
                            "A6" => __("Label Printer (A6)", "woocommerce-myparcelbe"),
                        ],
                    ]
                ],
                [
                    "name"  => "print_position_offset",
                    "label" => __("Ask for print start position", "woocommerce-myparcelbe"),
                    "type"  => "checkbox",
                    "args"  => [
                        "description" => __("This option enables you to continue printing where you left off last time",
                            "woocommerce-myparcelbe"
                        )
                    ]
                ],
                [
                    "name"  => "email_tracktrace",
                    "label" => __("Track & Trace in email", "woocommerce-myparcelbe"),
                    "type"  => "checkbox",
                    "args"  => [
                        "description" => __("Add the Track & Trace code to emails to the customer.<br/><strong>Note!</strong> When you select this option, make sure you have not enabled the Track & Trace email in your MyParcel BE backend.",
                            "woocommerce-myparcelbe"
                        )
                    ]
                ],
                [
                    "name"  => "myaccount_tracktrace",
                    "label" => __("Track & Trace in My Account", "woocommerce-myparcelbe"),
                    "type"  => "checkbox",
                    "args"  => [
                        "description" => __("Show Track & Trace trace code and link in My Account.",
                            "woocommerce-myparcelbe"
                        )
                    ]
                ],
                [
                    "name"  => "process_directly",
                    "label" => __("Process shipments directly", "woocommerce-myparcelbe"),
                    "type"  => "checkbox",
                    "args"  => [
                        "description" => __("When you enable this option, shipments will be directly processed when sent to MyParcel BE.",
                            "woocommerce-myparcelbe"
                        )
                    ]
                ],
                [
                    "name"  => "order_status_automation",
                    "label" => __("Order status automation", "woocommerce-myparcelbe"),
                    "type"  => "checkbox",
                    "args"  => [
                        "description" => __("Automatically set order status to a predefined status after successful MyParcel BE export.<br/>Make sure <strong>Process shipments directly</strong> is enabled when you use this option together with the <strong>Track & Trace in email</strong> option, otherwise the Track & Trace code will not be included in the customer email.",
                            "woocommerce-myparcelbe"
                        )
                    ]
                ],
                [
                    "name"  => "automatic_order_status",
                    "label" => __("Automatic order status", "woocommerce-myparcelbe"),
                    "type"  => "order_status_select",
                    "args"  => [
                        "class" => "automatic_order_status",
                    ]
                ],
                [
                    "name"  => "keep_shipments",
                    "label" => __("Keep old shipments", "woocommerce-myparcelbe"),
                    "type"  => "checkbox",
                    "args"  => [
                        "default"     => 0,
                        "description" => __("With this option enabled, data from previous shipments (Track & Trace links) will be kept in the order when you export more than once.",
                            "woocommerce-myparcelbe"
                        )
                    ]
                ],
                [
                    "name"  => "barcode_in_note",
                    "label" => __("Place barcode inside note", "woocommerce-myparcelbe"),
                    "type"  => "checkbox",
                    "args"  => [
                        "class"       => "barcode_in_note",
                        "description" => __("Place the barcode inside a note of the order", "woocommerce-myparcelbe")
                    ]
                ],
                [
                    "name"  => "barcode_in_note_title",
                    "label" => __("Title before the barcode", "woocommerce-myparcelbe"),
                    "type"  => "text_input",
                    "args"  => [
                        "class"       => "barcode_in_note_title",
                        "default"     => "Tracking code:",
                        "size"        => 25,
                        "description" => __("You can change the text before the barcode inside an note",
                            "woocommerce-myparcelbe"
                        ),
                    ]
                ]
            ];
        }

        private function get_general_section_checkout_options()
        {
            return [
                [
                    "name"  => "use_split_address_fields",
                    "label" => __("MyParcel BE address fields", "woocommerce-myparcelbe"),
                    "type"  => "checkbox",
                    "args"  => [
                        "class"       => "use_split_address_fields",
                        "description" => __("When enabled the checkout will use the MyParcel BE address fields. This means there will be three separate fields for street name, number and suffix. Want to use the WooCommerce default fields? Leave this option unchecked.",
                            "woocommerce-myparcelbe"
                        ),
                    ]
                ],
                [
                    "name"  => "delivery_options_enabled",
                    "label" => __("Enable MyParcel BE delivery options", "woocommerce-myparcelbe"),
                    "type"  => "checkbox",
                ],
                [
                    "name"  => "header_delivery_options_title",
                    "label" => __("Delivery options title", "woocommerce-myparcelbe"),
                    "type"  => "text_input",
                    "args"  => [
                        "size"        => "53",
                        "title"       => "Delivery options title",
                        "description" => __("You can place a delivery title above the MyParcel BE options. When there is no title, it will not be visible.",
                            "woocommerce-myparcelbe"
                        ),
                    ]
                ],
                [
                    "name"  => "at_home_delivery",
                    "label" => __("Home delivery title", "woocommerce-myparcelbe"),
                    "type"  => "text_input",
                    "args"  => [
                        "size"        => "53",
                        "title"       => "Delivered at home or at work",
                        "current"     => self::get_checkout_setting_title("at_home_delivery_title"),
                    ]
                ],
                [
                    "name"  => "standard_title",
                    "label" => __("Standard delivery title", "woocommerce-myparcelbe"),
                    "type"  => "text_input",
                    "args"  => [
                        "size"        => "53",
                        "title"       => "Standard delivery",
                        "current"     => self::get_checkout_setting_title("standard_title"),
                        "description" => __("When there is no title, the delivery time will automatically be visible.",
                            "woocommerce-myparcelbe"
                        ),
                    ]
                ],
                [
                    "name"  => "signature_title",
                    "label" => __("Signature on delivery", "woocommerce-myparcelbe"),
                    "type"  => "text_input",
                    "args"  => [
                        "has_title"   => true,
                        "title"       => "Signature on delivery",
                        "current"     => self::get_checkout_setting_title("signature_title"),
                        "size"        => "30",
                    ]
                ],
                [
                    "name"  => "pickup_title",
                    "label" => __("Pickup", "woocommerce-myparcelbe"),
                    "type"  => "text_input",
                    "args"  => [
                        "has_title"   => true,
                        "title"       => "Pickup",
                        "current"     => self::get_checkout_setting_title("pickup_title"),
                        "size"        => "30",
                    ]
                ],
                [
                    "name"  => "checkout_display",
                    "label" => __("Display for", "woocommerce-myparcelbe"),
                    "type"  => "select",
                    "args"  => [
                        "options"     => [
                            "selected_methods" => __("Shipping methods associated with Parcels",
                                "woocommerce-myparcelbe"
                            ),
                            "all_methods"      => __("All shipping methods", "woocommerce-myparcelbe"),
                        ],
                        "description" => __("You can link the delivery options to specific shipping methods by adding them to the package types under \"Standard export settings\". The delivery options are not visible at foreign addresses.",
                            "woocommerce-myparcelbe"
                        ),
                    ]
                ],
                [
                    "name"  => "checkout_position",
                    "label" => __("Checkout position", "woocommerce-myparcelbe"),
                    "type"  => "select",
                    "args"  => [
                        "options"     => [
                            "woocommerce_after_checkout_billing_form"  => __("Show checkout options after billing details",
                                "woocommerce-myparcelbe"
                            ),
                            "woocommerce_after_checkout_shipping_form" => __("Show checkout options after shipping details",
                                "woocommerce-myparcelbe"
                            ),
                            "woocommerce_after_order_notes"            => __("Show checkout options after notes",
                                "woocommerce-myparcelbe"
                            ),
                        ],
                        "description" => __("You can change the place of the checkout options on the checkout page. By default it will be placed after shipping details.",
                            "woocommerce-myparcelbe"
                        ),
                    ]
                ]
            ];
        }

        private function get_general_section_customizations()
        {
            return [
                "name"  => "custom_css",
                "label" => __("Custom styles", "woocommerce-myparcelbe"),
                "type"  => "textarea",
                "args"  => [
                    "width"  => "80",
                    "height" => "8",

                ],
            ];
        }

        private function get_general_section_diagnostics()
        {
            return [
                "name"  => "error_logging",
                "label" => __("Log API communication", "woocommerce-myparcelbe"),
                "type"  => "checkbox",
                "args"  => [
                    "description" => '<a href="' . esc_url_raw(admin_url("admin.php?page=wc-status&tab=logs")
                        ) . '" target="_blank">' . __("View logs", "woocommerce-myparcelbe") . "</a> (wc-myparcelbe)",
                ],

            ];
        }
    }

endif;

new wcmp_settings_data();
