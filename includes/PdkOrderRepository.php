<?php

declare(strict_types=1);

use MyParcelNL\Pdk\Api\Service\ApiServiceInterface;
use MyParcelNL\Pdk\Base\Model\ContactDetails;
use MyParcelNL\Pdk\Base\Service\CountryService;
use MyParcelNL\Pdk\Base\Service\WeightService;
use MyParcelNL\Pdk\Fulfilment\Model\Product;
use MyParcelNL\Pdk\Plugin\Collection\PdkOrderCollection;
use MyParcelNL\Pdk\Plugin\Collection\PdkOrderLineCollection;
use MyParcelNL\Pdk\Plugin\Model\PdkOrder;
use MyParcelNL\Pdk\Plugin\Model\PdkOrderLine;
use MyParcelNL\Pdk\Plugin\Repository\AbstractPdkOrderRepository;
use MyParcelNL\Pdk\Settings\Model\CustomsSettings;
use MyParcelNL\Pdk\Shipment\Model\CustomsDeclaration;
use MyParcelNL\Pdk\Shipment\Model\CustomsDeclarationItem;
use MyParcelNL\Pdk\Shipment\Model\DeliveryOptions;
use MyParcelNL\Pdk\Shipment\Service\DeliveryDateService;
use MyParcelNL\Pdk\Storage\StorageInterface;
use MyParcelNL\Sdk\src\Model\PickupLocation;
use MyParcelNL\Sdk\src\Model\Recipient;
use MyParcelNL\WooCommerce\includes\adapter\RecipientFromWCOrder;

class PdkOrderRepository extends AbstractPdkOrderRepository
{
    /**
     * @var \MyParcelNL\Pdk\Base\Service\CountryService
     */
    private $countryService;

    /**
     * @var WC_Order $order
     */
    private $order;

    /**
     * @param  \MyParcelNL\Pdk\Storage\StorageInterface        $storage
     * @param  \MyParcelNL\Pdk\Api\Service\ApiServiceInterface $api
     * @param  \MyParcelNL\Pdk\Base\Service\CountryService     $countryService
     */
    public function __construct(StorageInterface $storage, ApiServiceInterface $api, CountryService $countryService)
    {
        parent::__construct(
            $storage,
            $api
        );

        $this->countryService = $countryService;
    }

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

        return $this->retrieve((string) $order->id, function () use ($order) {
            $this->order = $order;
            return $this->getDataFromOrder();
        });
    }

    /**
     * @param  PdkOrderCollection $collection
     *
     * @return \MyParcelNL\Pdk\Plugin\Collection\PdkOrderCollection
     */
    public function updateMany(PdkOrderCollection $collection): \MyParcelNL\Pdk\Plugin\Collection\PdkOrderCollection
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
        $this->save($order);
        return $order;
    }

    /**
     * @param  \WC_Order $order
     *
     * @return \MyParcelNL\Pdk\Plugin\Model\PdkOrder
     * @throws \Exception
     */
    private function getDataFromOrder(): PdkOrder
    {
        $deliveryOptions = $this->getDeliveryOptions();
        return new PdkOrder([
            'orderDate'             => $this->order->get_date_created()->getTimestamp(),
            'customsDeclaration'    => $this->getCustomsDeclaration(),
            'deliveryOptions'       => $deliveryOptions,
            'externalIdentifier'    =>  $this->order->get_id(),
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

    /**
     * @param  array  $orderData
     *
     * @return null|\CustomsDeclaration
     */
    private function getCustomsDeclaration(): ?CustomsDeclaration
    {
        $isToRowCountry = $this->countryService->isRowCountry(strtoupper($orderData['iso_code']));
        $customFormConfiguration = Configuration::get(CustomsSettings::ID);

        if (! $isToRowCountry || 'No' === $customFormConfiguration) {
            return null;
        }

        $products = OrderLabel::getCustomsOrderProducts($this->order->id);

        $items = (new Collection($products))
            ->filter()
            ->map(function ($product) {
                $productHsCode = ProductConfigurationProvider::get(
                    $product['product_id'],
                    CustomsSettings::DEFAULT_CUSTOMS_CODE
                );

                $productCountryOfOrigin = ProductConfigurationProvider::get(
                    $product['product_id'],
                    CustomsSettings::DEFAULT_COUNTRY_OF_ORIGIN
                );

                return new CustomsDeclarationItem([
                    'amount'         => $product['product_quantity'],
                    'classification' => (int) ($productHsCode
                        ?: Configuration::get(
                            CustomsSettings::DEFAULT_CUSTOMS_CODE
                        )),
                    'country'        => $productCountryOfOrigin ?? Configuration::get(
                            CustomsSettings::DEFAULT_COUNTRY_OF_ORIGIN
                        ),
                    'description'    => $product['product_name'],
                    'itemValue'      => Tools::ps_round($product['unit_price_tax_incl'] * 100),
                    'weight'         => WeightService::convertToGrams($product['product_weight'], WeightService::UNIT_GRAMS),
                ]);
            });

        return new CustomsDeclaration([
            'contents' => null,
            'invoice'  => null,
            'items'    => $items->toArray(),
            'weight'   => null,
        ]);
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
    public function getLabelDescription($deliveryOptions): string
    {
        $defaultValue     = sprintf('Order: %s', $this->order->get_id());
        $valueFromSetting = WCMYPA()->settingCollection->getByName(WCMYPA_Settings::SETTING_LABEL_DESCRIPTION);
        $valueFromOrder   = $deliveryOptions['shipmentOptions']['labelDescription'];

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
}
