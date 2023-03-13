<?php

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Migration\Pdk;

use MyParcelNL\Pdk\Carrier\Model\Carrier;
use MyParcelNL\Pdk\Facade\Pdk;
use MyParcelNL\Pdk\Plugin\Action\Backend\Account\UpdateAccountAction;
use MyParcelNL\Pdk\Settings\Model\AccountSettings;
use MyParcelNL\Pdk\Settings\Model\CarrierSettings;
use MyParcelNL\Pdk\Settings\Model\CheckoutSettings;
use MyParcelNL\Pdk\Settings\Model\CustomsSettings;
use MyParcelNL\Pdk\Settings\Model\GeneralSettings;
use MyParcelNL\Pdk\Settings\Model\LabelSettings;
use MyParcelNL\Pdk\Settings\Model\OrderSettings;
use MyParcelNL\Pdk\Settings\Model\Settings;
use MyParcelNL\Pdk\Shipment\Model\DropOffDay;
use MyParcelNL\WooCommerce\Migration\Contract\MigrationInterface;
use MyParcelNL\WooCommerce\Pdk\Plugin\Repository\PdkAccountRepository;
use MyParcelNL\WooCommerce\Pdk\Settings\Repository\PdkSettingsRepository;

class SettingsMigration implements MigrationInterface
{
    private const GENERAL  = 'general';
    private const CHECKOUT = 'checkout';
    private const LABEL    = 'label';
    private const ORDER    = 'order';
    private const CUSTOMS  = 'customs';

    public function down(): void
    {
        /**
         * Nothing to do here.
         */
    }

    public function getVersion(): string
    {
        return '5.0.0';
    }

    /**
     * @param  string $carrierName
     *
     * @return array
     */
    public function getWcCarrierSettings(string $carrierName): array
    {
        if (! in_array($carrierName, [Carrier::CARRIER_POSTNL_NAME, 'dhlforyou'], true)) {
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
     * @return void
     * @throws \MyParcelNL\Pdk\Base\Exception\InvalidCastException
     */
    public function up(): void
    {
        /** @var  PdkSettingsRepository $pdkSettingsRepository */
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
            $pdkSettingsRepository->storeSettings($modelInstance);
        }

        $accountSettings = new AccountSettings($transformedWcSettingsData);
        if ($accountSettings->apiKey) {
            $pdkSettingsRepository->storeSettings($accountSettings);
            $accountRepository = Pdk::get(PdkAccountRepository::class);
            $accountUpdate     = Pdk::get(UpdateAccountAction::class);
            $account           = $accountRepository->getAccount(true);
            $accountUpdate->updateAndSaveAccount($account);
        }

        $carriers  = [Carrier::CARRIER_POSTNL_NAME, 'dhlforyou'];
        $aggregate = [CarrierSettings::ID => []];
        foreach ($carriers as $carrier) {
            $data                         = $this->getWcCarrierSettings($carrier) + $transformedWcSettingsData;
            $data['dropOffPossibilities'] = $this->getDropOffPossibilities($data);
            $data['allowDeliveryOptions'] = true;

            $carrierModel                             = new CarrierSettings($data);
            $aggregate[CarrierSettings::ID][$carrier] = $carrierModel->toStorableArray();
        }

        $settings = new Settings($aggregate);

        $pdkSettingsRepository->storeSettings($settings->getAttribute(CarrierSettings::ID));
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
     * @param  string $key
     *
     * @return array
     */
    private function getSettings(string $key): array
    {
        $value = get_option($key);

        if (! is_array($value)) {
            return [];
        }

        return $value;
    }

    /**
     * @return array
     */
    private function getWcSettings(): array
    {
        $wcSettings = array_merge(
            $this->getSettings('woocommerce_myparcel_general_settings'),
            $this->getSettings('woocommerce_myparcel_export_defaults_settings'),
            $this->getSettings('woocommerce_myparcel_checkout_settings')
        );

        $mapped      = $this->mapSettingKeys();
        $transformed = [];

        foreach ($wcSettings as $key => $value) {
            $search = $this->searchParentKey($key, $mapped);
            $newKey = $mapped[$search][$key] ?? $key;

            switch ($key) {
                case 'nothing_yet':
                    $value = $value === 'yes' ? '1' : '0';
                    break;
                case 'export_insured_for_be':
                    $value = 50000;
                    break;
            }

            $transformed[$newKey] = $value;
        }

        return $transformed;
    }

    /**
     * @return string[]
     */
    private function mapCarrierSettingKeys(): array
    {
        return [
            'export_age_check'              => 'exportAgeCheck',
            'export_insured'                => 'exportInsurance',
            'export_insured_from_price'     => 'exportInsuranceFromAmount',
            'export_insured_amount'         => 'exportInsuranceUpTo',
            'export_insured_eu_amount'      => 'exportInsuranceUpToEu',
            'export_insured_for_be'         => 'exportInsuranceUpToBe',
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
            'digital_stamp_default_weight'  => '',
            'drop_off_delay'                => 'dropOffDelay',
            'monday_delivery_enabled'       => 'allowMondayDelivery',
            'saturday_cutoff_time'          => 'saturdayCutoffTime',
            'delivery_morning_enabled'      => 'allowMorningDelivery',
            'delivery_morning_fee'          => 'priceMorningDelivery',
            'delivery_evening_enabled'      => 'allowEveningDelivery',
            'delivery_evening_fee'          => 'priceEveningDelivery',
            'allow_show_delivery_date'      => 'showDeliveryDay',
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
            self::GENERAL  => [
                'api_key'                 => 'apiKey',
                'error_logging'           => 'apiLogging',
                'connect_email'           => 'shareCustomerInformation',
                'process_directly'        => 'conceptShipments',
                'export_mode'             => 'orderMode',
                'track_trace_email'       => 'trackTraceInEmail',
                'track_trace_my_account'  => 'trackTraceInAccount',
                'barcode_in_note'         => 'barcodeInNote',
                'barcode_in_note_title'   => 'barcodeInNoteTitle',
                'export_automatic'        => 'processDirectly',
                'export_automatic_status' => 'exportWithAutomaticStatus',
                'automatic_order_status'  => 'orderStatusOnLabelCreate',
            ],
            self::ORDER    => [
                'save_customer_address'      => 'saveCustomerAddress',
                'empty_parcel_weight'        => 'emptyParcelWeight',
                'empty_digital_stamp_weight' => 'emptyDigitalStampWeight',
            ],
            self::LABEL    => [
                'label_description'      => 'description',
                'label_format'           => 'format',
                'download_display'       => 'output',
                'ask_for_print_position' => 'prompt',
            ],
            self::CUSTOMS  => [
                'package_contents'  => 'packageContents',
                'hs_code'           => 'customsCode',
                'country_of_origin' => 'countryOfOrigin',
            ],
            self::CHECKOUT => [
                'use_split_address_fields'                => 'useSeparateAddressFields',
                'delivery_options_enabled'                => 'enableDeliveryOptions',
                'delivery_options_enabled_for_backorders' => 'enableDeliveryOptionsWhenNotInStock',
                'header_delivery_options_title'           => 'deliveryOptionsHeader',
                'delivery_options_display'                => 'deliveryOptionsDisplay',
                'delivery_options_position'               => 'deliveryOptionsPosition',
                'delivery_options_price_format'           => 'priceType',
                'pickup_locations_default_view'           => 'pickupLocationsDefaultView',
                'delivery_options_custom_css'             => 'deliveryOptionsCustomCss',
            ],
        ];
    }

    /**
     * @param $needle
     * @param $haystack
     *
     * @return false|int|string
     */
    private function searchParentKey($needle, $haystack)
    {
        foreach ($haystack as $key => $value) {
            if ($needle === $value || (is_array($value) && $this->searchParentKey($needle, $value) !== false)) {
                return $key;
            }
        }

        return false;
    }
}
