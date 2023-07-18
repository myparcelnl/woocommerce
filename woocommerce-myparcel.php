<?php
/** @noinspection AutoloadingIssuesInspection */

declare(strict_types=1);

/*
Plugin Name: MyParcelNL WooCommerce
Plugin URI: https://github.com/myparcelnl/woocommerce
Description: Export your WooCommerce orders to MyParcel and print labels directly from the WooCommerce admin
Author: MyParcel
Author URI: https://myparcel.nl
Version: 5.0.0-alpha.16
License: MIT
License URI: http://www.opensource.org/licenses/mit-license.php
*/

use MyParcelNL\Pdk\Base\Pdk as PdkInstance;
use MyParcelNL\Pdk\Facade\Installer;
use MyParcelNL\Pdk\Facade\Pdk;
use MyParcelNL\WooCommerce\Facade\WooCommerce;
use MyParcelNL\WooCommerce\Pdk\WcPdkBootstrapper;
use MyParcelNL\WooCommerce\Service\WordPressHookService;
use function DI\value;

require(plugin_dir_path(__FILE__) . 'vendor/autoload.php');

final class MyParcelNLWooCommerce
{
    /**
     * @throws \Throwable
     */
    public function __construct()
    {
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
        $this->boot();

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
        $this->boot();

        Installer::install();
    }

    /**
     * @return void
     * @throws \Exception
     */
    private function boot(): void
    {
        $version = $this->getVersion();

        WcPdkBootstrapper::setAdditionalConfig([
            'pluginBasename' => value(plugin_basename(__FILE__)),
        ]);

        WcPdkBootstrapper::boot(
            'myparcelnl',
            'MyParcel',
            $version,
            plugin_dir_path(__FILE__),
            plugin_dir_url(__FILE__),
            constant('WP_DEBUG')
                ? PdkInstance::MODE_DEVELOPMENT
                : PdkInstance::MODE_PRODUCTION
        );

        $this->checkPrerequisites();

        if (! defined('MYPARCELNL_WC_VERSION')) {
            define('MYPARCELNL_WC_VERSION', $version);
        }
    }

    /**
     * Check if the minimum requirements are met and deactivate the plugin if not.
     *
     * @return void
     */
    private function checkPrerequisites(): void
    {
        $appInfo = Pdk::getAppInfo();
        $errors  = [];

        if (! Pdk::get('isPhpVersionSupported')) {
            $errors[] = sprintf('%s requires PHP %s or higher.', $appInfo->title, Pdk::get('minimumPhpVersion'));
        }

        if (! WooCommerce::isActive() || ! Pdk::get('isWooCommerceVersionSupported')) {
            $errors[] = sprintf(
                '%s requires WooCommerce %s or higher.',
                $appInfo->title,
                Pdk::get('minimumWooCommerceVersion')
            );
        }

        if (! empty($errors)) {
            deactivate_plugins(plugin_basename(__FILE__));
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
}

new MyParcelNLWooCommerce();
