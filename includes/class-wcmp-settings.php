<?php

if (! defined('ABSPATH')) {
    exit;
} // Exit if accessed directly

if (! class_exists('wcmp_settings')) :

    /**
     * Create & render settings page
     */
    class wcmp_settings
    {
        public function __construct()
        {
            add_action('admin_menu', [$this, 'menu']);
            add_filter(
                'plugin_action_links_' . WooCommerce_MyParcelBE()->plugin_basename,
                [
                    $this,
                    'add_settings_link'
                ]
            );

            // Create the admin settings
            require_once 'class-wcmp-settings-data.php';

            // notice for WC MyParcel Belgium plugin
            add_action('woocommerce_myparcelbe_before_settings_page', [$this, 'myparcelbe_be_notice'], 10, 1);
        }

        /**
         * Add settings item to WooCommerce menu
         */
        public function menu()
        {
            add_submenu_page(
                'woocommerce',
                __('MyParcel BE', 'woocommerce-myparcelbe'),
                __('MyParcel BE', 'woocommerce-myparcelbe'),
                'manage_options',
                'wcmp_settings',
                array($this, 'settings_page')
            );
        }

        /**
         * Add settings link to plugins page
         */
        public function add_settings_link($links)
        {
            $settings_link = '<a href="admin.php?page=wcmp_settings">' . __('Settings',
                    'woocommerce-myparcelbe'
                ) . '</a>';
            array_push($links, $settings_link);

            return $links;
        }

        public function settings_page()
        {
            $settings_tabs = apply_filters(
                'wcmp_settings_tabs',
                array(
                    'general'         => __('General', 'woocommerce-myparcelbe'),
                    'export_defaults' => __('Default export settings', 'woocommerce-myparcelbe'),
                    'bpost'           => __('bpost', 'woocommerce-myparcelbe'),
                    'dpd'             => __('DPD', 'woocommerce-myparcelbe'),
                )
            );

            $active_tab = isset($_GET['tab']) ? $_GET['tab'] : 'general';
            ?>
            <div class="wrap">
                <h1><?php _e('WooCommerce MyParcel BE Settings', 'woocommerce-myparcelbe'); ?></h1>
                <h2 class="nav-tab-wrapper">
                    <?php
                    foreach ($settings_tabs as $tab_slug => $tab_title) {
                        printf('<a href="?page=wcmp_settings&tab=%1$s" class="nav-tab nav-tab-%1$s %2$s">%3$s</a>',
                            $tab_slug,
                            (($active_tab == $tab_slug) ? 'nav-tab-active' : ''),
                            $tab_title
                        );
                    }
                    ?>
                </h2>

                <?php do_action('woocommerce_myparcelbe_before_settings_page', $active_tab); ?>

                <form method="post" action="options.php" id="wcmp_settings"
                      class="wcmp_shipment_options">
                    <?php
                    do_action('woocommerce_myparcelbe_before_settings', $active_tab);
                    settings_fields('woocommerce_myparcelbe_' . $active_tab . '_settings');
                    do_settings_sections('woocommerce_myparcelbe_' . $active_tab . '_settings');
                    do_action('woocommerce_myparcelbe_after_settings', $active_tab);

                    submit_button();
                    ?>
                </form>

                <?php do_action('woocommerce_myparcelbe_after_settings_page', $active_tab); ?>

            </div>
            <?php
        }

        public function myparcelbe_be_notice()
        {
            $base_country = WC()->countries->get_base_country();

            // save or check option to hide notice
            if (isset($_GET['myparcelbe_hide_be_notice'])) {
                update_option('myparcelbe_hide_be_notice', true);
                $hide_notice = true;
            } else {
                $hide_notice = get_option('myparcelbe_hide_be_notice');
            }

            // link to hide message when one of the premium extensions is installed
            if (! $hide_notice && $base_country == 'BE') {
                $myparcel_nl_link = '<a href="https://wordpress.org/plugins/woocommerce-myparcel/" target="blank">WC MyParcel Netherlands</a>';
                $text             = sprintf(
                    __('It looks like your shop is based in Netherlands. This plugin is for MyParcel Belgium. If you are using MyParcel Netherlands, download the %s plugin instead!',
                        'woocommerce-myparcelbe'
                    ),
                    $myparcel_nl_link
                );
                $dismiss_button   = sprintf(
                    '<a href="%s" style="display:inline-block; margin-top: 10px;">%s</a>',
                    add_query_arg('myparcelbe_hide_be_notice', 'true'),
                    __('Hide this message', 'woocommerce-myparcelbe')
                );
                printf('<div class="notice notice-warning"><p>%s %s</p></div>', $text, $dismiss_button);
            }
        }

        //        /**
//         * Register Bpost settings
//         */
//        public function bpost_settings()
//        {
//            $option_group = 'woocommerce_myparcelbe_bpost_settings';
//
//            // Register settings.
//            $option_name = 'woocommerce_myparcelbe_bpost_settings';
//            register_setting($option_group, $option_name, array($this->callbacks, 'validate'));
//
//            // Create option in wp_options.
//            if (false === get_option($option_name)) {
//                $this->default_settings($option_name);
//            }
//
//            // bpost Checkout options section.
//            add_settings_section(
//                'bpost_settings',
//                __('bpost settings', 'woocommerce-myparcelbe'),
//                array(
//                    $this->callbacks,
//                    'section'
//                ),
//                $option_group
//            );
//
//            add_settings_field(
//                'bpost_delivery_enabled',
//                __('Enable bpost delivery', 'woocommerce-myparcelbe'),
//                array($this->callbacks, 'checkbox'),
//                $option_group,
//                'bpost_settings',
//                array(
//                    'option_name' => $option_name,
//                    'id'          => 'bpost_delivery_enabled'
//                )
//            );
//
//            $bpost_days_of_the_week = array(
//                '0' => __('Sunday', 'woocommerce-myparcelbe'),
//                '1' => __('Monday', 'woocommerce-myparcelbe'),
//                '2' => __('Tuesday', 'woocommerce-myparcelbe'),
//                '3' => __('Wednesday', 'woocommerce-myparcelbe'),
//                '4' => __('Thursday', 'woocommerce-myparcelbe'),
//                '5' => __('Friday', 'woocommerce-myparcelbe'),
//                '6' => __('Saturday', 'woocommerce-myparcelbe'),
//            );
//
//            add_settings_field(
//                'bpost_dropoff_days',
//                __('Drop-off days', 'woocommerce-myparcelbe'),
//                array(
//                    $this->callbacks,
//                    'enhanced_select'
//                ),
//                $option_group,
//                'bpost_settings',
//                array(
//                    'option_name' => $option_name,
//                    'id'          => 'bpost_dropoff_days',
//                    'options'     => $bpost_days_of_the_week,
//                    'description' => __('Days of the week on which you hand over parcels to bpost',
//                        'woocommerce-myparcelbe'
//                    ),
//                )
//            );
//
//            add_settings_field(
//                'bpost_cutoff_time',
//                __('Cut-off time', 'woocommerce-myparcelbe'),
//                array(
//                    $this->callbacks,
//                    'text_input'
//                ),
//                $option_group,
//                'bpost_settings',
//                array(
//                    'option_name' => $option_name,
//                    'id'          => 'bpost_cutoff_time',
//                    'type'        => 'text',
//                    'size'        => '5',
//                    'description' => __('Time at which you stop processing orders for the day (format: hh:mm)',
//                        'woocommerce-myparcelbe'
//                    ),
//                )
//            );
//
//            add_settings_field(
//                'bpost_dropoff_delay',
//                __('Drop-off delay', 'woocommerce-myparcelbe'),
//                array(
//                    $this->callbacks,
//                    'text_input'
//                ),
//                $option_group,
//                'bpost_settings',
//                array(
//                    'option_name' => $option_name,
//                    'id'          => 'bpost_dropoff_delay',
//                    'type'        => 'text',
//                    'size'        => '5',
//                    'description' => __('Number of days you need to process an order.', 'woocommerce-myparcelbe'),
//                )
//            );
//
//            add_settings_field(
//                'bpost_deliverydays_window',
//                __('Delivery days window', 'woocommerce-myparcelbe'),
//                array(
//                    $this->callbacks,
//                    'checkbox'
//                ),
//                $option_group,
//                'bpost_settings',
//                array(
//                    'option_name' => $option_name,
//                    'id'          => 'bpost_deliverydays_window',
//                    'description' => __('Show the delivery date inside the checkout.', 'woocommerce-myparcelbe'),
//                )
//            );
//
//            // bpost Delivery options section.
//            add_settings_section(
//                'bpost_delivery_options',
//                __('bpost delivery options', 'woocommerce-myparcelbe'),
//                array(
//                    $this->callbacks,
//                    'section'
//                ),
//                $option_group
//            );
//
//            add_settings_field(
//                'bpost_signature',
//                __('Signature on delivery', 'woocommerce-myparcelbe'),
//                array(
//                    $this->callbacks,
//                    'delivery_option_enable'
//                ),
//                $option_group,
//                'bpost_delivery_options',
//                array(
//                    'has_title'          => false,
//                    'has_price'          => true,
//                    'option_name'        => $option_name,
//                    'id'                 => 'bpost_signature',
//                    'size'               => 3,
//                    'option_description' => sprintf(__('Enter an amount that is either positive or negative. For example, do you want to give a discount for using this function or do you want to charge extra for this delivery option.',
//                            'woocommerce-myparcelbe'
//                        )
//                    ),
//                )
//            );
//
//            add_settings_field(
//                'bpost_pickup',
//                __('bpost pickup', 'woocommerce-myparcelbe'),
//                array(
//                    $this->callbacks,
//                    'delivery_option_enable'
//                ),
//                $option_group,
//                'bpost_delivery_options',
//                array(
//                    'has_title'          => false,
//                    'has_price'          => true,
//                    'option_name'        => $option_name,
//                    'id'                 => 'bpost_pickup',
//                    'size'               => 3,
//                    'option_description' => sprintf(__('Enter an amount that is either positive or negative. For example, do you want to give a discount for using this function or do you want to charge extra for this delivery option.',
//                            'woocommerce-myparcelbe'
//                        )
//                    ),
//                )
//            );
//
//            // bpost standard label options
//            add_settings_section(
//                'bpost_standard_options',
//                __('bpost standard label options', 'woocommerce-myparcelbe'),
//                array(
//                    $this->callbacks,
//                    'section'
//                ),
//                $option_group
//            );
//
//            add_settings_field(
//                'signature',
//                __('Signature on delivery', 'woocommerce-myparcelbe'),
//                array($this->callbacks, 'checkbox'),
//                $option_group,
//                'bpost_standard_options',
//                array(
//                    'option_name' => $option_name,
//                    'id'          => 'signature',
//                    'description' => __('When the package is presented at the home address, a signuture will be required.',
//                        'woocommerce-myparcelbe'
//                    )
//                )
//            );
//
//            add_settings_field(
//                'insured',
//                __('Insured shipment (to €500)', 'woocommerce-myparcelbe'),
//                array($this->callbacks, 'checkbox'),
//                $option_group,
//                'bpost_standard_options',
//                array(
//                    'option_name' => $option_name,
//                    'id'          => 'insured',
//                    'description' => __('There is no default insurance on the domestic shipments. If you want to insure, you can do this. We insure the purchase value of your product, with a maximum insured value of € 500.',
//                        'woocommerce-myparcelbe'
//                    ),
//                    'class'       => 'insured',
//                )
//            );
//        }

//        /**
//         * Register DPD settings
//         */
//        public function dpd_settings()
//        {
//            $option_group = 'woocommerce_myparcelbe_dpd_settings';
//            $option_name  = 'woocommerce_myparcelbe_dpd_settings';
//
//            register_setting($option_group, $option_name, array($this->callbacks, 'validate'));
//
//            // Create option in wp_options with default settings.
//            if (false === get_option($option_name)) {
//                $this->default_settings($option_name);
//            }
//
//            // dpd Checkout options section.
//            add_settings_section(
//                'dpd_settings',
//                __('dpd settings', 'woocommerce-myparcelbe'),
//                array(
//                    $this->callbacks,
//                    'section'
//                ),
//                $option_group
//            );
//
//            add_settings_field(
//                'dpd_delivery_enabled',
//                __('Enable DPD delivery', 'woocommerce-myparcelbe'),
//                array($this->callbacks, 'checkbox'),
//                $option_group,
//                'dpd_settings',
//                array(
//                    'option_name' => $option_name,
//                    'id'          => 'dpd_delivery_enabled'
//                )
//            );
//
//
//            $dpd_days_of_the_week = array(
//                '0' => __('Sunday', 'woocommerce-myparcelbe'),
//                '1' => __('Monday', 'woocommerce-myparcelbe'),
//                '2' => __('Tuesday', 'woocommerce-myparcelbe'),
//                '3' => __('Wednesday', 'woocommerce-myparcelbe'),
//                '4' => __('Thursday', 'woocommerce-myparcelbe'),
//                '5' => __('Friday', 'woocommerce-myparcelbe'),
//                '6' => __('Saturday', 'woocommerce-myparcelbe'),
//            );
//
//            add_settings_field(
//                'dpd_dropoff_days',
//                __('Drop-off days', 'woocommerce-myparcelbe'),
//                array(
//                    $this->callbacks,
//                    'enhanced_select'
//                ),
//                $option_group,
//                'dpd_settings',
//                array(
//                    'option_name' => $option_name,
//                    'id'          => 'dpd_dropoff_days',
//                    'options'     => $dpd_days_of_the_week,
//                    'description' => __('Days of the week on which you hand over parcels to dpd',
//                        'woocommerce-myparcelbe'
//                    ),
//                )
//            );
//
//            add_settings_field(
//                'dpd_cutoff_time',
//                __('Cut-off time', 'woocommerce-myparcelbe'),
//                array(
//                    $this->callbacks,
//                    'text_input'
//                ),
//                $option_group,
//                'dpd_settings',
//                array(
//                    'option_name' => $option_name,
//                    'id'          => 'dpd_cutoff_time',
//                    'type'        => 'text',
//                    'size'        => '5',
//                    'description' => __('Time at which you stop processing orders for the day (format: hh:mm)',
//                        'woocommerce-myparcelbe'
//                    ),
//                )
//            );
//
//            add_settings_field(
//                'dpd_dropoff_delay',
//                __('Drop-off delay', 'woocommerce-myparcelbe'),
//                array(
//                    $this->callbacks,
//                    'text_input'
//                ),
//                $option_group,
//                'dpd_settings',
//                array(
//                    'option_name' => $option_name,
//                    'id'          => 'dpd_dropoff_delay',
//                    'type'        => 'text',
//                    'size'        => '5',
//                    'description' => __('Number of days you need to process an order.', 'woocommerce-myparcelbe'),
//                )
//            );
//
//
//            add_settings_field(
//                'dpd_deliverydays_window',
//                __('Delivery days window', 'woocommerce-myparcelbe'),
//                array(
//                    $this->callbacks,
//                    'checkbox'
//                ),
//                $option_group,
//                'dpd_settings',
//                array(
//                    'option_name' => $option_name,
//                    'id'          => 'dpd_deliverydays_window',
//                    'description' => __('Show the delivery date inside the checkout.', 'woocommerce-myparcelbe'),
//                )
//            );
//
//            // XXX
//
//            // dpd Delivery options section.
//            add_settings_section(
//                'dpd_delivery_options',
//                __('dpd delivery options', 'woocommerce-myparcelbe'),
//                array(
//                    $this->callbacks,
//                    'section'
//                ),
//                $option_group
//            );
//
//            add_settings_field(
//                'dpd_pickup',
//                __('dpd pickup', 'woocommerce-myparcelbe'),
//                array(
//                    $this->callbacks,
//                    'delivery_option_enable'
//                ),
//                $option_group,
//                'dpd_delivery_options',
//                array(
//                    'option_name'        => $option_name,
//                    'id'                 => 'dpd_pickup',
//                    'has_title'          => false,
//                    'has_price'          => true,
//                    'size'               => 3,
//                    'option_description' => sprintf(
//                        __('Enter an amount that is either positive or negative. For example, do you want to give a discount for using this function or do you want to charge extra for this delivery option.',
//                            'woocommerce-myparcelbe'
//                        )
//                    ),
//                )
//            );
//        }

    }

endif; // class_exists

return new wcmp_settings();
