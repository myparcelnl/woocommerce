<?php
/*
Plugin Name: MyParcel
Plugin URI: https://myparcel.nl/
Description: Export your WooCommerce orders to MyParcel (https://myparcel.nl/) and print labels directly from the WooCommerce admin
Author: MyParcel
Author URI: https://myparcel.nl
Version: 4.24.1
Text Domain: woocommerce-myparcel
License: GPLv3 or later
License URI: http://www.opensource.org/licenses/gpl-license.php

Tested up to: 6.5
WC tested up to: 8.9.1
Requires PHP: 7.4
*/

declare(strict_types=1);

use MyParcelNL\WooCommerce\includes\admin\Messages;
use MyParcelNL\WooCommerce\includes\admin\MessagesRepository;
use MyParcelNL\WooCommerce\includes\admin\views\MyParcelWidget;
use MyParcelNL\WooCommerce\includes\Concerns\HasApiKey;
use MyParcelNL\WooCommerce\includes\Concerns\HasInstance;
use MyParcelNL\WooCommerce\includes\Settings\Api\AccountSettings;
use MyParcelNL\WooCommerce\includes\Webhook\Service\WebhookSubscriptionService;
use MyParcelNL\WooCommerce\includes\Webhooks\Hooks\AccountSettingsWebhook;
use MyParcelNL\WooCommerce\includes\Webhooks\Hooks\OrderStatusWebhook;

defined('ABSPATH') or die();

require(plugin_dir_path(__FILE__) . 'vendor/autoload.php');

if (! class_exists('WCMYPA')) :
    class WCMYPA
    {
        use HasInstance;
        use HasApiKey;

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
        public $version;

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
            $this->version         = $this->getVersion();
            $this->define('WC_MYPARCEL_NL_VERSION', $this->version);
            $this->plugin_basename = plugin_basename(__FILE__);

            // Incompatibility HPOS check
            add_action( 'before_woocommerce_init', function() {
                if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
                    \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, false );
                }
            } );

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
        private function define(string $name, $value): void
        {
            if (! defined($name)) {
                define($name, $value);
            }
        }

        /**
         * @return string
         */
        private function getVersion(): string
        {
            $composerJson = json_decode(file_get_contents(__DIR__ . '/composer.json'), false);

            return $composerJson->version;
        }

        /**
         * @return void
         * @throws \Exception
         */
        private function registerWebhooks(): void
        {
            (new OrderStatusWebhook())->register();

            (new AccountSettingsWebhook())->register();
        }

        /**
         * This method is used internally, to be able to use the staging environment of MyParcel
         */
        private function useStagingEnvironment(): void
        {
            if (get_option('use_myparcel_staging_environment')) {
                putenv('MYPARCEL_API_BASE_URL=' . get_option('myparcel_base_url'));
            }
        }

        /**
         * Load the translation / text-domain files
         */
        public function translations(): void
        {
            $locale = apply_filters('plugin_locale', get_locale(), self::DOMAIN);

            load_textdomain(
                self::DOMAIN,
                sprintf(
                    '%s/languages/%s-%s.mo',
                    __DIR__,
                    self::DOMAIN,
                    $locale
                )
            );
        }

        /**
         * Load the main plugin classes and functions
         */
        public function includes(): void
        {
            $this->includes = $this->plugin_path() . '/includes';

            // include compatibility classes
            require_once($this->includes . '/compatibility/abstract-wc-data-compatibility.php');
            require_once($this->includes . '/compatibility/class-wc-date-compatibility.php');
            require_once($this->includes . '/compatibility/class-wc-core-compatibility.php');
            require_once($this->includes . '/compatibility/class-wc-order-compatibility.php');
            require_once($this->includes . '/compatibility/class-wc-product-compatibility.php');
            require_once($this->includes . '/compatibility/class-ce-compatibility.php');
            require_once($this->includes . '/compatibility/class-wcpdf-compatibility.php');
            require_once($this->includes . '/compatibility/ShippingZone.php');

            require_once($this->includes . '/class-wcmp-data.php');
            require_once($this->includes . '/collections/settings-collection.php');
            require_once($this->includes . '/entities/setting.php');
            require_once($this->includes . '/entities/settings-field-arguments.php');

            require_once($this->includes . '/class-wcmp-assets.php');
            require_once($this->includes . '/frontend/class-wcmp-cart-fees.php');
            require_once($this->includes . '/frontend/class-wcmp-frontend-track-trace.php');
            require_once($this->includes . '/frontend/class-wcmp-checkout.php');
            require_once($this->includes . '/frontend/class-wcmp-frontend.php');
            $temp = require_once($this->includes . '/admin/class-wcmypa-admin.php');
            if (true !== $temp) $this->admin = $temp;
            require_once($this->includes . '/admin/settings/class-wcmypa-settings.php');
            require_once($this->includes . '/class-wcmp-log.php');
            require_once($this->includes . '/admin/class-wcmp-country-codes.php');
            require_once($this->includes . '/admin/settings/class-wcmp-shipping-methods.php');
            $temp = require_once($this->includes . '/admin/class-wcmp-export.php');
            if (true !== $temp) $this->export = $temp;
            require_once($this->includes . '/class-wcmp-postcode-fields.php');
            require_once($this->includes . '/adapter/delivery-options-from-order-adapter.php');
            require_once($this->includes . '/adapter/pickup-location-from-order-adapter.php');
            require_once($this->includes . '/adapter/shipment-options-from-order-adapter.php');
            require_once($this->includes . '/adapter/OrderLineFromWooCommerce.php');
            require_once($this->includes . '/admin/class-wcmp-export-consignments.php');
            require_once($this->includes . '/Webhook/Hooks/OrderStatusWebhook.php');
            require_once($this->includes . '/Webhook/Hooks/AccountSettingsWebhook.php');
        }

        /**
         * Perform required tasks that initialize the plugin.
         */
        public function initialize(): void
        {
            if (! $this->checkPrerequisites()) {
                return;
            }

            $this->initMessenger();
            $this->useStagingEnvironment();
            $this->includes();
            $this->initSettings();

            add_action('wp_dashboard_setup', [new MyParcelWidget(), 'loadWidget']);

            if (! $this->validateApiKey()) {
                return;
            }

            $this->registerWebhooks();

            AccountSettings::getInstance();
            add_action(
                'wp_ajax_' . WCMYPA_Settings::SETTING_TRIGGER_MANUAL_UPDATE,
                [new AccountSettings(), 'ajaxRefreshFromApi']
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
        private function checkPrerequisites(): bool
        {
            return $this->isWoocommerceActivated()
                && $this->phpVersionMeets(self::PHP_VERSION_REQUIRED);
        }

        /**
         * Check if woocommerce is activated
         */
        public function isWoocommerceActivated(): bool
        {
            $blogPlugins = get_option('active_plugins', []);
            $sitePlugins = get_site_option('active_sitewide_plugins', []);

            if (isset($sitePlugins['woocommerce/woocommerce.php'])
                || in_array('woocommerce/woocommerce.php', $blogPlugins)
            ) {
                return true;
            }

            Messages::showAdminNotice(sprintf(
                __(
                    'MyParcel requires %sWooCommerce%s to be installed & activated!',
                    'woocommerce-myparcel'
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
        public function do_install(): void
        {
            $version_setting   = 'woocommerce_myparcel_version';
            $installed_version = get_option($version_setting) ?: '0';

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
        protected function upgrade($installed_version): void
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

                if (version_compare($installed_version, '4.22.0', '<')) {
                    require_once('migration/wcmp-upgrade-migration-v4-22-0.php');
                }

                require_once('migration/wcmp-upgrade-migration-always.php');
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
         */
        public function initSettings(): void
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

            $error = __('MyParcel requires PHP {PHP_VERSION} or higher.', 'woocommerce-myparcel');
            $error = str_replace('{PHP_VERSION}', self::PHP_VERSION_REQUIRED, $error);

            $howToUpdate = __('How to update your PHP version', 'woocommerce-myparcel');
            $message     = sprintf(
                '<p>%s</p><p><a href="%s">%s</a></p>',
                $error,
                'http://docs.wpovernight.com/general/how-to-update-your-php-version/',
                $howToUpdate
            );

            Messages::showAdminNotice($message, Messages::NOTICE_LEVEL_ERROR);

            return false;
        }

        /**
         * @return bool
         */
        private function validateApiKey(): bool
        {
            $apiKey = $this->setting_collection->getByName(WCMYPA_Settings::SETTING_API_KEY);

            if (! $apiKey) {
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

            return true;
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
