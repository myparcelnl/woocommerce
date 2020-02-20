<?php
/*
Plugin Name: WooCommerce PostNL
Plugin URI: http://www.postnl.nl
Description: Export your WooCommerce orders to PostNL (www.postnl.nl) and print labels directly from the WooCommerce admin
Author: PostNL
Version: 3.1.7

Text Domain: woocommerce-postnl

License: GPLv3 or later
License URI: http://www.opensource.org/licenses/gpl-license.php
*/

if ( ! defined('ABSPATH')) exit; // Exit if accessed directly

if ( ! class_exists('WooCommerce_PostNL')) :

class WooCommerce_PostNL {

    public $version = '3.1.7';
    public $plugin_basename;
    protected static $_instance = null;

    /**
     * Main Plugin Instance
     * Ensures only one instance of plugin is loaded or can be loaded.
     */
    public static function instance() {
        if (is_null(self::$_instance)) {
            self::$_instance = new self();
        }

        return self::$_instance;
    }

    /**
     * Constructor
     */

    public function __construct() {
        $this->define('WC_POSTNL_VERSION', $this->version);
        $this->define('WC_CHANNEL_ENGINE_ACTIVE', class_exists('Channel_Engine'));
        $this->plugin_basename = plugin_basename(__FILE__);

        // Load settings
        $this->general_settings = get_option('woocommerce_postnl_general_settings');
        $this->export_defaults = get_option('woocommerce_postnl_export_defaults_settings');
        $this->checkout_settings = get_option('woocommerce_postnl_checkout_settings');

        // load the localisation & classes
        add_action('plugins_loaded', array($this, 'translations'));
        add_action('init', array($this, 'load_classes'));

        // run lifecycle methods
        if (is_admin() && ! defined('DOING_AJAX')) {
            add_action('wp_loaded', array($this, 'do_install'));
        }
    }

    /**
     * Define constant if not already set
     *
     * @param  string      $name
     * @param  string|bool $value
     */
    private function define($name, $value) {
        if ( ! defined($name)) {
            define($name, $value);
        }
    }

    /**
     * Load the translation / text-domain files
     * Note: the first-loaded translation file overrides any following ones if the same translation is present
     */
    public function translations() {
        $locale = apply_filters('plugin_locale', get_locale(), 'woocommerce-postnl');
        $dir = trailingslashit(WP_LANG_DIR);

        /**
         * Frontend/global Locale. Looks in:
         *        - WP_LANG_DIR/woocommerce-postnl/woocommerce-postnl-LOCALE.mo
         *        - WP_LANG_DIR/plugins/woocommerce-postnl-LOCALE.mo
         *        - woocommerce-postnl/languages/woocommerce-postnl-LOCALE.mo (which if not found falls back to:)
         *        - WP_LANG_DIR/plugins/woocommerce-postnl-LOCALE.mo
         */
        load_textdomain('woocommerce-postnl', $dir . 'woocommerce-postnl/woocommerce-postnl-' . $locale . '.mo');
        load_textdomain('woocommerce-postnl', $dir . 'plugins/woocommerce-postnl-' . $locale . '.mo');
        load_plugin_textdomain('woocommerce-postnl', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }

    /**
     * Load the main plugin classes and functions
     */
    public function includes() {
        // include compatibility classes
        include_once('includes/compatibility/abstract-wc-data-compatibility.php');
        include_once('includes/compatibility/class-wc-date-compatibility.php');
        include_once('includes/compatibility/class-wc-core-compatibility.php');
        include_once('includes/compatibility/class-wc-order-compatibility.php');
        include_once('includes/compatibility/class-wc-product-compatibility.php');

        include_once('includes/class-wcmp-assets.php');
        $this->admin = include_once('includes/class-wcmp-admin.php');
        include_once('includes/class-wcmp-frontend-settings.php');
        include_once('includes/class-wcmp-frontend.php');
        include_once('includes/class-wcmp-settings.php');
        $this->export = include_once('includes/class-wcmp-export.php');
        include_once('includes/class-wcmp-nl-postcode-fields.php');
    }

    /**
     * Instantiate classes when woocommerce is activated
     */
    public function load_classes() {
        if ($this->is_woocommerce_activated() === false) {
            add_action('admin_notices', array($this, 'need_woocommerce'));

            return;
        }

        if (version_compare(PHP_VERSION, '5.4', '<')) {
            add_action('admin_notices', array($this, 'required_php_version'));

            return;
        }

        // all systems ready - GO!
        $this->includes();
    }

    /**
     * Check if woocommerce is activated
     */
    public function is_woocommerce_activated() {
        $blog_plugins = get_option('active_plugins', array());
        $site_plugins = get_site_option('active_sitewide_plugins', array());

        if (in_array('woocommerce/woocommerce.php', $blog_plugins) || isset($site_plugins['woocommerce/woocommerce.php'])) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * WooCommerce not active notice.
     * @return string Fallack notice.
     */
    public function need_woocommerce() {
        $error = sprintf(__('WooCommerce PostNL requires %sWooCommerce%s to be installed & activated!', 'woocommerce-postnl'), '<a href="http://wordpress.org/extend/plugins/woocommerce/">', '</a>');

        $message = '<div class="error"><p>' . $error . '</p></div>';

        echo $message;
    }

    /**
     * PHP version requirement notice
     */

    public function required_php_version() {
        $error = __('WooCommerce PostNL requires PHP 5.4 or higher (5.6 or later recommended).', 'woocommerce-postnl');
        $how_to_update = __('How to update your PHP version', 'woocommerce-postnl');
        $message = sprintf('<div class="error"><p>%s</p><p><a href="%s">%s</a></p></div>', $error, 'http://docs.wpovernight.com/general/how-to-update-your-php-version/', $how_to_update);

        echo $message;
    }

    /** Lifecycle methods *******************************************************
     * Because register_activation_hook only runs when the plugin is manually
     * activated by the user, we're checking the current version against the
     * version stored in the database
     ****************************************************************************/

    /**
     * Handles version checking
     */
    public function do_install() {
        $version_setting = 'woocommerce_postnl_version';
        $installed_version = get_option($version_setting);

        // installed version lower than plugin version?
        if (version_compare($installed_version, $this->version, '<')) {
            if ( ! $installed_version) {
                $this->install();
            } else {
                $this->upgrade($installed_version);
            }

            // new version number
            update_option($version_setting, $this->version);
        }
    }

    /**
     * Plugin install method. Perform any installation tasks here
     */
    protected function install() {
        // copy old settings if available (pre 2.0 didn't store the version, so technically, this is a new install)
        $old_settings = get_option('wcpostnl_settings');
        if ( ! empty($old_settings)) {
            // map old key => new_key
            $general_settings_keys = array(
                'api_key'              => 'api_key',
                'download_display'     => 'download_display',
                'email_tracktrace'     => 'email_tracktrace',
                'myaccount_tracktrace' => 'myaccount_tracktrace',
                'process'              => 'process_directly',
                'barcode_in_note'      => 'barcode_in_note',
                'keep_consignments'    => 'keep_shipments',
                'error_logging'        => 'error_logging',
            );

            $general_settings = array();
            foreach ($general_settings_keys as $old_key => $new_key) {
                if ( ! empty($old_settings[$old_key])) {
                    $general_settings[$new_key] = $old_settings[$old_key];
                }
            }
            // auto_complete breaks down into:
            // order_status_automation & automatic_order_status
            if ( ! empty($old_settings['auto_complete'])) {
                $general_settings['order_status_automation'] = 1;
                $general_settings['automatic_order_status'] = 'completed';
            }

            // map old key => new_key
            $defaults_settings_keys = array(
                'telefoon'           => 'connect_phone',
                'huisadres'          => 'only_recipient',
                'handtekening'       => 'signature',
                'retourbgg'          => 'return',
                'kenmerk'            => 'label_description',
                'verpakkingsgewicht' => 'empty_parcel_weight',
                'verzekerd'          => 'insured',
                'verzekerdbedrag'    => 'insured_amount',
            );
            $defaults_settings = array();
            foreach ($defaults_settings_keys as $old_key => $new_key) {
                if ( ! empty($old_settings[$old_key])) {
                    $defaults_settings[$new_key] = $old_settings[$old_key];
                }
            }
            // set custom insurance amount
            if ( ! empty($defaults_settings['insured']) && (int) $defaults_settings['insured_amount'] > 249) {
                $defaults_settings['insured_amount'] = 0;
                $defaults_settings['insured_amount_custom'] = $old_settings['verzekerdbedrag'];
            }

            // add options
            update_option('woocommerce_postnl_general_settings', $general_settings);
            update_option('woocommerce_postnl_export_defaults_settings', $defaults_settings);
        }
    }

    /**
     * Plugin upgrade method.  Perform any required upgrades here
     *
     * @param string $installed_version the currently installed ('old') version
     */
    protected function upgrade($installed_version) {
        if (version_compare($installed_version, '2.4.0-beta-4', '<')) {
            // remove log file (now uses WC logger)
            $upload_dir = wp_upload_dir();
            $upload_base = trailingslashit($upload_dir['basedir']);
            $log_file = $upload_base . 'postnl_log.txt';
            if (@file_exists($log_file)) {
                @unlink($log_file);
            }
        }

        if (version_compare($installed_version, '3.0.4', '<=')) {
            $old_settings = get_option('woocommerce_postnl_checkout_settings');
            $new_settings = $old_settings;

            // Add/replace new settings
            $new_settings['use_split_address_fields'] = '1';

            // Rename signed to signature and night to evening for consistency
            $new_settings['signature_enabled'] = $old_settings['signed_enabled'];
            $new_settings['signature_title'] = $old_settings['signed_title'];
            $new_settings['signature_fee'] = $old_settings['signed_fee'];

            $new_settings['evening_enabled'] = $old_settings['night_enabled'];
            $new_settings['evening_title'] = $old_settings['night_title'];
            $new_settings['evening_fee'] = $old_settings['night_fee'];

            // Remove old settings
            unset($new_settings['signed_enabled']);
            unset($new_settings['signed_title']);
            unset($new_settings['signed_fee']);

            unset($new_settings['night_enabled']);
            unset($new_settings['night_title']);
            unset($new_settings['night_fee']);

            update_option('woocommerce_postnl_checkout_settings', $new_settings);
        }
    }

    /**
     * Get the plugin url.
     * @return string
     */
    public function plugin_url() {
        return untrailingslashit(plugins_url('/', __FILE__));
    }

    /**
     * Get the plugin path.
     * @return string
     */
    public function plugin_path() {
        return untrailingslashit(plugin_dir_path(__FILE__));
    }
} // class WooCommerce_PostNL

endif; // class_exists

/**
 * Returns the main instance of the plugin class to prevent the need to use globals.
 * @since  2.0
 * @return WooCommerce_PostNL
 */
function WooCommerce_PostNL() {
    return WooCommerce_PostNL::instance();
}

WooCommerce_PostNL(); // load plugin
