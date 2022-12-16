<?php

declare(strict_types=1);

use migration\WCMP_Upgrade_Migration;
use MyParcelNL\Sdk\src\Model\Carrier\CarrierPostNL;
use MyParcelNL\Sdk\src\Model\Consignment\AbstractConsignment;

if (! defined('ABSPATH')) {
    exit;
}

if (class_exists('WCMP_Upgrade_Migration_v4_0_0')) {
    return new WCMP_Upgrade_Migration_v4_0_0();
}

/**
 * Migrates pre v4.0.0 settings
 */
class WCMP_Upgrade_Migration_v4_0_0 extends WCMP_Upgrade_Migration
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
    private $newPostnlSettings = [];

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
        require_once(WCMYPA()->plugin_path() . "/vendor/autoload.php");
        require_once(WCMYPA()->plugin_path() . '/includes/admin/settings/class-wcmypa-settings.php');
    }

    protected function migrate(): void
    {
        $this->oldCheckoutSettings       = $this->getSettings("woocommerce_myparcel_checkout_settings");
        $this->oldExportDefaultsSettings = $this->getSettings("woocommerce_myparcel_export_defaults_settings");
        $this->oldGeneralSettings        = $this->getSettings("woocommerce_myparcel_general_settings");

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
            "woocommerce_myparcel_checkout_settings"        => $this->newCheckoutSettings,
            "woocommerce_myparcel_export_defaults_settings" => $this->newExportDefaultsSettings,
            "woocommerce_myparcel_general_settings"         => $this->newGeneralSettings,
            "woocommerce_myparcel_postnl_settings"          => $this->newPostnlSettings,
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
        $this->newPostnlSettings = $this->migrateSettings(
            self::getCheckoutPostnlMap(),
            $this->newPostnlSettings,
            $this->oldCheckoutSettings
        );

        // Remove the settings that were moved to PostNL from checkout
        $this->newCheckoutSettings = $this->removeOldSettings(
            self::getCheckoutPostnlMap(),
            $this->newCheckoutSettings
        );
    }

    private function migrateExportDefaultsSettings(): void
    {
        // Migrate array value of shipping_methods_package_types
        $this->newExportDefaultsSettings[WCMYPA_Settings::SETTING_SHIPPING_METHODS_PACKAGE_TYPES] =
            $this->migrateSettings(
                array_flip(AbstractConsignment::PACKAGE_TYPES_NAMES_IDS_MAP),
                $this->newExportDefaultsSettings[WCMYPA_Settings::SETTING_SHIPPING_METHODS_PACKAGE_TYPES]
            );

        $this->newPostnlSettings = $this->migrateSettings(
            self::getExportDefaultsPostnlMap(),
            $this->newPostnlSettings,
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
    private static function getCheckoutPostnlMap(): array
    {
        $postnl = CarrierPostNL::NAME;

        return [
            "dropoff_days"        => "{$postnl}_" . WCMYPA_Settings::SETTING_CARRIER_DROP_OFF_DAYS,
            "cutoff_time"         => "{$postnl}_" . WCMYPA_Settings::SETTING_CARRIER_CUTOFF_TIME,
            "dropoff_delay"       => "{$postnl}_" . WCMYPA_Settings::SETTING_CARRIER_DROP_OFF_DELAY,
            "deliverydays_window" => "{$postnl}_" . WCMYPA_Settings::SETTING_CARRIER_DELIVERY_DAYS_WINDOW,
            "signature_enabled"   => "{$postnl}_" . WCMYPA_Settings::SETTING_CARRIER_SIGNATURE_ENABLED,
            "signature_title"     => "{$postnl}_" . WCMYPA_Settings::SETTING_SIGNATURE_TITLE,
            "signature_fee"       => "{$postnl}_" . WCMYPA_Settings::SETTING_CARRIER_SIGNATURE_FEE,
            "delivery_enabled"    => "{$postnl}_" . WCMYPA_Settings::SETTING_CARRIER_DELIVERY_ENABLED,
            "pickup_enabled"      => "{$postnl}_" . WCMYPA_Settings::SETTING_CARRIER_PICKUP_ENABLED,
            "pickup_title"        => "{$postnl}_" . WCMYPA_Settings::SETTING_CARRIER_PICKUP_TITLE,
            "pickup_fee"          => "{$postnl}_" . WCMYPA_Settings::SETTING_CARRIER_PICKUP_FEE,
        ];
    }

    /**
     * @return array
     */
    private static function getCheckoutMap(): array
    {
        return [
            "checkout_position" => WCMYPA_Settings::SETTING_DELIVERY_OPTIONS_POSITION,
            "custom_css"        => WCMYPA_Settings::SETTING_DELIVERY_OPTIONS_CUSTOM_CSS,
            "myparcel_checkout" => WCMYPA_Settings::SETTING_DELIVERY_OPTIONS_ENABLED,
        ];
    }

    /**
     * @return array
     */
    private static function getGeneralMap(): array
    {
        return [
            "email_tracktrace"     => WCMYPA_Settings::SETTING_TRACK_TRACE_EMAIL,
            "myaccount_tracktrace" => WCMYPA_Settings::SETTING_TRACK_TRACE_MY_ACCOUNT,
        ];
    }

    /**
     * Move insured and signature to PostNL because these settings are PostNL specific and there is no dpd equivalent.
     *
     * @return array
     */
    private static function getExportDefaultsPostnlMap(): array
    {
        $postnl = CarrierPostNL::NAME;

        return [
            "insured"   => "{$postnl}_" . WCMYPA_Settings::SETTING_CARRIER_DEFAULT_EXPORT_INSURED,
            "signature" => "{$postnl}_" . WCMYPA_Settings::SETTING_CARRIER_DEFAULT_EXPORT_SIGNATURE,
        ];
    }
}

return new WCMP_Upgrade_Migration_v4_0_0();
