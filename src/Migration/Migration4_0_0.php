<?php

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Migration;

use MyParcelNL\Sdk\src\Support\Arr;

class Migration4_0_0 extends AbstractUpgradeMigration implements MigrationInterface
{
    private const MAP_GENERAL                = [
        'email_tracktrace'     => 'track_trace_email',
        'myaccount_tracktrace' => 'track_trace_my_account',
    ];
    private const MAP_EXPORT_DEFAULTS_POSTNL = [
        'insured'   => 'postnl_export_insured',
        'signature' => 'postnl_export_signature',
    ];
    private const MAP_CHECKOUT_POSTNL        = [
        'dropoff_days'        => 'postnl_drop_off_days',
        'cutoff_time'         => 'postnl_cutoff_time',
        'dropoff_delay'       => 'postnl_drop_off_delay',
        'deliverydays_window' => 'postnl_delivery_days_window',
        'signature_enabled'   => 'postnl_signature_enabled',
        'signature_title'     => 'postnl_signature_title',
        'signature_fee'       => 'postnl_signature_fee',
        'delivery_enabled'    => 'postnl_delivery_enabled',
        'pickup_enabled'      => 'postnl_pickup_enabled',
        'pickup_title'        => 'postnl_pickup_title',
        'pickup_fee'          => 'postnl_pickup_fee',
    ];
    private const MAP_CHECKOUT               = [
        'checkout_position' => 'delivery_options_position',
        'custom_css'        => 'delivery_options_custom_css',
        'myparcel_checkout' => 'delivery_options_enabled',
    ];

    /**
     * @var array
     */
    private $newCheckoutSettings = [];

    /**
     * @var array
     */
    private $newDpdSettings = [];

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
    private $newPostnlSettings = [];

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

    public function down(): void
    {
        // Implement down() method.
    }

    public function getVersion(): string
    {
        return '4.0.0';
    }

    public function undot(array $array)
    {
        $result = [];

        foreach ($array as $key => $value) {
            Arr::set($result, $key, $value);
        }

        return $result;
    }

    public function up(): void
    {
        $this->oldCheckoutSettings       = $this->getSettings('woocommerce_myparcel_checkout_settings');
        $this->oldExportDefaultsSettings = $this->getSettings('woocommerce_myparcel_export_defaults_settings');
        $this->oldGeneralSettings        = $this->getSettings('woocommerce_myparcel_general_settings');

        $this->newCheckoutSettings       = $this->oldCheckoutSettings;
        $this->newExportDefaultsSettings = $this->oldExportDefaultsSettings;
        $this->newGeneralSettings        = $this->oldGeneralSettings;

        $this->migrateCheckoutSettings();
        $this->migrateExportDefaultsSettings();
        $this->migrateGeneralSettings();

        $this->saveSettings('woocommerce_myparcel_checkout_settings', $this->newCheckoutSettings ?? []);
        $this->saveSettings('woocommerce_myparcel_export_defaults_settings', $this->newExportDefaultsSettings ?? []);
        $this->saveSettings('woocommerce_myparcel_general_settings', $this->newGeneralSettings ?? []);
        $this->saveSettings('woocommerce_myparcel_postnl_settings', $this->newPostnlSettings ?? []);

//        foreach ($this->undot($this->migrateOptions()) as $option => $value) {
//            $exists = get_option($option) !== false;
//            $value  = is_array($value) ? json_encode($value) : $value;
//
//            if ($exists) {
//                if ($value === null) {
//                    delete_option($option);
//                } else {
//                    update_option($option, $value);
//                }
//            } else {
//                add_option($option, $value);
//            }
//        }


//        foreach ($this->migrateOptions() as $oldKey => $kew)
    }

    protected function migrateOptions()
    {
        return [
            'woocommerce_myparcel_checkout_settings.checkout_position'   => 'woocommerce_myparcel_checkout_settings.delivery_options_position',
            'woocommerce_myparcel_checkout_settings.custom_css'          => 'woocommerce_myparcel_checkout_settings.delivery_options_custom_css',
            'woocommerce_myparcel_checkout_settings.cutoff_time'         => 'woocommerce_myparcel_postnl_settings.postnl_cutoff_time',
            'woocommerce_myparcel_checkout_settings.delivery_enabled'    => 'woocommerce_myparcel_postnl_settings.postnl_delivery_enabled',
            'woocommerce_myparcel_checkout_settings.deliverydays_window' => 'woocommerce_myparcel_postnl_settings.postnl_delivery_days_window',
            'woocommerce_myparcel_checkout_settings.dropoff_days'        => 'woocommerce_myparcel_postnl_settings.postnl_drop_off_days',
            'woocommerce_myparcel_checkout_settings.dropoff_delay'       => 'woocommerce_myparcel_postnl_settings.postnl_drop_off_delay',
            'woocommerce_myparcel_checkout_settings.myparcel_checkout'   => 'woocommerce_myparcel_checkout_settings.delivery_options_enabled',
            'woocommerce_myparcel_checkout_settings.pickup_enabled'      => 'woocommerce_myparcel_postnl_settings.postnl_pickup_enabled',
            'woocommerce_myparcel_checkout_settings.pickup_fee'          => 'woocommerce_myparcel_postnl_settings.postnl_pickup_fee',
            'woocommerce_myparcel_checkout_settings.pickup_title'        => 'woocommerce_myparcel_postnl_settings.postnl_pickup_title',
            'woocommerce_myparcel_checkout_settings.signature_enabled'   => 'woocommerce_myparcel_postnl_settings.postnl_signature_enabled',
            'woocommerce_myparcel_checkout_settings.signature_fee'       => 'woocommerce_myparcel_postnl_settings.postnl_signature_fee',
            'woocommerce_myparcel_checkout_settings.signature_title'     => 'woocommerce_myparcel_postnl_settings.postnl_signature_title',
            'woocommerce_myparcel_export_defaults_settings.insured'      => 'woocommerce_myparcel_postnl_settings.postnl_export_insured',
            'woocommerce_myparcel_export_defaults_settings.signature'    => 'woocommerce_myparcel_postnl_settings.postnl_export_signature',
            'woocommerce_myparcel_general_settings.email_tracktrace'     => 'woocommerce_myparcel_general_settings.track_trace_email',
            'woocommerce_myparcel_general_settings.myaccount_tracktrace' => 'woocommerce_myparcel_general_settings.track_trace_my_account',
            'woocommerce_myparcel_postnl_settings.cutoff_time'           => 'woocommerce_myparcel_postnl_settings.postnl_cutoff_time',
            'woocommerce_myparcel_postnl_settings.delivery_enabled'      => 'woocommerce_myparcel_postnl_settings.postnl_delivery_enabled',
            'woocommerce_myparcel_postnl_settings.deliverydays_window'   => 'woocommerce_myparcel_postnl_settings.postnl_delivery_days_window',
            'woocommerce_myparcel_postnl_settings.dropoff_days'          => 'woocommerce_myparcel_postnl_settings.postnl_drop_off_days',
            'woocommerce_myparcel_postnl_settings.dropoff_delay'         => 'woocommerce_myparcel_postnl_settings.postnl_drop_off_delay',
            'woocommerce_myparcel_postnl_settings.pickup_enabled'        => 'woocommerce_myparcel_postnl_settings.postnl_pickup_enabled',
            'woocommerce_myparcel_postnl_settings.pickup_fee'            => 'woocommerce_myparcel_postnl_settings.postnl_pickup_fee',
            'woocommerce_myparcel_postnl_settings.pickup_title'          => 'woocommerce_myparcel_postnl_settings.postnl_pickup_title',
            'woocommerce_myparcel_postnl_settings.signature_enabled'     => 'woocommerce_myparcel_postnl_settings.postnl_signature_enabled',
            'woocommerce_myparcel_postnl_settings.signature_fee'         => 'woocommerce_myparcel_postnl_settings.postnl_signature_fee',
            'woocommerce_myparcel_postnl_settings.signature_title'       => 'woocommerce_myparcel_postnl_settings.postnl_signature_title',
        ];
    }

    protected function setOptionSettingsMap(): void
    {
        $this->optionSettingsMap = [
            'woocommerce_myparcel_checkout_settings'        => $this->newCheckoutSettings,
            'woocommerce_myparcel_export_defaults_settings' => $this->newExportDefaultsSettings,
            'woocommerce_myparcel_general_settings'         => $this->newGeneralSettings,
            'woocommerce_myparcel_postnl_settings'          => $this->newPostnlSettings,
        ];
    }

    private function migrateCheckoutSettings(): void
    {
        // Migrate existing checkout settings to new keys
        $this->newCheckoutSettings = $this->migrateSettings(
            self::MAP_CHECKOUT,
            $this->newCheckoutSettings
        );

        // Migrate old checkout settings to PostNL
        $this->newPostnlSettings = $this->migrateSettings(
            self::MAP_CHECKOUT_POSTNL,
            $this->newPostnlSettings,
            $this->oldCheckoutSettings
        );

        // Remove the settings that were moved to PostNL from checkout
        $this->newCheckoutSettings = $this->removeOldSettings(
            self::MAP_CHECKOUT_POSTNL,
            $this->newCheckoutSettings
        );
    }

    private function migrateExportDefaultsSettings(): void
    {
        // Migrate array value of shipping_methods_package_types
        $this->newExportDefaultsSettings['shipping_methods_package_types'] =
            $this->migrateSettings(
                array_flip([
                    'package'       => 1,
                    'mailbox'       => 2,
                    'letter'        => 3,
                    'digital_stamp' => 4,
                ]),
                $this->newExportDefaultsSettings['shipping_methods_package_types'] ?? []
            );

        $this->newPostnlSettings = $this->migrateSettings(
            self::MAP_EXPORT_DEFAULTS_POSTNL,
            $this->newPostnlSettings,
            $this->oldExportDefaultsSettings
        );

        $this->newExportDefaultsSettings = $this->removeOldSettings(
            self::MAP_EXPORT_DEFAULTS_POSTNL,
            $this->newExportDefaultsSettings
        );
    }

    private function migrateGeneralSettings(): void
    {
        // Rename existing settings
        $this->newGeneralSettings = $this->migrateSettings(
            self::MAP_GENERAL,
            $this->newGeneralSettings
        );
    }
}

