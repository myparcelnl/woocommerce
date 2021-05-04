<?php

use migration\WCMPBE_Upgrade_Migration;

if (! defined('ABSPATH')) {
    exit;
}

if (class_exists('WCMPBE_Upgrade_Migration_v4_1_0')) {
    return new WCMPBE_Upgrade_Migration_v4_1_0();
}

/**
 * Migrates pre v4.1.0 settings
 */
class WCMPBE_Upgrade_Migration_v4_1_0 extends WCMPBE_Upgrade_Migration
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
    private $oldGeneralSettings;

    /**
     * @var array
     */
    private $oldCheckoutSettings;

    /**
     * @var array
     */
    private $oldExportDefaultsSettings;

    public function __construct()
    {
        parent::__construct();
    }

    protected function import(): void
    {
        require_once(WCMYPABE()->plugin_path() . '/vendor/autoload.php');
        require_once(WCMYPABE()->plugin_path() . '/includes/admin/settings/class-wcmpbe-settings.php');
        require_once(WCMYPABE()->plugin_path() . '/includes/class-wcmpbe-data.php');
    }

    protected function migrate(): void
    {
        $this->oldGeneralSettings        = $this->getSettings("woocommerce_myparcelbe_general_settings");
        $this->oldCheckoutSettings       = $this->getSettings("woocommerce_myparcelbe_checkout_settings");
        $this->oldExportDefaultsSettings = $this->getSettings("woocommerce_myparcelbe_export_defaults_settings");
        $oldBpostSettings                = $this->getSettings("woocommerce_myparcelbe_bpost_settings");

        $this->newGeneralSettings        = $this->oldGeneralSettings;
        $this->newCheckoutSettings       = $this->oldCheckoutSettings;
        $this->newExportDefaultsSettings = $this->oldExportDefaultsSettings;
        $this->newBpostSettings          = $oldBpostSettings;

        $this->migrateGeneralSettings();
        $this->migrateCheckoutSettings();
        $this->migrateExportDefaultsSettings();

        $this->correctBpostInsurance();
    }

    protected function setOptionSettingsMap(): void
    {
        $this->optionSettingsMap = [
            "woocommerce_myparcelbe_general_settings"         => $this->newGeneralSettings,
            "woocommerce_myparcelbe_checkout_settings"        => $this->newCheckoutSettings,
            "woocommerce_myparcelbe_export_defaults_settings" => $this->newExportDefaultsSettings,
            "woocommerce_myparcelbe_postnl_settings"          => $this->newBpostSettings,
        ];
    }

    private function migrateGeneralSettings(): void
    {
        $this->newGeneralSettings = $this->migrateSettings(
            self::getGeneralMap(),
            $this->newGeneralSettings,
            $this->oldGeneralSettings
        );
    }

    private function migrateCheckoutSettings(): void
    {
        // Migrate existing checkout settings to new keys
        $this->newCheckoutSettings = $this->migrateSettings(
            self::getCheckoutMap(),
            $this->newCheckoutSettings,
            $this->oldCheckoutSettings
        );

        // Migrate old checkout settings to PostNL
        $this->newBpostSettings = $this->migrateSettings(
            self::getCheckoutPostnlMap(),
            $this->newBpostSettings,
            $this->oldCheckoutSettings
        );
    }

    private function migrateExportDefaultsSettings(): void
    {
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

    /**
     * @return array
     */
    private static function getGeneralMap(): array
    {
        return [
            'print_position_offset' => WCMPBE_Settings::SETTING_ASK_FOR_PRINT_POSITION,
        ];
    }

    /**
     * @return array
     */
    private static function getCheckoutPostnlMap(): array
    {
        $bpost = WCMPBE_Settings::SETTINGS_BPOST;

        return [
            "delivery_options_enabled" => "{$bpost}_" . WCMPBE_Settings::SETTING_CARRIER_DELIVERY_ENABLED,
            "evening_enabled"          => "{$bpost}_" . WCMPBE_Settings::SETTING_CARRIER_DELIVERY_EVENING_ENABLED,
            "evening_fee"              => "{$bpost}_" . WCMPBE_Settings::SETTING_CARRIER_DELIVERY_EVENING_FEE,
            "morning_enabled"          => "{$bpost}_" . WCMPBE_Settings::SETTING_CARRIER_DELIVERY_MORNING_ENABLED,
            "morning_fee"              => "{$bpost}_" . WCMPBE_Settings::SETTING_CARRIER_DELIVERY_MORNING_FEE,
            "myparcel_checkout"        => "{$bpost}_" . WCMPBE_Settings::SETTING_CARRIER_DELIVERY_ENABLED,
            "only_recipient_enabled"   => "{$bpost}_" . WCMPBE_Settings::SETTING_CARRIER_ONLY_RECIPIENT_ENABLED,
            "only_recipient_fee"       => "{$bpost}_" . WCMPBE_Settings::SETTING_CARRIER_ONLY_RECIPIENT_FEE,
        ];
    }

    /**
     * @return array
     */
    private static function getCheckoutMap(): array
    {
        return [
            "at_home_delivery" => WCMPBE_Settings::SETTING_DELIVERY_TITLE,
        ];
    }

    /**
     * @return array
     */
    private static function getExportDefaultsPostnlMap(): array
    {
        $bpost = WCMPBE_Settings::SETTINGS_POSTNL;

        return [
            "insured_amount" => "{$bpost}_" . WCMPBE_Settings::SETTING_CARRIER_DEFAULT_EXPORT_INSURED_AMOUNT,
            "large_format"   => "{$bpost}_" . WCMPBE_Settings::SETTING_CARRIER_DEFAULT_EXPORT_LARGE_FORMAT,
            "only_recipient" => "{$bpost}_" . WCMPBE_Settings::SETTING_CARRIER_DEFAULT_EXPORT_ONLY_RECIPIENT,
            "return"         => "{$bpost}_" . WCMPBE_Settings::SETTING_CARRIER_DEFAULT_EXPORT_RETURN,
        ];
    }

    /**
     * In case the current amount is not valid, choose the closest value from the allowed values (rounded up).
     */
    private function correctBpostInsurance(): void
    {
        $bpost            = WCMPBE_Settings::SETTINGS_POSTNL;
        $key              = "{$bpost}_" . WCMPBE_Settings::SETTING_CARRIER_DEFAULT_EXPORT_INSURED_AMOUNT;
        $availableAmounts = WCMPBE_Data::getInsuranceAmounts();
        $insuranceAmount  = $this->newBpostSettings[$key];

        if (! in_array($insuranceAmount, $availableAmounts)) {
            $closestValue = $this->roundUpToMatch($insuranceAmount, $availableAmounts);

            $this->newBpostSettings[$key] = $closestValue;
        }
    }

    /**
     * @param int   $target
     * @param int[] $possibleValues
     *
     * @return int
     */
    private function roundUpToMatch(int $target, array $possibleValues): int
    {
        rsort($possibleValues);
        $match = 0;

        foreach ($possibleValues as $i => $value) {
            if ($value < $target) {
                break;
            }

            $match = $i;
        }

        return $possibleValues[$match];
    }
}

return new WCMPBE_Upgrade_Migration_v4_1_0();
