<?php
/** @noinspection AutoloadingIssuesInspection */

declare(strict_types=1);

/*
Plugin Name: MyParcelNL WooCommerce
Plugin URI: https://github.com/myparcelnl/woocommerce
Description: Export your WooCommerce orders to MyParcel and print labels directly from the WooCommerce admin
Author: MyParcel
Author URI: https://myparcel.nl
Version: 5.0.0-alpha.15
License: MIT
License URI: http://www.opensource.org/licenses/mit-license.php
*/

use MyParcelNL\Pdk\Facade\Installer;
use MyParcelNL\Pdk\Facade\Pdk;
use MyParcelNL\WooCommerce\Pdk\WcPdkBootstrapper;
use MyParcelNL\WooCommerce\Service\WordPressHookService;

require(plugin_dir_path(__FILE__) . 'vendor/autoload.php');

final class MyParcelNLWooCommerce
{
    /**
     * @throws \Throwable
     */
    public function __construct()
    {
        $version = $this->getVersion();

        WcPdkBootstrapper::boot(
            'myparcelnl',
            'MyParcel',
            $version,
            plugin_dir_path(__FILE__),
            plugin_dir_url(__FILE__)
        );

        define('MYPARCELNL_WC_VERSION', $version);

        if (! defined('DOING_AJAX') && is_admin()) {
            add_action('init', [$this, 'install']);
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

        /** @var WordPressHookService $hookService */
        $hookService = Pdk::get(WordPressHookService::class);
        $hookService->applyAll();
    }

    /**
     * @return void
     * @throws \Exception
     */
    public function install(): void
    {
        Installer::install();
    }

    /**
     * @return bool
     */
    private function checkPrerequisites(): bool
    {
        return $this->isWoocommerceActivated() && $this->phpVersionMeets();
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
        $minimumPhpVersion = Pdk::get('minimumPhpVersion');

        if (version_compare(PHP_VERSION, $minimumPhpVersion, '>=')) {
            return true;
        }

        return false;
    }
}

new MyParcelNLWooCommerce();
