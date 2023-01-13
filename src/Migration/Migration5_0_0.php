<?php

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Migration;

use MyParcelNL\Pdk\Carrier\Model\CarrierOptions;
use MyParcelNL\Pdk\Facade\Pdk;
use MyParcelNL\Pdk\Settings\Model\CarrierSettings;
use MyParcelNL\Pdk\Settings\Model\CheckoutSettings;
use MyParcelNL\Pdk\Settings\Model\CustomsSettings;
use MyParcelNL\Pdk\Settings\Model\GeneralSettings;
use MyParcelNL\Pdk\Settings\Model\LabelSettings;
use MyParcelNL\Pdk\Settings\Model\OrderSettings;
use MyParcelNL\Pdk\Shipment\Model\DropOffDay;
use MyParcelNL\WooCommerce\Pdk\Settings\Repository\PdkSettingsRepository;

use DateTime;
use WC_Data;
use WC_Order;

/**
 * The PDK upgrade.
 */
class Migration5_0_0 extends AbstractUpgradeMigration implements Migration
{
    public function down(): void
    {
        // TODO: Implement down() method.
    }

    public function getVersion(): string
    {
        return '5.0.0';
    }

    public function up(): void
    {
        $this->migrateAllSettings();
        $this->migrateOrders();
    }

    /**
     * @param  array $data
     *
     * @return void
     */
    public function migrateOrder(array $data): void
    {
        $orderIds  = $data['orderIds'] ?? [];
        $chunk     = $data['chunk'] ?? null;
        $lastChunk = $data['lastChunk'] ?? null;

        $createTimestamp = static function () {
            return (new DateTime())->format('Y-m-d H:i:s');
        };

        $log = fopen(sprintf('%s/../../logs/pdk-migration.log', __DIR__), 'wb');

        fwrite(
            $log,
            sprintf(
                '[%s] Start migration for orders %d..%d (chunk %d/%d)',
                $createTimestamp(),
                $orderIds[0],
                $orderIds[count($orderIds) - 1],
                $chunk,
                $lastChunk
            ) . PHP_EOL
        );

        foreach ($orderIds as $orderId) {
            $wcOrder = wc_get_order($orderId);

            if (! $wcOrder instanceof WC_Order) {
                fwrite(
                    $log,
                    sprintf('[%s] Order %s is not an instance of WC_Order', $createTimestamp(), $orderId) . PHP_EOL
                );
                continue;
            }

            $this->updatePdkOrder($wcOrder);
            $this->migrateMetaKeys($wcOrder);

            fwrite($log, sprintf('[%s] Order %s migrated', $createTimestamp(), $orderId) . PHP_EOL);
        }

        fclose($log);
    }

    /**
     * @return array
     */
    private function getAllOrderIds(): array
    {
        $date = new DateTime();
        $date->modify('-3 months');

        $allOrders = wc_get_orders([
            'date_after' => $date->format('Y-m-d'),
        ]);

        return array_map(static function (WC_Order $order) {
            return $order->get_id();
        }, $allOrders);
    }

    /**
     * @param  null|array $items
     *
     * @return null|array
     */
    private function getCustomDeclarationItems(?array $items): ?array
    {
        $customsDeclarationItems = [];

        if (! $items) {
            return null;
        }

        foreach ($items as $item) {
            $customsDeclarationItems[] = $item ? [
                'amount'         => $item['amount'] ?? null,
                'classification' => $item['classification'] ?? null,
                'country'        => $item['country'] ?? null,
                'description'    => $item['description'] ?? null,
                'itemValue'      => [
                    'amount'   => $item['item_value']['amount'] ?? null,
                    'currency' => $item['item_value']['currency'] ?? null,
                ],
                'weight'         => $item['weight'] ?? null,
            ] : null;
        }

        return $customsDeclarationItems;
    }

    /**
     * @param  \WC_Order $wcOrder
     *
     * @return array
     */
    private function getDeliveryOptions(WC_Order $wcOrder): ?array
    {
        $deliveryOptions = $this->get_meta($wcOrder, '_myparcel_delivery_options');
        $extraOptions    = $this->get_meta($wcOrder, '_myparcel_shipment_options_extra');

        return $deliveryOptions ? [
            'carrier'         => $deliveryOptions['carrier'] ?? null,
            'date'            => $deliveryOptions['date'] ?? null,
            'deliveryType'    => $deliveryOptions['deliveryType'] ?? null,
            'labelAmount'     => $extraOptions['collo_amount'] ?? null,
            'packageType'     => $deliveryOptions['packageType'] ?? null,
            'pickupLocation'  => $deliveryOptions['pickupLocation'] ? [
                'boxNumber'       => $deliveryOptions['pickupLocation']['box_number'] ?? null,
                'cc'              => $deliveryOptions['pickupLocation']['cc'] ?? null,
                'city'            => $deliveryOptions['pickupLocation']['city'] ?? null,
                'number'          => $deliveryOptions['pickupLocation']['number'] ?? null,
                'numberSuffix'    => $deliveryOptions['pickupLocation']['number_suffix'] ?? null,
                'postalCode'      => $deliveryOptions['pickupLocation']['postal_code'] ?? null,
                'region'          => $deliveryOptions['pickupLocation']['region'] ?? null,
                'state'           => $deliveryOptions['pickupLocation']['state'] ?? null,
                'street'          => $deliveryOptions['pickupLocation']['street'] ?? null,
                'locationCode'    => $deliveryOptions['pickupLocation']['location_code'] ?? null,
                'locationName'    => $deliveryOptions['pickupLocation']['location_name'] ?? null,
                'retailNetworkId' => $deliveryOptions['pickupLocation']['retail_network_id'] ?? null,
            ] : null,
            'shipmentOptions' => $deliveryOptions['shipmentOptions'] ? [
                'signature'        => $deliveryOptions['shipmentOptions']['signature'] ?? null,
                'insurance'        => $deliveryOptions['shipmentOptions']['insurance'] ?? null,
                'ageCheck'         => $deliveryOptions['shipmentOptions']['age_check'] ?? null,
                'onlyRecipient'    => $deliveryOptions['shipmentOptions']['only_recipient'] ?? null,
                'return'           => $deliveryOptions['shipmentOptions']['return'] ?? null,
                'sameDayDelivery'  => $deliveryOptions['shipmentOptions']['same_day_delivery'] ?? null,
                'largeFormat'      => $deliveryOptions['shipmentOptions']['large_format'] ?? null,
                'labelDescription' => $deliveryOptions['shipmentOptions']['label_description'] ?? null,
            ] : null,
        ] : null;
    }

    /**
     * @param  \WC_Order $wcOrder
     *
     * @return array
     */
    private function getShipments(WC_Order $wcOrder): array
    {
        $shipmentCollection = [];
        $shipmentMeta       = $this->get_meta($wcOrder, '_myparcel_shipments');

        if (! $shipmentMeta) {
            return $shipmentCollection;
        }

        foreach ($shipmentMeta as $shipmentObject) {
            $shipment = $shipmentObject['shipment'];

            $shipmentCollection[] = (
            $shipment ? [
                'id'                       => $shipment['id'] ?? null,
                'parentId'                 => $shipment['parent_id'] ?? null,
                'shopId'                   => $shipment['shop_id'] ?? null,
                'referenceIdentifier'      => $shipment['reference_identifier'] ?? null,
                'externalIdentifier'       => $shipment['external_identifier'] ?? null,
                'apiKey'                   => null,
                'barcode'                  => $shipment['barcode'] ?? null,
                'carrier'                  => [
                    'id' => $shipment['carrier_id'] ?? null,
                ],
                'collectionContact'        => null,
                'customsDeclaration'       => [
                    'contents' => $shipment['customs_declaration']['contents'] ?? null,
                    'invoice'  => $shipment['customs_declaration']['invoice'] ?? null,
                    'items'    => $this->getCustomDeclarationItems(
                        $shipment['customs_declaration']['items'] ?? null
                    ),
                    'weight'   => $shipment['customs_declaration']['weight'] ?? null,
                ],
                'delayed'                  => $shipment['delayed'] ?? null,
                'delivered'                => $shipment['delivered'] ?? null,
                'deliveryOptions'          => $shipment['options'] ? [
                    'carrier'         => [
                        'id' => $shipment['carrier_id'] ?? null,
                    ],
                    'date'            => $shipment['options']['delivery_date'] ?? null,
                    'deliveryType'    => $shipment['options']['delivery_type'] ?? null,
                    'labelAmount'     => $shipment['options']['label_amount'] ?? null,
                    'packageType'     => $shipment['options']['package_type'] ?? null,
                    'pickupLocation'  => $shipment['pickup'] ? [
                        'boxNumber'       => $shipment['pickup']['box_number'] ?? null,
                        'cc'              => $shipment['pickup']['cc'] ?? null,
                        'city'            => $shipment['pickup']['city'] ?? null,
                        'number'          => $shipment['pickup']['number'] ?? null,
                        'numberSuffix'    => $shipment['pickup']['number_suffix'] ?? null,
                        'postalCode'      => $shipment['pickup']['postal_code'] ?? null,
                        'region'          => $shipment['pickup']['region'] ?? null,
                        'state'           => $shipment['pickup']['state'] ?? null,
                        'street'          => $shipment['pickup']['street'] ?? null,
                        'locationCode'    => $shipment['pickup']['location_code'] ?? null,
                        'locationName'    => $shipment['pickup']['location_name'] ?? null,
                        'retailNetworkId' => $shipment['pickup']['retail_network_id'] ?? null,
                    ] : null,
                    'shipmentOptions' => [
                        'signature'        => $shipment['options']['signature'] ?? null,
                        'insurance'        => $shipment['options']['insurance']['amount'] ?? null,
                        'ageCheck'         => $shipment['options']['age_check'] ?? null,
                        'onlyRecipient'    => $shipment['options']['only_recipient'] ?? null,
                        'return'           => $shipment['options']['return'] ?? null,
                        'sameDayDelivery'  => $shipment['options']['same_day_delivery'] ?? null,
                        'largeFormat'      => $shipment['options']['large_format'] ?? null,
                        'labelDescription' => $shipment['options']['label_description'] ?? null,
                    ],
                ] : null,
                'dropOffPoint'             => $shipment['drop_off_point'] ? [
                    'boxNumber'       => $shipment['drop_off_point']['box_number'] ?? null,
                    'cc'              => $shipment['drop_off_point']['cc'] ?? null,
                    'city'            => $shipment['drop_off_point']['city'] ?? null,
                    'number'          => $shipment['drop_off_point']['number'] ?? null,
                    'numberSuffix'    => $shipment['drop_off_point']['number_suffix'] ?? null,
                    'postalCode'      => $shipment['drop_off_point']['postal_code'] ?? null,
                    'region'          => $shipment['drop_off_point']['region'] ?? null,
                    'state'           => $shipment['drop_off_point']['state'] ?? null,
                    'street'          => $shipment['drop_off_point']['street'] ?? null,
                    'locationCode'    => $shipment['drop_off_point']['location_code'] ?? null,
                    'locationName'    => $shipment['drop_off_point']['location_name'] ?? null,
                    'retailNetworkId' => $shipment['drop_off_point']['retail_network_id'] ?? null,
                ] : null,
                'hidden'                   => $shipment['hidden'] ?? null,
                'linkConsumerPortal'       => $shipment['link_consumer_portal'] ?? null,
                'multiColloMainShipmentId' => $shipment['multi_collo_main_shipment_id'] ?? null,
                'partnerTrackTraces'       => $shipment['partner_track_traces'] ?? null,
                'physicalProperties'       => $shipment['physical_properties'] ? [
                    'weight' => $shipment['physical_properties']['weight'] ?? null,
                    'width'  => $shipment['physical_properties']['width'] ?? null,
                    'height' => $shipment['physical_properties']['height'] ?? null,
                    'length' => $shipment['physical_properties']['length'] ?? null,
                ] : null,
                'price'                    => $shipment['price'] ? [
                    'amount'   => $shipment['price']['amount'] ?? null,
                    'currency' => $shipment['price']['currency'] ?? null,
                ] : null,
                'recipient'                => $shipment['recipient'] ? [
                    'boxNumber'            => $shipment['recipient']['box_number'] ?? null,
                    'cc'                   => $shipment['recipient']['cc'] ?? null,
                    'city'                 => $shipment['recipient']['city'] ?? null,
                    'fullStreet'           => $shipment['recipient'][''] ?? null,
                    'number'               => $shipment['recipient']['number'] ?? null,
                    'numberSuffix'         => $shipment['recipient']['number_suffix'] ?? null,
                    'postalCode'           => $shipment['recipient']['postal_code'] ?? null,
                    'region'               => $shipment['recipient']['region'] ?? null,
                    'state'                => $shipment['recipient']['state'] ?? null,
                    'street'               => $shipment['recipient']['street'] ?? null,
                    'streetAdditionalInfo' => $shipment['recipient']['street_additional_info'] ?? null,
                    'email'                => $shipment['recipient']['email'] ?? null,
                    'phone'                => $shipment['recipient']['phone'] ?? null,
                    'person'               => $shipment['recipient']['person'] ?? null,
                    'company'              => $shipment['recipient']['company'] ?? null,
                ] : null,
                'sender'                   => $shipment['sender'] ? [
                    'boxNumber'            => $shipment['sender']['box_number'] ?? null,
                    'cc'                   => $shipment['sender']['cc'] ?? null,
                    'city'                 => $shipment['sender']['city'] ?? null,
                    'fullStreet'           => $shipment['sender'][''] ?? null,
                    'number'               => $shipment['sender']['number'] ?? null,
                    'numberSuffix'         => $shipment['sender']['number_suffix'] ?? null,
                    'postalCode'           => $shipment['sender']['postal_code'] ?? null,
                    'region'               => $shipment['sender']['region'] ?? null,
                    'state'                => $shipment['sender']['state'] ?? null,
                    'street'               => $shipment['sender']['street'] ?? null,
                    'streetAdditionalInfo' => $shipment['sender']['street_additional_info'] ?? null,
                    'email'                => $shipment['sender']['email'] ?? null,
                    'phone'                => $shipment['sender']['phone'] ?? null,
                    'person'               => $shipment['sender']['person'] ?? null,
                    'company'              => $shipment['sender']['company'] ?? null,
                ] : null,
                'shipmentType'             => $shipment['shipment_type'] ?? null,
                'status'                   => $shipment['status'] ?? null,
                'created'                  => $shipment['created'] ?? null,
                'createdBy'                => $shipment['created_by'] ?? null,
                'modified'                 => $shipment['modified'] ?? null,
                'modifiedBy'               => $shipment['modified_by'] ?? null,
            ] : null
            );
        }

        return $shipmentCollection;
    }

    /**
     * Gets an object's stored meta value.
     *
     * @param  WC_Data $object  the data object, likely \WC_Order or \WC_Product
     * @param  string  $key     the meta key
     * @param  bool    $single  whether to get the meta as a single item. Defaults to `true`
     * @param  string  $context if 'view' then the value will be filtered
     *
     * @return mixed
     * @since 4.6.0-dev
     */
    private function get_meta(WC_Data $object, string $key = '', bool $single = true, string $context = 'edit')
    {
        $value = $object->get_meta($key, $single, $context);

        if (is_string($value)) {
            $decoded = json_decode($value, true);
            // json_decode returns null if there was a syntax error, meaning input was not valid JSON.
            $value = $decoded ?? $value;
        }

        return $value;
    }

    /**
     * @param  \WC_Order $wcOrder
     *
     * @return void
     */
    private function migrateMetaKeys(WC_Order $wcOrder): void
    {
        $oldKeys = [
            '_myparcel_last_shipments_ids',
            '_myparcel_delivery_date',
            '_myparcel_highest_shipping_class',
            '_myparcel_order_version',
        ];

        foreach ($oldKeys as $key) {
            $oldMeta = $this->get_meta($wcOrder, $key);
            $newKey  = str_replace('myparcel', 'myparcelnl', $key);
            $wcOrder->update_meta_data($newKey, $oldMeta);
        }
    }

    /**
     * @return void
     */
    private function migrateOrders(): void
    {
        $orderIds  = $this->getAllOrderIds();
        $chunks    = array_chunk($orderIds, 100);
        $lastChunk = count($chunks);

        add_action('myparcelnl_migrate_order_to_pdk_5_0_0', [$this, 'migrateOrder']);

        foreach ($chunks as $index => $chunk) {
            $time = time() + $index * 5;
            wp_schedule_single_event($time, 'myparcelnl_migrate_order_to_pdk_5_0_0', [
                'orderIds'  => $chunk,
                'chunk'     => $index,
                'lastChunk' => $lastChunk,
            ]);
        }
    }

    /**
     * @param  array $pdkOrder
     *
     * @return void
     */
    private function saveMetaData(array $pdkOrder): void
    {
        update_post_meta(
            $pdkOrder['externalIdentifier'],
            'myparcelnl_order_data',
            [
                'deliveryOptions' => $pdkOrder['deliveryOptions'],
                'recipient'       => $pdkOrder['recipient'],
            ]
        );

        update_post_meta($pdkOrder['externalIdentifier'], 'myparcelnl_order_shipments', $pdkOrder['shipments']);

        update_post_meta($pdkOrder['externalIdentifier'], 'myparcelnl_pdk_migrated', true);
    }

    /**
     * @param  \WC_Order $wcOrder
     *
     * @return void
     */
    private function updatePdkOrder(WC_Order $wcOrder): void
    {
        $houseNumber       = $wcOrder->get_meta('_shipping_house_number');
        $houseNumberSuffix = $wcOrder->get_meta('_shipping_house_number_suffix');
        $streetName        = $wcOrder->get_meta('_shipping_street_name');

        $pdkOrder = [
            'externalIdentifier' => $wcOrder->get_id(),
            'deliveryOptions'    => $this->getDeliveryOptions($wcOrder),
            'recipient'          => [
                'number'       => $houseNumber ?? null,
                'numberSuffix' => $houseNumberSuffix ?? null,
                'street'       => $streetName ?? null,
            ],
            'shipments'          => $this->getShipments($wcOrder),
        ];

        $this->saveMetaData($pdkOrder);
    }

    /**
     * @return void
     */
    private function migrateAllSettings(): void
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
