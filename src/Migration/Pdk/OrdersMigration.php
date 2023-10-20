<?php

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Migration\Pdk;

use DateTime;
use MyParcelNL\Pdk\Base\Contract\CronServiceInterface;
use MyParcelNL\Pdk\Base\Support\Arr;
use MyParcelNL\Pdk\Base\Support\Collection;
use MyParcelNL\Pdk\Base\Support\Utils;
use MyParcelNL\Pdk\Facade\Pdk;
use WC_Order;

final class OrdersMigration extends AbstractPdkMigration
{
    public const LEGACY_META_DELIVERY_OPTIONS             = '_myparcel_delivery_options';
    public const LEGACY_META_ORDER_VERSION                = '_myparcel_order_version';
    public const LEGACY_META_PPS_EXPORTED                 = '_myparcel_pps_exported';
    public const LEGACY_META_SHIPMENTS                    = '_myparcel_shipments';
    public const LEGACY_META_SHIPMENT_OPTIONS_EXTRA       = '_myparcel_shipment_options_extra';
    public const LEGACY_META_SHIPPING_HOUSE_NUMBER        = '_shipping_house_number';
    public const LEGACY_META_SHIPPING_HOUSE_NUMBER_SUFFIX = '_shipping_house_number_suffix';
    public const LEGACY_META_SHIPPING_STREET_NAME         = '_shipping_street_name';

    /**
     * @var \MyParcelNL\Pdk\Base\Contract\CronServiceInterface
     */
    private $cronService;

    /**
     * @param  \MyParcelNL\Pdk\Base\Contract\CronServiceInterface $cronService
     */
    public function __construct(CronServiceInterface $cronService)
    {
        $this->cronService = $cronService;
    }

    public function down(): void
    {
        /*
         * Nothing to do here.
         */
    }

    /**
     * @param  array $data
     *
     * @return void
     * @throws \Exception
     */
    public function migrateOrder(array $data): void
    {
        $orderIds  = $data['orderIds'] ?? [];
        $chunk     = $data['chunk'] ?? null;
        $lastChunk = $data['lastChunk'] ?? null;

        $this->debug(
            sprintf(
                'Start migration for orders %d..%d (chunk %d/%d)',
                $orderIds[0],
                $orderIds[count($orderIds) - 1],
                $chunk,
                $lastChunk
            )
        );

        foreach ($orderIds as $orderId) {
            $this->savePdkData(new WC_Order($orderId));

            $this->debug("Order $orderId migrated.");
        }
    }

    /**
     * @return void
     */
    public function up(): void
    {
        if (! function_exists('wc_get_orders')) {
            $this->error('WooCommerce is not active, aborting migration.');

            return;
        }

        $orderIds  = $this->getAllOrderIds();
        $chunks    = array_chunk($orderIds, 100);
        $lastChunk = count($chunks);

        foreach ($chunks as $index => $chunk) {
            $time = time() + $index * 5;

            $chunkContext = [
                'orderIds'  => $chunk,
                'chunk'     => $index + 1,
                'lastChunk' => $lastChunk,
            ];

            $this->cronService->schedule(Pdk::get('migrateAction_5_0_0_Orders'), $time, $chunkContext);

            $this->debug('Scheduled migration for orders', [
                'time'  => $time,
                'chunk' => $chunkContext,
            ]);
        }
    }

    /**
     * @param  array $recipient
     *
     * @return string
     */
    protected function getAddress1(array $recipient): string
    {
        return trim(
            implode(' ', [
                $recipient['street'] ?? '',
                $recipient['number'] ?? '',
                $recipient['numberSuffix'] ?? $recipient['boxNumber'] ?? '',
            ])
        );
    }

    /**
     * @return array
     */
    private function getAllOrderIds(): array
    {
        $date = new DateTime();
        $date->modify('-3 months');

        $allOrders = wc_get_orders([
            'date_after'   => $date->format('Y-m-d'),
            'meta_key'     => Pdk::get('metaKeyMigrated'),
            'meta_value'   => sprintf('"%s"', $this->getVersion()),
            'meta_compare' => 'NOT LIKE',
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
        if (! $items) {
            return null;
        }

        return array_map(static function ($item) {
            return Utils::filterNull([
                'amount'         => $item['amount'] ?? null,
                'classification' => $item['classification'] ?? null,
                'country'        => $item['country'] ?? null,
                'description'    => $item['description'] ?? null,
                'itemValue'      => [
                    'amount'   => $item['item_value']['amount'] ?? null,
                    'currency' => $item['item_value']['currency'] ?? null,
                ],
                'weight'         => $item['weight'] ?? null,
            ]);
        }, array_filter($items));
    }

    /**
     * @param  \WC_Order $wcOrder
     *
     * @return array
     */
    private function getDeliveryOptions(WC_Order $wcOrder): ?array
    {
        $deliveryOptions = $wcOrder->get_meta(self::LEGACY_META_DELIVERY_OPTIONS);
        $extraOptions    = $wcOrder->get_meta(self::LEGACY_META_SHIPMENT_OPTIONS_EXTRA);

        if (! $deliveryOptions && ! $extraOptions) {
            return null;
        }

        return Utils::filterNull([
            'carrier'         => $deliveryOptions['carrier'] ?? null,
            'date'            => $deliveryOptions['date'] ?: null,
            'deliveryType'    => $deliveryOptions['deliveryType'] ?? null,
            'labelAmount'     => $extraOptions['collo_amount'] ?? null,
            'packageType'     => $deliveryOptions['packageType'] ?? null,
            'pickupLocation'  => $this->mapRetailLocation($deliveryOptions['pickupLocation'] ?? null),
            'shipmentOptions' => $this->mapShipmentOptions($deliveryOptions['shipmentOptions'] ?? null),
        ]);
    }

    /**
     * @param  \WC_Order $wcOrder
     *
     * @return array
     */
    private function getShipments(WC_Order $wcOrder): array
    {
        $shipmentMeta = $wcOrder->get_meta(self::LEGACY_META_SHIPMENTS);

        if (! $shipmentMeta) {
            return [];
        }

        $collection = new Collection(Arr::pluck($shipmentMeta, 'shipment'));

        $newShipments = $collection->map(function (array $shipment) use ($wcOrder) {
            return [
                'id'                       => $shipment['id'] ?? null,
                'parentId'                 => $shipment['parent_id'] ?? null,
                'shopId'                   => $shipment['shop_id'] ?? null,
                'orderId'                  => $wcOrder->get_id(),
                'referenceIdentifier'      => $shipment['reference_identifier'] ?? null,
                'externalIdentifier'       => $shipment['external_identifier'] ?? null,
                'barcode'                  => $shipment['barcode'] ?? null,
                'carrier'                  => [
                    'id' => $shipment['carrier_id'] ?? null,
                ],
                'customsDeclaration'       => $shipment['customs_declaration']
                    ? [
                        'contents' => $shipment['customs_declaration']['contents'] ?? null,
                        'invoice'  => $shipment['customs_declaration']['invoice'] ?? null,
                        'items'    => $this->getCustomDeclarationItems(
                            $shipment['customs_declaration']['items'] ?? null
                        ),
                        'weight'   => $shipment['customs_declaration']['weight'] ?? null,
                    ]
                    : null,
                'delayed'                  => $shipment['delayed'] ?? null,
                'delivered'                => $shipment['delivered'] ?? null,
                'deliveryOptions'          => $shipment['options']
                    ? [
                        'carrier'         => [
                            'id' => $shipment['carrier_id'] ?? null,
                        ],
                        'date'            => $shipment['options']['delivery_date'] ?? null,
                        'deliveryType'    => $shipment['options']['delivery_type'] ?? null,
                        'labelAmount'     => $shipment['options']['label_amount'] ?? null,
                        'packageType'     => $shipment['options']['package_type'] ?? null,
                        'pickupLocation'  => $this->mapRetailLocation($shipment['pickup'] ?? null),
                        'shipmentOptions' => $this->mapShipmentOptions($shipment['options']),
                    ]
                    : null,
                'dropOffPoint'             => $this->mapRetailLocation($shipment['drop_off_point'] ?? null),
                'hidden'                   => $shipment['hidden'] ?? null,
                'linkConsumerPortal'       => $shipment['link_consumer_portal'] ?? null,
                'multiColloMainShipmentId' => $shipment['multi_collo_main_shipment_id'] ?? null,
                'partnerTrackTraces'       => $shipment['partner_track_traces'] ?? null,
                'physicalProperties'       => $this->map($shipment['physical_properties'] ?? null, [
                    'weight' => 'weight',
                    'width'  => 'width',
                    'height' => 'height',
                    'length' => 'length',
                ]),
                'price'                    => $this->map($shipment['price'] ?? null, [
                    'amount'   => 'amount',
                    'currency' => 'currency',
                ]),
                'recipient'                => $this->mapAddress($shipment['recipient'] ?? null),
                'senderAddress'            => $this->mapAddress($shipment['sender'] ?? null),
                'shipmentType'             => $shipment['shipment_type'] ?? null,
                'status'                   => $shipment['status'] ?? null,
                'created'                  => $shipment['created'] ?? null,
                'createdBy'                => $shipment['created_by'] ?? null,
                'modified'                 => $shipment['modified'] ?? null,
                'modifiedBy'               => $shipment['modified_by'] ?? null,
            ];
        });

        return $newShipments->toArrayWithoutNull();
    }

    /**
     * @param  null|array $input
     * @param  array      $map
     *
     * @return null|array
     */
    private function map(?array $input, array $map): ?array
    {
        if (! $input) {
            return null;
        }

        return Utils::filterNull(
            array_map(static function ($value) use ($input) {
                return $input[$value] ?? null;
            }, $map)
        );
    }

    /**
     * @param  null|array $input
     *
     * @return null|array|string[]
     */
    private function mapAddress(?array $input): ?array
    {
        if (! $input) {
            return null;
        }

        return $this->map($input, [
                'address2'   => 'street_additional_info',
                'cc'         => 'cc',
                'city'       => 'city',
                'company'    => 'company',
                'email'      => 'email',
                'person'     => 'person',
                'phone'      => 'phone',
                'postalCode' => 'postal_code',
                'region'     => 'region',
                'state'      => 'state',
            ]) + ['address1' => $this->getAddress1($input)];
    }

    /**
     * @param  null|array $input
     *
     * @return null|array
     */
    private function mapRetailLocation(?array $input): ?array
    {
        return $this->map($input ?? null, [
            'boxNumber'       => 'box_number',
            'cc'              => 'cc',
            'city'            => 'city',
            'locationCode'    => 'location_code',
            'locationName'    => 'location_name',
            'number'          => 'number',
            'numberSuffix'    => 'number_suffix',
            'postalCode'      => 'postal_code',
            'region'          => 'region',
            'retailNetworkId' => 'retail_network_id',
            'state'           => 'state',
            'street'          => 'street',
        ]);
    }

    /**
     * @param  null|array $input
     *
     * @return null|array
     */
    private function mapShipmentOptions(?array $input): ?array
    {
        if (! $input) {
            return null;
        }

        $labelDescription = $input['label_description'];

        $data = $this->map($input, [
            'ageCheck'        => 'age_check',
            'insurance'       => 'insurance',
            'largeFormat'     => 'large_format',
            'onlyRecipient'   => 'only_recipient',
            'return'          => 'return',
            'sameDayDelivery' => 'same_day_delivery',
            'signature'       => 'signature',
        ]);

        if ($labelDescription) {
            $data['labelDescription'] = str_replace('[ORDER_NR]', '[ORDER_ID]', $labelDescription);
        }

        return Utils::filterNull($data);
    }

    /**
     * @param  \WC_Order $wcOrder
     *
     * @return void
     */
    private function savePdkData(WC_Order $wcOrder): void
    {
        $fulfilmentData = $wcOrder->get_meta(self::LEGACY_META_PPS_EXPORTED);

        $wcOrder->update_meta_data(
            Pdk::get('metaKeyOrderData'),
            Utils::filterNull([
                'apiIdentifier'   => $fulfilmentData['pps_uuid'] ?? null,
                'exported'        => $fulfilmentData['pps_exported'] ?? false,
                'deliveryOptions' => $this->getDeliveryOptions($wcOrder),
            ])
        );

        $wcOrder->update_meta_data(Pdk::get('metaKeyOrderShipments'), $this->getShipments($wcOrder));

        $wcOrder->update_meta_data(
            Pdk::get('metaKeyVersion'),
            $wcOrder->get_meta(self::LEGACY_META_ORDER_VERSION)
        );

        $migrationMeta = $this->getMigrationMeta($wcOrder);

        if ($migrationMeta) {
            $wcOrder->update_meta_data(Pdk::get('metaKeyMigrated'), $migrationMeta);
        }

        $wcOrder->save();
    }
}
