<?php

declare(strict_types=1);

use migration\WCMP_Upgrade_Migration;
use MyParcelNL\Sdk\src\Model\Carrier\CarrierPostNL;

if (! defined('ABSPATH')) {
    exit;
}

if (class_exists('WCMP_Upgrade_Migration_v4_4_1')) {
    return new WCMP_Upgrade_Migration_v4_4_1();
}

/**
 * Migrates pre v4.4.1 settings
 *  - move show delivery day setting from checkout to general
 *  - add per carrier feature allow show delivery date, set to on when delivery days window > 0, else off
 */
class WCMP_Upgrade_Migration_v4_4_1 extends WCMP_Upgrade_Migration
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
        $keyPostNl                 = CarrierPostNL::NAME;
        $keyDeliveryDaysWindow     = $keyPostNl . '_' . WCMYPA_Settings::SETTING_CARRIER_DELIVERY_DAYS_WINDOW;
        $keyAllowShowDeliveryDate  = $keyPostNl . '_' . WCMYPA_Settings::SETTING_CARRIER_ALLOW_SHOW_DELIVERY_DATE;
        $settingDeliveryDaysWindow = $this->newPostNlSettings[$keyDeliveryDaysWindow] ?? '1';

        if ('0' === $settingDeliveryDaysWindow) {
            $this->newPostNlSettings[$keyDeliveryDaysWindow]    = '1';
            $this->newPostNlSettings[$keyAllowShowDeliveryDate] = WCMP_Settings_Data::DISABLED;
        } else {
            $this->newPostNlSettings[$keyAllowShowDeliveryDate] = WCMP_Settings_Data::ENABLED;
        }
    }
}

return new WCMP_Upgrade_Migration_v4_4_1();
