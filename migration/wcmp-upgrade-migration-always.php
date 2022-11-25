<?php

use migration\WCMP_Upgrade_Migration;
use MyParcelNL\WooCommerce\includes\Settings\Api\AccountSettingsService;

if (! defined('ABSPATH')) {
    exit;
}

if (class_exists('WCMP_Upgrade_Migration_always')) {
    return new WCMP_Upgrade_Migration_always();
}

/**
 * Load account settings once during upgrade
 */
class WCMP_Upgrade_Migration_always extends WCMP_Upgrade_Migration
{
    public function __construct()
    {
        parent::__construct();
    }

    protected function import(): void
    {
        require_once(WCMYPA()->plugin_path() . '/vendor/autoload.php');

        WCMYPA()->includes();
        WCMYPA()->initSettings();
    }

    protected function migrate(): void
    {
        $apiKey = $this->getSettings('woocommerce_myparcel_general_settings')[WCMYPA_Settings::SETTING_API_KEY] ?? null;

        if (! $apiKey) {
            return;
        }

        try {
            if (! AccountSettingsService::getInstance()->refreshSettingsFromApi($apiKey)) {
                WCMP_Log::add('Upgrade MyParcel plugin: could not refresh settings from api');
            }
        } catch(\Throwable $thrown) {
            WCMP_Log::add($thrown->getMessage());
        }
    }
}

return new WCMP_Upgrade_Migration_always();
