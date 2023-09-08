<?php
/** @noinspection PhpUnhandledExceptionInspection,StaticClosureCanBeUsedInspection */

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Migration\Pdk;

use MyParcelNL\Pdk\Facade\Pdk;
use MyParcelNL\Pdk\Facade\Settings;
use MyParcelNL\WooCommerce\Tests\Uses\UsesMockWcPdkInstance;
use function MyParcelNL\Pdk\Tests\usesShared;
use function Spatie\Snapshots\assertMatchesJsonSnapshot;

usesShared(new UsesMockWcPdkInstance());

dataset('oldPluginSettings', [
    'empty'  => [[]],
    'filled' => [
        [
            'general'          => [
                'api_key'                   => 'b183f9372d6f9bd050418e31cc40965360ca814d',
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
                    'package'       => ['flat_rate:32', 'free_shipping', 'local_pickup', 'table_rate:5:2'],
                    'mailbox'       => ['flat_rate:30', 'table_rate:5:1'],
                    'letter'        => ['flat_rate:33'],
                    'digital_stamp' => ['flat_rate:31'],
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
    ],
]);

it('migrates pre v5.0.0 settings', function (array $settings) {
    /** @var \MyParcelNL\WooCommerce\Migration\Pdk\SettingsMigration $migration */
    $migration = Pdk::get(SettingsMigration::class);
    $migration->migrateSettings($settings);

    $allSettings = Settings::all();

    assertMatchesJsonSnapshot(json_encode($allSettings->toArray()));
})->with('oldPluginSettings');
