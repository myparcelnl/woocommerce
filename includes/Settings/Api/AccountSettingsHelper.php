<?php

namespace MyParcelNL\WooCommerce\includes\Settings\Api;

use WCMYPA_Settings;
use WPO\WC\MyParcel\Collections\SettingsCollection;

class AccountSettingsHelper implements \MyParcelNL\Pdk\Concerns\SomeInterface
{
    public function getStorageKey(): string
    {
        return 'woocommerce_myparcel_account_settings';
    }

    public function getApiKey(): string
    {
        return SettingsCollection::getInstance()->getByName(WCMYPA_Settings::SETTING_API_KEY);
    }
}
