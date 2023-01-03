<?php

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Migration;

/**
 * Migrates pre v4.4.1 settings
 *  - move show delivery day setting from checkout to general
 *  - add per carrier feature allow show delivery date, set to on when delivery days window > 0, else off
 */
class Migration4_4_1 extends AbstractUpgradeMigration implements Migration
{
    /**
     * @var array
     */
    private $newCheckoutSettings = [];

    /**
     * @var array
     */
    private $newGeneralSettings = [];

    /**
     * @var array
     */
    private $newPostNlSettings = [];

    public function down(): void
    {
        // TODO: Implement down() method.
    }

    public function getVersion(): string
    {
        return '4.4.1';
    }

    public function up(): void
    {
        $this->newGeneralSettings  = $this->getSettings('woocommerce_myparcel_general_settings');
        $this->newCheckoutSettings = $this->getSettings('woocommerce_myparcel_checkout_settings');
        $this->newPostNlSettings   = $this->getSettings('woocommerce_myparcel_postnl_settings');

        $this->migrateGeneralSettings();
        $this->migrateCarrierSettings();
    }

    protected function migrateCarrierSettings(): void
    {
        $keyDeliveryDaysWindow     = sprintf('postnl_%s', 'delivery_days_window');
        $keyAllowShowDeliveryDate  = sprintf('postnl_%s', 'allow_show_delivery_date');
        $settingDeliveryDaysWindow = $this->newPostNlSettings[$keyDeliveryDaysWindow] ?? '1';

        if ('0' === $settingDeliveryDaysWindow) {
            $this->newPostNlSettings[$keyDeliveryDaysWindow]    = '1';
            $this->newPostNlSettings[$keyAllowShowDeliveryDate] = '0';
        } else {
            $this->newPostNlSettings[$keyAllowShowDeliveryDate] = '1';
        }
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
        $this->newGeneralSettings['show_delivery_day'] = $this->newCheckoutSettings['show_delivery_day'] ?? '1';

        unset($this->newCheckoutSettings['show_delivery_day']);
    }
}

