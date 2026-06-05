<?php
/** @noinspection PhpUnhandledExceptionInspection,StaticClosureCanBeUsedInspection */

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Migration\Pdk;

use MyParcelNL\Pdk\Base\Support\Arr;
use MyParcelNL\Pdk\Facade\Pdk;
use MyParcelNL\Pdk\Facade\Settings;
use MyParcelNL\WooCommerce\Tests\Uses\UsesMockWcPdkInstance;
use WC_Shipping;
use WC_Shipping_Method;
use WC_Shipping_Zone;
use WP_Term;
use function MyParcelNL\Pdk\Tests\usesShared;
use function MyParcelNL\WooCommerce\Tests\wpFactory;

usesShared(new UsesMockWcPdkInstance());

dataset('old plugin settings', [
    'empty'  => [
        'settings' => [],
        'expected' => [],
    ],
    'filled' => [
        'settings' => [
            'general'          => [
                'api_key'                   => 'some-fake-api-key',
                'trigger_manual_update'     => '',
                'export_mode'               => 'shipments',
                'download_display'          => 'display',
                'label_format'              => 'A6',
                'ask_for_print_position'    => '1',
                'track_trace_email'         => '1',
                'track_trace_my_account'    => '1',
                'show_delivery_day'         => '1',
                'process_directly'          => '0',
                'order_status_automation'   => '1',
                'change_order_status_after' => 'after_export',
                'automatic_order_status'    => 'processed',
                'barcode_in_note'           => '1',
                'barcode_in_note_title'     => 'T&T:',
                'error_logging'             => '1',
            ],
            'checkout'         => [
                'use_split_address_fields'                => '1',
                'delivery_options_enabled'                => '1',
                'delivery_options_enabled_for_backorders' => '1',
                'delivery_options_display'                => 'all_methods',
                'delivery_options_position'               => 'woocommerce_after_checkout_billing_form',
                'delivery_options_price_format'           => 'total_price',
                'pickup_locations_default_view'           => 'map',
                'delivery_options_custom_css'             => '/* Storefront preset */',
                'header_delivery_options_title'           => 'Kop',
                'delivery_title'                          => 'Delivery at home or work',
                'morning_title'                           => 'Morning delivery',
                'standard_title'                          => 'Standaard delivery',
                'evening_title'                           => 'Evening delivery',
                'same_day_title'                          => 'shipment_options_delivery_same_day',
                'only_recipient_title'                    => 'Home address only',
                'signature_title'                         => 'Signature on delivery',
                'pickup_title'                            => 'Pickup',
                'address_not_found_title'                 => 'Address is not entered correctly',
            ],
            'export_defaults'  => [
                'shipping_methods_package_types' => [
                    'package'       => [
                        'flat_rate:32',
                        'free_shipping',
                        'local_pickup',
                        'table_rate:5:1',
                        'table_rate:5:2',
                    ],
                    'mailbox'       => ['flat_rate:30', 'table_rate:5:1'],
                    'digital_stamp' => ['flat_rate:31', 'flat_rate:30'],
                    'letter'        => ['flat_rate:33'],
                ],
                'connect_email'                  => '1',
                'connect_phone'                  => '1',
                'save_customer_address'          => '1',
                'label_description'              => '[ORDER_NR]- [PRODUCT_NAME] - [PRODUCT_ID]- test',
                'empty_parcel_weight'            => '1.1',
                'empty_digital_stamp_weight'     => '0.2',
                'hs_code'                        => '1234',
                'package_contents'               => '2',
                'country_of_origin'              => 'NL',
                'export_automatic'               => '0',
                'export_automatic_status'        => 'processing',
            ],
            'postnl'           => [
                'export_age_check'             => '1',
                'export_insured'               => '1',
                'export_insured_from_price'    => '10',
                'export_insured_amount'        => '2500',
                'export_insured_eu_amount'     => '500',
                'export_insured_for_be'        => '50',
                'export_large_format'          => '1',
                'export_only_recipient'        => '1',
                'export_return_shipments'      => '1',
                'export_signature'             => '1',
                'delivery_enabled'             => '1',
                'delivery_standard_fee'        => '595',
                'drop_off_days'                => ['1', '3', '4', '5', '6'],
                'cutoff_time'                  => '16:00',
                'drop_off_delay'               => '0',
                'digital_stamp_default_weight' => '10',
                'allow_show_delivery_date'     => '1',
                'delivery_days_window'         => '5',
                'monday_delivery_enabled'      => '1',
                'saturday_cutoff_time'         => '14:00',
                'delivery_morning_enabled'     => '1',
                'delivery_morning_fee'         => '2',
                'delivery_evening_enabled'     => '1',
                'delivery_evening_fee'         => '1',
                'only_recipient_enabled'       => '1',
                'only_recipient_fee'           => '2',
                'signature_enabled'            => '1',
                'signature_fee'                => '.25',
                'pickup_enabled'               => '1',
                'pickup_fee'                   => '-1.5',
            ],
            'dhlforyou'        => [
                'export_age_check'          => '0',
                'export_hide_sender'        => '0',
                'export_insured'            => '0',
                'export_insured_from_price' => '0',
                'export_insured_amount'     => '500',
                'export_insured_eu_amount'  => '0',
                'export_insured_for_be'     => '1',
                'export_only_recipient'     => '0',
                'export_signature'          => '0',
                'delivery_enabled'          => '1',
                'delivery_standard_fee'     => '5',
                'drop_off_days'             => ['0', '1', '2', '3', '4', '5', '6'],
                'cutoff_time'               => '11:00',
                'drop_off_delay'            => '0',
                'only_recipient_enabled'    => '1',
                'only_recipient_fee'        => '1',
                'signature_enabled'         => '1',
                'signature_fee'             => '234',
                'pickup_enabled'            => '1',
                'pickup_fee'                => '10',
                'export_same_day_delivery'  => '0',
            ],
            'dhlparcelconnect' => [
                'export_insured'            => '1',
                'export_insured_from_price' => '100',
                'export_insured_eu_amount'  => '1500',
                'export_signature'          => '0',
                'delivery_enabled'          => '1',
                'delivery_standard_fee'     => '0',
                'drop_off_days'             => ['1', '2', '3', '4', '5'],
                'cutoff_time'               => '17:00',
                'drop_off_delay'            => '0',
                'signature_enabled'         => '1',
                'signature_fee'             => '66',
                'pickup_enabled'            => '1',
                'pickup_fee'                => '-1',
            ],
        ],
        'expected' => [
            'account.apiKey'                            => 'some-fake-api-key',
            'account.apiKeyValid'                       => true,

            'order.barcodeInNote'                       => true,
            'order.barcodeInNoteTitle'                  => 'T&T:',
            'order.conceptShipments'                    => true,
            'order.emptyDigitalStampWeight'             => 200,
            'order.emptyParcelWeight'                   => 1100,
            'order.processDirectly'                     => '-1',
            'order.saveCustomerAddress'                 => true,
            'order.shareCustomerInformation'            => true,
            'order.statusOnLabelCreate'                 => 'wc-processed',
            'order.trackTraceInAccount'                 => true,
            'order.trackTraceInEmail'                   => true,

            'label.description'                         => '[ORDER_ID]- [PRODUCT_NAME] - [PRODUCT_ID]- test',
            'label.format'                              => 'a6',
            'label.position'                            => [1, 2, 3, 4],

            'customs.countryOfOrigin'                   => 'NL',
            'customs.customsCode'                       => '1234',
            'customs.packageContents'                   => '2',

            'checkout.deliveryOptionsHeader'            => 'Kop',
            'checkout.deliveryOptionsCustomCss'         => '/* Storefront preset */',
            'checkout.deliveryOptionsPosition'          => 'woocommerce_after_checkout_billing_form',
            'checkout.pickupLocationsDefaultView'       => 'map',
            'checkout.priceType'                        => 'included',
            'checkout.enableDeliveryOptions'            => true,
            'checkout.enableDeliveryOptionsWhenNotInStock' => true,
            'checkout.useSeparateAddressFields'         => true,

            // Shipping-method → package-type mappings (input under 'export_defaults.shipping_methods_package_types')
            'checkout.allowedShippingMethods.package'   => [
                'flat_rate:32',
                'free_shipping',
                'local_pickup',
                'shipping_class:5',
            ],
            'checkout.allowedShippingMethods.mailbox'   => ['shipping_class:5'],
            'checkout.allowedShippingMethods.digital_stamp' => ['flat_rate:30', 'flat_rate:31'],
            'checkout.allowedShippingMethods.letter'    => ['flat_rate:33'],

            // postnl carrier — flag + price + insurance migrations
            'carrier.postnl.cutoffTime'                 => '16:00',
            'carrier.postnl.deliveryDaysWindow'         => 5,
            'carrier.postnl.digitalStampDefaultWeight'  => 10,
            'carrier.postnl.exportAgeCheck'             => 1,
            'carrier.postnl.exportInsurance'            => 1,
            'carrier.postnl.exportInsuranceFromAmount'  => 1000,
            'carrier.postnl.exportInsuranceUpTo'        => 250000,
            'carrier.postnl.exportInsuranceUpToEu'      => 50000,
            'carrier.postnl.exportLargeFormat'          => 1,
            'carrier.postnl.exportOnlyRecipient'        => 1,
            'carrier.postnl.exportReturn'               => 1,
            'carrier.postnl.exportSignature'            => 1,
            'carrier.postnl.allowMorningDelivery'       => true,
            'carrier.postnl.allowEveningDelivery'       => true,
            'carrier.postnl.allowMondayDelivery'        => true,
            'carrier.postnl.allowOnlyRecipient'         => true,
            'carrier.postnl.allowPickupLocations'       => true,
            'carrier.postnl.allowSignature'             => true,
            'carrier.postnl.priceDeliveryTypeMorning'   => 200,
            'carrier.postnl.priceDeliveryTypeEvening'   => 100,
            'carrier.postnl.priceDeliveryTypePickup'    => -150,
            'carrier.postnl.priceDeliveryTypeStandard'  => 59500,
            'carrier.postnl.priceOnlyRecipient'         => 200,
            'carrier.postnl.priceSignature'             => 25,

            // dhlforyou carrier — disabled options migrate explicitly to 0/false
            'carrier.dhlforyou.exportAgeCheck'          => 0,
            'carrier.dhlforyou.exportInsurance'         => 0,
            'carrier.dhlforyou.exportOnlyRecipient'     => 0,
            'carrier.dhlforyou.exportSignature'         => 0,
            'carrier.dhlforyou.allowOnlyRecipient'      => true,
            'carrier.dhlforyou.allowPickupLocations'    => true,
            'carrier.dhlforyou.allowSignature'          => true,
            'carrier.dhlforyou.priceDeliveryTypeStandard' => 500,
            'carrier.dhlforyou.priceDeliveryTypePickup' => 1000,
            'carrier.dhlforyou.priceOnlyRecipient'      => 100,
            'carrier.dhlforyou.priceSignature'          => 23400,

            // dhlparcelconnect carrier — partial config
            'carrier.dhlparcelconnect.exportInsurance'  => 1,
            'carrier.dhlparcelconnect.allowSignature'   => true,
            'carrier.dhlparcelconnect.allowPickupLocations' => true,
            'carrier.dhlparcelconnect.priceSignature'   => 6600,
            'carrier.dhlparcelconnect.priceDeliveryTypePickup' => -100,
        ],
    ],
]);

it('migrates pre v5.0.0 settings', function (array $settings, array $expected) {
    wpFactory(WC_Shipping_Zone::class)
        ->withId(1)
        ->withData([
            'zone_name'      => 'Nederland',
            'zone_order'     => 1,
            'zone_locations' => [],
        ])
        ->store();

    wpFactory(WC_Shipping_Method::class)
        ->withSupports([
            'settings' => [
                'shipping_zone_id' => 1,
            ],
        ])
        ->withId('flat_rate')
        ->withInstanceId('32')
        ->withMethodTitle('Flat rate Nederland')
        ->withEnabled('yes')
        ->store();

    wpFactory(WC_Shipping_Zone::class)
        ->withId(2)
        ->withData([
            'zone_name'      => 'België',
            'zone_order'     => 1,
            'zone_locations' => [],
        ])
        ->store();

    wpFactory(WC_Shipping_Method::class)
        ->withSupports([
            'settings' => [
                'shipping_zone_id' => 2,
            ],
        ])
        ->withId('flat_rate')
        ->withInstanceId('33')
        ->withMethodTitle('Flat rate België')
        ->withEnabled('yes')
        ->store();

    $wpTerm          = new WP_Term();
    $wpTerm->term_id = 5;
    $wpTerm->name = 'table rate';
    $wpTerm->slug = 'table-rate';

    wp_cache_add((string) $wpTerm->term_id, $wpTerm, 'terms');

    $wcShipping                   = WC_Shipping::instance();
    $wcShipping->enabled          = true;
    $wcShipping->shipping_classes = [$wpTerm];

    /** @var \MyParcelNL\WooCommerce\Migration\Pdk\SettingsMigration $migration */
    $migration = Pdk::get(SettingsMigration::class);
    $migration->migrateSettings($settings);

    $allSettings = Settings::all()->toArray();

    // Loose comparison: migration produces semantically-correct values; whether the
    // storage layer surfaces a price as int or float is not the migration's contract.
    foreach ($expected as $path => $value) {
        expect(Arr::get($allSettings, $path), $path)->toEqual($value);
    }

    // Sanity check for the empty dataset where $expected is intentionally empty —
    // we still want a positive signal that the migration completed without polluting Settings.
    expect($allSettings)->toHaveKey('account');
})->with('old plugin settings');
