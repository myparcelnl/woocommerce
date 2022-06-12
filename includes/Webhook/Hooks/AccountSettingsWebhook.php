<?php

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\includes\Webhooks\Hooks;

defined('ABSPATH') or die();

use MyParcelNL\Pdk\Repository\AccountSettingsRepository;
use MyParcelNL\Sdk\src\Services\Web\Webhook\ShopCarrierAccessibilityUpdatedWebhookWebService;
use MyParcelNL\Sdk\src\Services\Web\Webhook\ShopCarrierConfigurationUpdatedWebhookWebService;
use MyParcelNL\Sdk\src\Services\Web\Webhook\ShopUpdatedWebhookWebService;
use MyParcelNL\WooCommerce\includes\admin\Messages;
use MyParcelNL\WooCommerce\includes\Settings\Api\AccountSettings;
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
        WCMYPA()->pdk()->get(AccountSettingsRepository::class)->refreshFromApi();

        $response = new WP_REST_Response();
        $response->set_status(200);

        if (! AccountSettings::getInstance()->isValid()) {
            $response->set_status(400);
        }

        return $response;
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
