<?php
/*
Plugin Name: WooCommerce MyParcel
Plugin URI: https://myparcel.nl/
Description: Export your WooCommerce orders to MyParcel (https://myparcel.nl/) and print labels directly from the WooCommerce admin
Author: MyParcel
Author URI: https://myparcel.nl
Version: 4.4.2
Text Domain: woocommerce-myparcel

License: GPLv3 or later
License URI: http://www.opensource.org/licenses/gpl-license.php
*/

if (! defined('ABSPATH')) {
    exit;
} // Exit if accessed directly

if (! class_exists('WCMYPA')) :

    class WCMYPA
    {
        /**
         * Translations domain
         */
        const DOMAIN               = 'woocommerce-myparcel';
        const NONCE_ACTION         = 'wc_myparcel';
        const PHP_VERSION_7_1      = '7.1';
        const PHP_VERSION_REQUIRED = self::PHP_VERSION_7_1;

        public $version = '4.4.2';

        public $plugin_basename;

        protected static $_instance = null;

        /**
         * @var WPO\WC\MyParcel\Collections\SettingsCollection
         */
        public $setting_collection;

        /**
         * @var string
         */
        public $includes;

        /**
         * @var WCMP_Export
         */
        public $export;

        /**
         * @var WCMYPA_Admin
         */
        public $admin;

        /**
         * Main Plugin Instance
         * Ensures only one instance of plugin is loaded or can be loaded.
         */
        public static function instance()
        {
            if (is_null(self::$_instance)) {
                self::$_instance = new self();
            }

            return self::$_instance;
        }

        /**
         * Constructor
         */
        public function __construct()
        {
            $this->define('WC_MYPARCEL_NL_VERSION', $this->version);
            $this->plugin_basename = plugin_basename(__FILE__);

            // load the localisation & classes
            add_action('plugins_loaded', [$this, 'translations']);
            add_action('init', [$this, 'load_classes'], 9999);

            // run lifecycle methods
            if (is_admin() && ! defined('DOING_AJAX')) {
                add_action('init', [$this, 'do_install']);
            }
        }

        /**
         * Define constant if not already set
         *
         * @param string      $name
         * @param string|bool $value
         */
        private function define($name, $value)
        {
            if (! defined($name)) {
                define($name, $value);
            }
        }

        /**
         * This method is used internally, to be able to use the staging environment of MyParcel
         */
        private function useStagingEnvironment(): void
        {
            if (get_option('use_myparcel_staging_environment')) {
                putenv('API_BASE_URL=' . get_option('myparcel_base_url'));
            }
        }

        /**
         * Load the translation / text-domain files
         * Note: the first-loaded translation file overrides any following ones if the same translation is present
         */
        public function translations()
        {
            $locale = apply_filters('plugin_locale', get_locale(), self::DOMAIN);
            $dir    = trailingslashit(WP_LANG_DIR);

            /**
             * Frontend/global Locale. Looks in:
             *        - WP_LANG_DIR/woocommerce-myparcel/woocommerce-myparcel-LOCALE.mo
             *        - WP_LANG_DIR/plugins/woocommerce-myparcel-LOCALE.mo
             *        - woocommerce-myparcel/languages/woocommerce-myparcel-LOCALE.mo (which if not found falls back to:)
             *        - WP_LANG_DIR/plugins/woocommerce-myparcel-LOCALE.mo
             */
            load_textdomain(
                self::DOMAIN,
                $dir . 'woocommerce-myparcel/' . self::DOMAIN . '-' . $locale . '.mo'
            );
            load_textdomain(self::DOMAIN, $dir . 'plugins/' . self::DOMAIN . '-' . $locale . '.mo');
            load_plugin_textdomain(self::DOMAIN, false, dirname(plugin_basename(__FILE__)) . '/languages');
        }

        /**
         * Load the main plugin classes and functions
         */
        public function includes()
        {
            $this->includes = $this->plugin_path() . '/includes';
            // Use minimum php version 7.1
            require_once($this->plugin_path() . "/vendor/autoload.php");

            require_once($this->includes . "/admin/OrderSettings.php");
            require_once($this->includes . "/admin/OrderSettingsRows.php");

            // include compatibility classes
            require_once($this->includes . "/compatibility/abstract-wc-data-compatibility.php");
            require_once($this->includes . "/compatibility/class-wc-date-compatibility.php");
            require_once($this->includes . "/compatibility/class-wc-core-compatibility.php");
            require_once($this->includes . "/compatibility/class-wc-order-compatibility.php");
            require_once($this->includes . "/compatibility/class-wc-product-compatibility.php");
            require_once($this->includes . "/compatibility/class-ce-compatibility.php");
            require_once($this->includes . "/compatibility/class-wcpdf-compatibility.php");

            require_once($this->includes . "/class-wcmp-data.php");
            require_once($this->includes . "/collections/settings-collection.php");
            require_once($this->includes . "/entities/setting.php");
            require_once($this->includes . "/entities/settings-field-arguments.php");

            require_once($this->includes . "/class-wcmp-assets.php");
            require_once($this->includes . "/frontend/class-wcmp-cart-fees.php");
            require_once($this->includes . "/frontend/class-wcmp-frontend-track-trace.php");
            require_once($this->includes . "/frontend/class-wcmp-checkout.php");
            require_once($this->includes . "/frontend/class-wcmp-frontend.php");
            $this->admin = require_once($this->includes . "/admin/class-wcmypa-admin.php");
            require_once($this->includes . "/admin/settings/class-wcmypa-settings.php");
            require_once($this->includes . "/class-wcmp-log.php");
            require_once($this->includes . "/admin/class-wcmp-country-codes.php");
            require_once($this->includes . '/admin/settings/class-wcmp-shipping-methods.php');
            $this->export = require_once($this->includes . "/admin/class-wcmp-export.php");
            require_once($this->includes . "/class-wcmp-postcode-fields.php");
            require_once($this->includes . "/adapter/delivery-options-from-order-adapter.php");
            require_once($this->includes . "/adapter/pickup-location-from-order-adapter.php");
            require_once($this->includes . "/adapter/shipment-options-from-order-adapter.php");
            require_once($this->includes . "/adapter/OrderLineFromWooCommerce.php");
            require_once($this->includes . "/admin/class-wcmp-export-consignments.php");
        }

        /**
         * Instantiate classes when WooCommerce is activated
         */
        public function load_classes()
        {
            if ($this->is_woocommerce_activated() === false) {
                add_action('admin_notices', [$this, 'need_woocommerce']);

                return;
            }

            if (! $this->phpVersionMeets(self::PHP_VERSION_REQUIRED)) {
                add_action('admin_notices', [$this, 'required_php_version']);

                return;
            }

            $this->useStagingEnvironment();
            $this->includes();
            $this->initSettings();
        }

        /**
         * Check if woocommerce is activated
         */
        public function is_woocommerce_activated()
        {
            $blog_plugins = get_option('active_plugins', []);
            $site_plugins = get_site_option('active_sitewide_plugins', []);

            if (in_array('woocommerce/woocommerce.php', $blog_plugins)
                || isset($site_plugins['woocommerce/woocommerce.php'])) {
                return true;
            } else {
                return false;
            }
        }

        /**
         * WooCommerce not active notice.
         */
        public function need_woocommerce()
        {
            $error = sprintf(
                __("WooCommerce MyParcel requires %sWooCommerce%s to be installed & activated!",
                    "woocommerce-myparcel"
                ),
                '<a href="http://wordpress.org/extend/plugins/woocommerce/">',
                '</a>'
            );

            $message = '<div class="error"><p>' . $error . '</p></div>';

            echo $message;
        }

        /**
         * PHP version requirement notice
         */

        public function required_php_version()
        {
            $error = __("WooCommerce MyParcel requires PHP {PHP_VERSION} or higher.", "woocommerce-myparcel");
            $error = str_replace('{PHP_VERSION}', self::PHP_VERSION_REQUIRED, $error);

            $how_to_update = __("How to update your PHP version", "woocommerce-myparcel");
            $message       = sprintf(
                '<div class="error"><p>%s</p><p><a href="%s">%s</a></p></div>',
                $error,
                'http://docs.wpovernight.com/general/how-to-update-your-php-version/',
                $how_to_update
            );

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
        public function do_install()
        {
            $version_setting   = "woocommerce_myparcel_version";
            $installed_version = get_option($version_setting);

            // installed version lower than plugin version?
            if (version_compare($installed_version, $this->version, '<')) {
                if (! $installed_version) {
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
        protected function install()
        {
            // Pre 2.0.0
            if (! empty(get_option('wcmyparcel_settings'))) {
                require_once('migration/wcmp-installation-migration-v2-0-0.php');
            }
            // todo: Pre 4.0.0?
        }

        /**
         * Plugin upgrade method. Perform any required upgrades here
         *
         * @param string $installed_version the currently installed ('old') version
         */
        protected function upgrade($installed_version)
        {
            if (version_compare($installed_version, '2.4.0-beta-4', '<')) {
                require_once('migration/wcmp-upgrade-migration-v2-4-0-beta-4.php');
            }

            if (version_compare($installed_version, '3.0.4', '<=')) {
                require_once('migration/wcmp-upgrade-migration-v3-0-4.php');
            }

            if ($this->phpVersionMeets(\WCMYPA::PHP_VERSION_7_1)) {
                // Import the migration class base
                require_once('migration/wcmp-upgrade-migration.php');

                // Migrate php 7.1+ only version settings
                if (version_compare($installed_version, '4.0.0', '<=')) {
                    require_once('migration/wcmp-upgrade-migration-v4-0-0.php');
                }

                if (version_compare($installed_version, '4.1.0', '<=')) {
                    require_once('migration/wcmp-upgrade-migration-v4-1-0.php');
                }

                if (version_compare($installed_version, '4.2.1', '<=')) {
                    require_once('migration/wcmp-upgrade-migration-v4-2-1.php');
                }

                if (version_compare($installed_version, '4.4.1', '<')) {
                    require_once('migration/wcmp-upgrade-migration-v4-4-1.php');
                }
            }
        }

        /**
         * Get the plugin url.
         *
         * @return string
         */
        public function plugin_url()
        {
            return untrailingslashit(plugins_url('/', __FILE__));
        }

        /**
         * Get the plugin path.
         *
         * @return string
         */
        public function plugin_path()
        {
            return untrailingslashit(plugin_dir_path(__FILE__));
        }

        /**
         * Initialize the settings.
         * Legacy: Before PHP 7.1, use old settings structure.
         */
        public function initSettings()
        {
            if (! $this->phpVersionMeets(\WCMYPA::PHP_VERSION_7_1)) {
                $this->general_settings  = get_option('woocommerce_myparcel_general_settings');
                $this->export_defaults   = get_option('woocommerce_myparcel_export_defaults_settings');
                $this->checkout_settings = get_option('woocommerce_myparcel_checkout_settings');

                return;
            }

            // Create the settings collection by importing this function, because we can't use the sdk
            // imports in the legacy version.
            require_once('includes/wcmp-initialize-settings-collection.php');
            if (empty($this->setting_collection)) {
                $this->setting_collection = (new WCMP_Initialize_Settings_Collection())->initialize();
            }
        }

        /**
         * @param string $version
         *
         * @return bool
         */
        private function phpVersionMeets($version)
        {
            return version_compare(PHP_VERSION, $version, '>=');
        }
    }

endif;

/**
 * Returns the main instance of the plugin class to prevent the need to use globals.
 *
 * @return WCMYPA
 * @since  2.0
 */
function WCMYPA()
{
    return WCMYPA::instance();
}

/**
 * For PHP < 7.1 support.
 *
 * @return WCMYPA
 */
function WooCommerce_MyParcel()
{
    return WCMYPA();
}

WCMYPA(); // load plugin
