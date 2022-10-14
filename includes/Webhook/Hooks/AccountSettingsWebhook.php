<?php

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\includes\Webhooks\Hooks;

defined('ABSPATH') or die();

use MyParcelNL\Sdk\src\Services\Web\Webhook\ShopCarrierAccessibilityUpdatedWebhookWebService;
use MyParcelNL\Sdk\src\Services\Web\Webhook\ShopCarrierConfigurationUpdatedWebhookWebService;
use MyParcelNL\Sdk\src\Services\Web\Webhook\ShopUpdatedWebhookWebService;
use MyParcelNL\WooCommerce\includes\admin\Messages;
use MyParcelNL\WooCommerce\includes\Settings\Api\AccountSettingsService;
use MyParcelNL\WooCommerce\includes\Webhook\Hooks\AbstractWebhook;
use WCMYPA_Admin;
use WP_REST_Request;
use WP_REST_Response;

class AccountSettingsWebhook extends AbstractWebhook
{
    /**
     * @var array
     */
    public const ACCOUNT_SETTINGS_WEBHOOKS = [
        ShopCarrierAccessibilityUpdatedWebhookWebService::class,
        ShopCarrierConfigurationUpdatedWebhookWebService::class,
        ShopUpdatedWebhookWebService::class,
    ];

    /**
     * @param  \WP_REST_Request $request
     *
     * @return \WP_REST_Response
     */
    public function getCallback(WP_REST_Request $request): WP_REST_Response
    {
        return AccountSettingsService::getInstance()
            ->restRefreshSettingsFromApi();
    }

    /**
     * @return bool
     */
    public function validate(): bool
    {
        if (! WCMYPA_Admin::canUseWebhooks()) {
            Messages::showAdminNotice(
                __('setting_account_settings_manual_update_hint', 'woocommerce-myparcel'),
                Messages::NOTICE_LEVEL_WARNING
            );

            return false;
        }

        return parent::validate();
    }

    /**
     * @return class-string<\MyParcelNL\Sdk\src\Services\Web\Webhook\AbstractWebhookWebService>[]
     */
    protected function getHooks(): array
    {
        return self::ACCOUNT_SETTINGS_WEBHOOKS;
    }
}
