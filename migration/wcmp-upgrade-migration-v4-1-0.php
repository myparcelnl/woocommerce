<?php

use migration\WCMP_Upgrade_Migration;
use MyParcelNL\Sdk\src\Model\Carrier\CarrierPostNL;
use MyParcelNL\Sdk\src\Model\Consignment\AbstractConsignment;

if (! defined('ABSPATH')) {
    exit;
}

if (class_exists('WCMP_Upgrade_Migration_v4_1_0')) {
    return new WCMP_Upgrade_Migration_v4_1_0();
}

/**
 * Migrates pre v4.1.0 settings
 */
class WCMP_Upgrade_Migration_v4_1_0 extends WCMP_Upgrade_Migration
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
    private $newPostNlSettings = [];

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
        require_once(WCMYPA()->plugin_path() . '/vendor/autoload.php');
        require_once(WCMYPA()->plugin_path() . '/includes/admin/settings/class-wcmypa-settings.php');
        require_once(WCMYPA()->plugin_path() . '/includes/class-wcmp-data.php');
    }

    protected function migrate(): void
    {
        $this->oldGeneralSettings        = $this->getSettings('woocommerce_myparcel_general_settings');
        $this->oldCheckoutSettings       = $this->getSettings('woocommerce_myparcel_checkout_settings');
        $this->oldExportDefaultsSettings = $this->getSettings('woocommerce_myparcel_export_defaults_settings');
        $oldPostNlSettings               = $this->getSettings('woocommerce_myparcel_postnl_settings');

        $this->newGeneralSettings        = $this->oldGeneralSettings;
        $this->newCheckoutSettings       = $this->oldCheckoutSettings;
        $this->newExportDefaultsSettings = $this->oldExportDefaultsSettings;
        $this->newPostNlSettings         = $oldPostNlSettings;

        $this->migrateGeneralSettings();
        $this->migrateCheckoutSettings();
        $this->migrateExportDefaultsSettings();

        $this->correctPostNlInsurance();
    }

    protected function setOptionSettingsMap(): void
    {
        $this->optionSettingsMap = [
            'woocommerce_myparcel_general_settings'         => $this->newGeneralSettings,
            'woocommerce_myparcel_checkout_settings'        => $this->newCheckoutSettings,
            'woocommerce_myparcel_export_defaults_settings' => $this->newExportDefaultsSettings,
            'woocommerce_myparcel_postnl_settings'          => $this->newPostNlSettings,
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
        $this->newPostNlSettings = $this->migrateSettings(
            self::getCheckoutPostnlMap(),
            $this->newPostNlSettings,
            $this->oldCheckoutSettings
        );
    }

    private function migrateExportDefaultsSettings(): void
    {
        $this->newPostNlSettings = $this->migrateSettings(
            self::getExportDefaultsPostnlMap(),
            $this->newPostNlSettings,
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
            'print_position_offset' => WCMYPA_Settings::SETTING_ASK_FOR_PRINT_POSITION,
        ];
    }

    /**
     * @return array
     */
    private static function getCheckoutPostnlMap(): array
    {
        $postnl = CarrierPostNL::NAME;

        return [
            'delivery_options_enabled' => "{$postnl}_" . WCMYPA_Settings::SETTING_CARRIER_DELIVERY_ENABLED,
            'evening_enabled'          => "{$postnl}_" . WCMYPA_Settings::SETTING_CARRIER_DELIVERY_EVENING_ENABLED,
            'evening_fee'              => "{$postnl}_" . WCMYPA_Settings::SETTING_CARRIER_DELIVERY_EVENING_FEE,
            'morning_enabled'          => "{$postnl}_" . WCMYPA_Settings::SETTING_CARRIER_DELIVERY_MORNING_ENABLED,
            'morning_fee'              => "{$postnl}_" . WCMYPA_Settings::SETTING_CARRIER_DELIVERY_MORNING_FEE,
            'myparcel_checkout'        => "{$postnl}_" . WCMYPA_Settings::SETTING_CARRIER_DELIVERY_ENABLED,
            'only_recipient_enabled'   => "{$postnl}_" . WCMYPA_Settings::SETTING_CARRIER_ONLY_RECIPIENT_ENABLED,
            'only_recipient_fee'       => "{$postnl}_" . WCMYPA_Settings::SETTING_CARRIER_ONLY_RECIPIENT_FEE,
            'saturday_cutoff_enabled'  => "{$postnl}_" . WCMYPA_Settings::SETTING_CARRIER_MONDAY_DELIVERY_ENABLED,
        ];
    }

    /**
     * @return array
     */
    private static function getCheckoutMap(): array
    {
        return [
            'at_home_delivery' => WCMYPA_Settings::SETTING_DELIVERY_TITLE,
        ];
    }

    /**
     * @return array
     */
    private static function getExportDefaultsPostnlMap(): array
    {
        $postnl = CarrierPostNL::NAME;

        return [
            'insured_amount' => "{$postnl}_" . WCMYPA_Settings::SETTING_CARRIER_DEFAULT_EXPORT_INSURED_AMOUNT,
            'large_format'   => "{$postnl}_" . WCMYPA_Settings::SETTING_CARRIER_DEFAULT_EXPORT_LARGE_FORMAT,
            'only_recipient' => "{$postnl}_" . WCMYPA_Settings::SETTING_CARRIER_DEFAULT_EXPORT_ONLY_RECIPIENT,
            'return'         => "{$postnl}_" . WCMYPA_Settings::SETTING_CARRIER_DEFAULT_EXPORT_RETURN,
        ];
    }

    /**
     * In case the current amount is not valid, choose the closest value from the allowed values (rounded up).
     *
     * @throws \Exception
     */
    private function correctPostNlInsurance(): void
    {
        $postnl           = CarrierPostNL::NAME;
        $key              = "{$postnl}_" . WCMYPA_Settings::SETTING_CARRIER_DEFAULT_EXPORT_INSURED_AMOUNT;
        $availableAmounts = WCMP_Data::getInsuranceAmounts(AbstractConsignment::CC_NL, $postnl);
        $insuranceAmount  = $this->newPostNlSettings[$key] ?? 0;

        if (! in_array($insuranceAmount, $availableAmounts)) {
            $closestValue = $this->roundUpToMatch($insuranceAmount, $availableAmounts);

            $this->newPostNlSettings[$key] = $closestValue;
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

        return $possibleValues[$match] ?? $possibleValues[0];
    }
}

return new WCMP_Upgrade_Migration_v4_1_0();
