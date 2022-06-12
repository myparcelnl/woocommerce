<?php

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\includes\Settings\Api;

defined('ABSPATH') or die();

use MyParcelNL\Pdk\Repository\AccountSettingsRepository;
use MyParcelNL\Sdk\src\Support\Collection;
use MyParcelNL\WooCommerce\includes\admin\Messages;
use MyParcelNL\WooCommerce\includes\Webhook\Service\WebhookSubscriptionService;

/**
 * Wrapper for Pdk AccountSettings.
 */
class AccountSettings
{
    /**
     * @return \MyParcelNL\Pdk\Model\AccountSettings
     */
    public static function getInstance(): \MyParcelNL\Pdk\Model\AccountSettings
    {
        try {
            return (WCMYPA()
                ->pdk()
                ->get(AccountSettingsRepository::class))->get();
        } catch (\Throwable $e) {
            Messages::showAdminNotice(
                __('error_settings_account_missing', 'woocommerce-myparcel'),
                Messages::NOTICE_LEVEL_WARNING
            );
        }

        return new \MyParcelNL\Pdk\Model\AccountSettings(new Collection([]));
    }

    /**
     * @return void
     */
    public static function removeSettings():void
    {
        WCMYPA()->pdk()->get(AccountSettingsRepository::class)->removeSettings();
        (new WebhookSubscriptionService())->deleteAll();
    }
}
