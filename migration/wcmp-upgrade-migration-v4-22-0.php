<?php

declare(strict_types=1);

use migration\WCMP_Upgrade_Migration;
use MyParcelNL\Sdk\src\Model\Carrier\CarrierPostNL;

if (! defined('ABSPATH')) {
    exit;
}

if (class_exists('WCMP_Upgrade_Migration_v4_22_0')) {
    return new WCMP_Upgrade_Migration_v4_22_0();
}

/**
 * Migrates pre v4.22.0 settings
 *  - move digital stamp weight ranges 50-100g adn 100-350g to 50-350g
 */
class WCMP_Upgrade_Migration_v4_22_0 extends WCMP_Upgrade_Migration
{
    /**
     * @var array
     */
    private $newCheckoutSettings = [];

    /**
     * @var array
     */
    private $newDhlEuroplusSettings;

    /**
     * @var array
     */
    private $newDhlForYouSettings;

    /**
     * @var array
     */
    private $newDhlParcelConnectSettings;

    /**
     * @var array
     */
    private $newGeneralSettings = [];

    /**
     * @var array
     */
    private $newPostNlSettings = [];

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * @return void
     */
    protected function import(): void
    {
        require_once(WCMYPA()->plugin_path() . '/vendor/autoload.php');
        require_once(WCMYPA()->plugin_path() . '/includes/admin/settings/class-wcmypa-settings.php');
        require_once(WCMYPA()->plugin_path() . '/includes/class-wcmp-data.php');
    }

    /**
     * @return void
     */
    protected function migrate(): void
    {
        $this->newGeneralSettings          = $this->getSettings('woocommerce_myparcel_general_settings');
        $this->newCheckoutSettings         = $this->getSettings('woocommerce_myparcel_checkout_settings');
        $this->newPostNlSettings           = $this->getSettings('woocommerce_myparcel_postnl_settings');
        $this->newDhlForYouSettings        = $this->getSettings('woocommerce_myparcel_dhlforyou_settings');
        $this->newDhlParcelConnectSettings = $this->getSettings('woocommerce_myparcel_dhlparcelconnect_settings');
        $this->newDhlEuroplusSettings      = $this->getSettings('woocommerce_myparcel_dhleuroplus_settings');

        $this->migrateCarrierSettings();
    }

    /**
     * @return void
     */
    protected function migrateCarrierSettings(): void
    {
        $keyDpzDefaultWeight =
            sprintf('%s_%s', CarrierPostNL::NAME, WCMYPA_Settings::SETTING_CARRIER_DIGITAL_STAMP_DEFAULT_WEIGHT);

        if (in_array($this->newPostNlSettings[$keyDpzDefaultWeight], [75, 225], true)) {
            $this->newPostNlSettings[$keyDpzDefaultWeight] = 200;
        }
    }

    /**
     * @return void
     */
    protected function setOptionSettingsMap(): void
    {
        $this->optionSettingsMap = [
            'woocommerce_myparcel_general_settings'          => $this->newGeneralSettings,
            'woocommerce_myparcel_checkout_settings'         => $this->newCheckoutSettings,
            'woocommerce_myparcel_postnl_settings'           => $this->newPostNlSettings,
            'woocommerce_myparcel_dhlforyou_settings'        => $this->newDhlForYouSettings,
            'woocommerce_myparcel_dhleuroplus_settings'      => $this->newDhlEuroplusSettings,
            'woocommecre_myparcel_dhlparcelconnect_settings' => $this->newDhlParcelConnectSettings,
        ];
    }
}

return new WCMP_Upgrade_Migration_v4_22_0();
