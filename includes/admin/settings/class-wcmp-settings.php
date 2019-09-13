<?php

use MyParcelNL\Sdk\src\Model\Consignment\BpostConsignment;
use MyParcelNL\Sdk\src\Model\Consignment\DPDConsignment;

if (! defined('ABSPATH')) {
    exit;
} // Exit if accessed directly

if (! class_exists('WCMP_Settings')) :

    /**
     * Create & render settings page
     */
    class WCMP_Settings
    {
        const SETTINGS_GENERAL         = 'general';
        const SETTINGS_EXPORT_DEFAULTS = 'export_defaults';
        const SETTINGS_BPOST           = BpostConsignment::CARRIER_NAME;
        const SETTINGS_DPD             = DPDConsignment::CARRIER_NAME;

        public function __construct()
        {
            add_action('admin_menu', [$this, 'menu']);
            add_filter(
                'plugin_action_links_' . WCMP()->plugin_basename,
                [
                    $this,
                    'add_settings_link',
                ]
            );

            /**
             * Add the new screen to the woocommerce screen ids to make tooltips work.
             */
            add_filter(
                'woocommerce_screen_ids',
                function ($ids) {
                    $ids[] = "woocommerce_page_wcmp_settings";
                    return $ids;
                }
            );

            // Create the admin settings
            require_once('class-wcmp-settings-data.php');

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
                _wcmp('MyParcel BE'),
                _wcmp('MyParcel BE'),
                'manage_woocommerce',
                'wcmp_settings',
                [$this, 'settings_page']
            );
        }

        /**
         * Add settings link to plugins page
         */
        public function add_settings_link($links)
        {
            $settings_link = '<a href="../../../../../../wp-admin/admin.php?page=wcmp_settings">' .  _wcmp('Settings') . '</a>';
            array_push($links, $settings_link);

            return $links;
        }

        public function settings_page()
        {
            $settings_tabs = apply_filters(
                'wcmp_settings_tabs',
                [
                    self::SETTINGS_GENERAL         => _wcmp('General'),
                    self::SETTINGS_EXPORT_DEFAULTS => _wcmp('Default export settings'),
                    self::SETTINGS_BPOST           => _wcmp('bpost'),
                    self::SETTINGS_DPD             => _wcmp('DPD'),
                ]
            );

            $active_tab = isset($_GET['tab']) ? $_GET['tab'] : self::SETTINGS_GENERAL;
            ?>
            <div class="wrap">
                <h1><?php _wcmpe('WooCommerce MyParcel BE Settings'); ?></h1>
                <h2 class="nav-tab-wrapper">
                    <?php
                    foreach ($settings_tabs as $tab_slug => $tab_title) :
                        printf(
                            '<a href="?page=wcmp_settings&tab=%1$s" class="nav-tab nav-tab-%1$s %2$s">%3$s</a>',
                            $tab_slug,
                            (($active_tab === $tab_slug) ? 'nav-tab-active' : ''),
                            $tab_title
                        );
                    endforeach;
                    ?>
                </h2>
                <?php do_action('woocommerce_myparcelbe_before_settings_page', $active_tab); ?>
                <form method="post" action="options.php" id="wcmp_settings" class="wcmp_shipment_options">
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
                $myparcel_nl_link =
                    '<a href="https://wordpress.org/plugins/woocommerce-myparcel/" target="blank">WC MyParcel Netherlands</a>';
                $text             = sprintf(
                     _wcmp('It looks like your shop is based in Netherlands. This plugin is for MyParcel Belgium. If you are using MyParcel Netherlands, download the %s plugin instead!'),
                    $myparcel_nl_link
                );
                $dismiss_button   = sprintf(
                    '<a href="%s" style="display:inline-block; margin-top: 10px;">%s</a>',
                    add_query_arg('myparcelbe_hide_be_notice', 'true'),
                    _wcmp('Hide this message')
                );
                printf('<div class="notice notice-warning"><p>%s %s</p></div>', $text, $dismiss_button);
            }
        }
    }

endif; // class_exists

return new WCMP_Settings();
