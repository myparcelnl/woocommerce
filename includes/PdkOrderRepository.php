<?php

declare(strict_types=1);

namespace MyParcelNL\WooCommerce;

use Exception;
use MyParcelNL\Pdk\Base\Model\ContactDetails;
use MyParcelNL\Pdk\Base\Service\CountryService;
use MyParcelNL\Pdk\Base\Service\WeightService;
use MyParcelNL\Pdk\Fulfilment\Model\Product;
use MyParcelNL\Pdk\Plugin\Collection\PdkOrderCollection;
use MyParcelNL\Pdk\Plugin\Collection\PdkOrderLineCollection;
use MyParcelNL\Pdk\Plugin\Model\PdkOrder;
use MyParcelNL\Pdk\Plugin\Model\PdkOrderLine;
use MyParcelNL\Pdk\Plugin\Repository\AbstractPdkOrderRepository;
use MyParcelNL\Pdk\Shipment\Collection\CustomsDeclarationItemCollection;
use MyParcelNL\Pdk\Shipment\Collection\ShipmentCollection;
use MyParcelNL\Pdk\Shipment\Model\CustomsDeclaration;
use MyParcelNL\Pdk\Shipment\Model\CustomsDeclarationItem;
use MyParcelNL\Pdk\Shipment\Model\DeliveryOptions;
use MyParcelNL\Pdk\Shipment\Model\Shipment;
use MyParcelNL\Pdk\Shipment\Service\DeliveryDateService;
use MyParcelNL\Sdk\src\Adapter\DeliveryOptions\AbstractDeliveryOptionsAdapter;
use MyParcelNL\Sdk\src\Model\Recipient;
use MyParcelNL\WooCommerce\Helper\ExportRow;
use MyParcelNL\WooCommerce\Helper\LabelDescriptionFormatter;
use MyParcelNL\WooCommerce\includes\adapter\RecipientFromWCOrder;
use WC_Order;
use WC_Order_Item;
use WCMP_Log;
use WCMP_Shipping_Methods;
use WCMYPA_Admin;
use WCMYPA_Settings;
use WPO\WC\MyParcel\Compatibility\Order as WCX_Order;

class PdkOrderRepository extends AbstractPdkOrderRepository
{
    /**
     * @var WC_Order $order
     */
    private $order;

    /**
     * @param  PdkOrder ...$orders
     *
     * @return void
     */
    public function add(PdkOrder ...$orders): void
    {
        foreach ($orders as $order) {
            $this->save($order->externalIdentifier, $order);
        }
    }

    /**
     * @param  int|string $input
     *
     * @return \MyParcelNL\Pdk\Plugin\Model\PdkOrder
     * @throws \Exception
     */
    public function get($input): PdkOrder
    {
        $order = $input;

        if (! is_a($input, WC_Order::class)) {
            $order = new WC_Order($input);
        }

        return $this->retrieve((string) $order->get_id(), function () use ($order) {
            $this->order = $order;
            return $this->getDataFromOrder();
        });
    }

    /**
     * @param  PdkOrderCollection $collection
     *
     * @return \MyParcelNL\Pdk\Plugin\Collection\PdkOrderCollection
     */
    public function updateMany(PdkOrderCollection $collection): PdkOrderCollection
    {
        return $collection->map([$this, 'update']);
    }

    /**
     * @param  \MyParcelNL\Pdk\Plugin\Model\PdkOrder $order
     *
     * @return \MyParcelNL\Pdk\Plugin\Model\PdkOrder
     */
    public function update(PdkOrder $order): PdkOrder
    {
        $this->save($order->externalIdentifier, $order);
        return $order;
    }

    /**
     * @return array|mixed|string
     * @throws \JsonException
     */
    private function generateShipmentsForOrder()
    {
        $shipments = WCX_Order::get_meta($this->order, WCMYPA_Admin::META_SHIPMENTS);

        if (! $shipments) {
            return null;
        }

        $shipmentCollection = new ShipmentCollection();

        foreach ($shipments as $shipmentId => $shipmentData) {
            if (isset($shipmentData['shipment']) && $shipmentData['shipment']) {
                $shipmentCollection->push(new Shipment($shipmentData['shipment']));
            }
        }

        return $shipmentCollection;
    }

    /**
     * @return \MyParcelNL\Pdk\Plugin\Model\PdkOrder
     * @throws \Exception
     */
    private function getDataFromOrder(): PdkOrder
    {
        $deliveryOptions = $this->getDeliveryOptions();

        return new PdkOrder([
            'orderDate'             => $this->order->get_date_created()
                ->getTimestamp(),
            'customsDeclaration'    => $this->getCustomsDeclaration(),
            'deliveryOptions'       => $deliveryOptions,
            'externalIdentifier'    => $this->order->get_id(),
            'lines'                 => $this->getOrderLines(),
            'orderPrice'            => $this->order->get_total(),
            'orderPriceAfterVat'    => $this->order->get_total() + $this->order->get_cart_tax(),
            'orderVat'              => $this->order->get_total_tax(),
            'recipient'             => $this->getShippingRecipient(),
            'shipmentPrice'         => (float) $this->order->get_shipping_total(),
            'shipmentPriceAfterVat' => (float) $this->order->get_shipping_total(),
            'shipments'             => $this->generateShipmentsForOrder(),
            'shipmentVat'           => (float) $this->order->get_shipping_tax(),
            'totalPrice'            => (float) $this->order->get_shipping_total() + $this->order->get_total(),
            'totalPriceAfterVat'    => ($this->order->get_total() + $this->order->get_cart_tax(
                    )) + ((float) $this->order->get_shipping_total() + (float) $this->order->get_shipping_tax()),
            'totalVat'              => (float) $this->order->get_shipping_tax() + $this->order->get_cart_tax(),
        ]);
    }

    /**
     * @return \MyParcelNL\Pdk\Shipment\Model\DeliveryOptions
     * @throws \Exception
     */
    public function getDeliveryOptions(): DeliveryOptions
    {
        $deliveryOptions = $this->getDeliveryOptionsFromOrder();

        return new DeliveryOptions([
            'carrier'         => $deliveryOptions->getCarrier(),
            'date'            => DeliveryDateService::fixPastDeliveryDate($deliveryOptions->getDate() ?? ''),
            'deliveryType'    => $deliveryOptions->getDeliveryType(),
            'labelAmount'     => 1,
            'packageType'     => $deliveryOptions->getPackageType(),
            //            'pickupLocation'  => (array) $deliveryOptions->getPickupLocation(),
            //            'pickupLocation'  => null,
            'shipmentOptions' => $this->getShipmentOptions($deliveryOptions),
        ]);
    }

    /**
     * @return \MyParcelNL\Pdk\Shipment\Model\CustomsDeclaration
     */
    private function getCustomsDeclaration(): CustomsDeclaration
    {
        $customDeclarationItems = $this->getCustomsDeclarationItems();
        $totalWeight            = $customDeclarationItems->reduce(
            static function (int $acc, CustomsDeclarationItem $item) {
                $acc += $item->weight;
                return $acc;
            },
            0
        );

        return new CustomsDeclaration([
            'contents' => CustomsDeclaration::CONTENTS_COMMERCIAL_GOODS,
            'invoice'  => '1234',
            'items'    => $customDeclarationItems,
            'weight'   => $totalWeight,
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
     * @return \MyParcelNL\Pdk\Base\Model\ContactDetails
     * @throws \Exception
     */
    public function getShippingRecipient(): ContactDetails
    {
        $shippingRecipient = $this->createRecipientFromWCOrder();

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
     * @return Recipient|null
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
     * @return void
     */
    public function getLabelDescription(AbstractDeliveryOptionsAdapter $deliveryOptions): string
    {
        $defaultValue     = sprintf('Order: %s', $this->order->get_id());
        $valueFromSetting = WCMYPA()->settingCollection->getByName(WCMYPA_Settings::SETTING_LABEL_DESCRIPTION);
        $valueFromOrder   = $deliveryOptions->getShipmentOptions()
            ->getLabelDescription();

        return (string) ($valueFromOrder ?? $valueFromSetting ?? $defaultValue);
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

        return WCMP_Shipping_Methods::LOCAL_PICKUP === $shippingMethodId;
    }

    /**
     * @return int
     * @throws \MyParcelNL\Sdk\src\Exception\ValidationException|\JsonException
     * @throws \Exception
     */
    public function getDigitalStampRangeWeight(): int
    {
        $extraOptions    = $this->getExtraOptions();
        $deliveryOptions = WCMYPA_Admin::getDeliveryOptionsFromOrder($this->order);
        $savedWeight     = $extraOptions['digital_stamp_weight'] ?? null;
        $orderWeight     = $this->getWeight();
        $defaultWeight   = WCMYPA()->settingCollection->getByName(
            WCMYPA_Settings::SETTING_CARRIER_DIGITAL_STAMP_DEFAULT_WEIGHT
        ) ?: null;
        $weight          = (float) ($savedWeight ?? $defaultWeight ?? $orderWeight);

        if (DeliveryOptions::PACKAGE_TYPE_DIGITAL_STAMP_NAME === $deliveryOptions->getPackageType()) {
            $weight += (float) WCMYPA()->settingCollection->getByName(
                WCMYPA_Settings::SETTING_EMPTY_DIGITAL_STAMP_WEIGHT
            );
        }

        return WeightService::convertToDigitalStamp((int) $weight);
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
     * @return \MyParcelNL\Sdk\src\Adapter\DeliveryOptions\AbstractDeliveryOptionsAdapter
     * @throws \Exception
     */
    private function getDeliveryOptionsFromOrder(): AbstractDeliveryOptionsAdapter
    {
        return WCMYPA_Admin::getDeliveryOptionsFromOrder($this->order);
    }

    /**
     * @return void
     * @throws \JsonException
     */
    public function getWeight(): float
    {
        $weight = $this->getExtraOptions()['weight'] ?? null;

        if (null === $weight && $this->order->meta_exists(WCMYPA_Admin::META_ORDER_WEIGHT)) {
            $weight = $this->order->get_meta(WCMYPA_Admin::META_ORDER_WEIGHT);
        }

        return (float) $weight;
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
     * @param  \MyParcelNL\Sdk\src\Adapter\DeliveryOptions\AbstractDeliveryOptionsAdapter $deliveryOptions
     *
     * @return array
     */
    private function getShipmentOptions(AbstractDeliveryOptionsAdapter $deliveryOptions): array
    {
        $shipmentOptions = $deliveryOptions->getShipmentOptions()
            ? $deliveryOptions->getShipmentOptions()
                ->toArray()
            : [];

        $labelDescription                     = $this->getLabelDescription($deliveryOptions);
        $shipmentOptions['label_description'] = (new LabelDescriptionFormatter(
            $this->order, $labelDescription, $deliveryOptions
        ))->getFormattedLabelDescription();

        return $shipmentOptions;
    }
}
