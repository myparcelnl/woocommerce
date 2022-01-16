<?php
/*
Plugin Name: WooCommerce MyParcel
Plugin URI: https://myparcel.nl/
Description: Export your WooCommerce orders to MyParcel (https://myparcel.nl/) and print labels directly from the WooCommerce admin
Author: MyParcel
Author URI: https://myparcel.nl
Version: 4.7.0
Text Domain: woocommerce-myparcel

License: GPLv3 or later
License URI: http://www.opensource.org/licenses/gpl-license.php
*/

use MyParcelNL\WooCommerce\includes\admin\Messages;
use MyParcelNL\WooCommerce\includes\admin\MessagesRepository;
use MyParcelNL\WooCommerce\includes\Concerns\HasInstance;
use MyParcelNL\WooCommerce\includes\Settings\Api\AccountSettings;

defined('ABSPATH') or die();

require(plugin_dir_path(__FILE__) . 'vendor/autoload.php');

if (! class_exists('WCMYPA')) :
    class WCMYPA
    {
        use HasInstance;

        /**
         * Translations domain
         */
        public const DOMAIN               = 'woocommerce-myparcel';
        public const NONCE_ACTION         = 'wc_myparcel';
        public const PHP_VERSION_7_1      = '7.1';
        public const PHP_VERSION_REQUIRED = self::PHP_VERSION_7_1;
        public const NAME                 = 'woocommerce-myparcel';

        /**
         * @var string
         */
        public $version = '4.7.0';

        /**
         * @var string
         */
        public $plugin_basename;

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
         * @var array
         */
        private $activeCarriers;

        /**
         * Constructor
         */
        public function __construct()
        {
            $this->define('WC_MYPARCEL_NL_VERSION', $this->version);
            $this->plugin_basename = plugin_basename(__FILE__);

            // load the localisation & classes
            add_action('plugins_loaded', [$this, 'translations']);
            add_action('init', [$this, 'initialize'], 9999);

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

            // include compatibility classes
            require_once($this->includes . "/compatibility/abstract-wc-data-compatibility.php");
            require_once($this->includes . "/compatibility/class-wc-date-compatibility.php");
            require_once($this->includes . "/compatibility/class-wc-core-compatibility.php");
            require_once($this->includes . "/compatibility/class-wc-order-compatibility.php");
            require_once($this->includes . "/compatibility/class-wc-product-compatibility.php");
            require_once($this->includes . "/compatibility/class-ce-compatibility.php");
            require_once($this->includes . "/compatibility/class-wcpdf-compatibility.php");
            require_once($this->includes . "/compatibility/ShippingZone.php");

            require_once($this->includes . "/class-wcmp-data.php");
            require_once($this->includes . "/collections/settings-collection.php");
            require_once($this->includes . "/entities/setting.php");
            require_once($this->includes . "/entities/settings-field-arguments.php");

            require_once($this->includes . "/class-wcmp-assets.php");
            require_once($this->includes . "/frontend/class-wcmp-cart-fees.php");
            require_once($this->includes . "/frontend/class-wcmp-frontend-track-trace.php");
            require_once($this->includes . "/frontend/class-wcmp-checkout.php");
            require_once($this->includes . "/frontend/class-wcmp-frontend.php");
            $this->admin = require($this->includes . "/admin/class-wcmypa-admin.php");
            require_once($this->includes . "/admin/settings/class-wcmypa-settings.php");
            require_once($this->includes . "/class-wcmp-log.php");
            require_once($this->includes . "/admin/class-wcmp-country-codes.php");
            require_once($this->includes . '/admin/settings/class-wcmp-shipping-methods.php');
            $this->export = require($this->includes . "/admin/class-wcmp-export.php");
            require_once($this->includes . "/class-wcmp-postcode-fields.php");
            require_once($this->includes . "/adapter/delivery-options-from-order-adapter.php");
            require_once($this->includes . "/adapter/pickup-location-from-order-adapter.php");
            require_once($this->includes . "/adapter/shipment-options-from-order-adapter.php");
            require_once($this->includes . "/adapter/OrderLineFromWooCommerce.php");
            require_once($this->includes . "/admin/class-wcmp-export-consignments.php");
        }

        /**
         * Perform required tasks that initialize the plugin.
         */
        public function initialize(): void
        {
            if (! $this->checkPreRequisites()) {
                return;
            }

            $this->initMessenger();
            $this->useStagingEnvironment();
            $this->includes();
            $this->initSettings();

            if (! $this->validateApiKeyPresence()) {
                return;
            }

            AccountSettings::getInstance();
            add_action(
                'wp_ajax_' . WCMYPA_Settings::SETTING_TRIGGER_MANUAL_UPDATE,
                [AccountSettings::class, "restRefreshFromApi"]
            );

        }

        public function initMessenger(): void
        {
            // Always call the MessagesRepository to make sure lingering messages are shown
            MessagesRepository::getInstance();
            // Show temporary message concerning insurances for shipments to Belgium.
            Messages::showAdminNotice(
                __('message_insurance_belgium_2022', 'woocommerce-myparcel'),
                Messages::NOTICE_LEVEL_WARNING,
                'message_insurance_belgium_2022',
                [MessagesRepository::SETTINGS_PAGE, MessagesRepository::PLUGINS_PAGE]
            );
        }

        /**
         * @return bool
         */
        private function checkPreRequisites(): bool
        {
            return $this->isWoocommerceActivated()
                && $this->phpVersionMeets(self::PHP_VERSION_REQUIRED);
        }

        /**
         * Check if woocommerce is activated
         */
        public function isWoocommerceActivated(): bool
        {
            $blog_plugins = get_option('active_plugins', []);
            $site_plugins = get_site_option('active_sitewide_plugins', []);

            if (isset($site_plugins['woocommerce/woocommerce.php'])
                || in_array('woocommerce/woocommerce.php', $blog_plugins)
            ) {
                return true;
            }

            Messages::showAdminNotice(sprintf(
                __("WooCommerce MyParcel requires %sWooCommerce%s to be installed & activated!",
                    "woocommerce-myparcel"
                ),
                '<a href="http://wordpress.org/extend/plugins/woocommerce/">',
                '</a>'
            ), Messages::NOTICE_LEVEL_ERROR);

            return false;
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

            if ($this->phpVersionMeets(WCMYPA::PHP_VERSION_7_1)) {
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
         */
        public function initSettings()
        {
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
        private function phpVersionMeets(string $version): bool
        {
            if (version_compare(PHP_VERSION, $version, '>=')) {
                return true;
            }

            $error = __('WooCommerce MyParcel requires PHP {PHP_VERSION} or higher.', 'woocommerce-myparcel');
            $error = str_replace('{PHP_VERSION}', self::PHP_VERSION_REQUIRED, $error);

            $how_to_update = __('How to update your PHP version', 'woocommerce-myparcel');
            $message       = sprintf(
                '<p>%s</p><p><a href="%s">%s</a></p>',
                $error,
                'http://docs.wpovernight.com/general/how-to-update-your-php-version/',
                $how_to_update
            );

            Messages::showAdminNotice($message, Messages::NOTICE_LEVEL_ERROR);

            return false;
        }

        /**
         * @return bool
         */
        private function validateApiKeyPresence(): bool
        {
            if ($this->setting_collection->getByName(WCMYPA_Settings::SETTING_API_KEY)) {
                return true;
            }

            Messages::showAdminNotice(
                sprintf(
                    __('error_settings_api_key_missing', 'woocommerce-myparcel'),
                    sprintf('<a href="%s">', WCMYPA_Settings::getSettingsUrl()),
                    '</a>'
                ),
                Messages::NOTICE_LEVEL_WARNING
            );

            return false;
        }
    }

endif;

/**
 * Returns the main instance of the plugin class to prevent the need to use globals.
 *
 * @return WCMYPA
 * @since  2.0
 */
function WCMYPA(): WCMYPA
{
    return WCMYPA::getInstance();
}

WCMYPA(); // load plugin
