<?php
/** @noinspection AutoloadingIssuesInspection */

declare(strict_types=1);

/*
Plugin Name: MyParcel WooCommerce
Plugin URI: https://myparcel.nl/
Description: Export your WooCommerce orders to MyParcel and print labels directly from the WooCommerce admin
Author: MyParcel
Author URI: https://myparcel.nl
Version: 5.0.0-alpha.0

License: MIT
License URI: http://www.opensource.org/licenses/mit-license.php
*/

use MyParcelNL\Pdk\Facade\Pdk;
use MyParcelNL\WooCommerce\Facade\Messages;
use MyParcelNL\WooCommerce\Migration\Migrator;
use MyParcelNL\WooCommerce\Pdk\WcPdkBootstrapper;
use MyParcelNL\WooCommerce\Service\WordPressHookService;

require(plugin_dir_path(__FILE__) . 'vendor/autoload.php');

class MyParcelNL
{
    private const PHP_VERSION_MINIMUM = '7.1';

    /**
     * @var string
     */
    private $version;

    /**
     * @throws \Throwable
     */
    public function __construct()
    {
        $this->version = $this->getVersion();

        WcPdkBootstrapper::boot(
            'myparcelnl',
            'MyParcel WooCommerce',
            $this->version,
            plugin_dir_path(__FILE__),
            plugin_dir_url(__FILE__)
        );

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

        /** @var WordPressHookService $hookService */
        $hookService = Pdk::get(WordPressHookService::class);
        $hookService->applyAll();
    }

    public function upgrade(): void
    {
        $versionSetting   = 'woocommerce_myparcel_version';
        $installedVersion = get_option($versionSetting) ?: '0';

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
            && $this->phpVersionMeets();
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
     * @return bool
     */
    private function phpVersionMeets(): bool
    {
        if (version_compare(PHP_VERSION, self::PHP_VERSION_MINIMUM, '>=')) {
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
