<?php

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Service;

use MyParcelNL\Pdk\Facade\Pdk;
use MyParcelNL\WooCommerce\Hooks\AutomaticOrderExportHooks;
use MyParcelNL\WooCommerce\Hooks\CartFeesHooks;
use MyParcelNL\WooCommerce\Hooks\CheckoutScriptHooks;
use MyParcelNL\WooCommerce\Hooks\Contract\WordPressHooksInterface;
use MyParcelNL\WooCommerce\Hooks\PdkAdminEndpointHooks;
use MyParcelNL\WooCommerce\Hooks\PdkWebhookHooks;
use MyParcelNL\WooCommerce\Hooks\ScheduledMigrationHooks;
use MyParcelNL\WooCommerce\Hooks\SeparateAddressFieldsHooks;
use MyParcelNL\WooCommerce\Hooks\TaxFieldsHooks;
use MyParcelNL\WooCommerce\Hooks\TrackTraceHooks;
use MyParcelNL\WooCommerce\Pdk\Hooks\PdkCoreHooks;
use MyParcelNL\WooCommerce\Pdk\Hooks\PdkOrderHooks;
use MyParcelNL\WooCommerce\Pdk\Hooks\PdkOrderListHooks;
use MyParcelNL\WooCommerce\Pdk\Hooks\PdkPlaceOrderHooks;
use MyParcelNL\WooCommerce\Pdk\Hooks\PdkPluginSettingsHooks;
use MyParcelNL\WooCommerce\Pdk\Hooks\PdkProductSettingsHooks;
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

    /**
     * @return class-string<\MyParcelNL\WooCommerce\Hooks\Contract\WordPressHooksInterface>[]
     */
    private function getHooks(): array
    {
        return [
            AutomaticOrderExportHooks::class,
            CartFeesHooks::class,
            CheckoutScriptHooks::class,
            PdkAdminEndpointHooks::class,
            PdkCoreHooks::class,
            PdkOrderHooks::class,
            PdkOrderListHooks::class,
            PdkPlaceOrderHooks::class,
            PdkPluginSettingsHooks::class,
            PdkProductSettingsHooks::class,
            PdkWebhookHooks::class,
            ScheduledMigrationHooks::class,
            SeparateAddressFieldsHooks::class,
            TaxFieldsHooks::class,
            TrackTraceHooks::class,
        ];
    }
}
