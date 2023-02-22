<?php

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Migration;

use MyParcelNL\Sdk\src\Factory\ConsignmentFactory;
use MyParcelNL\Sdk\src\Model\Consignment\PostNLConsignment;

class Migration4_1_0 extends AbstractUpgradeMigration implements MigrationInterface
{
    /**
     * @var array
     */
    private $newCheckoutSettings = [];

    /**
     * @var array
     */
    private $newExportDefaultsSettings = [];

    /**
     * @var array
     */
    private $newGeneralSettings = [];

    /**
     * @var array
     */
    private $newPostNlSettings = [];

    /**
     * @var array
     */
    private $oldCheckoutSettings;

    /**
     * @var array
     */
    private $oldExportDefaultsSettings;

    /**
     * @var array
     */
    private $oldGeneralSettings;

    /**
     * @return array
     */
    private static function getCheckoutMap(): array
    {
        return [
            'at_home_delivery' => 'delivery_title',
        ];
    }

    /**
     * @return array
     */
    private static function getCheckoutPostnlMap(): array
    {
        return [
            'delivery_options_enabled' => 'postnl_delivery_enabled',
            'evening_enabled'          => 'postnl_delivery_evening_enabled',
            'evening_fee'              => 'postnl_delivery_evening_fee',
            'morning_enabled'          => 'postnl_delivery_morning_enabled',
            'morning_fee'              => 'postnl_delivery_morning_fee',
            'myparcel_checkout'        => 'postnl_delivery_enabled',
            'only_recipient_enabled'   => 'postnl_only_recipient_enabled',
            'only_recipient_fee'       => 'postnl_only_recipient_fee',
            'saturday_cutoff_enabled'  => 'postnl_monday_delivery_enabled',
        ];
    }

    /**
     * @return array
     */
    private static function getExportDefaultsPostnlMap(): array
    {
        return [
            'insured_amount' => 'postnl_export_insured_amount',
            'large_format'   => 'postnl_export_large_format',
            'only_recipient' => 'postnl_export_only_recipient',
            'return'         => 'postnl_export_return_shipments',
        ];
    }

    /**
     * @return array
     */
    private static function getGeneralMap(): array
    {
        return [
            'print_position_offset' => 'ask_for_print_position',
        ];
    }

    public function down(): void
    {
        // Implement down() method.
    }

    public function getVersion(): string
    {
        return '4.1.0';
    }

    /**
     * @return void
     * @throws \Exception
     */
    public function up(): void
    {
        $this->oldGeneralSettings        = $this->getSettings('woocommerce_myparcel_general_settings');
        $this->oldCheckoutSettings       = $this->getSettings('woocommerce_myparcel_checkout_settings');
        $this->oldExportDefaultsSettings = $this->getSettings('woocommerce_myparcel_export_defaults_settings');

        $this->newGeneralSettings        = $this->oldGeneralSettings;
        $this->newCheckoutSettings       = $this->oldCheckoutSettings;
        $this->newExportDefaultsSettings = $this->oldExportDefaultsSettings;
        $this->newPostNlSettings         = $this->getSettings('woocommerce_myparcel_postnl_settings');

        $this->migrateGeneralSettings();
        $this->migrateCheckoutSettings();
        $this->migrateExportDefaultsSettings();

        $this->correctPostNlInsurance();

        $this->save();
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

    /**
     * In case the current amount is not valid, choose the closest value from the allowed values (rounded up).
     *
     * @throws \Exception
     */
    private function correctPostNlInsurance(): void
    {
        $key = 'postnl_export_insured_amount';

        $availableAmounts = $this->getNewInsuranceAmounts();
        $insuranceAmount  = $this->newPostNlSettings[$key] ?? 0;

        if (! in_array($insuranceAmount, $availableAmounts)) {
            $closestValue = $this->roundUpToMatch($insuranceAmount, $availableAmounts);

            $this->newPostNlSettings[$key] = $closestValue;
        }
    }

    /**
     * @return array
     * @throws \Exception
     */
    private function getNewInsuranceAmounts(): array
    {
        $amounts = [];

        /**
         * @type PostNLConsignment $carrier
         */
        $carrier             = ConsignmentFactory::createByCarrierName('postnl');
        $amountPossibilities = $carrier->getInsurancePossibilities();

        foreach ($amountPossibilities as $value) {
            $amounts[$value] = $value;
        }

        return $amounts;
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

    private function migrateGeneralSettings(): void
    {
        $this->newGeneralSettings = $this->migrateSettings(
            self::getGeneralMap(),
            $this->newGeneralSettings,
            $this->oldGeneralSettings
        );
    }

    /**
     * @param  int   $target
     * @param  int[] $possibleValues
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

