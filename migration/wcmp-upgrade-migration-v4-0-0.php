<?php

if (! defined('ABSPATH')) {
    exit;
}

if (class_exists('WCMP_Upgrade_Migration_v4_0_0')) {
    return new WCMP_Upgrade_Migration_v4_0_0();
}

/**
 * Migrates pre v4.0.0 settings
 */
class WCMP_Upgrade_Migration_v4_0_0
{
    /**
     * @var array
     */
    private $newGeneralSettings = [];
    /**
     * @var array
     */
    private $newExportDefaultsSettings = [];
    /**
     * @var array
     */
    private $newCheckoutSettings = [];
    /**
     * @var array
     */
    private $newBpostSettings = [];
    /**
     * @var array
     */
    private $newDpdSettings = [];
    /**
     * @var void
     */
    private $oldCheckoutSettings;
    /**
     * @var void
     */
    private $oldExportDefaultsSettings;
    /**
     * @var void
     */
    private $oldGeneralSettings;

    public function __construct()
    {
        // TODO
        echo "<pre>";
        $this->oldCheckoutSettings       = get_option("woocommerce_myparcelbe_checkout_settings");
        $this->oldExportDefaultsSettings = get_option("woocommerce_myparcelbe_export_defaults_settings");
        $this->oldGeneralSettings        = get_option("woocommerce_myparcelbe_general_settings");
        echo "</pre>";
        exit();
    }

    /**
     * @param array $legacyCheckoutSettings
     * @param array $newDefaultSettings
     *
     * @return array
     */
    public function migrateDeliveryOptionsSettings(array $legacyCheckoutSettings, array $newDefaultSettings)
    {
        $bpostSettings          = $legacyCheckoutSettings;
        $exportDefaultsSettings = $newDefaultSettings;

        $this->newBpostSettings = $this->setFromCheckoutToBpostSettings($bpostSettings, $legacyCheckoutSettings);
        $exportDefaultsSettings =
            $this->migrateFromExportDefaultsToBpostSettings($exportDefaultsSettings, $newDefaultSettings);

        return [$bpostSettings, $exportDefaultsSettings];
    }

    public function renamedSettings()
    {
        $general = [
            "custom_css" => "delivery_options_custom_css",
        ];
    }

    public function migrateFromExportDefaultsToBpostSettings($bpostSettings, $exportDefaultsSettings)
    {
        $bpost = WCMP_Settings::SETTINGS_BPOST;

        $fromExportDefaultsToBpost = [
            "insured"   => "{$bpost}_" . WCMP_Settings::SETTING_CARRIER_INSURED,
            "signature" => "{$bpost}_" . WCMP_Settings::SETTING_CARRIER_SIGNATURE_ENABLED,
        ];

        foreach ($fromExportDefaultsToBpost as $exportDefaultSetting => $bpostSetting) {
            $bpostSettings[$bpostSetting] = $exportDefaultsSettings[$exportDefaultSetting];
            unset($exportDefaultSettings[$exportDefaultSetting]);
        }

        return $bpostSettings;
    }

    /**
     * @param array $checkoutSettings
     *
     * @return array
     */
    public function setFromCheckoutToGeneralSettings(array $checkoutSettings): array
    {
        // todo: important note, I moved these to to something that's still called checkout instead of general
        $renamedCheckoutSettings = [
            "checkout_position"   => WCMP_Settings::SETTING_DELIVERY_OPTIONS_POSITION,
            "custom_css"          => WCMP_Settings::SETTING_DELIVERY_OPTIONS_CUSTOM_CSS,
            "myparcelbe_checkout" => WCMP_Settings::SETTING_DELIVERY_OPTIONS_ENABLED,
        ];

        foreach ($renamedCheckoutSettings as $oldSetting => $newSetting) {
            $checkoutSettings[$newSetting] = $checkoutSettings[$oldSetting];
            unset($checkoutSettings[$oldSetting]);
        }

        return $checkoutSettings;
    }

    public function setFromCheckoutToBpostSettings($bpostSettings, $checkoutSettings)
    {
        $bpost = WCMP_Settings::SETTINGS_BPOST;

        $fromCheckoutToBpost = [
            "dropoff_days"        => "{$bpost}_" . WCMP_Settings::SETTING_CARRIER_DROP_OFF_DAYS,
            "cutoff_time"         => "{$bpost}_" . WCMP_Settings::SETTING_CARRIER_CUTOFF_TIME,
            "dropoff_delay"       => "{$bpost}_" . WCMP_Settings::SETTING_CARRIER_DROP_OFF_DELAY,
            "deliverydays_window" => "{$bpost}_" . WCMP_Settings::SETTING_CARRIER_DELIVERY_DAYS_WINDOW,
            "signature_enabled"   => "{$bpost}_" . WCMP_Settings::SETTING_CARRIER_SIGNATURE_ENABLED,
            "signature_title"     => "{$bpost}_" . WCMP_Settings::SETTING_SIGNATURE_TITLE,
            "signature_fee"       => "{$bpost}_" . WCMP_Settings::SETTING_CARRIER_SIGNATURE_FEE,
            "delivery_enabled"    => "{$bpost}_" . WCMP_Settings::SETTING_CARRIER_DELIVERY_ENABLED,
            "pickup_enabled"      => "{$bpost}_" . WCMP_Settings::SETTING_CARRIER_PICKUP_ENABLED,
            "pickup_title"        => "{$bpost}_" . WCMP_Settings::SETTING_CARRIER_PICKUP_TITLE,
            "pickup_fee"          => "{$bpost}_" . WCMP_Settings::SETTING_CARRIER_PICKUP_FEE,
        ];

        foreach ($fromCheckoutToBpost as $checkoutSetting => $bpostSetting) {
            $bpostSettings[$bpostSetting] = $checkoutSettings[$checkoutSetting];
            unset($bpostSettings[$checkoutSetting]);
        }

        return $bpostSettings;
    }
}

return new WCMP_Upgrade_Migration_v4_0_0();
