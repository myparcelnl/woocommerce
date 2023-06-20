<?php

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Migration\Pdk;

use Generator;
use MyParcelNL\Pdk\Base\Contract\CurrencyServiceInterface;
use MyParcelNL\Pdk\Base\Contract\WeightServiceInterface;
use MyParcelNL\Pdk\Base\Support\Arr;
use MyParcelNL\Pdk\Facade\Pdk;
use MyParcelNL\Pdk\Settings\Collection\SettingsModelCollection;
use MyParcelNL\Pdk\Settings\Contract\SettingsRepositoryInterface;
use MyParcelNL\Pdk\Settings\Model\Settings;
use MyParcelNL\Pdk\Shipment\Model\DropOffDay;
use MyParcelNL\Sdk\src\Support\Str;

const PREFIX_FLAT_RATE = 'flat_rate:';
final class SettingsMigration extends AbstractPdkMigration
{
    private const OLD_CARRIERS            = ['postnl', 'dhlforyou', 'dhlparcelconnect', 'dhleuroplus'];
    private const TRANSFORM_CAST_BOOL     = 'bool';
    private const TRANSFORM_CAST_CENTS    = 'cents';
    private const TRANSFORM_CAST_FLOAT    = 'float';
    private const TRANSFORM_CAST_GRAMS    = 'grams';
    private const TRANSFORM_CAST_INT      = 'int';
    private const TRANSFORM_CAST_STRING   = 'string';
    private const TRANSFORM_KEY_CAST      = 'cast';
    private const TRANSFORM_KEY_SOURCE    = 'source';
    private const TRANSFORM_KEY_TARGET    = 'target';
    private const TRANSFORM_KEY_TRANSFORM = 'transform';

    /**
     * @var \MyParcelNL\Pdk\Base\Contract\CurrencyServiceInterface
     */
    private $currencyService;

    /**
     * @var \MyParcelNL\Pdk\Base\Contract\WeightServiceInterface
     */
    private $weightService;

    /**
     * @param  \MyParcelNL\Pdk\Base\Contract\CurrencyServiceInterface $currencyService
     * @param  \MyParcelNL\Pdk\Base\Contract\WeightServiceInterface   $weightService
     */
    public function __construct(CurrencyServiceInterface $currencyService, WeightServiceInterface $weightService)
    {
        $this->currencyService = $currencyService;
        $this->weightService   = $weightService;
    }

    /**
     * @return void
     */
    public function down(): void
    {
        /**
         * Nothing to do here.
         */
    }

    /**
     * @note This method is public for testing purposes.
     *
     * @param  array $oldSettings
     *
     * @return void
     * @see  \MyParcelNL\WooCommerce\Tests\Unit\Migration\Pdk\SettingsMigrationTest
     */
    public function migrateSettings(array $oldSettings): void
    {
        /** @var \MyParcelNL\Pdk\Settings\Contract\SettingsRepositoryInterface $settingsRepository */
        $settingsRepository = Pdk::get(SettingsRepositoryInterface::class);

        $newSettings = $this->transformSettings($oldSettings);

        $newSettings['carrier'] = new SettingsModelCollection();

        foreach (self::OLD_CARRIERS as $carrier) {
            $transformed                            = $this->transformSettings($oldSettings[$carrier] ?? []);
            $transformed['dropOffPossibilities']    =
                $this->transformDropOffPossibilities($oldSettings[$carrier] ?? []);
            $transformed['defaultPackageType']      = 'package';
            $transformed['exportReturnPackageType'] = 'package';

            $newSettings['carrier']->put($carrier, $transformed);
        }

        $settings = new Settings($newSettings);

        $settingsRepository->storeAllSettings($settings);
    }

    /**
     * @return void
     */
    public function up(): void
    {
        $this->migrateSettings($this->getOldSettings());
    }

    /**
     * @param  string $cast
     * @param  mixed  $value
     *
     * @return mixed
     */
    private function castValue(string $cast, $value)
    {
        switch ($cast) {
            case self::TRANSFORM_CAST_BOOL:
                return (bool) $value;

            case self::TRANSFORM_CAST_INT:
                return (int) $value;

            case self::TRANSFORM_CAST_STRING:
                return (string) $value;

            case self::TRANSFORM_CAST_FLOAT:
                return (float) $value;

            case self::TRANSFORM_CAST_CENTS:
                return $this->currencyService->convertToCents((float) $value);

            case self::TRANSFORM_CAST_GRAMS:
                return $this->weightService->convertToGrams((float) $value);

            default:
                return $value;
        }
    }

    private function getOldSettings(): array
    {
        return [
            'general'          => $this->getSettings('woocommerce_myparcel_general_settings'),
            'checkout'         => $this->getSettings('woocommerce_myparcel_checkout_settings'),
            'export_defaults'  => $this->getSettings('woocommerce_myparcel_export_defaults_settings'),

            // Carriers
            'postnl'           => $this->getSettings('woocommerce_myparcel_postnl_settings'),
            'dhleuroplus'      => $this->getSettings('woocommerce_myparcel_dhleuroplus_settings'),
            'dhlforyou'        => $this->getSettings('woocommerce_myparcel_dhlforyou_settings'),
            'dhlparcelconnect' => $this->getSettings('woocommerce_myparcel_dhlparcelconnect_settings'),
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
     * @return \Generator
     */
    private function getTransformationMap(): Generator
    {
        /**
         * General
         */

        yield [
            self::TRANSFORM_KEY_SOURCE => 'general.api_key',
            self::TRANSFORM_KEY_TARGET => 'account.apiKey',
            self::TRANSFORM_KEY_CAST   => self::TRANSFORM_CAST_STRING,
        ];

        yield [
            self::TRANSFORM_KEY_SOURCE    => 'general.export_mode',
            self::TRANSFORM_KEY_TARGET    => 'general.orderMode',
            self::TRANSFORM_KEY_TRANSFORM => function ($value): bool {
                return $value === 'pps';
            },
        ];

        yield [
            self::TRANSFORM_KEY_SOURCE    => 'general.download_display',
            self::TRANSFORM_KEY_TARGET    => 'label.output',
            self::TRANSFORM_KEY_TRANSFORM => function ($value): string {
                return $value === 'display' ? 'open' : 'download';
            },
        ];

        yield [
            self::TRANSFORM_KEY_SOURCE    => 'general.label_format',
            self::TRANSFORM_KEY_TARGET    => 'label.format',
            self::TRANSFORM_KEY_TRANSFORM => function ($value): string {
                return $value === 'A6' ? 'a6' : 'a4';
            },
        ];

        yield [
            self::TRANSFORM_KEY_SOURCE => 'general.ask_for_print_position',
            self::TRANSFORM_KEY_TARGET => 'label.prompt',
            self::TRANSFORM_KEY_CAST   => self::TRANSFORM_CAST_BOOL,
        ];

        yield [
            self::TRANSFORM_KEY_SOURCE => 'general.track_trace_email',
            self::TRANSFORM_KEY_TARGET => 'general.trackTraceInEmail',
            self::TRANSFORM_KEY_CAST   => self::TRANSFORM_CAST_BOOL,
        ];

        yield [
            self::TRANSFORM_KEY_SOURCE => 'general.track_trace_my_account',
            self::TRANSFORM_KEY_TARGET => 'general.trackTraceInAccount',
            self::TRANSFORM_KEY_CAST   => self::TRANSFORM_CAST_BOOL,
        ];

        yield [
            self::TRANSFORM_KEY_SOURCE => 'general.show_delivery_day',
            self::TRANSFORM_KEY_TARGET => 'checkout.deliveryOptionsDisplay',
            self::TRANSFORM_KEY_CAST   => self::TRANSFORM_CAST_BOOL,
        ];

        yield [
            self::TRANSFORM_KEY_SOURCE    => 'general.process_directly',
            self::TRANSFORM_KEY_TARGET    => 'general.conceptShipments',
            self::TRANSFORM_KEY_TRANSFORM => function ($value): bool {
                return ! $value;
            },
        ];

        yield [
            self::TRANSFORM_KEY_SOURCE => 'general.barcode_in_note',
            self::TRANSFORM_KEY_TARGET => 'general.barcodeInNote',
            self::TRANSFORM_KEY_CAST   => self::TRANSFORM_CAST_BOOL,
        ];

        yield [
            self::TRANSFORM_KEY_SOURCE => 'general.barcode_in_note_title',
            self::TRANSFORM_KEY_TARGET => 'general.barcodeInNoteTitle',
            self::TRANSFORM_KEY_CAST   => self::TRANSFORM_CAST_STRING,
        ];

        yield [
            self::TRANSFORM_KEY_SOURCE => 'general.error_logging',
            self::TRANSFORM_KEY_TARGET => 'general.apiLogging',
            self::TRANSFORM_KEY_CAST   => self::TRANSFORM_CAST_BOOL,
        ];

        yield [
            self::TRANSFORM_KEY_SOURCE    => 'general.order_status_automation',
            self::TRANSFORM_KEY_TARGET    => 'order.statusOnLabelCreate',
            self::TRANSFORM_KEY_TRANSFORM => function ($value, array $oldSettings) {
                if ('1' !== $value) {
                    return -1; // "None"
                }

                $oldOrderStatus = Arr::get($oldSettings, 'general.automatic_order_status');

                return sprintf("wc-%s", $oldOrderStatus ?? 'processing');
            },
        ];

        /**
         * Checkout
         */

        yield [
            self::TRANSFORM_KEY_SOURCE => 'checkout.use_split_address_fields',
            self::TRANSFORM_KEY_TARGET => 'checkout.useSeparateAddressFields',
            self::TRANSFORM_KEY_CAST   => self::TRANSFORM_CAST_BOOL,
        ];

        yield [
            self::TRANSFORM_KEY_SOURCE => 'checkout.delivery_options_enabled',
            self::TRANSFORM_KEY_TARGET => 'checkout.enableDeliveryOptions',
            self::TRANSFORM_KEY_CAST   => self::TRANSFORM_CAST_BOOL,
        ];

        yield [
            self::TRANSFORM_KEY_SOURCE => 'checkout.delivery_options_enabled_for_backorders',
            self::TRANSFORM_KEY_TARGET => 'checkout.enableDeliveryOptionsWhenNotInStock',
            self::TRANSFORM_KEY_CAST   => self::TRANSFORM_CAST_BOOL,
        ];

        yield [
            self::TRANSFORM_KEY_SOURCE => 'checkout.delivery_options_display',
            self::TRANSFORM_KEY_TARGET => 'checkout.deliveryOptionsDisplay',
            self::TRANSFORM_KEY_CAST   => self::TRANSFORM_CAST_BOOL,
        ];

        // NOTE: Risky. Resulting value may not exist in array of checkout hooks.
        yield [
            self::TRANSFORM_KEY_SOURCE => 'checkout.delivery_options_position',
            self::TRANSFORM_KEY_TARGET => 'checkout.deliveryOptionsPosition',
            self::TRANSFORM_KEY_CAST   => self::TRANSFORM_CAST_STRING,
        ];

        yield [
            self::TRANSFORM_KEY_SOURCE    => 'checkout.delivery_options_price_format',
            self::TRANSFORM_KEY_TARGET    => 'checkout.priceType',
            self::TRANSFORM_KEY_TRANSFORM => function ($value): string {
                return $value === 'total_price' ? 'included' : 'excluded';
            },
        ];

        yield [
            self::TRANSFORM_KEY_SOURCE    => 'checkout.pickup_locations_default_view',
            self::TRANSFORM_KEY_TARGET    => 'checkout.pickupLocationsDefaultView',
            self::TRANSFORM_KEY_TRANSFORM => function ($value): string {
                return $value === 'map' ? 'map' : 'list';
            },
        ];

        yield [
            self::TRANSFORM_KEY_SOURCE => 'checkout.delivery_options_custom_css',
            self::TRANSFORM_KEY_TARGET => 'checkout.deliveryOptionsCustomCss',
            self::TRANSFORM_KEY_CAST   => self::TRANSFORM_CAST_STRING,
        ];

        yield [
            self::TRANSFORM_KEY_SOURCE => 'checkout.header_delivery_options_title',
            self::TRANSFORM_KEY_TARGET => 'checkout.deliveryOptionsHeader',
            self::TRANSFORM_KEY_CAST   => self::TRANSFORM_CAST_STRING,
        ];

        /**
         * Export defaults
         */

        yield [
            self::TRANSFORM_KEY_SOURCE    => 'export_defaults.shipping_methods_package_types',
            self::TRANSFORM_KEY_TARGET    => 'checkout.allowedShippingMethods',
            self::TRANSFORM_KEY_TRANSFORM => function ($value): array {
                if (! is_array($value)) {
                    return [];
                }

                $shippingMethods = array_reduce(Arr::flatten($value), static function ($carry, $item) {
                    $parts  = explode(':', $item);
                    $method = $item;

                    if (count($parts) === 1) {
                        $method = $parts[0] . ':1';
                    }

                    if (count($parts) > 2) {
                        $method = sprintf('%s:%s', $parts[0], $parts[1]);
                    }

                    if ($parts[1] > 10 && Str::startsWith($item, PREFIX_FLAT_RATE)) {
                        $carry[] = $method;
                        $method  = sprintf('%s%s', PREFIX_FLAT_RATE, $parts[1][0]);
                    }

                    $carry[] = $method;

                    return $carry;
                }, []);

                return array_values(array_unique($shippingMethods));
            },
        ];

        yield [
            self::TRANSFORM_KEY_SOURCE => 'export_defaults.connect_email',
            self::TRANSFORM_KEY_TARGET => 'general.shareCustomerInformation',
            self::TRANSFORM_KEY_CAST   => self::TRANSFORM_CAST_BOOL,
        ];

        yield [
            self::TRANSFORM_KEY_SOURCE => 'export_defaults.save_customer_address',
            self::TRANSFORM_KEY_TARGET => 'order.saveCustomerAddress',
            self::TRANSFORM_KEY_CAST   => self::TRANSFORM_CAST_BOOL,
        ];

        yield [
            self::TRANSFORM_KEY_SOURCE => 'export_defaults.label_description',
            self::TRANSFORM_KEY_TARGET => 'label.description',
            self::TRANSFORM_KEY_CAST   => self::TRANSFORM_CAST_STRING,
        ];

        yield [
            self::TRANSFORM_KEY_SOURCE => 'export_defaults.empty_parcel_weight',
            self::TRANSFORM_KEY_TARGET => 'order.emptyParcelWeight',
            self::TRANSFORM_KEY_CAST   => self::TRANSFORM_CAST_GRAMS,
        ];

        yield [
            self::TRANSFORM_KEY_SOURCE => 'export_defaults.empty_digital_stamp_weight',
            self::TRANSFORM_KEY_TARGET => 'order.emptyDigitalStampWeight',
            self::TRANSFORM_KEY_CAST   => self::TRANSFORM_CAST_GRAMS,
        ];

        yield [
            self::TRANSFORM_KEY_SOURCE => 'export_defaults.hs_code',
            self::TRANSFORM_KEY_TARGET => 'customs.customsCode',
            self::TRANSFORM_KEY_CAST   => self::TRANSFORM_CAST_STRING,
        ];

        yield [
            self::TRANSFORM_KEY_SOURCE => 'export_defaults.package_contents',
            self::TRANSFORM_KEY_TARGET => 'customs.packageContents',
            self::TRANSFORM_KEY_CAST   => self::TRANSFORM_CAST_STRING,
        ];

        yield [
            self::TRANSFORM_KEY_SOURCE => 'export_defaults.country_of_origin',
            self::TRANSFORM_KEY_TARGET => 'customs.countryOfOrigin',
            self::TRANSFORM_KEY_CAST   => self::TRANSFORM_CAST_STRING,
        ];

        /**
         * Carriers
         */
        yield [
            self::TRANSFORM_KEY_SOURCE => 'export_age_check',
            self::TRANSFORM_KEY_TARGET => 'exportAgeCheck',
            self::TRANSFORM_KEY_CAST   => self::TRANSFORM_CAST_BOOL,
        ];

        yield [
            self::TRANSFORM_KEY_SOURCE => 'export_insured',
            self::TRANSFORM_KEY_TARGET => 'exportInsurance',
            self::TRANSFORM_KEY_CAST   => self::TRANSFORM_CAST_BOOL,
        ];

        yield [
            self::TRANSFORM_KEY_SOURCE => 'export_insured_from_price',
            self::TRANSFORM_KEY_TARGET => 'exportInsuranceFromAmount',
            self::TRANSFORM_KEY_CAST   => self::TRANSFORM_CAST_CENTS,
        ];

        yield [
            self::TRANSFORM_KEY_SOURCE => 'export_insured_amount',
            self::TRANSFORM_KEY_TARGET => 'exportInsuranceUpTo',
            self::TRANSFORM_KEY_CAST   => self::TRANSFORM_CAST_CENTS,
        ];

        yield [
            self::TRANSFORM_KEY_SOURCE => 'export_insured_eu_amount',
            self::TRANSFORM_KEY_TARGET => 'exportInsuranceUpToEu',
            self::TRANSFORM_KEY_CAST   => self::TRANSFORM_CAST_CENTS,
        ];

        yield [
            self::TRANSFORM_KEY_SOURCE => 'export_insured_for_be',
            self::TRANSFORM_KEY_TARGET => 'exportInsuranceUpToBe',
            self::TRANSFORM_KEY_CAST   => self::TRANSFORM_CAST_CENTS,
        ];

        yield [
            self::TRANSFORM_KEY_SOURCE => 'export_large_format',
            self::TRANSFORM_KEY_TARGET => 'exportLargeFormat',
            self::TRANSFORM_KEY_CAST   => self::TRANSFORM_CAST_BOOL,
        ];

        yield [
            self::TRANSFORM_KEY_SOURCE => 'export_only_recipient',
            self::TRANSFORM_KEY_TARGET => 'exportOnlyRecipient',
            self::TRANSFORM_KEY_CAST   => self::TRANSFORM_CAST_BOOL,
        ];

        yield [
            self::TRANSFORM_KEY_SOURCE => 'export_return_shipments',
            self::TRANSFORM_KEY_TARGET => 'exportReturn',
            self::TRANSFORM_KEY_CAST   => self::TRANSFORM_CAST_BOOL,
        ];

        yield [
            self::TRANSFORM_KEY_SOURCE => 'export_signature',
            self::TRANSFORM_KEY_TARGET => 'exportSignature',
            self::TRANSFORM_KEY_CAST   => self::TRANSFORM_CAST_BOOL,
        ];

        yield [
            self::TRANSFORM_KEY_SOURCE => 'delivery_enabled',
            self::TRANSFORM_KEY_TARGET => 'allowDeliveryOptions',
            self::TRANSFORM_KEY_CAST   => self::TRANSFORM_CAST_BOOL,
        ];

        yield [
            self::TRANSFORM_KEY_SOURCE => 'delivery_standard_fee',
            self::TRANSFORM_KEY_TARGET => 'priceDeliveryTypeStandard',
            self::TRANSFORM_KEY_CAST   => self::TRANSFORM_CAST_CENTS,
        ];

        yield [
            self::TRANSFORM_KEY_SOURCE => 'digital_stamp_default_weight',
            self::TRANSFORM_KEY_TARGET => 'digitalStampDefaultWeight',
            self::TRANSFORM_KEY_CAST   => self::TRANSFORM_CAST_INT,
        ];

        yield [
            self::TRANSFORM_KEY_SOURCE => 'allow_show_delivery_date',
            self::TRANSFORM_KEY_TARGET => 'showDeliveryDay',
            self::TRANSFORM_KEY_CAST   => self::TRANSFORM_CAST_BOOL,
        ];

        yield [
            self::TRANSFORM_KEY_SOURCE => 'delivery_days_window',
            self::TRANSFORM_KEY_TARGET => 'deliveryDaysWindow',
            self::TRANSFORM_KEY_CAST   => self::TRANSFORM_CAST_INT,
        ];

        yield [
            self::TRANSFORM_KEY_SOURCE => 'drop_off_delay',
            self::TRANSFORM_KEY_TARGET => 'dropOffDelay',
            self::TRANSFORM_KEY_CAST   => self::TRANSFORM_CAST_INT,
        ];

        yield [
            self::TRANSFORM_KEY_SOURCE => 'monday_delivery_enabled',
            self::TRANSFORM_KEY_TARGET => 'allowMondayDelivery',
            self::TRANSFORM_KEY_CAST   => self::TRANSFORM_CAST_BOOL,
        ];

        yield [
            self::TRANSFORM_KEY_SOURCE => 'delivery_morning_enabled',
            self::TRANSFORM_KEY_TARGET => 'allowMorningDelivery',
            self::TRANSFORM_KEY_CAST   => self::TRANSFORM_CAST_BOOL,
        ];

        yield [
            self::TRANSFORM_KEY_SOURCE => 'delivery_morning_fee',
            self::TRANSFORM_KEY_TARGET => 'priceDeliveryTypeMorning',
            self::TRANSFORM_KEY_CAST   => self::TRANSFORM_CAST_CENTS,
        ];

        yield [
            self::TRANSFORM_KEY_SOURCE => 'delivery_evening_enabled',
            self::TRANSFORM_KEY_TARGET => 'allowEveningDelivery',
            self::TRANSFORM_KEY_CAST   => self::TRANSFORM_CAST_BOOL,
        ];

        yield [
            self::TRANSFORM_KEY_SOURCE => 'delivery_evening_fee',
            self::TRANSFORM_KEY_TARGET => 'priceDeliveryTypeEvening',
            self::TRANSFORM_KEY_CAST   => self::TRANSFORM_CAST_CENTS,
        ];

        yield [
            self::TRANSFORM_KEY_SOURCE => 'only_recipient_enabled',
            self::TRANSFORM_KEY_TARGET => 'allowOnlyRecipient',
            self::TRANSFORM_KEY_CAST   => self::TRANSFORM_CAST_BOOL,
        ];

        yield [
            self::TRANSFORM_KEY_SOURCE => 'only_recipient_fee',
            self::TRANSFORM_KEY_TARGET => 'priceOnlyRecipient',
            self::TRANSFORM_KEY_CAST   => self::TRANSFORM_CAST_CENTS,
        ];

        yield [
            self::TRANSFORM_KEY_SOURCE => 'signature_enabled',
            self::TRANSFORM_KEY_TARGET => 'allowSignature',
            self::TRANSFORM_KEY_CAST   => self::TRANSFORM_CAST_BOOL,
        ];

        yield [
            self::TRANSFORM_KEY_SOURCE => 'signature_fee',
            self::TRANSFORM_KEY_TARGET => 'priceSignature',
            self::TRANSFORM_KEY_CAST   => self::TRANSFORM_CAST_CENTS,
        ];

        yield [
            self::TRANSFORM_KEY_SOURCE => 'pickup_enabled',
            self::TRANSFORM_KEY_TARGET => 'allowPickupPoints',
            self::TRANSFORM_KEY_CAST   => self::TRANSFORM_CAST_BOOL,
        ];

        yield [
            self::TRANSFORM_KEY_SOURCE => 'pickup_fee',
            self::TRANSFORM_KEY_TARGET => 'priceDeliveryTypePickup',
            self::TRANSFORM_KEY_CAST   => self::TRANSFORM_CAST_CENTS,
        ];
    }

    /**
     * @param  array $oldSettings
     *
     * @return array
     */
    private function transformDropOffPossibilities(array $oldSettings): array
    {
        return [
            'dropOffDays' => array_map(static function ($weekday) use ($oldSettings) {
                $cutoffTime = $oldSettings['cutoff_time'] ?? null;

                switch ($weekday) {
                    case DropOffDay::WEEKDAY_FRIDAY:
                        $cutoffTime = $oldSettings['friday_cutoff_time'] ?? $cutoffTime;
                        break;

                    case DropOffDay::WEEKDAY_SATURDAY:
                        $cutoffTime = $oldSettings['saturday_cutoff_time'] ?? $cutoffTime;
                        break;
                }

                return [
                    'cutoffTime'        => $cutoffTime,
                    'sameDayCutoffTime' => $oldSettings['same_day_delivery_cutoff_time'] ?? null,
                    'weekday'           => $weekday,
                    'dispatch'          => in_array((string) $weekday, $oldSettings['drop_off_days'] ?? [], true),
                ];
            }, DropOffDay::WEEKDAYS),
        ];
    }

    /**
     * @param  array $oldSettings
     *
     * @return array
     */
    private function transformSettings(array $oldSettings): array
    {
        $newSettings = [];

        foreach ($this->getTransformationMap() as $item) {
            if (! Arr::has($oldSettings, $item[self::TRANSFORM_KEY_SOURCE])) {
                continue;
            }

            $value    = Arr::get($oldSettings, $item[self::TRANSFORM_KEY_SOURCE]);
            $newValue = $value;

            if ($item[self::TRANSFORM_KEY_TRANSFORM] ?? false) {
                $newValue = $item[self::TRANSFORM_KEY_TRANSFORM]($newValue, $oldSettings);
            }

            if ($item[self::TRANSFORM_KEY_CAST] ?? false) {
                $newValue = $this->castValue($item[self::TRANSFORM_KEY_CAST], $newValue);
            }

            Arr::set($newSettings, $item[self::TRANSFORM_KEY_TARGET], $newValue);
        }

        return $newSettings;
    }
}
