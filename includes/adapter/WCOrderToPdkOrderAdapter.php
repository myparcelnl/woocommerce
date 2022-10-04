<?php

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\includes\adapter;

use MyParcelNL\Pdk\Base\Model\ContactDetails;
use MyParcelNL\Pdk\Fulfilment\Model\Product;
use MyParcelNL\Pdk\Plugin\Collection\PdkOrderCollection;
use MyParcelNL\Pdk\Plugin\Collection\PdkOrderLineCollection;
use MyParcelNL\Pdk\Plugin\Model\PdkOrder;
use MyParcelNL\Pdk\Plugin\Model\PdkOrderLine;
use MyParcelNL\Pdk\Shipment\Collection\ShipmentCollection;
use MyParcelNL\Pdk\Shipment\Model\CustomsDeclaration;
use MyParcelNL\Pdk\Shipment\Model\DeliveryOptions;
use MyParcelNL\Sdk\src\Model\PickupLocation;
use MyParcelNL\WooCommerce\includes\admin\OrderSettings;
use WPO\WC\MyParcel\Compatibility\WC_Core as WCX;
use WC_Order_Item;

/**
 *
 */
class WCOrderToPdkOrderAdapter
{
    /**
     * @var
     */
    private $currentOrderSettings;

    /**
     * @var
     */
    private $currentOrder;

    /**
     * @var array
     */
    private $orderIds;

    /**
     * @var \MyParcelNL\Pdk\Plugin\Collection\PdkOrderCollection
     */
    private $pdkOrderCollection;

    /**
     * @param  array $orderIds
     */
    public function __construct(array $orderIds)
    {
        $this->orderIds = $orderIds;
        $this->pdkOrderCollection = new PdkOrderCollection();
    }

    /**
     * @return PdkOrderCollection
     * @throws \JsonException
     */
    public function convert(): PdkOrderCollection
    {
        if (is_null($this->orderIds)) {
            // Pdk log error
            // Cant create order with shipments
        }

        foreach($this->orderIds as $orderId) {
            $this->pushPdkOrderToCollection($orderId);
        }

        // Create the shipments
        //$this->buildShipmentCollection();

        return $this->pdkOrderCollection;
    }

    /**
     * @param $pdkOrder
     *
     * @return void
     */
    private function buildShipmentData($pdkOrder)
    {

        return [

        ];
    }

    /**
     * @param $orderId
     *
     * @return void
     * @throws \JsonException
     */
    private function pushPdkOrderToCollection($orderId): void
    {
        $this->currentOrder         = WCX::get_order($orderId);
        $this->currentOrderSettings = new OrderSettings($this->currentOrder);
        $this->pdkOrderCollection->push(
            new PdkOrder([
                'customsDeclaration'    => CustomsDeclaration::class,
                'deliveryOptions'       => $this->getDeliveryOptions(),
                'externalIdentifier'    => $this->currentOrder->get_id(),
                'label'                 => $this->currentOrderSettings->getLabelDescription(),
                'lines'                 => $this->getOrderLines(),
                'orderPrice'            => $this->currentOrder->get_total(),
                'orderPriceAfterVat'    => $this->currentOrder->get_total() + $this->currentOrder->get_cart_tax(),
                'orderVat'              => $this->currentOrder->get_total_tax(),
                'recipient'             => $this->getShippingRecipient(),
                'sender'                => $this->setSender(),
                'shipmentPrice'         => (float) $this->currentOrder->get_shipping_total(),
                'shipmentPriceAfterVat' => (float) $this->currentOrder->get_shipping_total(),
                'shipmentVat'           => (float) $this->currentOrder->get_shipping_tax(),
                'totalPrice'            => (float) $this->currentOrder->get_shipping_total() + $this->currentOrder->get_total(),
                'totalPriceAfterVat'    => ($this->currentOrder->get_total() + $this->currentOrder->get_cart_tax(
                        )) + ((float) $this->currentOrder->get_shipping_total() + (float) $this->currentOrder->get_shipping_tax()),
                'totalVat'              => (float) $this->currentOrder->get_shipping_tax() + $this->currentOrder->get_cart_tax(),
            ])
        );
    }

    /**
     * @return void
     */
    private function buildShipmentCollection(): void
    {
        $shipmentCollection = new ShipmentCollection();
        foreach ($this->pdkOrderCollection as $pdkOrder) {
            //$data = $this->buildShipmentData($pdkOrder);
            $pdkOrder->createShipment();
        }
    }

    /**
     * @return \MyParcelNL\Pdk\Plugin\Collection\PdkOrderLineCollection
     */
    private function getOrderLines(): PdkOrderLineCollection
    {
        $orderLinesCollection = new PdkOrderLineCollection();
        foreach ($this->currentOrder->get_items() as $item) {
            $orderLinesCollection->push(
                new PdkOrderLine([
                    'quantity'      => $item['quantity'],
                    'price'         => 0,
                    'vat'           => 0,
                    'priceAfterVat' => 0,
                    'product'       => $this->getProduct($item),
                ])
            );
        }

        return $orderLinesCollection;
    }

    /**
     * @param  \WC_Order_Item $item
     *
     * @return \MyParcelNL\Pdk\Fulfilment\Model\Product
     */
    private function getProduct(WC_Order_Item $item): Product
    {
        return new Product([
            'uuid'               => $item->get_id(),
            'sku'                => null,
            'ean'                => null,
            'externalIdentifier' => $item->get_order_id(),
            'name'               => $item->get_name(),
            'description'        => $item->get_name(),
            'width'              => 0,
            'length'             => 0,
            'height'             => 0,
            'weight'             => 0,
        ]);
    }

    /**
     * @return \MyParcelNL\Pdk\Base\Model\ContactDetails
     */
    private function getShippingRecipient(): ContactDetails
    {
        $shippingRecipient = $this->currentOrderSettings->getShippingRecipient();

        return new ContactDetails(
            $shippingRecipient ? [
                'boxNumber'            => $shippingRecipient->getBoxNumber(),
                'cc'                   => $shippingRecipient->getCc(),
                'city'                 => $shippingRecipient->getCity(),
                'company'              => $shippingRecipient->getCompany(),
                'email'                => $shippingRecipient->getEmail(),
                'fullStreet'           => null,
                'number'               => $shippingRecipient->getNumber(),
                'numberSuffix'         => $shippingRecipient->getNumberSuffix(),
                'person'               => $shippingRecipient->getPerson(),
                'phone'                => $shippingRecipient->getPhone(),
                'postalCode'           => $shippingRecipient->getPostalCode(),
                'region'               => $shippingRecipient->getRegion(),
                'street'               => $shippingRecipient->getStreet(),
                'streetAdditionalInfo' => $shippingRecipient->getStreetAdditionalInfo(),
            ] : []
        );
    }

    /**
     * @return \MyParcelNL\Pdk\Shipment\Model\DeliveryOptions
     */
    private function getDeliveryOptions(): DeliveryOptions
    {
        $deliveryOptions = $this->currentOrderSettings->getDeliveryOptions();

        return new DeliveryOptions([
            'carrier'         => $deliveryOptions->getCarrier(),
            'date'            => $deliveryOptions->getDate(),
            'deliveryType'    => $deliveryOptions->getDeliveryType(),
            'labelAmount'     => 1,
            'packageType'     => $deliveryOptions->getPackageType(),
//            'pickupLocation'  => (array) $deliveryOptions->getPickupLocation(),
            'pickupLocation'  => (array) new PickupLocation([
                'location_code' => 'NL'
            ]),
            'shipmentOptions' => (array) $deliveryOptions->getShipmentOptions(),
        ]);
    }

    /**
     * @return void
     */
    private function setSender()
    {
        return null;
    }
}
