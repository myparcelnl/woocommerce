<?php

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Migration\Pdk;

use Generator;
use MyParcelNL\Pdk\Base\Contract\CurrencyServiceInterface;
use MyParcelNL\Pdk\Base\Contract\WeightServiceInterface;
use MyParcelNL\Pdk\Base\Support\Arr;
use MyParcelNL\Pdk\Base\Support\Collection;
use MyParcelNL\Pdk\Facade\Pdk;
use MyParcelNL\Pdk\Settings\Collection\SettingsModelCollection;
use MyParcelNL\Pdk\Settings\Contract\PdkSettingsRepositoryInterface;
use MyParcelNL\Pdk\Settings\Model\Settings;
use MyParcelNL\Pdk\Shipment\Model\DropOffDay;
use MyParcelNL\Sdk\src\Support\Str;
use MyParcelNL\WooCommerce\WooCommerce\Repository\WcShippingRepository;
use WP_Term;

class SettingsMigration extends AbstractPdkMigration
{
    public const    LEGACY_OPTION_GENERAL_SETTINGS          = 'woocommerce_myparcel_general_settings';
    public const    LEGACY_OPTION_CHECKOUT_SETTINGS         = 'woocommerce_myparcel_checkout_settings';
    public const    LEGACY_OPTION_EXPORT_DEFAULTS_SETTINGS  = 'woocommerce_myparcel_export_defaults_settings';
    public const    LEGACY_OPTION_POSTNL_SETTINGS           = 'woocommerce_myparcel_postnl_settings';
    public const    LEGACY_OPTION_DHLEUROPLUS_SETTINGS      = 'woocommerce_myparcel_dhleuroplus_settings';
    public const    LEGACY_OPTION_DHLFORYOU_SETTINGS        = 'woocommerce_myparcel_dhlforyou_settings';
    public const    LEGACY_OPTION_DHLPARCELCONNECT_SETTINGS = 'woocommerce_myparcel_dhlparcelconnect_settings';
    protected const TRANSFORM_CAST_BOOL                     = 'bool';
    protected const TRANSFORM_CAST_CENTS                    = 'cents';
    protected const TRANSFORM_CAST_FLOAT                    = 'float';
    protected const TRANSFORM_CAST_GRAMS                    = 'grams';
    protected const TRANSFORM_CAST_INT                      = 'int';
    protected const TRANSFORM_CAST_STRING                   = 'string';
    protected const TRANSFORM_KEY_CAST                      = 'cast';
    protected const TRANSFORM_KEY_SOURCE                    = 'source';
    protected const TRANSFORM_KEY_TARGET                    = 'target';
    protected const TRANSFORM_KEY_TRANSFORM                 = 'transform';
    private const   OLD_CARRIERS                            = [
        'postnl',
        'dhlforyou',
        'dhlparcelconnect',
        'dhleuroplus',
    ];

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
     * @see  \MyParcelNL\WooCommerce\Pdk\SettingsMigrationTest
     */
    public function migrateSettings(array $oldSettings): void
    {
        /** @var \MyParcelNL\Pdk\Settings\Contract\PdkSettingsRepositoryInterface $settingsRepository */
        $settingsRepository = Pdk::get(PdkSettingsRepositoryInterface::class);

        $newSettings = $this->transformSettings($oldSettings, $this->getTransformationMap());

        $newSettings['carrier'] = new SettingsModelCollection();

        foreach (self::OLD_CARRIERS as $carrier) {
            $transformed                            =
                $this->transformSettings($oldSettings[$carrier] ?? [], $this->getTransformationMap());
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
     * @param  array      $oldSettings
     * @param  \Generator $map
     *
     * @return array
     */
    protected function transformSettings(array $oldSettings, Generator $map): array
    {
        $newSettings = [];

        //todo: onderstaande variabelen verwijderen
        $sources    = [];
        $targets    = [];
        $casts      = [];
        $transforms = [];
        foreach ($map as $item) {
            if (! Arr::has($oldSettings, $item[self::TRANSFORM_KEY_SOURCE])) {
                continue;
            }
            $sources[] = $item['source'];

            $value    = Arr::get($oldSettings, $item[self::TRANSFORM_KEY_SOURCE]);
            $newValue = $value;

            if ($item[self::TRANSFORM_KEY_TRANSFORM] ?? false) {
                $transforms[] = $item['transform'];
                // De Magic gebeurt in deze closures, kijk hier goed wat er mis gaat. En bedenk ook wat er goed moet gaan.
                $newValue = $item[self::TRANSFORM_KEY_TRANSFORM]($newValue, $oldSettings);
            }

            if ($item[self::TRANSFORM_KEY_CAST] ?? false) {
                $casts[] = $item['cast'];
                $newValue = $this->castValue($item[self::TRANSFORM_KEY_CAST], $newValue);
            }

            $targets[] = $item['target'];
            Arr::set($newSettings, $item[self::TRANSFORM_KEY_TARGET], $newValue);
        }

        return $newSettings;
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

    /**
     * @param  string                                  $shippingMethod
     * @param  \MyParcelNL\Pdk\Base\Support\Collection $wcShippingMethods
     *
     * @return string
     */
    private function convertShippingMethodId(string $shippingMethod, Collection $wcShippingMethods): string
    {
        if (! Str::contains($shippingMethod, ':')) {
            return $shippingMethod;
        }

        [$shippingMethodName, $termId] = explode(':', $shippingMethod);

        if ($termId) {
            $match = $wcShippingMethods
                ->filter(function ($shippingMethod) use ($termId, $shippingMethodName) {
                    return $shippingMethod->id === $shippingMethodName && (int) $shippingMethod->instance_id === (int) $termId;
                })
                ->first();

            if (! $match) {
                /** @var WP_Term|null $foundShippingClass */
                $foundShippingClass = $wcShippingMethods
                    ->filter(function ($shippingMethod) use ($termId) {
                        if (! $shippingMethod instanceof WP_Term) {
                            return false;
                        }

                        return $shippingMethod->term_id === (int) $termId;
                    })
                    ->first();

                if ($foundShippingClass) {
                    return sprintf('shipping_class:%s', $foundShippingClass->term_id);
                }
            }
        }

        return $shippingMethod;
    }

    private function getOldSettings(): array
    {
        return [
            'general'          => $this->getSettings(self::LEGACY_OPTION_GENERAL_SETTINGS),
            'checkout'         => $this->getSettings(self::LEGACY_OPTION_CHECKOUT_SETTINGS),
            'export_defaults'  => $this->getSettings(self::LEGACY_OPTION_EXPORT_DEFAULTS_SETTINGS),

            // Carriers
            'postnl'           => $this->getSettings(self::LEGACY_OPTION_POSTNL_SETTINGS),
            'dhleuroplus'      => $this->getSettings(self::LEGACY_OPTION_DHLEUROPLUS_SETTINGS),
            'dhlforyou'        => $this->getSettings(self::LEGACY_OPTION_DHLFORYOU_SETTINGS),
            'dhlparcelconnect' => $this->getSettings(self::LEGACY_OPTION_DHLPARCELCONNECT_SETTINGS),
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
            self::TRANSFORM_KEY_TARGET    => 'order.orderMode',
            self::TRANSFORM_KEY_TRANSFORM => function ($value): bool {
                return 'pps' === $value;
            },
        ];

        yield [
            self::TRANSFORM_KEY_SOURCE    => 'general.download_display',
            self::TRANSFORM_KEY_TARGET    => 'label.output',
            self::TRANSFORM_KEY_TRANSFORM => function ($value): string {
                return 'display' === $value ? 'open' : 'download';
            },
        ];

        yield [
            self::TRANSFORM_KEY_SOURCE    => 'general.label_format',
            self::TRANSFORM_KEY_TARGET    => 'label.format',
            self::TRANSFORM_KEY_TRANSFORM => function ($value): string {
                return 'A6' === $value ? 'a6' : 'a4';
            },
        ];

        yield [
            self::TRANSFORM_KEY_SOURCE => 'general.ask_for_print_position',
            self::TRANSFORM_KEY_TARGET => 'label.prompt',
            self::TRANSFORM_KEY_CAST   => self::TRANSFORM_CAST_BOOL,
        ];

        yield [
            self::TRANSFORM_KEY_SOURCE => 'general.track_trace_email',
            self::TRANSFORM_KEY_TARGET => 'order.trackTraceInEmail',
            self::TRANSFORM_KEY_CAST   => self::TRANSFORM_CAST_BOOL,
        ];

        yield [
            self::TRANSFORM_KEY_SOURCE => 'general.track_trace_my_account',
            self::TRANSFORM_KEY_TARGET => 'order.trackTraceInAccount',
            self::TRANSFORM_KEY_CAST   => self::TRANSFORM_CAST_BOOL,
        ];

        yield [
            self::TRANSFORM_KEY_SOURCE => 'general.show_delivery_day',
            self::TRANSFORM_KEY_TARGET => 'checkout.deliveryOptionsDisplay',
            self::TRANSFORM_KEY_CAST   => self::TRANSFORM_CAST_BOOL,
        ];

        yield [
            self::TRANSFORM_KEY_SOURCE    => 'general.process_directly',
            self::TRANSFORM_KEY_TARGET    => 'order.conceptShipments',
            self::TRANSFORM_KEY_TRANSFORM => function ($value): bool {
                return ! $value;
            },
        ];

        yield [
            self::TRANSFORM_KEY_SOURCE => 'general.barcode_in_note',
            self::TRANSFORM_KEY_TARGET => 'order.barcodeInNote',
            self::TRANSFORM_KEY_CAST   => self::TRANSFORM_CAST_BOOL,
        ];

        yield [
            self::TRANSFORM_KEY_SOURCE => 'general.barcode_in_note_title',
            self::TRANSFORM_KEY_TARGET => 'order.barcodeInNoteTitle',
            self::TRANSFORM_KEY_CAST   => self::TRANSFORM_CAST_STRING,
        ];

        yield [
            self::TRANSFORM_KEY_SOURCE => 'general.error_logging',
            self::TRANSFORM_KEY_TARGET => 'order.apiLogging',
            self::TRANSFORM_KEY_CAST   => self::TRANSFORM_CAST_BOOL,
        ];

        yield [
            self::TRANSFORM_KEY_SOURCE    => 'general.order_status_automation',
            self::TRANSFORM_KEY_TARGET    => 'order.statusOnLabelCreate',
            self::TRANSFORM_KEY_TRANSFORM => function ($value, array $oldSettings) {
                if ('1' !== $value) {
                    return Settings::OPTION_NONE;
                }

                $oldOrderStatus = Arr::get($oldSettings, 'general.automatic_order_status');

                return sprintf('wc-%s', $oldOrderStatus ?? 'processing');
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
                return 'total_price' === $value ? 'included' : 'excluded';
            },
        ];

        yield [
            self::TRANSFORM_KEY_SOURCE    => 'checkout.pickup_locations_default_view',
            self::TRANSFORM_KEY_TARGET    => 'checkout.pickupLocationsDefaultView',
            self::TRANSFORM_KEY_TRANSFORM => function ($value): string {
                return 'map' === $value ? 'map' : 'list';
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
                /** @var string[] $keys */
                $keys = Pdk::get('allowedShippingMethodsKeys');
                // Create a new empty shipping methods map
                $newValue = array_combine($keys, array_fill(0, count($keys), []));

                if (! is_array($value)) {
                    return $newValue;
                }

                $wcShippingRepository = Pdk::get(WcShippingRepository::class);
                $wcShippingMethods    = $wcShippingRepository
                    ->getShippingMethods()
                    ->merge($wcShippingRepository->getShippingClasses());

                $allShippingMethods = array_unique(Arr::flatten($value));

                // Find the smallest enabled package type for each shipping method, and add it to the new map.
                foreach ($allShippingMethods as $shippingMethod) {
                    $packageTypes = array_keys(array_filter($value, static function ($methods) use ($shippingMethod) {
                        return in_array($shippingMethod, $methods, true);
                    }));

                    $smallestPackageType              = Arr::last($packageTypes);
                    $newValue[$smallestPackageType][] = $this->convertShippingMethodId(
                        $shippingMethod,
                        $wcShippingMethods
                    );
                }

                return $newValue;
            },
        ];

        yield [
            self::TRANSFORM_KEY_SOURCE => 'export_defaults.connect_email',
            self::TRANSFORM_KEY_TARGET => 'order.shareCustomerInformation',
            self::TRANSFORM_KEY_CAST   => self::TRANSFORM_CAST_BOOL,
        ];

        yield [
            self::TRANSFORM_KEY_SOURCE => 'export_defaults.save_customer_address',
            self::TRANSFORM_KEY_TARGET => 'order.saveCustomerAddress',
            self::TRANSFORM_KEY_CAST   => self::TRANSFORM_CAST_BOOL,
        ];

        yield [
            self::TRANSFORM_KEY_SOURCE    => 'export_defaults.label_description',
            self::TRANSFORM_KEY_TARGET    => 'label.description',
            self::TRANSFORM_KEY_CAST      => self::TRANSFORM_CAST_STRING,
            self::TRANSFORM_KEY_TRANSFORM => function ($value): string {
                return str_replace('[ORDER_NR]', '[ORDER_ID]', (string) $value);
            },
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
            self::TRANSFORM_KEY_TARGET => 'allowPickupLocations',
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
}
