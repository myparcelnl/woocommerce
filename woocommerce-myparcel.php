<?php

/** @noinspection AutoloadingIssuesInspection */

/*
 * Plugin Name: MyParcelNL
 * Plugin URI: https://github.com/myparcelnl/woocommerce
 * Description: Export your WooCommerce orders to MyParcel and print labels directly from the WooCommerce admin
 * Author: MyParcel
 * Author URI: https://www.myparcel.nl
 * Version: 5.4.2
 * License: MIT
 * License URI: https://opensource.org/license/mit
 * Requires Plugins: woocommerce
 */

declare(strict_types=1);

use Automattic\Jetpack\Constants;
use Automattic\WooCommerce\Blocks\Integrations\IntegrationRegistry;
use MyParcelNL\Pdk\Base\Pdk as PdkInstance;
use MyParcelNL\Pdk\Facade\Installer;
use MyParcelNL\Pdk\Facade\Pdk;
use MyParcelNL\WooCommerce\Facade\WooCommerce;
use MyParcelNL\WooCommerce\Integration\WcBlocksLoader;
use MyParcelNL\WooCommerce\Service\WooCommerceService;
use MyParcelNL\WooCommerce\Service\WordPressHookService;

use function MyParcelNL\WooCommerce\bootPdk;

require plugin_dir_path(__FILE__) . 'vendor/autoload.php';

final class MyParcelNLWooCommerce
{
    public const PLUGIN_NAMESPACE = 'myparcelnl';

    /**
     * @throws \Throwable
     */
    public function __construct()
    {
        register_activation_hook(__FILE__, [$this, 'install']);
        // Since wordpress 3.1 register_activation_hook is not called when a plugin is updated
        add_action('wp_loaded', [$this, 'upgrade']);

        register_deactivation_hook(__FILE__, [$this, 'uninstall']);
        add_action('init', [$this, 'initialize'], 9999);
        add_action('woocommerce_init', [$this, 'onWoocommerceInit'], 9999);
        add_action('woocommerce_blocks_checkout_block_registration', [$this, 'registerCheckoutBlocks']);
    }

    /**
     * Perform required tasks that initialize the plugin.
     *
     * @throws \Throwable
     */
    public function initialize(): void
    {
        /** @var WordPressHookService $hookService */
        $hookService = Pdk::get(WordPressHookService::class);
        $hookService->applyAll();
    }

    /**
    * Run code when WooCommerce is initialized.
    *
    * @throws \Throwable
    */
    public function onWoocommerceInit(): void
    {
        $this->boot(); // This seems to fire earlier than the init hook, so we need to boot the PDK here.

        /** @var WordPressHookService $hookService */
        $hookService = Pdk::get(WordPressHookService::class);
        $hookService->onInit();
    }

    /**
     * @return void
     * @throws \Exception
     */
    public function install(): void
    {
        $this->boot();

        // Prerequisites check also runs in boot() but here we want to stop on error rather than just show a notice.
        $errors = $this->checkPrerequisites();

        if (! empty($errors)) {
            /** @noinspection ForgottenDebugOutputInspection */
            wp_die(implode('<br>', $errors), '', ['back_link' => true]);
        }

        Installer::install();
    }

    /**
     * Run upgrade migrations
     * @return void
     * @throws Exception
     */
    public function upgrade(): void
    {
        // The install function will check whether we are installing a new plugin or upgrading an existing one and run the appropiate migrations.
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
        $loader->registerBlocks(Pdk::get('wooCommerceBlocksCheckout'));
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
            self::PLUGIN_NAMESPACE,
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
