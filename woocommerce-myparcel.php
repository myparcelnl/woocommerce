<?php
/*
Plugin Name: WooCommerce MyParcel
Plugin URI: https://myparcel.nl/
Description: Export your WooCommerce orders to MyParcel (https://myparcel.nl/) and print labels directly from the WooCommerce admin
Author: MyParcel
Author URI: https://myparcel.nl
Version: 4.4.4
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
        public const  NONCE_ACTION                 = 'wc_myparcel';
        private const TRANSLATION_DOMAIN           = 'woocommerce-myparcel';
        private const WOOCOMMERCE_VERSION_REQUIRED = '5.1.0';
        private const PHP_VERSION_REQUIRED         = '7.1';

        /**
         * @var string
         */
        public $version = '4.4.4';

        /**
         * @var string
         */
        public $plugin_basename;

        /**
         * @var object|null
         */
        protected static $_instance = null;

        /**
         * @var WPO\WC\MyParcel\Collections\SettingsCollection
         */
        public $setting_collection;

        /**
         * @var array|null
         */
        public $errorMessage = null;

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
         *
         * @return self|null
         */
        public static function instance(): ?self
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
         * @param string $name
         * @param string $value
         */
        private function define(string $name, string $value): void
        {
            if (! defined($name)) {
                define($name, $value);
            }
        }

        /**
         * Load the translation / text-domain files
         * Note: the first-loaded translation file overrides any following ones if the same translation is present
         */
        public function translations(): void
        {
            $locale = apply_filters('plugin_locale', get_locale(), self::TRANSLATION_DOMAIN);
            $dir    = trailingslashit(WP_LANG_DIR);

            /**
             * Frontend/global Locale. Looks in:
             *        - WP_LANG_DIR/woocommerce-myparcel/woocommerce-myparcel-LOCALE.mo
             *        - WP_LANG_DIR/plugins/woocommerce-myparcel-LOCALE.mo
             *        - woocommerce-myparcel/languages/woocommerce-myparcel-LOCALE.mo (which if not found falls back to:)
             *        - WP_LANG_DIR/plugins/woocommerce-myparcel-LOCALE.mo
             */
            load_textdomain(
                self::TRANSLATION_DOMAIN,
                $dir . 'woocommerce-myparcel/' . self::TRANSLATION_DOMAIN . '-' . $locale . '.mo'
            );
            load_textdomain(self::TRANSLATION_DOMAIN, $dir . 'plugins/' . self::TRANSLATION_DOMAIN . '-' . $locale . '.mo');
            load_plugin_textdomain(self::TRANSLATION_DOMAIN, false, dirname(plugin_basename(__FILE__)) . '/languages');
        }

        /**
         * Load the main plugin classes and functions
         */
        public function includes(): void
        {
            // Use php version 5.6
            if (! $this->phpVersionMeets(WCMYPA::PHP_VERSION_REQUIRED)) {
                $this->includes = $this->plugin_path() . "/includes_php56";

                // include compatibility classes
                require_once($this->includes . "/compatibility/abstract-wc-data-compatibility.php");
                require_once($this->includes . "/compatibility/class-wc-date-compatibility.php");
                require_once($this->includes . "/compatibility/class-wc-core-compatibility.php");
                require_once($this->includes . "/compatibility/class-wc-order-compatibility.php");
                require_once($this->includes . "/compatibility/class-wc-product-compatibility.php");

                require_once($this->includes . "/class-wcmp-assets.php");
                $this->admin = require_once($this->includes . "/class-wcmp-admin.php");
                require_once($this->includes . "/class-wcmp-frontend-settings.php");
                require_once($this->includes . "/class-wcmp-frontend.php");
                require_once($this->includes . "/class-wcmp-settings.php");
                $this->export = require_once($this->includes . "/class-wcmp-export.php");
                require_once($this->includes . "/class-wcmp-nl-postcode-fields.php");

                return;
            }

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
            require_once($this->includes . "/admin/class-wcmp-export-consignments.php");
        }

        /**
         * Instantiate classes when WooCommerce is activated
         */
        public function load_classes(): void
        {
            if ($this->checkInstalledWooCommerceVersion()) {
                add_action('admin_notices', [$this, 'showRequiredWooCommerceNotification']);

                return;
            }

            if (! $this->phpVersionMeets(self::PHP_VERSION_REQUIRED)) {
                add_action('admin_notices', [$this, 'showRequiredPhpNotification']);

                return;
            }

            if (! $this->phpVersionMeets(\WCMYPA::PHP_VERSION_REQUIRED)) {
                // php 5.6
                $this->initSettings();
                $this->includes();
            } else {
                // php 7.1
                $this->includes();
                $this->initSettings();
            }
        }

        /**
         * @return bool
         */
        public function isWooCommerceInstalled(): bool
        {
            return isset(get_plugins()['woocommerce/woocommerce.php']);
        }

        /**
         * @return array
         */
        public function getWooCommerceData(): array
        {
            if ($this->isWooCommerceInstalled()) {
                return get_plugin_data(WP_PLUGIN_DIR . '/woocommerce/woocommerce.php');
            }

            return [];
        }

        /**
         * Check if woocommerce is activated
         *
         * @return bool
         */
        public function isWooCommerceActivated(): bool
        {
            return is_plugin_active('woocommerce/woocommerce.php');
        }

        /**
         * Notification for the minimum WooCommerce version
         */
        public function showRequiredWooCommerceNotification(): void
        {
            $message = $this->errorMessage['message'];
            $button  = $this->errorMessage['button'];

            if ($message) {
                echo sprintf(
                    '<div class="notice notice-error"><p><strong>%s</strong> %s</p><p>%s %s</p></div>',
                    __('woocommerce_myparcel', 'woocommerce-myparcel'),
                    $message,
                    $button,
                    $this->deactivatePlugin()
                );
            }
        }

        /**
         * @return bool
         */
        public function checkInstalledWooCommerceVersion(): bool
        {
            if (! empty($this->getWooCommerceData()) && ! $this->woocommerceVersionMeets()) {
                $errorMessage = __("error_woocommerce_minimum_version", "woocommerce-myparcel");
                $error        = str_replace('{woocommerce_version}', self::WOOCOMMERCE_VERSION_REQUIRED, $errorMessage);

                $this->setAdminErrorMessage(__($error), null);
            }

            if (! $this->isWooCommerceActivated()) {
                $this->setAdminErrorMessage(__('error_woocommerce_not_activated', 'woocommerce-myparcel'), $this->getActivateWooCommerceButton());
            }

            if (! $this->getWooCommerceData()) {
                $this->setAdminErrorMessage(__('error_woocommerce_not_installed', 'woocommerce-myparcel'), $this->getInstallWooCommerceButton());
            }

            return (bool) $this->errorMessage;
        }

        /**
         * @param string      $message
         * @param string|null $button
         */
        public function setAdminErrorMessage(string $message, ?string $button): void
        {
            $this->errorMessage['message'] = $message;
            $this->errorMessage['button']  = $button;
        }

        /**
         * @param string $path
         * @param string $action
         * @param string $message
         * @param string $class
         *
         * @return string
         */
        public function generateButtons(string $path, string $action, string $message, string $class): string
        {
            return sprintf(
                '<a href="%s" class="%s">%s</a>',
                wp_nonce_url(self_admin_url($path), $action),
                $class,
                $message
            );
        }

        /**
         * @return string|null
         */
        public function getActivateWooCommerceButton(): ?string
        {
            if ($this->getWooCommerceData() && current_user_can('activate_plugin', 'woocommerce/woocommerce.php')) {
                $path    = 'plugins.php?action=activate&plugin=woocommerce/woocommerce.php';
                $action  = 'activate-plugin_woocommerce/woocommerce.php';
                $message = __('error_button_woocommerce_activate', 'woocommerce-myparcel');
                $class   = 'button-primary';

                return $this->generateButtons($path, $action, $message, $class);
            }

            return null;
        }

        /**
         * @return string|null
         */
        public function deactivatePlugin(): ?string
        {
            if (current_user_can('deactivate_plugin', 'woocommerce-myparcel/woocommerce-myparcel.php')) {
                $path    = 'plugins.php?action=deactivate&plugin=woocommerce-myparcel/woocommerce-myparcel.php';
                $action  = 'deactivate-plugin_woocommerce-myparcel/woocommerce-myparcel.php';
                $message = __('error_button_turn_off_myparcel_plugin', 'woocommerce-myparcel');
                $class   = 'button-secondary';

                return $this->generateButtons($path, $action, $message, $class);
            }

            return null;
        }

        /**
         * @return string
         */
        public function getInstallWooCommerceButton(): string
        {
            $path = 'http://wordpress.org/plugins/woocommerce/';

            if (current_user_can('install_plugins')) {
                $path = 'update.php?action=install-plugin&plugin=woocommerce';
            }

            $action  = 'install-plugin_woocommerce';
            $message = __('error_button_install_woocommerce', 'woocommerce-myparcel');
            $class   = 'button-primary';

            return $this->generateButtons($path, $action, $message, $class);
        }

        /**
         * Notification for the minimum PHP version
         */
        public function showRequiredPhpNotification(): void
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
        public function do_install(): void
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
        protected function install(): void
        {
            if (! empty(get_option('wcmyparcel_settings'))) {
                require_once('migration/wcmp-installation-migration-v2-0-0.php');
            }
        }

        /**
         * Plugin upgrade method. Perform any required upgrades here
         *
         * @param string $installed_version the currently installed ('old') version
         */
        protected function upgrade(string $installed_version): void
        {
            if (version_compare($installed_version, '2.4.0-beta-4', '<')) {
                require_once('migration/wcmp-upgrade-migration-v2-4-0-beta-4.php');
            }

            if (version_compare($installed_version, '3.0.4', '<=')) {
                require_once('migration/wcmp-upgrade-migration-v3-0-4.php');
            }

            if ($this->phpVersionMeets(\WCMYPA::PHP_VERSION_REQUIRED)) {
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
        public function plugin_url(): string
        {
            return untrailingslashit(plugins_url('/', __FILE__));
        }

        /**
         * Get the plugin path.
         *
         * @return string
         */
        public function plugin_path(): string
        {
            return untrailingslashit(plugin_dir_path(__FILE__));
        }

        /**
         * Initialize the settings.
         * Legacy: Before PHP 7.1, use old settings structure.
         */
        public function initSettings(): void
        {
            if (! $this->phpVersionMeets(self::PHP_VERSION_REQUIRED)) {
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
         * @return bool
         */
        private function woocommerceVersionMeets(): bool
        {
            return version_compare(self::WOOCOMMERCE_VERSION_REQUIRED, $this->getWooCommerceData()['Version'], '<');
        }

        /**
         * @param string $version
         *
         * @return bool
         */
        private function phpVersionMeets(string $version): bool
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
