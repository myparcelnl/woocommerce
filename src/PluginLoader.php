<?php

declare(strict_types=1);

namespace MyParcelNL\WooCommerce;

use Automattic\WooCommerce\Blocks\Integrations\IntegrationRegistry;
use MyParcelNL\Pdk\Facade\Installer;
use MyParcelNL\Pdk\Facade\Pdk;
use MyParcelNL\WooCommerce\Facade\WooCommerce;
use MyParcelNL\WooCommerce\Integration\WcBlocksLoader;
use MyParcelNL\WooCommerce\Service\WordPressHookService;

class PluginLoader
{
    public function __construct()
    {
        add_action('init', [$this, 'initialize'], 9999);

        // This cannot be done after the init hook
        add_action('woocommerce_blocks_checkout_block_registration', [$this, 'registerWcCheckoutBlocks']);
    }

    /**
     * Perform required tasks that initialize the plugin.
     *
     * @throws \Throwable
     */
    public function initialize(): void
    {
        $this->setup();

        /** @var WordPressHookService $hookService */
        $hookService = Pdk::get(WordPressHookService::class);
        $hookService->applyAll();
    }

    /**
     * @return void
     * @throws \MyParcelNL\WooCommerce\Tests\Exception\DieException
     */
    public function install(): void
    {
        $this->setup();

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
    public function registerWcCheckoutBlocks(IntegrationRegistry $integrationRegistry): void
    {
        initializePdk();

        /** @var \MyParcelNL\WooCommerce\Integration\WcBlocksLoader $loader */
        $loader = Pdk::get(WcBlocksLoader::class);
        $loader->setRegistry($integrationRegistry);
        $loader->registerBlocks(Pdk::get('wooCommerceBlocksCheckout'));
    }

    /**
     * @return void
     */
    public function uninstall(): void
    {
        $this->setup();

        Installer::uninstall();
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
     * @return void
     */
    private function setup(): void
    {
        initializePdk();

        if (! defined('MYPARCELNL_WC_VERSION')) {
            define('MYPARCELNL_WC_VERSION', Pdk::getAppInfo()->version);
        }

        $errors = $this->checkPrerequisites();

        if (empty($errors)) {
            return;
        }

        handleFatalError($errors);
    }
}
