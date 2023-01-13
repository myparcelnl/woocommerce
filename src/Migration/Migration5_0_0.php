<?php

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Migration;

use MyParcelNL\Pdk\Facade\Pdk;
use MyParcelNL\Pdk\Plugin\Model\PdkOrder;
use MyParcelNL\Pdk\Shipment\Collection\ShipmentCollection;
use MyParcelNL\Pdk\Shipment\Model\DeliveryOptions;
use MyParcelNL\WooCommerce\Pdk\Plugin\Repository\PdkOrderRepository;
use WC_Data;
use WC_Order;

/**
 * The PDK upgrade.
 */
class Migration5_0_0 implements Migration
{
    public function down(): void
    {
        // TODO: Implement down() method.
    }

    public function getVersion(): string
    {
        return '5.0.0';
    }

    /**
     * @throws \MyParcelNL\Pdk\Base\Exception\InvalidCastException
     */
    public function up(): void
    {
        $orderIds = $this->getAllOrderIds();

        foreach ($orderIds as $orderId) {
            $wcOrder = wc_get_order($orderId);

            $this->migrateDeliveryOptions($wcOrder);
            $this->updatePdkOrder($wcOrder);
            $this->migrateMetaKeys($wcOrder);
        }
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
     * @return \MyParcelNL\Pdk\Shipment\Model\DeliveryOptions
     */
    private function getDeliveryOptions(WC_Order $wcOrder): DeliveryOptions
    {
        $deliveryOptions = $this->get_meta($wcOrder, '_myparcel_delivery_options');
        $extraOptions    = $this->get_meta($wcOrder, '_myparcel_shipment_options_extra');
        return (new DeliveryOptions(
            $deliveryOptions ? [
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
            ] : null
        ));
    }

    private function getPdkOrderLines(WC_Order $wcOrder): array
    {
        $orderLines = [];

        foreach ($wcOrder->get_items() as $item) {
            $productData  = $item->get_product()
                ->get_data();
            $orderLines[] = [
                'uuid'          => $item->get_id(),
                'quantity'      => $item->get_quantity(),
                'price'         => $item->get_total() - $item->get_total_tax(),
                'vat'           => $item->get_total_tax(),
                'priceAfterVat' => $item->get_total(),
                'product'       => [
                    'uuid'               => $productData['id'],
                    'sku'                => $productData['sku'],
                    'ean'                => null,
                    'externalIdentifier' => $productData['id'],
                    'name'               => $productData['name'],
                    'description'        => $productData['description'],
                    'width'              => $productData['width'],
                    'length'             => $productData['length'],
                    'height'             => $productData['height'],
                    'weight'             => $productData['weight'],
                ],
            ];
        }

        return $orderLines;
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

    private function getAllOrderIds(): array
    {
        $allOrders = wc_get_orders([]);
        $ids       = [];

        foreach ($allOrders as $order) {
            $ids[] = $order->get_id();
        }

        return $ids;
    }

    /**
     * @param  \WC_Order $wcOrder
     *
     * @return void
     * @throws \MyParcelNL\Pdk\Base\Exception\InvalidCastException
     */
    private function migrateDeliveryOptions(WC_Order $wcOrder): void
    {
        $pdkDeliveryOptions = $this->getDeliveryOptions($wcOrder)
            ->toArray();

        $wcOrder->update_meta_data(PdkOrderRepository::WC_ORDER_META_ORDER_DATA, $pdkDeliveryOptions);
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
     * @param  \WC_Order $wcOrder
     *
     * @return \MyParcelNL\Pdk\Shipment\Collection\ShipmentCollection
     */
    private function getShipments(WC_Order $wcOrder): ShipmentCollection
    {
        $shipmentCollection = new ShipmentCollection();
        $shipmentMeta       = $this->get_meta($wcOrder, '_myparcel_shipments');

        if (! $shipmentMeta) {
            return $shipmentCollection;
        }

        foreach ($shipmentMeta as $shipmentObject) {
            $shipment = $shipmentObject['shipment'];

            $shipmentCollection->push(
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

    private function updatePdkOrder(WC_Order $wcOrder): void
    {
        $deliveryOptions = $this->getDeliveryOptions($wcOrder);
        $orderLines      = $this->getPdkOrderLines($wcOrder);
        $totalWeight     = 0;

        foreach ($orderLines as $orderLine) {
            $totalWeight += $orderLine['quantity'] * $orderLine['product']['weight'];
        }

        $pdkOrder = new PdkOrder([
            'externalIdentifier'    => $wcOrder->get_id(),
            'customsDeclaration'    => [

            ],
            'deliveryOptions'       => $deliveryOptions,
            'label'                 => null,
            'lines'                 => $orderLines,
            'physicalProperties'    => [
                'weight' => $totalWeight,
            ],
            'recipient'             => [
                'boxNumber'    => $wcOrder->get_shipping_country(),
                'cc'           => $wcOrder->get_shipping_country(),
                'city'         => $wcOrder->get_shipping_city(),
                'fullStreet'   => $shipment['recipient'][''] ?? null,
                'number'       => $shipment['recipient']['number'] ?? null,
                'numberSuffix' => $shipment['recipient']['number_suffix'] ?? null,
                'postalCode'   => $wcOrder->get_shipping_postcode(),
                'region'       => $wcOrder->get_shipping_state(),
                'state'        => $wcOrder->get_shipping_state(),
                'street'       => $shipment['recipient']['street'] ?? null,
                'email'        => $wcOrder->get_billing_email(),
                'phone'        => $wcOrder->get_shipping_phone(),
                'person'       => $wcOrder->get_shipping_first_name() . ' ' . $wcOrder->get_shipping_last_name(),
                'company'      => $wcOrder->get_shipping_company(),
            ],
            'sender'                => [
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
            ],
            'shipments'             => $this->getShipments($wcOrder),
            'shipmentPrice'         => 0,
            'shipmentPriceAfterVat' => 0,
            'shipmentVat'           => 0,
            'orderPrice'            => 0,
            'orderPriceAfterVat'    => 0,
            'orderVat'              => 0,
            'totalPrice'            => 0,
            'totalVat'              => $wcOrder->get_tax_totals(),
            'totalPriceAfterVat'    => $wcOrder->get_prices_include_tax(),
        ]);

        $pdkOrderRepository = Pdk::get(PdkOrderRepository::class);
        $pdkOrderRepository->update($pdkOrder);
    }
}
