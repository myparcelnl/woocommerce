<?php

declare(strict_types=1);

use MyParcelNL\Pdk\Api\Service\ApiServiceInterface;
use MyParcelNL\Pdk\Base\Model\ContactDetails;
use MyParcelNL\Pdk\Base\Service\CountryService;
use MyParcelNL\Pdk\Base\Service\WeightService;
use MyParcelNL\Pdk\Plugin\Collection\PdkOrderCollection;
use MyParcelNL\Pdk\Plugin\Model\PdkOrder;
use MyParcelNL\Pdk\Plugin\Repository\AbstractPdkOrderRepository;
use MyParcelNL\Pdk\Settings\Model\CustomsSettings;
use MyParcelNL\Pdk\Shipment\Model\CustomsDeclaration;
use MyParcelNL\Pdk\Shipment\Model\CustomsDeclarationItem;
use MyParcelNL\Pdk\Storage\StorageInterface;
use MyParcelNL\Sdk\src\Model\Recipient;
use MyParcelNL\WooCommerce\includes\adapter\RecipientFromWCOrder;

class PdkOrderRepository extends AbstractPdkOrderRepository
{
    /**
     * @var \MyParcelNL\Pdk\Base\Service\CountryService
     */
    private $countryService;

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
     */
    public function get($input): PdkOrder
    {
        $order = $input;

        if (! is_a($input, WC_Order::class)) {
            $order = new WC_Order($input);
        }

        return $this->retrieve((string) $order->id, function () use ($order) {
            return $this->getDataFromOrder($order);
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
    private function getDataFromOrder(WC_Order $order): PdkOrder
    {
        $deliveryOptions = $this->getDeliveryOptions();
        return new PdkOrder([
            'orderDate'             => $order->get_date_created()->getTimestamp(),
            'customsDeclaration'    => $this->getCustomsDeclaration(),
            'deliveryOptions'       => $deliveryOptions,
            'externalIdentifier'    =>  $order->get_id(),
            'label'                 => $this->getLabelDescription($deliveryOptions),
            'lines'                 => $this->getOrderLines(),
            'orderPrice'            => $order->get_total(),
            'orderPriceAfterVat'    => $order->get_total() + $order->get_cart_tax(),
            'orderVat'              => $order->get_total_tax(),
            'recipient'             => $this->getShippingRecipient($order),
            'shipmentPrice'         => (float) $order->get_shipping_total(),
            'shipmentPriceAfterVat' => (float) $order->get_shipping_total(),
            'shipmentVat'           => (float) $order->get_shipping_tax(),
            'totalPrice'            => (float) $order->get_shipping_total() + $order->get_total(),
            'totalPriceAfterVat'    => ($order->get_total() + $order->get_cart_tax(
                    )) + ((float) $order->get_shipping_total() + (float) $order->get_shipping_tax()),
            'totalVat'              => (float) $order->get_shipping_tax() + $order->get_cart_tax(),
        ]);
    }

    /**
     * @param  WC_ORDER $order
     * @param  array  $orderData
     *
     * @return null|\CustomsDeclaration
     */
    private function getCustomsDeclaration(WC_Order $order, array $orderData): ?CustomsDeclaration
    {
        $isToRowCountry = $this->countryService->isRowCountry(strtoupper($orderData['iso_code']));
        $customFormConfiguration = Configuration::get(CustomsSettings::ID);

        if (! $isToRowCountry || 'No' === $customFormConfiguration) {
            return null;
        }

        $products = OrderLabel::getCustomsOrderProducts($order->id);

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
     * @param  \WC_Order $order
     *
     * @return \MyParcelNL\Pdk\Base\Model\ContactDetails
     * @throws \Exception
     */
    public function getShippingRecipient(WC_Order $order: ContactDetails
    {
        $shippingRecipient = $this->createRecipientFromWCOrder($order);

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
     * @param  \WC_Order $order
     *
     * @return Recipient|null
     */
    private function createRecipientFromWCOrder(WC_Order $order): ?Recipient
    {
        try {
            return (new RecipientFromWCOrder(
                $order,
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
}
