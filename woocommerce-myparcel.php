<?php
/** @noinspection AutoloadingIssuesInspection */

/*
Plugin Name: MyParcel
Plugin URI: https://myparcel.nl/
Description: Export your WooCommerce orders to MyParcel (https://myparcel.nl/) and print labels directly from the WooCommerce admin
Author: MyParcel
Author URI: https://myparcel.nl
Version: 5.0.0-alpha.0
Text Domain: woocommerce-myparcel

License: MIT
License URI: http://www.opensource.org/licenses/mit-license.php
*/

use MyParcelNL\Pdk\Facade\Pdk;
use MyParcelNL\WooCommerce\Admin\MessageLogger;
use MyParcelNL\WooCommerce\Facade\Messages;
use MyParcelNL\WooCommerce\Migration\Migrator;
use MyParcelNL\WooCommerce\Pdk\Boot;
use MyParcelNL\WooCommerce\Pdk\Service\AdminPdkHookService;
use MyParcelNL\WooCommerce\Pdk\Service\CheckoutHookService;
use MyParcelNL\WooCommerce\Pdk\Service\RestApiHookService;

defined('ABSPATH') or die();

require(plugin_dir_path(__FILE__) . 'vendor/autoload.php');

class MyParcelNL
{
    public const  ROOT_FILE              = __FILE__;
    public const  PHP_VERSION_MINIMUM    = '7.1';
    public const  NAME                   = 'myparcelnl';
    public const  CUSTOM_ORDER_COLUMN_ID = 'myparcelnl';
    public const  SETTINGS_MENU_SLUG     = 'myparcelnl_settings';
    /**
     * @var class-string<\MyParcelNL\WooCommerce\Pdk\Service\WordPressHookServiceInterface>[]
     */
    private const HOOK_SERVICES = [
        AdminPdkHookService::class,
        CheckoutHookService::class,
        RestApiHookService::class,
    ];

    /**
     * @var WCMYPA_Admin
     */
    public $admin;

    /**
     * @var ExportActions
     */
    public $export;

    /**
     * @var string
     */
    public $includes;

    /**
     * @var string
     */
    public $pluginBasename;

    /**
     * @var string
     */
    public $version;

    /**
     * @throws \Throwable
     */
    public function __construct()
    {
        $this->version        = $this->getVersion();
        $this->pluginBasename = plugin_basename(self::ROOT_FILE);

        Boot::setupPdk($this);

        define('MYPARCELNL_WC_VERSION', $this->version);

        // run lifecycle methods
        if (! defined('DOING_AJAX') && is_admin()) {
            add_action('init', [$this, 'install']);
        }

        // load the localisation & classes
        //        add_action('plugins_loaded', [$this, 'translations']);
        add_action('init', [$this, 'initialize'], 9999);
    }

    //        /**
    //         * Load the main plugin classes and functions
    //         */
    //        public function includes(): void
    //        {
    //            $this->includes = $this->plugin_path() . '/includes';
    //
    //            // include compatibility classes
    //            require_once($this->includes . '/compatibility/abstract-wc-data-compatibility.php');
    //            require_once($this->includes . '/compatibility/class-wc-date-compatibility.php');
    //            require_once($this->includes . '/compatibility/class-wc-core-compatibility.php');
    //            require_once($this->includes . '/compatibility/class-wc-order-compatibility.php');
    //            require_once($this->includes . '/compatibility/class-wc-product-compatibility.php');
    //            require_once($this->includes . '/compatibility/class-ce-compatibility.php');
    //            require_once($this->includes . '/compatibility/class-wcpdf-compatibility.php');
    //            require_once($this->includes . '/compatibility/ShippingZone.php');
    //
    //            require_once($this->includes . '/Data.php');
    //            require_once($this->includes . '/collections/settings-collection.php');
    //            require_once($this->includes . '/entities/setting.php');
    //            require_once($this->includes . '/entities/settings-field-arguments.php');
    //
    //            require_once($this->includes . '/class-wcmp-assets.php');
    //            require_once($this->includes . '/frontend/class-wcmp-cart-fees.php');
    //            require_once($this->includes . '/frontend/class-wcmp-frontend-track-trace.php');
    //            require_once($this->includes . '/frontend/class-wcmp-checkout.php');
    //            require_once($this->includes . '/frontend/class-wcmp-frontend.php');
    //            $this->admin = require($this->includes . '/admin/class-wcmypa-admin.php');
    //            require_once($this->includes . '/admin/settings/class-wcmypa-settings.php');
    //            require_once($this->includes . '/class-wcmp-log.php');
    //            require_once($this->includes . '/admin/CountryCodes.php');
    //            require_once($this->includes . '/admin/settings/class-wcmp-shipping-methods.php');
    //            $this->export = require($this->includes . '/admin/ExportActions.php');
    //            require_once($this->includes . '/class-wcmp-postcode-fields.php');
    //            require_once($this->includes . '/adapter/delivery-options-from-order-adapter.php');
    //            require_once($this->includes . '/adapter/pickup-location-from-order-adapter.php');
    //            require_once($this->includes . '/adapter/shipment-options-from-order-adapter.php');
    //            require_once($this->includes . '/adapter/OrderLineFromWooCommerce.php');
    //            require_once($this->includes . '/Webhook/Hooks/OrderStatusWebhook.php');
    //            require_once($this->includes . '/Webhook/Hooks/AccountSettingsWebhook.php');
    //        }

    public function initMessenger(): void
    {
        // Always call the MessagesRepository to make sure lingering messages are shown
        MessagesRepository::getInstance();
        // Show temporary message concerning insurances for shipments to Belgium.
        Messages::log(
            __('message_insurance_belgium_2022', 'woocommerce-myparcel'),
            MessageLogger::NOTICE_LEVEL_WARNING,
            'message_insurance_belgium_2022',
            [MessagesRepository::SETTINGS_PAGE, MessagesRepository::PLUGINS_PAGE]
        );
    }

    /**
     * Perform required tasks that initialize the plugin.
     *
     * @throws \Throwable
     */
    public function initialize(): void
    {
        if (! $this->checkPrerequisites()) {
            return;
        }

        $this->useStagingEnvironment();

        foreach (self::HOOK_SERVICES as $service) {
            /** @var \MyParcelNL\WooCommerce\Pdk\Service\WordPressHookServiceInterface $instance */
            $instance = Pdk::get($service);
            $instance->initialize();
        }
    }

    /**
     * Handles version checking
     */
    public function install(): void
    {
        $versionSetting   = 'woocommerce_myparcel_version';
        $installedVersion = get_option($versionSetting);

        // installed version lower than plugin version?
        if (version_compare($installedVersion, $this->version, '<')) {
            //            $this->upgrade($installedVersion);

            /** @var \MyParcelNL\WooCommerce\Migration\Migrator $migrator */
            $migrator = Pdk::get(Migrator::class);

            $migrator->migrate($installedVersion);

            // new version number
            // update_option($versionSetting, $this->version);
        }
    }



    //    /**
    //     * Load the translation / text-domain files
    //     * Note: the first-loaded translation file overrides any following ones if the same translation is present
    //     */
    //    public function translations(): void
    //    {
    //        $locale = apply_filters('plugin_locale', get_locale(), self::DOMAIN);
    //        $dir    = trailingslashit(WP_LANG_DIR);
    //
    //        /**
    //         * Frontend/global Locale. Looks in:
    //         *        - WP_LANG_DIR/woocommerce-myparcel/woocommerce-myparcel-LOCALE.mo
    //         *        - WP_LANG_DIR/plugins/woocommerce-myparcel-LOCALE.mo
    //         *        - woocommerce-myparcel/languages/woocommerce-myparcel-LOCALE.mo (which if not found falls back to:)
    //         *        - WP_LANG_DIR/plugins/woocommerce-myparcel-LOCALE.mo
    //         */
    //        load_textdomain(
    //            self::DOMAIN,
    //            $dir . 'woocommerce-myparcel/' . self::DOMAIN . '-' . $locale . '.mo'
    //        );
    //        load_textdomain(self::DOMAIN, $dir . 'plugins/' . self::DOMAIN . '-' . $locale . '.mo');
    //        load_plugin_textdomain(self::DOMAIN, false, dirname(plugin_basename(__FILE__)) . '/languages');
    //    }

    /**
     * @return bool
     */
    private function checkPrerequisites(): bool
    {
        return $this->isWoocommerceActivated()
            && $this->phpVersionMeets(self::PHP_VERSION_MINIMUM);
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
     * Check if woocommerce is activated
     */
    private function isWoocommerceActivated(): bool
    {
        $blogPlugins = get_option('active_plugins', []);
        $sitePlugins = get_site_option('active_sitewide_plugins', []);

        if (isset($sitePlugins['woocommerce/woocommerce.php'])
            || in_array('woocommerce/woocommerce.php', $blogPlugins, true)
        ) {
            return true;
        }

        //        \MyParcelNL\WooCommerce\Facade\Messages::log(
        //            sprintf(
        //                __(
        //                    'WooCommerce MyParcel requires %sWooCommerce%s to be installed & activated!',
        //                    'woocommerce-myparcel'
        //                ),
        //                '<a href="http://wordpress.org/extend/plugins/woocommerce/">',
        //                '</a>'
        //            ),
        //            MessageLogger::NOTICE_LEVEL_ERROR
        //        );

        return false;
    }

    /**
     * @param  string $version
     *
     * @return bool
     */
    private function phpVersionMeets(string $version): bool
    {
        if (version_compare(PHP_VERSION, $version, '>=')) {
            return true;
        }

        $error = __('WooCommerce MyParcel requires PHP {PHP_VERSION} or higher.', 'woocommerce-myparcel');
        $error = str_replace('{PHP_VERSION}', self::PHP_VERSION_MINIMUM, $error);

        $howToUpdate = __('How to update your PHP version', 'woocommerce-myparcel');
        $message     = sprintf(
            '<p>%s</p><p><a href="%s">%s</a></p>',
            $error,
            'http://docs.wpovernight.com/general/how-to-update-your-php-version/',
            $howToUpdate
        );

        Messages::error($message);

        return false;
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
     * @return bool
     */
    private function validateApiKey(): bool
    {
        $apiKey = $this->settingCollection->getByName('api_key');

        if (! $apiKey) {
            Messages::log(
                sprintf(
                    __('error_settings_api_key_missing', 'woocommerce-myparcel'),
                    sprintf('<a href="%s">', WCMYPA_Settings::getSettingsUrl()),
                    '</a>'
                ),
                MessageLogger::NOTICE_LEVEL_WARNING
            );

            return false;
        }

        return true;
    }
}

///**
// * Returns the main instance of the plugin class to prevent the need to use globals.
// *
// * @return WCMYPA
// * @since  2.0
// */
//function WCMYPA(): WCMYPA
//{
//    return WCMYPA::getInstance();
//}
//
//WCMYPA(); // load plugin

new MyParcelNL();
