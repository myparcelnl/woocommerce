<?php

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\includes\Webhooks\Hooks;

defined('ABSPATH') or die();

use MyParcelNL\Sdk\src\Services\Web\Webhook\ShopCarrierAccessibilityUpdatedWebhookWebService;
use MyParcelNL\Sdk\src\Services\Web\Webhook\ShopCarrierConfigurationUpdatedWebhookWebService;
use MyParcelNL\Sdk\src\Services\Web\Webhook\ShopUpdatedWebhookWebService;
use MyParcelNL\WooCommerce\includes\admin\Messages;
use MyParcelNL\WooCommerce\includes\Concerns\HasApiKey;
use MyParcelNL\WooCommerce\includes\Concerns\HasInstance;
use MyParcelNL\WooCommerce\includes\Settings\Api\AccountSettingsService;
use MyParcelNL\WooCommerce\includes\Webhook\Service\WebhookSubscriptionService;
use WCMYPA_Admin;

class AccountSettingsWebhook
{
    use HasApiKey;
    use HasInstance;

    /**
     * Webhooks that should refresh the account settings when triggered.
     *
     * @var class-string[]
     */
    public const ACCOUNTSETTINGS_WEBHOOKS = [
        ShopCarrierAccessibilityUpdatedWebhookWebService::class,
        ShopCarrierConfigurationUpdatedWebhookWebService::class,
        ShopUpdatedWebhookWebService::class,
    ];

    /**
     * @var bool
     */
    public $useManualUpdate = false;

    /**
     * @throws \Exception
     */
    public function __construct()
    {
        if (! $this->validateWebhooksUsage()) {
            return;
        }

        $accountSettingsServiceClass = AccountSettingsService::getInstance();
        $callback                    = [$accountSettingsServiceClass, 'restRefreshSettingsFromApi'];
        $webhookSubscriptionService  = new WebhookSubscriptionService();

        foreach (self::ACCOUNTSETTINGS_WEBHOOKS as $webhookClass) {
            $service = (new $webhookClass())->setApiKey($this->ensureHasApiKey());
            $webhookSubscriptionService->create($service, $callback);
        }
    }

    /**
     * @return bool
     */
    public function validateWebhooksUsage(): bool
    {
        if (! WCMYPA_Admin::canUseWebhooks()) {
            Messages::showAdminNotice(
                __('setting_account_settings_manual_update_hint', 'woocommerce-myparcel'),
                Messages::NOTICE_LEVEL_WARNING
            );
            $this->useManualUpdate = true;
            return false;
        }
        return true;
    }

    /**
     * @return bool
     */
    public function useManualUpdate(): bool
    {
        return $this->useManualUpdate;
    }
}
