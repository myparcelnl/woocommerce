<?php

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Service;

use MyParcelNL\Pdk\Facade\Pdk;
use MyParcelNL\WooCommerce\Hooks\AddressWidgetHooks;
use MyParcelNL\WooCommerce\Hooks\AutomaticOrderExportHooks;
use MyParcelNL\WooCommerce\Hooks\BlocksIntegrationHooks;
use MyParcelNL\WooCommerce\Hooks\CartFeesHooks;
use MyParcelNL\WooCommerce\Hooks\CheckoutScriptHooks;
use MyParcelNL\WooCommerce\Hooks\Contract\WooCommerceInitCallbacksInterface;
use MyParcelNL\WooCommerce\Hooks\Contract\WordPressHooksInterface;
use MyParcelNL\WooCommerce\Hooks\OrderNotesHooks;
use MyParcelNL\WooCommerce\Hooks\PluginInfoHooks;
use MyParcelNL\WooCommerce\Hooks\RanWebhookActions;
use MyParcelNL\WooCommerce\Hooks\ScheduledMigrationHooks;
use MyParcelNL\WooCommerce\Hooks\SeparateAddressFieldsHooks;
use MyParcelNL\WooCommerce\Hooks\TaxFieldsHooks;
use MyParcelNL\WooCommerce\Hooks\TrackTraceHooks;
use MyParcelNL\WooCommerce\Hooks\WebhookActions;
use MyParcelNL\WooCommerce\Pdk\Hooks\PdkAdminEndpointHooks;
use MyParcelNL\WooCommerce\Pdk\Hooks\PdkCheckoutPlaceOrderHooks;
use MyParcelNL\WooCommerce\Pdk\Hooks\PdkCoreHooks;
use MyParcelNL\WooCommerce\Pdk\Hooks\PdkFrontendEndpointHooks;
use MyParcelNL\WooCommerce\Pdk\Hooks\PdkOrderHooks;
use MyParcelNL\WooCommerce\Pdk\Hooks\PdkOrderListHooks;
use MyParcelNL\WooCommerce\Pdk\Hooks\PdkPluginSettingsHooks;
use MyParcelNL\WooCommerce\Pdk\Hooks\PdkProductSettingsHooks;
use MyParcelNL\WooCommerce\Pdk\Hooks\PdkWebhookHooks;
use RuntimeException;

final class WordPressHookService
{
    /**
     * @return void
     */
    public function applyAll(): void
    {
        foreach ($this->getHooks() as $service) {
            /** @var \MyParcelNL\WooCommerce\Hooks\Contract\WordPressHooksInterface $instance */
            $instance = Pdk::get($service);

            if (! $instance instanceof WordPressHooksInterface) {
                throw new RuntimeException("Service {$service} does not implement WordPressHooksInterface");
            }

            $instance->apply();
        }
    }

    public function onInit(): void
    {
        foreach ($this->getInitHooks() as $service) {
            /** @var \MyParcelNL\WooCommerce\Hooks\Contract\WooCommerceInitCallbacksInterface $instance */
            $instance = Pdk::get($service);

            if (! $instance instanceof WooCommerceInitCallbacksInterface) {
                throw new RuntimeException("Service {$service} does not implement WooCommerceInitCallbacksInterface");
            }

            $instance->onWoocommerceInit();
        }
    }

    /**
     * @return class-string<\MyParcelNL\WooCommerce\Hooks\Contract\WordPressHooksInterface>[]
     */
    private function getHooks(): array
    {
        return [
            AddressWidgetHooks::class,
            AutomaticOrderExportHooks::class,
            BlocksIntegrationHooks::class,
            CartFeesHooks::class,
            CheckoutScriptHooks::class,
            OrderNotesHooks::class,
            PdkAdminEndpointHooks::class,
            PdkCheckoutPlaceOrderHooks::class,
            PdkCoreHooks::class,
            PdkFrontendEndpointHooks::class,
            PdkOrderHooks::class,
            PdkOrderListHooks::class,
            PdkPluginSettingsHooks::class,
            PdkProductSettingsHooks::class,
            PdkWebhookHooks::class,
            PluginInfoHooks::class,
            RanWebhookActions::class,
            ScheduledMigrationHooks::class,
            SeparateAddressFieldsHooks::class,
            TaxFieldsHooks::class,
            TrackTraceHooks::class,
            WebhookActions::class,
        ];
    }

    /**
     * @return class-string<\MyParcelNL\WooCommerce\Hooks\Contract\WooCommerceInitCallbacksInterface>[]
     */
    private function getInitHooks(): array
    {
        return [
            SeparateAddressFieldsHooks::class,
        ];
    }
}
