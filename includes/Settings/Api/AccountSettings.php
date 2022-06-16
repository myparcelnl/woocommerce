<?php

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\includes\Settings\Api;

defined('ABSPATH') or die();

use MyParcelNL\Sdk\src\Support\Collection;
use MyParcelNL\WooCommerce\includes\admin\Messages;
use MyParcelNL\WooCommerce\includes\Webhook\Service\WebhookSubscriptionService;

/**
 * Wrapper for Pdk AccountSettings.
 */
class AccountSettings
{
    /**
     * @return \MyParcelNL\Pdk\Account\Model\AccountSettings
     */
    public static function getInstance(): \MyParcelNL\Pdk\Account\Model\AccountSettings
    {
        return WCMYPA()->pdk()->get(new AccountSettingsHelper(), \MyParcelNL\Pdk\Account\Model\AccountSettings::class);
        try {
            return WCMYPA()->pdk()->get(new AccountSettingsHelper(), \MyParcelNL\Pdk\Account\Model\AccountSettings::class);
        } catch (\Throwable $e) {
            Messages::showAdminNotice(
                __('error_settings_account_missing', 'woocommerce-myparcel'),
                Messages::NOTICE_LEVEL_WARNING
            );
        }

        return new \MyParcelNL\Pdk\Account\Model\AccountSettings(new Collection([]));
    }

    /**
     * @return void
     */
    public static function removeSettings():void
    {
        //WCMYPA()->container->get(AccountSettingsRepository::class)->removeSettings();
        (new WebhookSubscriptionService())->deleteAll();
    }
}
