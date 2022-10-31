<?php

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\includes\adapter;

use Exception;
use MyParcelNL\Pdk\Base\Service\CountryService;
use MyParcelNL\Pdk\Base\Service\WeightService;
use MyParcelNL\Pdk\Fulfilment\Model\Product;
use MyParcelNL\Pdk\Plugin\Collection\PdkOrderLineCollection;
use MyParcelNL\Pdk\Plugin\Model\PdkOrder;
use MyParcelNL\Pdk\Plugin\Model\PdkOrderLine;
use MyParcelNL\Pdk\Shipment\Collection\CustomsDeclarationItemCollection;
use MyParcelNL\Pdk\Shipment\Model\CustomsDeclaration;
use MyParcelNL\Pdk\Shipment\Model\CustomsDeclarationItem;
use MyParcelNL\Pdk\Shipment\Model\DeliveryOptions;
use MyParcelNL\Pdk\Shipment\Model\ShipmentOptions;
use MyParcelNL\Pdk\Shipment\Service\DeliveryDateService;
use MyParcelNL\Sdk\src\Model\Consignment\AbstractConsignment;
use MyParcelNL\Sdk\src\Model\PickupLocation;
use MyParcelNL\Sdk\src\Model\Recipient;
use MyParcelNL\WooCommerce\Helper\ExportRow;
use WC_Order;
use WCMP_Log;
use WCMP_Shipping_Methods;
use WCMYPA_Admin;
use WCMYPA_Settings;
use WC_Order_Item;

/**
 *
 */
class PdkOrderFromWCOrderAdapter
{
    public const DEFAULT_BELGIAN_INSURANCE = 500;

    /**
     * @var \WC_Order
     */
    private $order;

    /**
     * @param  \WC_Order $order
     */
    public function __construct(WC_Order $order)
    {
        $this->order = $order;
    }

    /**
     * @throws \Exception
     */
    public function getPdkOrder(): PdkOrder
    {
        $deliveryOptions = $this->getDeliveryOptions();
        return new PdkOrder([
            'orderDate'             => $this->order->get_date_created()->getTimestamp(),
            'customsDeclaration'    => $this->getCustomsDeclaration(),
            'deliveryOptions'       => $deliveryOptions,
            'externalIdentifier'    => $this->order->get_id(),
            'label'                 => $this->getLabelDescription($deliveryOptions),
            'lines'                 => $this->getOrderLines(),
            'orderPrice'            => $this->order->get_total(),
            'orderPriceAfterVat'    => $this->order->get_total() + $this->order->get_cart_tax(),
            'orderVat'              => $this->order->get_total_tax(),
            'recipient'             => $this->getShippingRecipient(),
            'shipmentPrice'         => (float) $this->order->get_shipping_total(),
            'shipmentPriceAfterVat' => (float) $this->order->get_shipping_total(),
            'shipmentVat'           => (float) $this->order->get_shipping_tax(),
            'totalPrice'            => (float) $this->order->get_shipping_total() + $this->order->get_total(),
            'totalPriceAfterVat'    => ($this->order->get_total() + $this->order->get_cart_tax(
                    )) + ((float) $this->order->get_shipping_total() + (float) $this->order->get_shipping_tax()),
            'totalVat'              => (float) $this->order->get_shipping_tax() + $this->order->get_cart_tax(),
        ]);
    }

    /**
     * @param $data
     *
     * @return \MyParcelNL\WooCommerce\includes\adapter\PdkOrderFromWCOrderAdapter
     */
    public function setDeliveryOptions($data): PdkOrderFromWCOrderAdapter
    {
        $this->order->deliveryOptions = new DeliveryOptions([
            'carrier'     => $data['carrier'],
            'labelAmount' => $data['extra_options']['collo_amount'],
            'packageType' => $data['package_type'],
            //            'pickupLocation'  => (array) $deliveryOptions->getPickupLocation(),

            'shipmentOptions' => new ShipmentOptions([
                'ageCheck'         => $data['shipment_options']['age_check'],
                'insurance'        => $data['shipment_options']['insured_amount'],
                'labelDescription' => $data['shipment_options']['label_description'],
                'largeFormat'      => $data['shipment_options']['large_format'],
                'onlyRecipient'    => $data['shipment_options']['only_recipient'] ?? null,
                'return'           => $data['shipment_options']['return'],
                'sameDayDelivery'  => $data['shipment_options']['same_day_delivery'],
                'signature'        => $data['shipment_options']['signature'] ?? null,
            ]),
        ]);

        return $this;
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
     * @return \WC_Order
     */
    public function getOrder(): WC_Order
    {
        return $this->order;
    }

    /**
     * @return void
     * @throws \JsonException
     */
    public function getWeight(): float
    {
        $weight = $this->getExtraOptions()['weight'] ?? null;

        if (null===$weight && $this->order->meta_exists(WCMYPA_Admin::META_ORDER_WEIGHT)) {
            $weight = $this->order->get_meta(WCMYPA_Admin::META_ORDER_WEIGHT);
        }

        return (float) $weight;
    }

    /**
     * @return array
     * @throws \JsonException
     */
    private function getExtraOptions(): array
    {
        return WCMYPA_Admin::getExtraOptionsFromOrder($this->order);
    }

    /**
     * @return int
     * @throws \JsonException
     */
    public function getColloAmount(): int
    {
        return (int) ($this->getExtraOptions()['collo_amount'] ?? 1);
    }

    /**
     * @return int
     * @throws \MyParcelNL\Sdk\src\Exception\ValidationException|\JsonException
     * @throws \Exception
     */
    public function getDigitalStampRangeWeight(): int
    {
        $weight       = 0;
        $extraOptions = $this->getExtraOptions();
        if (AbstractConsignment::PACKAGE_TYPE_DIGITAL_STAMP_NAME===$this->getPdkOrder()->deliveryOptions->packageType) {
            $emptyWeight = (float) WCMYPA()->settingCollection->getByName(
                WCMYPA_Settings::SETTING_EMPTY_DIGITAL_STAMP_WEIGHT
            );

            $weight += $emptyWeight;
        }

        $savedWeight   = $extraOptions['digital_stamp_weight'] ?? null;
        $orderWeight   = $this->getWeight();
        $defaultWeight = WCMYPA()->settingCollection->getByName(
            WCMYPA_Settings::SETTING_CARRIER_DIGITAL_STAMP_DEFAULT_WEIGHT
        ) ?: null;
        $weight        = (float) ($savedWeight ?? $defaultWeight ?? $orderWeight);

        return WeightService::convertToDigitalStamp((int) $weight);
    }

    /**
     * @return void
     */
    public function getLabelDescription($deliveryOptions): string
    {
        $defaultValue     = 'Order: ' . $this->order->get_id();
        $valueFromSetting = WCMYPA()->settingCollection->getByName(WCMYPA_Settings::SETTING_LABEL_DESCRIPTION);
        $valueFromOrder   = $deliveryOptions['shipmentOptions']['labelDescription'];

        return (string) ($valueFromOrder ?? $valueFromSetting ?? $defaultValue);
    }

    /**
     * @throws \JsonException
     * @throws \ErrorException
     */
    private function getCustomsDeclarationItems(): CustomsDeclarationItemCollection
    {
        $customsDeclarationItemCollection = new CustomsDeclarationItemCollection();

        foreach ($this->order->get_items() as $item) {
            $product       = $item->get_product();
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
     * @return bool
     */
    public function hasLocalPickup(): bool
    {
        $shippingMethods  = $this->order->get_shipping_methods();
        $shippingMethod   = array_shift($shippingMethods);
        $shippingMethodId = $shippingMethod ? $shippingMethod->get_method_id() : null;

        return WCMP_Shipping_Methods::LOCAL_PICKUP===$shippingMethodId;
    }

    /**
     * @return \MyParcelNL\Sdk\src\Model\Recipient|null
     * @throws \Exception
     */
    private function createRecipientFromWCOrder(): ?Recipient
    {
        try {
            return (new RecipientFromWCOrder(
                $this->order,
                CountryService::CC_NL,
                RecipientFromWCOrder::SHIPPING
            ));
        } catch (Exception $exception) {
            WCMP_Log::add(
                sprintf(
                    'Failed to create recipient from order %d',
                    $this->order->get_id()
                ),
                $exception->getMessage()
            );
        }

        return null;
    }

    /**
     * @return \MyParcelNL\Pdk\Shipment\Model\DeliveryOptions
     * @throws \Exception
     */
    public function getDeliveryOptions(): DeliveryOptions
    {
        $deliveryOptions = WCMYPA_Admin::getDeliveryOptionsFromOrder($this->order);

        return new DeliveryOptions([
            'carrier'         => $deliveryOptions->getCarrier(),
            'date'            => DeliveryDateService::fixPastDeliveryDate($deliveryOptions->getDate() ?? ''),
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
