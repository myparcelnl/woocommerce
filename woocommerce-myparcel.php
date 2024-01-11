<?php
/** @noinspection AutoloadingIssuesInspection */

declare(strict_types=1);

/*
Plugin Name: MyParcelNL WooCommerce
Plugin URI: https://github.com/myparcelnl/woocommerce
Description: Export your WooCommerce orders to MyParcel and print labels directly from the WooCommerce admin
Author: MyParcel
Author URI: https://myparcel.nl
Version: 5.0.0-beta.4
License: MIT
License URI: http://www.opensource.org/licenses/mit-license.php
*/

use Automattic\WooCommerce\Blocks\Integrations\IntegrationRegistry;
use MyParcelNL\Pdk\Base\Pdk as PdkInstance;
use MyParcelNL\Pdk\Facade\Installer;
use MyParcelNL\Pdk\Facade\Pdk;
use MyParcelNL\WooCommerce\Facade\WooCommerce;
use MyParcelNL\WooCommerce\Integration\WcBlocksLoader;
use MyParcelNL\WooCommerce\Service\WordPressHookService;
use function MyParcelNL\WooCommerce\bootPdk;

require(plugin_dir_path(__FILE__) . 'vendor/autoload.php');

final class MyParcelNLWooCommerce
{
    /**
     * @throws \Throwable
     */
    public function __construct()
    {
        register_activation_hook(__FILE__, [$this, 'install']);
        register_deactivation_hook(__FILE__, [$this, 'uninstall']);
        add_action('init', [$this, 'initialize'], 9999);
        add_action('woocommerce_blocks_checkout_block_registration', [$this, 'registerCheckoutBlocks']);
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

        $errors = $this->checkPrerequisites();

        if (! empty($errors)) {
            /** @noinspection ForgottenDebugOutputInspection */
            wp_die(implode('<br>', $errors), '', ['back_link' => true]);
        }

        Installer::install();
    }

    /**
     * @param  \Automattic\WooCommerce\Blocks\Integrations\IntegrationRegistry $integrationRegistry
     *
     * @return void
     * @throws \Throwable
     */
    public function registerCheckoutBlocks(IntegrationRegistry $integrationRegistry): void
    {
        $this->initialize();
        /** @var \MyParcelNL\WooCommerce\Integration\WcBlocksLoader $loader */
        $loader = Pdk::get(WcBlocksLoader::class);
        $loader->setRegistry($integrationRegistry);

        $loader->registerCheckoutBlocks();
    }

    /**
     * @return void
     * @throws \Exception
     */
    public function uninstall(): void
    {
        $this->boot();

        Installer::uninstall();
    }

    /**
     * @return void
     * @throws \Exception
     */
    private function boot(): void
    {
        $version = $this->getVersion();

        bootPdk(
            'myparcelnl',
            'MyParcel',
            $version,
            plugin_dir_path(__FILE__),
            plugin_dir_url(__FILE__),
            constant('WP_DEBUG')
                ? PdkInstance::MODE_DEVELOPMENT
                : PdkInstance::MODE_PRODUCTION
        );

        if (! defined('MYPARCELNL_WC_VERSION')) {
            define('MYPARCELNL_WC_VERSION', $version);
        }

        $errors = $this->checkPrerequisites();

        if (! empty($errors)) {
            add_action('admin_init', static function () use ($errors) {
                add_action('admin_notices', static function () use ($errors) {
                    echo sprintf('<div class="error"><p>%s</p></div>', implode('<br>', $errors));
                });

                deactivate_plugins(plugin_basename(__FILE__));
            });
        }
    }

    /**
     * Check if the minimum requirements are met.
     *
     * @return array
     */
    private function checkPrerequisites(): array
    {
        $errors = [];

        if (! Pdk::get('isPhpVersionSupported')) {
            $errors[] = Pdk::get('errorMessagePhpVersion');
        }

        if (! WooCommerce::isActive() || ! Pdk::get('isWooCommerceVersionSupported')) {
            $errors[] = Pdk::get('errorMessageWooCommerceVersion');
        }

        return $errors;
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
