<?php

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Migration;

/**
 * Migrates pre v4.2.1 settings
 */
class Migration4_2_1 extends AbstractUpgradeMigration implements MigrationInterface
{
    /**
     * @var array
     */
    private $defaultExportSettings;

    public function down(): void
    {
        // Implement down() method.
    }

    public function getVersion(): string
    {
        return '4.2.1';
    }

    public function up(): void
    {
        $this->defaultExportSettings = $this->getSettings('woocommerce_myparcel_export_defaults_settings');

        $this->defaultExportSettings = $this->replaceValue(
            $this->defaultExportSettings,
            'empty_parcel_weight',
            $this->calculateNewWeight()
        );
    }

    protected function calculateNewWeight(): float
    {
        $emptyParcelWeight = (float) ($this->defaultExportSettings['empty_parcel_weight'] ?? 0);
        $weightUnit        = get_option('woocommerce_weight_unit');
        $weight            = $emptyParcelWeight;

        if ('kg' === $weightUnit) {
            $dividedWeight = $emptyParcelWeight / 1000;

            // Don't allow the weight to go below 1 gram.
            if ($dividedWeight > 0.001) {
                $weight = $dividedWeight;
            }
        }

        return $weight;
    }

    protected function setOptionSettingsMap(): void
    {
        $this->optionSettingsMap = [
            'woocommerce_myparcel_export_defaults_settings' => $this->defaultExportSettings,
        ];
    }
}

