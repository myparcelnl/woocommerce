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
use MyParcelNL\WooCommerce\includes\admin\OrderSettings;
use WPO\WC\MyParcel\Compatibility\WC_Core as WCX;
use WC_Order_Item;

/**
 *
 */
class WCOrderToPdkOrderAdapter
{
    private $order;

    private $orderSettings;

    /**
     * @var \MyParcelNL\Pdk\Plugin\Model\PdkOrder
     */
    private $pdkOrder;

    /**
     * @param  array $orderIds
     *
     * @throws \JsonException
     */
    public function __construct(array $orderIds)
    {
        $this->orderIds = $orderIds;
        $this->pdkOrderCollection = new PdkOrderCollection();
    }

    /**
     * @param  null|array $orderIds
     *
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
        $shippingRecipient = $this->orderSettings->getShippingRecipient();

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
        $deliveryOptions = $this->orderSettings->getDeliveryOptions();

        return new DeliveryOptions([
            'carrier'         => $deliveryOptions->getCarrier(),
            'date'            => $deliveryOptions->getDate(),
            'deliveryType'    => $deliveryOptions->getDeliveryType(),
            'labelAmount'     => 1,
            'packageType'     => $deliveryOptions->getPackageType(),
            'pickupLocation'  => $deliveryOptions->getPickupLocation(),
            'shipmentOptions' => $deliveryOptions->getShipmentOptions(),
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
