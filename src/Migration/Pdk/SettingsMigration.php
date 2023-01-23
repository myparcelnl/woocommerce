<?php

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Migration\Pdk;

use MyParcelNL\Pdk\Carrier\Model\CarrierOptions;
use MyParcelNL\Pdk\Facade\Pdk;
use MyParcelNL\Pdk\Settings\Model\CarrierSettings;
use MyParcelNL\Pdk\Settings\Model\CheckoutSettings;
use MyParcelNL\Pdk\Settings\Model\CustomsSettings;
use MyParcelNL\Pdk\Settings\Model\GeneralSettings;
use MyParcelNL\Pdk\Settings\Model\LabelSettings;
use MyParcelNL\Pdk\Settings\Model\OrderSettings;
use MyParcelNL\Pdk\Shipment\Model\DropOffDay;
use MyParcelNL\WooCommerce\Migration\AbstractUpgradeMigration;
use MyParcelNL\WooCommerce\Pdk\Settings\Repository\PdkSettingsRepository;

class SettingsMigration extends AbstractUpgradeMigration
{
    /**
     * @return void
     */
    public function run(): void
    {
        $pdkSettingsRepository = Pdk::get(PdkSettingsRepository::class);

        $pdkSettingsModel = [
            GeneralSettings::class,
            CheckoutSettings::class,
            LabelSettings::class,
            CustomsSettings::class,
            OrderSettings::class,
        ];

        $transformedWcSettingsData = $this->getWcSettings();
        foreach ($pdkSettingsModel as $model) {
            $modelInstance = new $model($transformedWcSettingsData);
            $pdkSettingsRepository->store($modelInstance);
        }

        $carriers = [CarrierOptions::CARRIER_POSTNL_NAME, 'dhlforyou'];
        foreach ($carriers as $carrier) {
            $data                         = $this->getWcCarrierSettings($carrier) + $transformedWcSettingsData;
            $data['dropOffPossibilities'] = $this->getDropOffPossibilities($data);

            $carrierModel = new CarrierSettings($data);
            $pdkSettingsRepository->store($carrierModel);
        }
    }

    /**
     * @param  array $data
     *
     * @return \array[][]
     */
    private function getDropOffPossibilities(array $data): array
    {
        $defaultCutoffTime     = $data['cutoffTime'] ?? '23:59:59';
        $saturdayCutoffTime    = $data['saturdayCutoffTime'] ?? $defaultCutoffTime;
        $sameDayCutoffTime     = '10:00:00';
        $mondayDeliveryEnabled = $data['mondayDeliveryEnabled'] ?? false;
        $dropOffDays           = $data['dropOffDays'] ?? [];

        return [
            'dropOffDays' => [
                [
                    'cutoffTime'        => $defaultCutoffTime,
                    'sameDayCutoffTime' => $sameDayCutoffTime,
                    'weekday'           => DropOffDay::WEEKDAY_MONDAY,
                    'dispatch'          => in_array(
                        DropOffDay::WEEKDAY_MONDAY,
                        $dropOffDays,
                        true
                    ),
                ],
                [
                    'cutoffTime'        => $defaultCutoffTime,
                    'sameDayCutoffTime' => $sameDayCutoffTime,
                    'weekday'           => DropOffDay::WEEKDAY_TUESDAY,
                    'dispatch'          => in_array(
                        DropOffDay::WEEKDAY_TUESDAY,
                        $dropOffDays,
                        true
                    ),
                ],
                [
                    'cutoffTime'        => $defaultCutoffTime,
                    'sameDayCutoffTime' => $sameDayCutoffTime,
                    'weekday'           => DropOffDay::WEEKDAY_WEDNESDAY,
                    'dispatch'          => in_array(
                        DropOffDay::WEEKDAY_WEDNESDAY,
                        $dropOffDays,
                        true
                    ),
                ],
                [
                    'cutoffTime'        => $defaultCutoffTime,
                    'sameDayCutoffTime' => $sameDayCutoffTime,
                    'weekday'           => DropOffDay::WEEKDAY_THURSDAY,
                    'dispatch'          => in_array(
                        DropOffDay::WEEKDAY_THURSDAY,
                        $dropOffDays,
                        true
                    ),
                ],
                [
                    'cutoffTime'        => $defaultCutoffTime,
                    'sameDayCutoffTime' => $sameDayCutoffTime,
                    'weekday'           => DropOffDay::WEEKDAY_FRIDAY,
                    'dispatch'          => in_array(
                        DropOffDay::WEEKDAY_FRIDAY,
                        $dropOffDays,
                        true
                    ),
                ],
                [
                    'cutoffTime'        => $saturdayCutoffTime,
                    'sameDayCutoffTime' => $sameDayCutoffTime,
                    'weekday'           => DropOffDay::WEEKDAY_SATURDAY,
                    'dispatch'          => $mondayDeliveryEnabled
                        && in_array(
                            DropOffDay::WEEKDAY_SATURDAY,
                            $dropOffDays,
                            true
                        ),
                ],
                [
                    'cutoffTime'        => null,
                    'sameDayCutoffTime' => null,
                    'weekday'           => DropOffDay::WEEKDAY_SUNDAY,
                    'dispatch'          => in_array(
                        DropOffDay::WEEKDAY_SUNDAY,
                        $dropOffDays,
                        true
                    ),
                ],
            ],
        ];
    }

    /**
     * @return string[]
     */
    private function mapCarrierSettingKeys(): array
    {
        return [
            'export_age_check'              => 'exportAgeCheck',
            'export_insured'                => 'exportInsurance',
            'export_insured_from_price'     => 'priceInsurance',
            'export_insured_amount'         => 'exportInsuranceAmount',
            'export_insured_eu_amount'      => '', // EMPTY
            'export_insured_for_be'         => '', // EMPTY
            'export_hide_sender'            => 'exportHideSender',
            'export_extra_assurance'        => 'exportExtraAssurance',
            'export_large_format'           => 'exportLargeFormat',
            'export_only_recipient'         => 'exportOnlyRecipient',
            'export_return_shipments'       => 'exportReturnShipments',
            'export_same_day_delivery'      => 'exportSameDayDelivery',
            'export_signature'              => 'exportSignature',
            'delivery_enabled'              => 'allowStandardDelivery',
            'delivery_standard_fee'         => 'priceStandardDelivery',
            'drop_off_days'                 => 'dropOffDays',
            'cutoff_time'                   => 'cutoffTime',
            'delivery_days_window'          => 'featureShowDeliveryDate',
            'digital_stamp_default_weight'  => '', // EMPTY
            'drop_off_delay'                => 'dropOffDelay',
            'monday_delivery_enabled'       => 'allowMondayDelivery',
            'saturday_cutoff_time'          => 'saturdayCutoffTime',
            'delivery_morning_enabled'      => 'allowMorningDelivery',
            'delivery_morning_fee'          => 'priceMorningDelivery',
            'delivery_evening_enabled'      => 'allowEveningDelivery',
            'delivery_evening_fee'          => 'priceEveningDelivery',
            'allow_show_delivery_date'      => '', // EMPTY
            'only_recipient_enabled'        => 'allowOnlyRecipient',
            'only_recipient_fee'            => 'priceOnlyRecipient',
            'same_day_delivery'             => 'allowSameDayDelivery',
            'same_day_delivery_fee'         => 'priceSameDayDelivery',
            'same_day_delivery_cutoff_time' => 'cutoffTimeSameDay',
            'signature_enabled'             => 'allowSignature',
            'signature_fee'                 => 'priceSignature',
            'pickup_enabled'                => 'allowPickupLocations',
            'pickup_fee'                    => 'pricePickup',
        ];
    }

    /**
     * @return string[]
     */
    private function mapSettingKeys(): array
    {
        return [
            // General & label mixed
            'api_key'                                 => 'apiKey',
            'trigger_manual_update'                   => '', // EMPTY
            'export_mode'                             => 'orderMode',
            'download_display'                        => '', // EMPTY
            'label_format'                            => 'format',
            'ask_for_print_position'                  => 'position',
            'track_trace_email'                       => 'trackTraceInEmail',
            'track_trace_my_account'                  => 'trackTraceInAccount',
            'show_delivery_day'                       => '', // EMPTY
            'process_directly'                        => 'processDirectly',
            'order_status_automation'                 => 'exportWithAutomaticStatus',
            'change_order_status_after'               => '', // EMPTY
            'automatic_order_status'                  => '', // EMPTY
            'barcode_in_note'                         => 'barcodeInNote',
            'barcode_in_note_title'                   => '', // EMPTY
            'error_logging'                           => 'apiLogging',

            // Export
            'shipping_methods_package_types'          => '', // EMPTY
            'connect_email'                           => 'shareCustomerInformation',
            'connect_phone'                           => 'shareCustomerInformation',
            'save_customer_address'                   => 'saveCustomerAddress',
            'label_description'                       => 'description',
            'empty_parcel_weight'                     => 'emptyParcelWeight',
            'empty_digital_stamp_weight'              => 'emptyDigitalStampWeight',
            'hs_code'                                 => 'customsCode',
            'package_contents'                        => 'packageContents',
            'country_of_origin'                       => 'countryOfOrigin',
            'export_automatic'                        => '', // EMPTY
            'export_automatic_status'                 => 'exportWithAutomaticStatus',
            'return_in_the_box'                       => '', // EMPTY

            // Checkout
            'use_split_address_fields'                => '', // EMPTY
            'delivery_options_enabled_for_backorders' => '', // EMPTY
            'delivery_options_enabled'                => '', // EMPTY
            'delivery_options_display'                => 'deliveryOptionsDisplay',
            'delivery_options_position'               => 'deliveryOptionsPosition',
            'delivery_options_price_format'           => 'priceType',
            'pickup_locations_default_view'           => 'pickupLocationsDefaultView',
            'delivery_options_custom_css'             => 'deliveryOptionsCustomCss',
            'header_delivery_options_title'           => '', // EMPTY
            'delivery_title'                          => 'stringDelivery',
            'morning_title'                           => 'stringMorningDelivery',
            'standard_title'                          => 'stringStandardDelivery',
            'evening_title'                           => 'stringEveningDelivery',
            'same_day_title'                          => '', // EMPTY
            'only_recipient_title'                    => 'stringOnlyRecipient',
            'signature_title'                         => 'stringSignature',
            'pickup_title'                            => 'stringPickup',
            'address_not_found_title'                 => 'stringAddressNotFound',
        ];
    }

    /**
     * @param  string $carrierName
     *
     * @return array
     */
    public function getWcCarrierSettings(string $carrierName): array
    {
        if (! in_array($carrierName, [CarrierOptions::CARRIER_POSTNL_NAME, 'dhlforyou'], true)) {
            return [];
        }

        $wcCarrierSettings = $this->getSettings("woocommerce_myparcel_{$carrierName}_settings");

        $mapped      = $this->mapCarrierSettingKeys();
        $transformed = [];

        foreach ($wcCarrierSettings as $key => $value) {
            $newKey               = $mapped[$key] ?? $key;
            $transformed[$newKey] = $value;
        }

        return $transformed + ['carrierName' => $carrierName];
    }

    /**
     * @return array
     */
    private function getWcSettings(): array
    {
        $wcSettings = array_merge(
            $this->getSettings('woocommerce_myparcel_general_settings'),
            $this->getSettings('woocommerce_myparcel_export_defaults_settings'),
            $this->getSettings('woocommerce_myparcel_checkout_settings'),
        );

        $mapped      = $this->mapSettingKeys();
        $transformed = [];

        foreach ($wcSettings as $key => $value) {
            $newKey               = $mapped[$key] ?? $key;
            $transformed[$newKey] = $value;
        }

        return $transformed;
    }
}