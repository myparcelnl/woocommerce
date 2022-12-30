<?php
/** @noinspection AutoloadingIssuesInspection */

/*
Plugin Name: MyParcel
Plugin URI: https://myparcel.nl/
Description: Export your WooCommerce orders to MyParcel (https://myparcel.nl/) and print labels directly from the WooCommerce admin
Author: MyParcel
Author URI: https://myparcel.nl
Version: 4.14.0
Text Domain: woocommerce-myparcel

License: GPLv3 or later
License URI: http://www.opensource.org/licenses/gpl-license.php
*/

use MyParcelNL\Pdk\Facade\Pdk;
use MyParcelNL\Pdk\Facade\RenderService;
use MyParcelNL\Pdk\Plugin\Repository\AbstractPdkOrderRepository;
use MyParcelNL\WooCommerce\Pdk\Boot;

//use MyParcelNL\WooCommerce\includes\admin\Messages;
//use MyParcelNL\WooCommerce\includes\admin\MessagesRepository;

//use MyParcelNL\WooCommerce\includes\Boot;
//use MyParcelNL\WooCommerce\includes\Settings\Api\AccountSettings;
//use MyParcelNL\WooCommerce\includes\Webhook\Service\WebhookSubscriptionService;
//use MyParcelNL\WooCommerce\includes\Webhooks\Hooks\AccountSettingsWebhook;
//use MyParcelNL\WooCommerce\includes\Webhooks\Hooks\OrderStatusWebhook;

defined('ABSPATH') or die();

require(plugin_dir_path(__FILE__) . 'vendor/autoload.php');

class MyParcelNL
{
    /**
     * Translations domain
     */
    public const DOMAIN                 = 'woocommerce-myparcel';
    public const NONCE_ACTION           = 'wc_myparcel';
    public const PHP_VERSION_7_1        = '7.1';
    public const PHP_VERSION_REQUIRED   = self::PHP_VERSION_7_1;
    public const NAME                   = 'myparcelnl';
    public const PLATFORM               = 'myparcel';
    const        CUSTOM_ORDER_COLUMN_ID = 'myparcelnl';
    public const SETTINGS_MENU_SLUG     = 'wcmp_settings';

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
     * @var array
     */
    private $activeCarriers;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->version = $this->getVersion();
        define('WC_MYPARCEL_NL_VERSION', $this->version);
        $this->pluginBasename = plugin_basename(__FILE__);

        // load the localisation & classes
        //        add_action('plugins_loaded', [$this, 'translations']);
        add_action('init', [$this, 'initialize'], 9999);

        // run lifecycle methods
        if (is_admin() && ! defined('DOING_AJAX')) {
            add_action('init', [$this, 'do_install']);
        }
    }

    /**
     * @param  array $columns
     *
     * @return array
     */
    public function addMyParcelColumnToOrderGrid(array $columns): array
    {
        $newColumns = [];

        // Insert the column before the column we want to appear after
        foreach ($columns as $name => $data) {
            $newColumns[$name] = $data;

            if ('shipping_address' === $name) {
                $newColumns[self::CUSTOM_ORDER_COLUMN_ID] = __('MyParcel', 'my-textdomain');
            }
        }

        return $newColumns;
    }

    /**
     * Handles version checking
     */
    public function do_install(): void
    {
        $version_setting   = 'woocommerce_myparcel_version';
        $installed_version = get_option($version_setting);

        // installed version lower than plugin version?
        if (version_compare($installed_version, $this->version, '<')) {
            $this->upgrade($installed_version);

            // new version number
            update_option($version_setting, $this->version);
        }
    }

    /**
     * Get the plugin path.
     *
     * @return string
     */
    public function getPluginPath(): string
    {
        return untrailingslashit(plugin_dir_path(__FILE__));
    }

    /**
     * Get the plugin url.
     *
     * @return string
     */
    public function getPluginUrl(): string
    {
        return untrailingslashit(plugins_url('/', __FILE__));
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

        //        $this->initMessenger();
        $this->useStagingEnvironment();
        //            $this->includes();
        //            $this->initSettings();

        //            add_action('wp_dashboard_setup', [new MyParcelWidget(), 'loadWidget']);

        //            if (! $this->validateApiKey()) {
        //                return;
        //            }

        $this->setupPdk();
        //            $this->registerWebhooks();

        //            AccountSettings::getInstance();
        //            add_action(
        //                'wp_ajax_' . WCMYPA_Settings::SETTING_TRIGGER_MANUAL_UPDATE,
        //                [AccountSettings::class, 'restRefreshFromApi']
        //            );

        // Load the js necessary to run the pdk frontend
        add_action('admin_enqueue_scripts', [$this, 'loadPdkScripts']);

        // Render scripts in the footer
        add_action('admin_footer', [$this, 'renderPdkInitScripts']);

        // Render scripts in woocommerce header
        add_action('admin_notices', [$this, 'renderPdkNotifications']);

        // Render custom column in order grid
        add_filter('manage_edit-shop_order_columns', [$this, 'addMyParcelColumnToOrderGrid'], 20);

        // Render pdk order list column in above custom order grid column
        add_action('manage_shop_order_posts_custom_column', [$this, 'renderPdkOrderListColumn']);

        add_action('admin_menu', [$this, 'addSubMenu']);
    }

    /**
     * @return void
     */
    public function renderPdkInitScripts(): void
    {
        echo RenderService::renderInitScript();
        echo RenderService::renderModals();
    }

    /**
     * @return void
     */
    public function renderPdkNotifications(): void
    {
        echo RenderService::renderNotifications();
    }

    /**
     * @return void
     */
    public function renderPdkPluginSettings(): void
    {
        echo 'hier komen settings';
//        echo RenderService::renderPluginSettings();
    }

    /**
     * @return void
     */
    public function addSubMenu()
    {
        add_submenu_page(
            'woocommerce',
            __('MyParcel', 'woocommerce-myparcel'),
            __('MyParcel', 'woocommerce-myparcel'),
            'edit_pages',
            self::SETTINGS_MENU_SLUG,
            [$this, 'renderPdkPluginSettings']
        );
    }

    /**
     * Check if woocommerce is activated
     */
    public function isWoocommerceActivated(): bool
    {
        $blogPlugins = get_option('active_plugins', []);
        $sitePlugins = get_site_option('active_sitewide_plugins', []);

        if (isset($sitePlugins['woocommerce/woocommerce.php'])
            || in_array('woocommerce/woocommerce.php', $blogPlugins, true)
        ) {
            return true;
        }

        Messages::showAdminNotice(
            sprintf(
                __(
                    'WooCommerce MyParcel requires %sWooCommerce%s to be installed & activated!',
                    'woocommerce-myparcel'
                ),
                '<a href="http://wordpress.org/extend/plugins/woocommerce/">',
                '</a>'
            ),
            Messages::NOTICE_LEVEL_ERROR
        );

        return false;
    }

    /**
     * @return void
     */
    public function loadPdkScripts(): void
    {
        $enqueue = static function (string $handle, string $url) {
            wp_enqueue_script($handle, $url, [], null, true);
        };

        if (Pdk::isDevelopment()) {
            $enqueue('vue', 'https://cdnjs.cloudflare.com/ajax/libs/vue/3.2.45/vue.global.js');
            $enqueue('vue-demi', 'https://cdnjs.cloudflare.com/ajax/libs/vue-demi/0.13.11/index.iife.js');
        } else {
            $enqueue('vue', 'https://cdnjs.cloudflare.com/ajax/libs/vue/3.2.45/vue.global.min.js');
            $enqueue('vue-demi', 'https://cdnjs.cloudflare.com/ajax/libs/vue-demi/0.13.11/index.iife.min.js');
        }

        $enqueue(
            'myparcel-pdk-frontend',
            $this->getPluginUrl() . '/views/admin/lib/woocommerce-admin.iife.js'
        );

        wp_enqueue_style(
            'myparcel-pdk-frontend',
            $this->getPluginUrl() . '/views/admin/lib/style.css'
        );
    }

    /**
     * @param $column
     *
     * @return void
     */
    public function renderPdkOrderListColumn($column): void
    {
        global $post;

        if (self::CUSTOM_ORDER_COLUMN_ID === $column) {
            /** @var \MyParcelNL\Pdk\Plugin\Repository\AbstractPdkOrderRepository $orderRepository */
            $orderRepository = Pdk::get(AbstractPdkOrderRepository::class);

            $pdkOrder = $orderRepository->get($post->ID);

            echo RenderService::renderOrderListColumn($pdkOrder);
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
     * Plugin upgrade method. Perform any required upgrades here
     *
     * @param  string $installed_version the currently installed ('old') version
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
        }
    }

    /**
     * @return bool
     */
    private function checkPrerequisites(): bool
    {
        return $this->isWoocommerceActivated()
            && $this->phpVersionMeets(self::PHP_VERSION_REQUIRED);
    }

    /** Lifecycle methods *******************************************************
     * Because register_activation_hook only runs when the plugin is manually
     * activated by the user, we're checking the current version against the
     * version stored in the database
     ****************************************************************************/

    /**
     * Define constant if not already set
     *
     * @param  string      $name
     * @param  string|bool $value
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
     * @return void
     * @throws \Exception
     */
    private function registerWebhooks(): void
    {
        if (WebhookSubscriptionService::shouldRegisterOrderStatusRoute()) {
            (new OrderStatusWebhook())->register();
        }

        (new AccountSettingsWebhook())->register();
    }

    /**
     * @return void
     * @throws \Throwable
     */
    private function setupPdk(): void
    {
        Boot::setupPdk($this);
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
        $apiKey = $this->settingCollection->getByName(WCMYPA_Settings::SETTING_API_KEY);

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
