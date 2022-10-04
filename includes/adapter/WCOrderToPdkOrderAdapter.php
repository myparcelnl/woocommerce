<?php

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\includes\adapter;

use MyParcelNL\Pdk\Base\Model\ContactDetails;
use MyParcelNL\Pdk\Plugin\Collection\PdkOrderLineCollection;
use MyParcelNL\Pdk\Plugin\Model\PdkOrder;
use MyParcelNL\Pdk\Plugin\Model\PdkOrderLine;
use MyParcelNL\Pdk\Shipment\Collection\ShipmentCollection;
use MyParcelNL\Pdk\Shipment\Model\CustomsDeclaration;
use MyParcelNL\Pdk\Shipment\Model\DeliveryOptions;
use MyParcelNL\Pdk\Shipment\Model\ShipmentOptions;
use WC_ORDER;

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
     * @param  \WC_Order $order
     * @param            $orderSettings
     */
    public function __construct(WC_Order $order, $orderSettings)
    {
        $this->order         = $order;
        $this->orderSettings = $orderSettings;
        $this->pdkOrder      = $this->convert();
    }

    /**
     * @return \MyParcelNL\Pdk\Plugin\Model\PdkOrder
     */
    private function convert(): PdkOrder
    {
        return new PdkOrder([
            'customsDeclaration'    => CustomsDeclaration::class,
            'deliveryOptions'       => $this->getDeliveryOptions(),
            'externalIdentifier'    => $this->order->id,
            'label'                 => $this->orderSettings->getLabelDescription(),
            'lines'                 => $this->getOrderLines(),
            'orderPrice'            => $this->order->data['total'],
            'orderPriceAfterVat'    => $this->order->data['total'] + $this->order->data['cart_tax'],
            'orderVat'              => $this->order->data['total_tax'],
            'recipient'             => $this->getShippingRecipient(),
            'sender'                => $this->setSender(),
            'shipmentPrice'         => $this->order->data['shipping_total'],
            'shipmentPriceAfterVat' => $this->order->data['shipping_total'],
            'shipmentVat'           => $this->order->data['shipping_tax'],
            'totalPrice'            => $this->order->data['shipping_total'] + $this->order->data['total'],
            'totalPriceAfterVat'    => ($this->order->data['total'] + $this->order->data['cart_tax']) + ($this->order->data['shipping_total'] + $this->order->data['shipping_tax']),
            'totalVat'              => $this->order->data['shipping_tax'] + $this->order->data['cart_tax'],
        ]);
    }

    /**
     * @return \MyParcelNL\Pdk\Plugin\Collection\PdkOrderLineCollection
     */
    private function getOrderLines(): PdkOrderLineCollection
    {
        // 1. Create new order line for every product
        // 2. Add to PdkOrderLineCollection
        // 3. Return OrderLineCollection
        $orderLinesCollection = new PdkOrderLineCollection();
        foreach ($this->order->items as $item) {
            $orderLinesCollection->push(
                new PdkOrderLine([
                    'quantity'      => $item['quantity'],
                    'price'         => 0,
                    'vat'           => 0,
                    'priceAfterVat' => 0,
                    'product'       => $item['name'],
                ])
            );
        }

        return $orderLinesCollection;
    }

    /**
     * @return \MyParcelNL\Pdk\Base\Model\ContactDetails
     */
    private function getShippingRecipient(): ContactDetails
    {
        $shippingRecipient = $this->orderSettings->getShippingRecipient();

        return new ContactDetails([
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
        ]);
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
