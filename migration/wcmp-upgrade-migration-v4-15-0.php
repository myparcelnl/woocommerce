<?php

use migration\WCMP_Upgrade_Migration;
use MyParcelNL\WooCommerce\includes\Settings\Api\AccountSettingsService;

if (! defined('ABSPATH')) {
    exit;
}

if (class_exists('WCMP_Upgrade_Migration_v4_15_0')) {
    return new WCMP_Upgrade_Migration_v4_15_0();
}

/**
 * Load account settings once during upgrade because Instabox is removed
 */
class WCMP_Upgrade_Migration_v4_15_0 extends WCMP_Upgrade_Migration
{
    public function __construct()
    {
        parent::__construct();
    }

    protected function import(): void
    {
        require_once(WCMYPA()->plugin_path() . '/vendor/autoload.php');
        require_once(WCMYPA()->plugin_path() . '/includes/admin/settings/class-wcmypa-settings.php');
    }

    protected function log(string $message): void
    {

    }

    protected function migrate(): void
    {
        $apiKey = $this->getSettings('woocommerce_myparcel_general_settings')[WCMYPA_Settings::SETTING_API_KEY] ?? null;

        if (! $apiKey) {
            return;
        }

        try {
            AccountSettingsService::getInstance()->refreshSettingsFromApi($apiKey);
        } catch(\Throwable $thrown) {
            // nope
        }
    }
}

return new WCMP_Upgrade_Migration_v4_15_0();
