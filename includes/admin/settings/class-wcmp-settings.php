<?php

use MyParcelNL\Sdk\src\Model\Consignment\BpostConsignment;
use MyParcelNL\Sdk\src\Model\Consignment\DPDConsignment;

if (! defined('ABSPATH')) {
    exit;
} // Exit if accessed directly

if (class_exists('WCMP_Settings')) {
    return new WCMP_Settings();
}

/**
 * Create & render settings page
 */
class WCMP_Settings
{
    public const SETTINGS_MENU_SLUG = "wcmp_settings";

    public const SETTINGS_GENERAL         = "general";
    public const SETTINGS_EXPORT_DEFAULTS = "export_defaults";
    public const SETTINGS_BPOST           = BpostConsignment::CARRIER_NAME;
    public const SETTINGS_DPD             = DPDConsignment::CARRIER_NAME;

    /**
     * All settings except carrier specific ones.
     */

    public const SETTING_API_KEY                        = "api_key";
    public const SETTING_AUTOMATIC_ORDER_STATUS         = "automatic_order_status";
    public const SETTING_BARCODE_IN_NOTE                = "barcode_in_note";
    public const SETTING_BARCODE_IN_NOTE_TITLE          = "barcode_in_note_title";
    public const SETTING_CHECKOUT_POSITION              = "checkout_position";
    public const SETTING_CONNECT_EMAIL                  = "connect_email";
    public const SETTING_CONNECT_PHONE                  = "connect_phone";
    public const SETTING_DELIVERY_OPTIONS_CUSTOM_CSS    = "delivery_options_custom_css";
    public const SETTING_DELIVERY_OPTIONS_DISPLAY       = "delivery_options_display";
    public const SETTING_DELIVERY_OPTIONS_ENABLED       = "delivery_options_enabled";
    public const SETTING_DELIVERY_TITLE                 = "at_home_delivery";
    public const SETTING_DOWNLOAD_DISPLAY               = "download_display";
    public const SETTING_EMAIL_TRACK_TRACE              = "email_tracktrace";
    public const SETTING_ERROR_LOGGING                  = "error_logging";
    public const SETTING_HEADER_DELIVERY_OPTIONS_TITLE  = "header_delivery_options_title";
    public const SETTING_KEEP_SHIPMENTS                 = "keep_shipments";
    public const SETTING_LABEL_DESCRIPTION              = "label_description";
    public const SETTING_LABEL_FORMAT                   = "label_format";
    public const SETTING_MY_ACCOUNT_TRACK_TRACE         = "myaccount_tracktrace";
    public const SETTING_ORDER_STATUS_AUTOMATION        = "order_status_automation";
    public const SETTING_PICKUP_TITLE                   = "pickup_title";
    public const SETTING_PRINT_POSITION_OFFSET          = "print_position_offset";
    public const SETTING_PROCESS_DIRECTLY               = "process_directly";
    public const SETTING_SHIPPING_METHODS_PACKAGE_TYPES = "shipping_methods_package_types";
    public const SETTING_SIGNATURE_TITLE                = "signature_title";
    public const SETTING_STANDARD_TITLE                 = "standard_title";
    public const SETTING_USE_SPLIT_ADDRESS_FIELDS       = "use_split_address_fields";

    /*
     * Carrier settings will be prefixed with carrier names.
     */

    public const SETTING_CARRIER_CUTOFF_TIME          = "cutoff_time";
    public const SETTING_CARRIER_DELIVERY_DAYS_WINDOW = "delivery_days_window";
    public const SETTING_CARRIER_DELIVERY_ENABLED     = "delivery_enabled";
    public const SETTING_CARRIER_DROP_OFF_DAYS        = "drop_off_days";
    public const SETTING_CARRIER_DROP_OFF_DELAY       = "drop_off_delay";
    public const SETTING_CARRIER_INSURED              = "insured";
    public const SETTING_CARRIER_PICKUP_ENABLED       = "pickup_enabled";
    public const SETTING_CARRIER_PICKUP_FEE           = "pickup_fee";
    public const SETTING_CARRIER_PICKUP_TITLE         = "pickup_title";
    public const SETTING_CARRIER_SIGNATURE_ENABLED    = "signature_enabled";
    public const SETTING_CARRIER_SIGNATURE_FEE        = "signature_fee";

    // Currently not implemented
    public const SETTING_CARRIER_SATURDAY_DELIVERY_ENABLED = "saturday_delivery_enabled";
    public const SETTING_CARRIER_SATURDAY_DELIVERY_FEE     = "saturday_delivery_fee";

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
         * Add the new screen to the woocommerce screen ids to make wc tooltips work.
         */
        add_filter(
            'woocommerce_screen_ids',
            function ($ids) {
                $ids[] = "woocommerce_page_" . self::SETTINGS_MENU_SLUG;
                return $ids;
            }
        );

        // Create the admin settings
        require_once('class-wcmp-settings-data.php');

        // notice for WC MyParcel Belgium plugin
        add_action('woocommerce_myparcelbe_before_settings_page', [$this, 'myparcelbe_country_notice'], 10, 1);
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
            'manage_options',
            self::SETTINGS_MENU_SLUG,
            [$this, 'settings_page']
        );
    }

    /**
     * Add settings link to plugins page
     */
    public function add_settings_link($links)
    {
        $settings_link =
            '<a href="../../../../../../wp-admin/admin.php?page=' . self::SETTINGS_MENU_SLUG . '">' . _wcmp(
                'Settings'
            ) . '</a>';
        array_push($links, $settings_link);

        return $links;
    }

    /**
     * Output the settings pages.
     */
    public function settings_page()
    {
        $settings_tabs = apply_filters(
            self::SETTINGS_MENU_SLUG . '_tabs',
            [
                self::SETTINGS_GENERAL         => _wcmp('General'),
                self::SETTINGS_EXPORT_DEFAULTS => _wcmp('Default export settings'),
                self::SETTINGS_BPOST           => _wcmp('bpost'),
                self::SETTINGS_DPD             => _wcmp('DPD'),
            ]
        );

        $active_tab = isset($_GET['tab']) ? $_GET['tab'] : self::SETTINGS_GENERAL;
        ?>
        <div class="wrap woocommerce">
            <h1><?php _wcmpe('WooCommerce MyParcel BE Settings'); ?></h1>
            <h2 class="nav-tab-wrapper">
                <?php
                foreach ($settings_tabs as $tab_slug => $tab_title) :
                    printf(
                        '<a href="?page='
                        . self::SETTINGS_MENU_SLUG
                        . '&tab=%1$s" class="nav-tab nav-tab-%1$s %2$s">%3$s</a>',
                        $tab_slug,
                        (($active_tab === $tab_slug) ? 'nav-tab-active' : ''),
                        $tab_title
                    );
                endforeach;
                ?>
            </h2>
            <?php do_action('woocommerce_myparcelbe_before_settings_page', $active_tab); ?>
            <form method="post" action="options.php" id="<?php echo self::SETTINGS_MENU_SLUG; ?>">
                <?php
                do_action('woocommerce_myparcelbe_before_settings', $active_tab);
                settings_fields(self::getOptionId($active_tab));
                $this->render_settings_sections(self::getOptionId($active_tab));
                do_action('woocommerce_myparcelbe_after_settings', $active_tab);

                submit_button();
                ?>
            </form>
            <?php do_action('woocommerce_myparcelbe_after_settings_page', $active_tab); ?>
        </div>
        <?php
    }

    /**
     * Show the user a notice if they might be using the wrong plugin.
     */
    public function myparcelbe_country_notice()
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
                _wcmp(
                    'It looks like your shop is based in Netherlands. This plugin is for MyParcel Belgium. If you are using MyParcel Netherlands, download the %s plugin instead!'
                ),
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

    /**
     * @param string $option
     *
     * @return string
     */
    public static function getOptionId(string $option)
    {
        return 'woocommerce_myparcelbe_' . $option . '_settings';
    }

    /**
     * Render the settings sections. Mostly taken from the WordPress equivalent but done like this so parts can
     * be overridden/changed easily.
     *
     * @param string $page - Page ID
     *
     * @see \do_settings_sections
     */
    private function render_settings_sections(string $page)
    {
        global $wp_settings_sections, $wp_settings_fields;

        if (! isset($wp_settings_sections[$page])) {
            return;
        }

        foreach ((array) $wp_settings_sections[$page] as $section) {
            if ($section['title']) {
                echo "<h2>{$section['title']}</h2>\n";
            }

            if ($section['callback']) {
                call_user_func($section['callback'], $section);
            }

            if (! isset($wp_settings_fields)
                || ! isset($wp_settings_fields[$page])
                || ! isset($wp_settings_fields[$page][$section['id']])) {
                continue;
            }
            echo '<table class="form-table" role="presentation">';
            $this->render_settings_fields($page, $section['id']);
            echo '</table>';
        }
    }

    /**
     * Mostly copied from the WordPress function.
     *
     * @param $page
     * @param $section
     *
     * @see \do_settings_fields
     */
    private function render_settings_fields($page, $section): void
    {
        global $wp_settings_fields;

        if (! isset($wp_settings_fields[$page][$section])) {
            return;
        }

        foreach ((array) $wp_settings_fields[$page][$section] as $field) {
            $help_tip = '';
            $class    = '';

            if (! empty($field['args']['class'])) {
                $class = $field['args']['class'];
                $class = is_array($class) ? implode(" ", $class) : $class;
                $class = ' class="' . esc_attr($class) . '"';
            }

            echo "<tr{$class}>";

            if (! empty($field['args']['help_text'])) {
                $help_tip = wc_help_tip($field['args']['help_text']);
            }

            if (! empty($field['args']['label_for'])) {
                echo '<th scope="row""><label class="wcmp-white-space--nowrap" for="' . esc_attr(
                        $field['args']['label_for']
                    ) . '">' . $field['title'] . $help_tip . '</label></th>';
            } else {
                echo '<th scope="row"><span class="wcmp-white-space--nowrap">'
                     . $field['title']
                     . $help_tip
                     . '</span></th>';
            }

            // Pass the option id as argument
            $field["args"]["option_id"] = $page;

            echo '<td>';
            call_user_func($field['callback'], $field['args']);
            echo '</td>';
            echo '</tr>';
        }
    }
}

return new WCMP_Settings();
