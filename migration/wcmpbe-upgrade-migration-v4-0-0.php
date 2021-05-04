<?php

use migration\WCMPBE_Upgrade_Migration;
use MyParcelNL\Sdk\src\Model\Consignment\AbstractConsignment;

if (! defined('ABSPATH')) {
    exit;
}

if (class_exists('WCMPBE_Upgrade_Migration_v4_0_0')) {
    return new WCMPBE_Upgrade_Migration_v4_0_0();
}

/**
 * Migrates pre v4.0.0 settings
 */
class WCMPBE_Upgrade_Migration_v4_0_0 extends WCMPBE_Upgrade_Migration
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
        parent::__construct();
    }

    protected function import(): void
    {
        require_once(WCMYPABE()->plugin_path() . "/vendor/autoload.php");
        require_once(WCMYPABE()->plugin_path() . '/includes/admin/settings/class-wcmpbe-settings.php');
    }

    protected function migrate(): void
    {
        $this->oldCheckoutSettings       = $this->getSettings("woocommerce_myparcelbe_checkout_settings");
        $this->oldExportDefaultsSettings = $this->getSettings("woocommerce_myparcelbe_export_defaults_settings");
        $this->oldGeneralSettings        = $this->getSettings("woocommerce_myparcelbe_general_settings");

        $this->newCheckoutSettings       = $this->oldCheckoutSettings;
        $this->newExportDefaultsSettings = $this->oldExportDefaultsSettings;
        $this->newGeneralSettings        = $this->oldGeneralSettings;

        $this->migrateCheckoutSettings();
        $this->migrateExportDefaultsSettings();
        $this->migrateGeneralSettings();
    }

    protected function setOptionSettingsMap(): void
    {
        $this->optionSettingsMap = [
            "woocommerce_myparcelbe_checkout_settings"        => $this->newCheckoutSettings,
            "woocommerce_myparcelbe_export_defaults_settings" => $this->newExportDefaultsSettings,
            "woocommerce_myparcelbe_general_settings"         => $this->newGeneralSettings,
            "woocommerce_myparcelbe_bpost_settings"           => $this->newBpostSettings,
        ];
    }

    private function migrateCheckoutSettings(): void
    {
        // Migrate existing checkout settings to new keys
        $this->newCheckoutSettings = $this->migrateSettings(
            self::getCheckoutMap(),
            $this->newCheckoutSettings
        );

        // Migrate old checkout settings to PostNL
        $this->newBpostSettings = $this->migrateSettings(
            self::getCheckoutBpostMap(),
            $this->newBpostSettings,
            $this->oldCheckoutSettings
        );

        // Remove the settings that were moved to PostNL from checkout
        $this->newCheckoutSettings = $this->removeOldSettings(
            self::getCheckoutBpostMap(),
            $this->newCheckoutSettings
        );
    }

    private function migrateExportDefaultsSettings(): void
    {
        // Migrate array value of shipping_methods_package_types
        $this->newExportDefaultsSettings[WCMPBE_Settings::SETTING_SHIPPING_METHODS_PACKAGE_TYPES] =
            $this->migrateSettings(
                array_flip(AbstractConsignment::PACKAGE_TYPES_NAMES_IDS_MAP),
                $this->newExportDefaultsSettings[WCMPBE_Settings::SETTING_SHIPPING_METHODS_PACKAGE_TYPES]
            );

        $this->newBpostSettings = $this->migrateSettings(
            self::getExportDefaultsPostnlMap(),
            $this->newBpostSettings,
            $this->oldExportDefaultsSettings
        );

        $this->newExportDefaultsSettings = $this->removeOldSettings(
            self::getExportDefaultsPostnlMap(),
            $this->newExportDefaultsSettings
        );
    }

    private function migrateGeneralSettings(): void
    {
        // Rename existing settings
        $this->newGeneralSettings = $this->migrateSettings(
            self::getGeneralMap(),
            $this->newGeneralSettings
        );
    }

    /**
     * @return array
     */
    private static function getCheckoutBpostMap(): array
    {
        $bpost = WCMPBE_Settings::SETTINGS_BPOST;

        return [
            "dropoff_days"        => "{$bpost}_" . WCMPBE_Settings::SETTING_CARRIER_DROP_OFF_DAYS,
            "cutoff_time"         => "{$bpost}_" . WCMPBE_Settings::SETTING_CARRIER_CUTOFF_TIME,
            "dropoff_delay"       => "{$bpost}_" . WCMPBE_Settings::SETTING_CARRIER_DROP_OFF_DELAY,
            "deliverydays_window" => "{$bpost}_" . WCMPBE_Settings::SETTING_CARRIER_DELIVERY_DAYS_WINDOW,
            "signature_enabled"   => "{$bpost}_" . WCMPBE_Settings::SETTING_CARRIER_SIGNATURE_ENABLED,
            "signature_title"     => "{$bpost}_" . WCMPBE_Settings::SETTING_SIGNATURE_TITLE,
            "signature_fee"       => "{$bpost}_" . WCMPBE_Settings::SETTING_CARRIER_SIGNATURE_FEE,
            "delivery_enabled"    => "{$bpost}_" . WCMPBE_Settings::SETTING_CARRIER_DELIVERY_ENABLED,
            "pickup_enabled"      => "{$bpost}_" . WCMPBE_Settings::SETTING_CARRIER_PICKUP_ENABLED,
            "pickup_title"        => "{$bpost}_" . WCMPBE_Settings::SETTING_CARRIER_PICKUP_TITLE,
            "pickup_fee"          => "{$bpost}_" . WCMPBE_Settings::SETTING_CARRIER_PICKUP_FEE,
        ];
    }

    /**
     * @return array
     */
    private static function getCheckoutMap(): array
    {
        return [
            "checkout_position" => WCMPBE_Settings::SETTING_DELIVERY_OPTIONS_POSITION,
            "custom_css"        => WCMPBE_Settings::SETTING_DELIVERY_OPTIONS_CUSTOM_CSS,
            "myparcelbe_checkout" => WCMPBE_Settings::SETTING_DELIVERY_OPTIONS_ENABLED,
        ];
    }

    /**
     * @return array
     */
    private static function getGeneralMap(): array
    {
        return [
            "email_tracktrace"     => WCMPBE_Settings::SETTING_TRACK_TRACE_EMAIL,
            "myaccount_tracktrace" => WCMPBE_Settings::SETTING_TRACK_TRACE_MY_ACCOUNT,
        ];
    }

    /**
     * Move insured and signature to PostNL because these settings are PostNL specific and there is no dpd equivalent.
     *
     * @return array
     */
    private static function getExportDefaultsPostnlMap(): array
    {
        $bpost = WCMPBE_Settings::SETTINGS_BPOST;

        return [
            "insured"   => "{$bpost}_" . WCMPBE_Settings::SETTING_CARRIER_DEFAULT_EXPORT_INSURED,
            "signature" => "{$bpost}_" . WCMPBE_Settings::SETTING_CARRIER_DEFAULT_EXPORT_SIGNATURE,
        ];
    }
}

return new WCMPBE_Upgrade_Migration_v4_0_0();
