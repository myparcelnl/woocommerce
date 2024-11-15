<?php

declare(strict_types=1);

namespace MyParcelNL\WooCommerce;

use MyParcelNL\Pdk\Base\Pdk as PdkInstance;
use MyParcelNL\Pdk\Facade\Installer;
use MyParcelNL\Pdk\Facade\Pdk;
use MyParcelNL\WooCommerce\Facade\WooCommerce;
use MyParcelNL\WooCommerce\Hooks\AutomaticOrderExportHooks;
use MyParcelNL\WooCommerce\Hooks\BlocksIntegrationHooks;
use MyParcelNL\WooCommerce\Hooks\CartFeesHooks;
use MyParcelNL\WooCommerce\Hooks\CheckoutScriptHooks;
use MyParcelNL\WooCommerce\Hooks\OnWcBlocksCheckoutBlockRegistrationHooks;
use MyParcelNL\WooCommerce\Hooks\OnWcBlocksLoadedHooks;
use MyParcelNL\WooCommerce\Hooks\OrderNotesHooks;
use MyParcelNL\WooCommerce\Hooks\PluginInfoHooks;
use MyParcelNL\WooCommerce\Hooks\RanWebhookActionsHooks;
use MyParcelNL\WooCommerce\Hooks\ScheduledMigrationHooks;
use MyParcelNL\WooCommerce\Hooks\TrackTraceHooks;
use MyParcelNL\WooCommerce\Hooks\WcSeparateAddressFieldsHooks;
use MyParcelNL\WooCommerce\Hooks\WcTaxFieldsHooks;
use MyParcelNL\WooCommerce\Hooks\WebhookActionsHooks;
use MyParcelNL\WooCommerce\Pdk\Hooks\PdkAdminEndpointHooks;
use MyParcelNL\WooCommerce\Pdk\Hooks\PdkCheckoutPlaceOrderHooks;
use MyParcelNL\WooCommerce\Pdk\Hooks\PdkCoreHooks;
use MyParcelNL\WooCommerce\Pdk\Hooks\PdkFrontendEndpointHooks;
use MyParcelNL\WooCommerce\Pdk\Hooks\PdkOrderHooks;
use MyParcelNL\WooCommerce\Pdk\Hooks\PdkOrderListHooks;
use MyParcelNL\WooCommerce\Pdk\Hooks\PdkPluginSettingsHooks;
use MyParcelNL\WooCommerce\Pdk\Hooks\PdkProductSettingsHooks;
use MyParcelNL\WooCommerce\Pdk\Hooks\PdkWebhookHooks;
use MyParcelNL\WooCommerce\Pdk\WcPdkBootstrapper;
use MyParcelNL\WooCommerce\Tests\Mock\MockWcPdkBootstrapper;
use Throwable;

final class PluginLoader
{
    private const WP_HOOK_INIT = 'init';
    /**
     * @type class-string<\MyParcelNL\WooCommerce\Hooks\Contract\WordPressHooksInterface>[]
     */
    private const PLUGIN_HOOKS = [
        AutomaticOrderExportHooks::class    => self::WP_HOOK_INIT,
        BlocksIntegrationHooks::class       => self::WP_HOOK_INIT,
        CartFeesHooks::class                => self::WP_HOOK_INIT,
        CheckoutScriptHooks::class          => self::WP_HOOK_INIT,
        OrderNotesHooks::class              => self::WP_HOOK_INIT,
        PdkAdminEndpointHooks::class        => self::WP_HOOK_INIT,
        PdkCheckoutPlaceOrderHooks::class   => self::WP_HOOK_INIT,
        PdkCoreHooks::class                 => self::WP_HOOK_INIT,
        PdkFrontendEndpointHooks::class     => self::WP_HOOK_INIT,
        PdkOrderHooks::class                => self::WP_HOOK_INIT,
        PdkOrderListHooks::class            => self::WP_HOOK_INIT,
        PdkPluginSettingsHooks::class       => self::WP_HOOK_INIT,
        PdkProductSettingsHooks::class      => self::WP_HOOK_INIT,
        PdkWebhookHooks::class              => self::WP_HOOK_INIT,
        PluginInfoHooks::class              => self::WP_HOOK_INIT,
        RanWebhookActionsHooks::class       => self::WP_HOOK_INIT,
        ScheduledMigrationHooks::class      => self::WP_HOOK_INIT,
        WcSeparateAddressFieldsHooks::class => self::WP_HOOK_INIT,
        WcTaxFieldsHooks::class             => self::WP_HOOK_INIT,
        TrackTraceHooks::class              => self::WP_HOOK_INIT,
        WebhookActionsHooks::class          => self::WP_HOOK_INIT,

        OnWcBlocksCheckoutBlockRegistrationHooks::class => 'woocommerce_blocks_checkout_block_registration',
        OnWcBlocksLoadedHooks::class                    => 'woocommerce_blocks_loaded',

    ];

    /**
     * @param  class-string<\MyParcelNL\WooCommerce\Hooks\Contract\WordPressHooksInterface> $service
     *
     * @return void
     */
    public function applyHook(string $service): void
    {
        $this->initializePdk();

        /** @var \MyParcelNL\WooCommerce\Hooks\Contract\WordPressHooksInterface $instance */
        $instance = Pdk::get($service);
        $instance->apply();
    }

    /**
     * @return void
     */
    public function initialize(): void
    {
        $this->setup();

        $errors = $this->checkPrerequisites();

        if (! empty($errors)) {
            $this->handleFatalError($errors);
        }
    }

    /**
     * @return void
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
     * @return void
     */
    public function load(): void
    {
        register_activation_hook(constant('MYPARCELNL_FILE'), [$this, 'install']);
        register_deactivation_hook(constant('MYPARCELNL_FILE'), [$this, 'uninstall']);

        add_action('init', [$this, 'initialize']);

        $this->applyHooks();
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
     * Perform required tasks that initialize the plugin.
     */
    private function applyHooks(): void
    {
        foreach (self::PLUGIN_HOOKS as $service => $hook) {
            if (! $hook) {
                $this->applyHook($service);
                continue;
            }

            add_action($hook, function () use ($service) {
                $this->applyHook($service);
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
     * @noinspection OneTimeUseVariablesInspection
     */
    private function getVersionFromComposer(): string
    {
        $composerJsonPath = sprintf('%s/composer.json', plugin_dir_path(constant('MYPARCELNL_FILE')));
        $json             = json_decode(file_get_contents($composerJsonPath), false);

        return $json->version;
    }

    /**
     * Report a fatal error and gracefully disable the plugin.
     *
     * @param  array $errors
     */
    private function handleFatalError(array $errors): void
    {
        add_action('admin_init', static function () use ($errors) {
            add_action('admin_notices', static function () use ($errors) {
                echo sprintf('<div class="error"><p>%s</p></div>', implode('<br>', $errors));
            });

            deactivate_plugins(plugin_basename(constant('MYPARCELNL_FILE')));
        });
    }

    /**
     * @return void
     */
    private function initializePdk(): void
    {
        // TODO: find a way to make this work without having this in production code
        $bootstrapper = defined('MYPARCELNL_PEST') ? MockWcPdkBootstrapper::class : WcPdkBootstrapper::class;

        if ($bootstrapper::isBooted()) {
            return;
        }

        try {
            $bootstrapper::boot(
                'myparcelnl',
                'MyParcel',
                $this->getVersionFromComposer(),
                plugin_dir_path(constant('MYPARCELNL_FILE')),
                plugin_dir_url(constant('MYPARCELNL_FILE')),
                constant('WP_DEBUG') ? PdkInstance::MODE_DEVELOPMENT : PdkInstance::MODE_PRODUCTION
            );
        } catch (Throwable $e) {
            $this->handleFatalError([$e->getMessage()]);
        }
    }

    /**
     * @return void
     */
    private function setup(): void
    {
        $this->initializePdk();

        if (! defined('MYPARCELNL_WC_VERSION')) {
            define('MYPARCELNL_WC_VERSION', Pdk::getAppInfo()->version);
        }
    }
}
