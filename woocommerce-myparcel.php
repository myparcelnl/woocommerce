<?php
/*
Plugin Name: WC MyParcel Belgium
Plugin URI: https://sendmyparcel.be/
Description: Export your WooCommerce orders to MyParcel BE (https://sendmyparcel.be/) and print labels directly from the WooCommerce admin
Author: Richard Perdaan
Version: 4.3.5
Text Domain: woocommerce-myparcelbe

License: GPLv3 or later
License URI: http://www.opensource.org/licenses/gpl-license.php
*/

if (! defined('ABSPATH')) {
    exit;
} // Exit if accessed directly

if (! class_exists('WCMYPABE')) :

    class WCMYPABE
    {
        /**
         * Translations domain
         */
        const DOMAIN               = 'woocommerce-myparcelbe';
        const NONCE_ACTION         = 'wc_myparcel';
        const PHP_VERSION_7_1      = '7.1';
        const PHP_VERSION_REQUIRED = self::PHP_VERSION_7_1;

        public $version = '4.3.5';

        public $plugin_basename;

        protected static $_instance = null;

        /**
         * @var WPO\WC\MyParcelBE\Collections\SettingsCollection
         */
        public $setting_collection;

        /**
         * @var string
         */
        public $includes;

        /**
         * @var WCMPBE_Export
         */
        public $export;

        /**
         * @var WCMYPABE_Admin
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
            $this->define('WC_MYPARCEL_BE_VERSION', $this->version);
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
         * Load the translation / text-domain files
         * Note: the first-loaded translation file overrides any following ones if the same translation is present
         */
        public function translations()
        {
            $locale = apply_filters('plugin_locale', get_locale(), self::DOMAIN);
            $dir    = trailingslashit(WP_LANG_DIR);

            /**
             * Frontend/global Locale. Looks in:
             *        - WP_LANG_DIR/woocommerce-myparcelbe/woocommerce-myparcelbe-LOCALE.mo
             *        - WP_LANG_DIR/plugins/woocommerce-myparcelbe-LOCALE.mo
             *        - woocommerce-myparcelbe/languages/woocommerce-myparcelbe-LOCALE.mo (which if not found falls back to:)
             *        - WP_LANG_DIR/plugins/woocommerce-myparcelbe-LOCALE.mo
             */
            load_textdomain(
                self::DOMAIN,
                $dir . 'woocommerce-myparcelbe/' . self::DOMAIN . '-' . $locale . '.mo'
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

            require_once($this->includes . "/class-wcmpbe-data.php");
            require_once($this->includes . "/collections/settings-collection.php");
            require_once($this->includes . "/entities/setting.php");
            require_once($this->includes . "/entities/settings-field-arguments.php");

            require_once($this->includes . "/class-wcmpbe-assets.php");
            require_once($this->includes . "/frontend/class-wcmpbe-cart-fees.php");
            require_once($this->includes . "/frontend/class-wcmpbe-frontend-track-trace.php");
            require_once($this->includes . "/frontend/class-wcmpbe-checkout.php");
            require_once($this->includes . "/frontend/class-wcmpbe-frontend.php");
            $this->admin = require_once($this->includes . "/admin/class-wcmypabe-admin.php");
            require_once($this->includes . "/admin/settings/class-wcmpbe-settings.php");
            require_once($this->includes . "/class-wcmpbe-log.php");
            require_once($this->includes . "/admin/class-wcmpbe-country-codes.php");
            require_once($this->includes . '/admin/settings/class-wcmpbe-shipping-methods.php');
            $this->export = require_once($this->includes . "/admin/class-wcmpbe-export.php");
            require_once($this->includes . "/class-wcmpbe-postcode-fields.php");
            require_once($this->includes . "/adapter/delivery-options-from-order-adapter.php");
            require_once($this->includes . "/adapter/pickup-location-from-order-adapter.php");
            require_once($this->includes . "/adapter/shipment-options-from-order-adapter.php");
            require_once($this->includes . "/admin/class-wcmpbe-export-consignments.php");
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
            // php 7.1
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
                __("WooCommerce MyParcel BE requires %sWooCommerce%s to be installed & activated!",
                    "woocommerce-myparcelbe"
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
            $error = __("WooCommerce MyParcel BE requires PHP {PHP_VERSION} or higher.", "woocommerce-myparcelbe");
            $error = str_replace('{PHP_VERSION}', self::PHP_VERSION_REQUIRED, $error);

            $how_to_update = __("How to update your PHP version", "woocommerce-myparcelbe");
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
            $version_setting   = "woocommerce_myparcelbe_version";
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
            if (! empty(get_option('wcmyparcelbe_settings'))) {
                require_once('migration/wcmpbe-installation-migration-v2-0-0.php');
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
                require_once('migration/wcmpbe-upgrade-migration-v2-4-0-beta-4.php');
            }

            if (version_compare($installed_version, '3.0.4', '<=')) {
                require_once('migration/wcmpbe-upgrade-migration-v3-0-4.php');
            }

            if ($this->phpVersionMeets(\WCMYPABE::PHP_VERSION_7_1)) {
                // Import the migration class base
                require_once('migration/wcmpbe-upgrade-migration.php');

                // Migrate php 7.1+ only version settings
                if (version_compare($installed_version, '4.0.0', '<=')) {
                    require_once('migration/wcmpbe-upgrade-migration-v4-0-0.php');
                }

                if (version_compare($installed_version, '4.1.0', '<=')) {
                    require_once('migration/wcmpbe-upgrade-migration-v4-1-0.php');
                }

                if (version_compare($installed_version, '4.2.1', '<=')) {
                    require_once('migration/wcmpbe-upgrade-migration-v4-2-1.php');
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
            if (! $this->phpVersionMeets(\WCMYPABE::PHP_VERSION_7_1)) {
                $this->general_settings  = get_option('woocommerce_myparcelbe_general_settings');
                $this->export_defaults   = get_option('woocommerce_myparcelbe_export_defaults_settings');
                $this->checkout_settings = get_option('woocommerce_myparcelbe_checkout_settings');

                return;
            }

            // Create the settings collection by importing this function, because we can't use the sdk
            // imports in the legacy version.
            require_once('includes/wcmpbe-initialize-settings-collection.php');
            if (empty($this->setting_collection)) {
                $this->setting_collection = (new WCMPBE_Initialize_Settings_Collection())->initialize();
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
 * @return WCMYPABE
 * @since  2.0
 */
function WCMYPABE()
{
    return WCMYPABE::instance();
}

/**
 * For PHP < 7.1 support.
 *
 * @return WCMYPABE
 */
function WooCommerce_MyParcelBE()
{
    return WCMYPABE();
}

WCMYPABE(); // load plugin
