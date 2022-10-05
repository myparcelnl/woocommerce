<?php

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\includes\adapter;

use MyParcelNL\Pdk\Base\Model\ContactDetails;
use MyParcelNL\Pdk\Base\Model\Currency;
use MyParcelNL\Pdk\Facade\Pdk;
use MyParcelNL\Pdk\Fulfilment\Model\Product;
use MyParcelNL\Pdk\Plugin\Collection\PdkOrderCollection;
use MyParcelNL\Pdk\Plugin\Collection\PdkOrderLineCollection;
use MyParcelNL\Pdk\Plugin\Model\PdkOrder;
use MyParcelNL\Pdk\Plugin\Model\PdkOrderLine;
use MyParcelNL\Pdk\Shipment\Collection\CustomsDeclarationItemCollection;
use MyParcelNL\Pdk\Shipment\Model\CustomsDeclaration;
use MyParcelNL\Pdk\Shipment\Model\CustomsDeclarationItem;
use MyParcelNL\Pdk\Shipment\Model\DeliveryOptions;
use MyParcelNL\Pdk\Shipment\Service\DeliveryDateService;
use MyParcelNL\Sdk\src\Model\PickupLocation;
use MyParcelNL\WooCommerce\Helper\ExportRow;
use MyParcelNL\WooCommerce\includes\admin\OrderSettings;
use PdkLogger;
use WCMP_Log;
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
    private $orderSettings;

    /**
     * @var \WC_Order
     */
    private $order;

    /**
     * @var mixed
     */
    private $logger;

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
        $this->logger             = Pdk::get(PdkLogger::class);
        $this->orderIds           = $orderIds;
        $this->pdkOrderCollection = new PdkOrderCollection();
    }

    /**
     * @return PdkOrderCollection
     * @throws \JsonException
     */
    public function convert(): PdkOrderCollection
    {
        if (is_null($this->orderIds)) {
            $this->logger->log(WCMP_Log::LOG_LEVELS['error'], 'No order ids found');
        }

        foreach ($this->orderIds as $orderId) {
            $this->pushPdkOrderToCollection($orderId);
        }

        return $this->pdkOrderCollection;
    }

    /**
     * @param $orderId
     *
     * @return void
     * @throws \JsonException
     */
    private function pushPdkOrderToCollection($orderId): void
    {
        $this->order         = WCX::get_order($orderId);
        $this->orderSettings = new OrderSettings($this->order);
        $this->pdkOrderCollection->push(
            new PdkOrder([
                'customsDeclaration'    => $this->getCustomsDeclaration(),
                'deliveryOptions'       => $this->getDeliveryOptions(),
                'externalIdentifier'    => $this->order->get_id(),
                'label'                 => $this->orderSettings->getLabelDescription(),
                'lines'                 => $this->getOrderLines(),
                'orderPrice'            => $this->order->get_total(),
                'orderPriceAfterVat'    => $this->order->get_total() + $this->order->get_cart_tax(),
                'orderVat'              => $this->order->get_total_tax(),
                'recipient'             => $this->getShippingRecipient(),
                'shipmentPrice'         => (float) $this->order->get_shipping_total(),
                'shipmentPriceAfterVat' => (float) $this->order->get_shipping_total(),
                'shipmentVat'           => (float) $this->order->get_shipping_tax(),
                'totalPrice'            => (float) $this->order->get_shipping_total(
                    ) + $this->order->get_total(),
                'totalPriceAfterVat'    => ($this->order->get_total() + $this->order->get_cart_tax(
                        )) + ((float) $this->order->get_shipping_total(
                        ) + (float) $this->order->get_shipping_tax()),
                'totalVat'              => (float) $this->order->get_shipping_tax(
                    ) + $this->order->get_cart_tax(),
            ])
        );
    }

    /**
     * @return \MyParcelNL\Pdk\Shipment\Model\CustomsDeclaration
     */
    private function getCustomsDeclaration(): CustomsDeclaration
    {
        return new CustomsDeclaration([
            'contents' => CustomsDeclaration::CONTENTS_COMMERCIAL_GOODS,
            'invoice'  => null,
            'items'    => $this->getCustomsDeclarationItems(),
            'weight'   => 1000,
        ]);
    }

    /**
     * @throws \JsonException
     * @throws \ErrorException
     */
    private function getCustomsDeclarationItems(): CustomsDeclarationItemCollection
    {
        $customsDeclarationItemCollection = new CustomsDeclarationItemCollection();

        foreach ($this->order->get_items() as $item) {
            $product = $item->get_product();
            $productHelper = new ExportRow($this->order, $product);

            $customsDeclarationItemCollection->push(
                new CustomsDeclarationItem([
                    'amount'         => $productHelper->getItemAmount($item),
                    'classification' => $productHelper->getHsCode(),
                    'country'        => $productHelper->getCountryOfOrigin(),
                    'description'    => $productHelper->getItemDescription(),
                    'itemValue'      => $productHelper->getValueOfItem(),
                    'weight'         => $productHelper->getItemWeight(),
                ])
            );
        }

        return $customsDeclarationItemCollection;
    }

    /**
     * @return \MyParcelNL\Pdk\Plugin\Collection\PdkOrderLineCollection
     */
    private function getOrderLines(): PdkOrderLineCollection
    {
        $orderLinesCollection = new PdkOrderLineCollection();
        foreach ($this->order->get_items() as $item) {
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
            'externalIdentifier'  => $item->get_order_id(),
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
                'numberSuffix'          => $shippingRecipient->getNumberSuffix(),
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
            'date'            => DeliveryDateService::fixPastDeliveryDate($deliveryOptions->getDate()),
            'deliveryType'    => $deliveryOptions->getDeliveryType(),
            'labelAmount'     => 1,
            'packageType'     => $deliveryOptions->getPackageType(),
            //            'pickupLocation'  => (array) $deliveryOptions->getPickupLocation(),
            'pickupLocation'  => (array) new PickupLocation([
                'location_code' => 'NL',
            ]),
            'shipmentOptions' => (array) $deliveryOptions->getShipmentOptions(),
        ]);
    }
}
