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
use MyParcelNL\WooCommerce\Facade\Messages;
use MyParcelNL\WooCommerce\Hooks\CheckoutHooks;
use MyParcelNL\WooCommerce\Hooks\RestApiHooks;
use MyParcelNL\WooCommerce\Migration\Migrator;
use MyParcelNL\WooCommerce\Pdk\Boot;
use MyParcelNL\WooCommerce\Pdk\Hooks\PdkCoreHooks;
use MyParcelNL\WooCommerce\Pdk\Hooks\PdkOrderHooks;
use MyParcelNL\WooCommerce\Pdk\Hooks\PdkOrderListHooks;
use MyParcelNL\WooCommerce\Pdk\Hooks\PdkPluginSettingsHooks;
use MyParcelNL\WooCommerce\Pdk\Hooks\PdkProductSettingsHooks;

defined('ABSPATH') or die();

require(plugin_dir_path(__FILE__) . 'vendor/autoload.php');

class MyParcelNL
{
    public const  ROOT_FILE              = __FILE__;
    public const  PHP_VERSION_MINIMUM    = '7.1';
    public const  NAME                   = 'myparcelnl';
    public const  CUSTOM_ORDER_COLUMN_ID = 'myparcelnl';
    /**
     * @var class-string<\MyParcelNL\WooCommerce\Hooks\WordPressHooksInterface>[]
     */
    private const HOOK_SERVICES = [
        CheckoutHooks::class,
        RestApiHooks::class,
        PdkCoreHooks::class,
        PdkOrderHooks::class,
        PdkOrderListHooks::class,
        PdkPluginSettingsHooks::class,
        PdkProductSettingsHooks::class,
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

        if (! defined('DOING_AJAX') && is_admin()) {
            add_action('init', [$this, 'upgrade']);
        }

        add_action('init', [$this, 'initialize'], 9999);
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
            /** @var \MyParcelNL\WooCommerce\Hooks\WordPressHooksInterface $instance */
            $instance = Pdk::get($service);
            $instance->apply();
        }
    }

    public function upgrade(): void
    {
        $versionSetting   = 'woocommerce_myparcel_version';
        $installedVersion = get_option($versionSetting);

        if (version_compare($installedVersion, $this->version, '<')) {
            /** @var \MyParcelNL\WooCommerce\Migration\Migrator $migrator */
            $migrator = Pdk::get(Migrator::class);

            $migrator->migrate($installedVersion);

            // new version number
            update_option($versionSetting, $this->version);
        }
    }

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

        return isset($sitePlugins['woocommerce/woocommerce.php'])
            || in_array('woocommerce/woocommerce.php', $blogPlugins, true);
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
}

new MyParcelNL();
