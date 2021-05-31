<?php

use migration\WCMP_Upgrade_Migration;

if (! defined('ABSPATH')) {
    exit;
}

if (class_exists('WCMP_Upgrade_Migration_v4_3_2')) {
    return new WCMP_Upgrade_Migration_v4_3_2();
}

/**
 * Migrates pre v4.3.2 settings
 */
class WCMP_Upgrade_Migration_v4_3_2 extends WCMP_Upgrade_Migration
{
    /**
     * @var array
     */
    private $newGeneralSettings = [];

    /**
     * @var array
     */
    private $newCheckoutSettings = [];

    /**
     * @var array
     */
    private $newPostNlSettings = [];

    public function __construct()
    {
        parent::__construct();
    }

    protected function import(): void
    {
        require_once(WCMYPA()->plugin_path() . '/vendor/autoload.php');
        require_once(WCMYPA()->plugin_path() . '/includes/admin/settings/class-wcmypa-settings.php');
        require_once(WCMYPA()->plugin_path() . '/includes/class-wcmp-data.php');
    }

    protected function migrate(): void
    {
        $this->newGeneralSettings  = $this->getSettings('woocommerce_myparcel_general_settings');
        $this->newCheckoutSettings = $this->getSettings('woocommerce_myparcel_checkout_settings');
        $this->newPostNlSettings   = $this->getSettings('woocommerce_myparcel_postnl_settings');

        $this->migrateGeneralSettings();
        $this->migrateCarrierSettings();
    }

    protected function setOptionSettingsMap(): void
    {
        $this->optionSettingsMap = [
            'woocommerce_myparcel_general_settings'  => $this->newGeneralSettings,
            'woocommerce_myparcel_checkout_settings' => $this->newCheckoutSettings,
            'woocommerce_myparcel_postnl_settings'   => $this->newPostNlSettings,
        ];
    }

    private function migrateGeneralSettings(): void
    {
        $this->newGeneralSettings[WCMYPA_Settings::SETTING_SHOW_DELIVERY_DAY] =
            $this->newCheckoutSettings[WCMYPA_Settings::SETTING_SHOW_DELIVERY_DAY] ?? WCMP_Settings_Data::ENABLED;

        unset($this->newCheckoutSettings[WCMYPA_Settings::SETTING_SHOW_DELIVERY_DAY]);
    }

    protected function migrateCarrierSettings(): void
    {
        $settingDeliveryDaysWindow    = WCMYPA_Settings::SETTINGS_POSTNL
            . '_'
            . WCMYPA_Settings::SETTING_CARRIER_DELIVERY_DAYS_WINDOW;
        $settingAllowShowDeliveryDate = WCMYPA_Settings::SETTINGS_POSTNL
            . '_'
            . WCMYPA_Settings::SETTING_CARRIER_ALLOW_SHOW_DELIVERY_DATE;

        $this->newPostNlSettings[$settingAllowShowDeliveryDate] =
            ('0' !== $this->newPostNlSettings[$settingDeliveryDaysWindow])
            ? WCMP_Settings_Data::ENABLED
            : WCMP_Settings_Data::DISABLED;
    }
}

return new WCMP_Upgrade_Migration_v4_3_2();
