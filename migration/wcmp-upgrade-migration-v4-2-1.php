<?php

use migration\WCMP_Upgrade_Migration;

if (! defined('ABSPATH')) {
    exit;
}

if (class_exists('WCMP_Upgrade_Migration_v4_2_1')) {
    return new WCMP_Upgrade_Migration_v4_2_1();
}

/**
 * Migrates pre v4.2.1 settings
 */
class WCMP_Upgrade_Migration_v4_2_1 extends WCMP_Upgrade_Migration
{
    /**
     * @var void
     */
    private $defaultExportSettings = [];

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
        $this->defaultExportSettings = $this->getSettings("woocommerce_myparcel_export_defaults_settings");

        $this->replaceEmptyParcelWeight();
    }

    protected function replaceEmptyParcelWeight(): void
    {
        $this->defaultExportSettings = $this->replaceValue(
            $this->defaultExportSettings,
            WCMYPA_Settings::SETTING_EMPTY_PARCEL_WEIGHT,
            $this->calculateNewWeight()
        );
    }

    protected function setOptionSettingsMap(): void
    {
        $this->optionSettingsMap = [
            "woocommerce_myparcel_export_defaults_settings" => $this->defaultExportSettings,
        ];
    }

    protected function calculateNewWeight(): float
    {
        $emptyParcelWeight = (float) $this->defaultExportSettings[WCMYPA_Settings::SETTING_EMPTY_PARCEL_WEIGHT];
        $weightUnit        = get_option('woocommerce_weight_unit');

        if ('kg' === $weightUnit) {
            return $emptyParcelWeight / 1000;
        }

        return $emptyParcelWeight;
    }
}

return new WCMP_Upgrade_Migration_v4_2_1();
