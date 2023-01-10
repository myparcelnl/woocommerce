<?php

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Pdk\Plugin\Repository;

use MyParcelNL\Pdk\Base\Service\WeightService;
use MyParcelNL\Pdk\Facade\DefaultLogger;
use MyParcelNL\Pdk\Facade\Pdk;
use MyParcelNL\Pdk\Plugin\Collection\PdkOrderCollection;
use MyParcelNL\Pdk\Plugin\Model\PdkOrder;
use MyParcelNL\Pdk\Plugin\Repository\AbstractPdkOrderRepository;
use MyParcelNL\Pdk\Product\Repository\AbstractProductRepository;
use MyParcelNL\Pdk\Shipment\Collection\ShipmentCollection;
use MyParcelNL\Pdk\Shipment\Model\CustomsDeclaration;
use MyParcelNL\Pdk\Shipment\Model\CustomsDeclarationItem;
use MyParcelNL\Pdk\Shipment\Model\Shipment;
use MyParcelNL\Pdk\Storage\StorageInterface;
use MyParcelNL\Sdk\src\Adapter\DeliveryOptions\AbstractDeliveryOptionsAdapter;
use MyParcelNL\WooCommerce\Helper\LabelDescriptionFormatter;
use MyParcelNL\WooCommerce\Service\WcRecipientService;
use Throwable;
use WC_Order;
use WC_Order_Item;
use WC_Product;

class PdkOrderRepository extends AbstractPdkOrderRepository
{
    protected const WC_ORDER_META_ORDER_DATA = 'myparcelnl_order_data';
    protected const WC_ORDER_META_SHIPMENTS  = 'myparcelnl_order_shipments';

    /**
     * @var \MyParcelNL\Pdk\Product\Repository\AbstractProductRepository
     */
    private $productRepository;

    /**
     * @var \MyParcelNL\Pdk\Base\Service\WeightService
     */
    private $weightService;

    /**
     * @param  \MyParcelNL\Pdk\Storage\StorageInterface                     $storage
     * @param  \MyParcelNL\Pdk\Base\Service\WeightService                   $weightService
     * @param  \MyParcelNL\Pdk\Product\Repository\AbstractProductRepository $productRepository
     */
    public function __construct(
        StorageInterface          $storage,
        WeightService             $weightService,
        AbstractProductRepository $productRepository
    ) {
        parent::__construct($storage);
        $this->weightService     = $weightService;
        $this->productRepository = $productRepository;
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
            try {
                return $this->getDataFromOrder($order);
            } catch (Throwable $exception) {
                DefaultLogger::error(
                    'Could not retrieve order data from WooCommerce order',
                    [
                        'order_id' => $order->get_id(),
                        'error'    => $exception->getMessage(),
                    ]
                );

                return new PdkOrder();
            }
        });
    }

    /**
     * @param  \WC_Order $order
     *
     * @return array
     */
    public function getDeliveryOptions(WC_Order $order): array
    {
        $meta = $order->get_meta(self::WC_ORDER_META_ORDER_DATA) ?: [];

        // $meta is a json string, create an instance
        //        if (! empty($meta) && ! $meta instanceof AbstractDeliveryOptionsAdapter) {
        //            if (is_string($meta)) {
        //                $meta = json_decode(stripslashes($meta), true);
        //            }
        //
        //            if (! $meta['carrier']
        //                || ! AccountSettings::getInstance()
        //                    ->isEnabledCarrier($meta['carrier'])) {
        //                $meta['carrier'] = (Data::DEFAULT_CARRIER_CLASS)::NAME;
        //            }
        //
        //            $meta['date'] = $meta['date'] ?? '';
        //
        //            try {
        //                // create new instance from known json
        //                $meta = DeliveryOptionsAdapterFactory::create((array) $meta);
        //            } catch (BadMethodCallException $e) {
        //                // create new instance from unknown json data
        //                $meta = new WCMP_DeliveryOptionsFromOrderAdapter(null, (array) $meta);
        //            }
        //        }
        //
        //        // Create or update immutable adapter from order with a instanceof DeliveryOptionsAdapter
        //        if (empty($meta) || ! empty([])) {
        //            $meta = new WCMP_DeliveryOptionsFromOrderAdapter($meta, []);
        //        }

        return apply_filters('wc_myparcel_order_delivery_options', $meta, $order);
    }

    //    /**
    //     * @return int
    //     * @throws \MyParcelNL\Sdk\src\Exception\ValidationException|\JsonException
    //     * @throws \Exception
    //     */
    //    public function getDigitalStampRangeWeight(WC_Order $order): int
    //    {
    //        $extraOptions    = $this->getExtraOptions($order);
    //        $deliveryOptions = $this->getDeliveryOptions($order);
    //        $savedWeight     = $extraOptions['digital_stamp_weight'] ?? null;
    //        $orderWeight     = $this->getWeight();
    //        $defaultWeight   = WCMYPA()->settingCollection->getByName(
    //            WCMYPA_Settings::SETTING_CARRIER_DIGITAL_STAMP_DEFAULT_WEIGHT
    //        ) ?: null;
    //        $weight          = (float) ($savedWeight ?? $defaultWeight ?? $orderWeight);
    //
    //        if (DeliveryOptions::PACKAGE_TYPE_DIGITAL_STAMP_NAME === $deliveryOptions->getPackageType()) {
    //            $weight += (float) WCMYPA()->settingCollection->getByName(
    //                WCMYPA_Settings::SETTING_EMPTY_DIGITAL_STAMP_WEIGHT
    //            );
    //        }
    //
    //        return $this->weightService->convertToDigitalStamp((int) $weight);
    //    }

    //    /**
    //     * @param  \WC_Order $order
    //     *
    //     * @return array
    //     * @throws \JsonException
    //     */
    //    public function getExtraOptions(WC_Order $order): array
    //    {
    //        $meta = WCX_Order::get_meta($order, WCMYPA_Admin::META_SHIPMENT_OPTIONS_EXTRA) ?: null;
    //
    //        if (empty($meta)) {
    //            $meta['collo_amount'] = 1;
    //        }
    //
    //        return (array) $meta;
    //    }

    /**
     * @return void
     */
    public function getLabelDescription(AbstractDeliveryOptionsAdapter $deliveryOptions, WC_Order $order): string
    {
        $defaultValue     = sprintf('Order: %s', $order->get_id());
        $valueFromSetting = WCMYPA()->settingCollection->getByName('label_description');
        $valueFromOrder   = $deliveryOptions->getShipmentOptions()
            ->getLabelDescription();

        return (string) ($valueFromOrder ?? $valueFromSetting ?? $defaultValue);
    }

    //    /**
    //     * @return void
    //     * @throws \JsonException
    //     */
    //    public function getWeight(): float
    //    {
    //        $weight = (WCMYPA_Admin::getExtraOptionsFromOrder($order))['weight'] ?? null;
    //
    //        if (null === $weight && $order->meta_exists(WCMYPA_Admin::META_ORDER_WEIGHT)) {
    //            $weight = $order->get_meta(WCMYPA_Admin::META_ORDER_WEIGHT);
    //        }
    //
    //        return (float) $weight;
    //    }

    //    /**
    //     * @return bool
    //     */
    //    public function hasLocalPickup(WC_Order $order): bool
    //    {
    //        $shippingMethods  = $order->get_shipping_methods();
    //        $shippingMethod   = array_shift($shippingMethods);
    //        $shippingMethodId = $shippingMethod ? $shippingMethod->get_method_id() : null;
    //
    //        return WCMP_Shipping_Methods::LOCAL_PICKUP === $shippingMethodId;
    //    }

    /**
     * @param  \MyParcelNL\Pdk\Plugin\Model\PdkOrder $order
     *
     * @return \MyParcelNL\Pdk\Plugin\Model\PdkOrder
     * @throws \MyParcelNL\Pdk\Base\Exception\InvalidCastException
     */
    public function update(PdkOrder $order): PdkOrder
    {
        $idOrder = $order->externalIdentifier;

        update_post_meta($idOrder, self::WC_ORDER_META_ORDER_DATA, $order->deliveryOptions->toArray());

        $shipments = $order->shipments;

        if (($existing = get_post_meta($idOrder, self::WC_ORDER_META_SHIPMENTS, false))) {
            $shipments = $order->shipments->merge(... $existing);
        }

        $shipments = $shipments->reduce(function(array $carry, Shipment $shipment) {
            $carry[$shipment->getId()] = $shipment->toArray();

            return $carry;
        }, []);

        update_post_meta($idOrder, self::WC_ORDER_META_SHIPMENTS, $shipments);

        return $order;
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
     * @param  \WC_Order $order
     *
     * @return \MyParcelNL\Pdk\Plugin\Model\PdkOrder
     * @throws \ErrorException
     * @throws \JsonException
     */
    private function getDataFromOrder(WC_Order $order): PdkOrder
    {
        /** @var \MyParcelNL\WooCommerce\Service\WcRecipientService $recipientService */
        $recipientService = Pdk::get(WcRecipientService::class);

        $wcOrderItems = $order->get_items();

        return new PdkOrder([
            'orderDate'             => $order->get_date_created()
                ->getTimestamp(),
            'customsDeclaration'    => [
                'contents' => CustomsDeclaration::CONTENTS_COMMERCIAL_GOODS,
                'invoice'  => '1234',
                'weight'   => 1,
                'items'    => array_map(
                    function (WC_Order_Item $item) {
                        /** @var WC_Product $product */
                        $product    = $item->get_product();
                        $pdkProduct = $this->productRepository->getProduct($product);

                        return CustomsDeclarationItem::fromProduct($pdkProduct);
                    },
                    $wcOrderItems
                ),
            ],
            'deliveryOptions'       => $this->getDeliveryOptions($order),
            'externalIdentifier'    => $order->get_id(),
            'lines'                 => array_map(
                function (WC_Order_Item $item) {
                    /** @var WC_Product $product */
                    $product    = $item->get_product();
                    $pdkProduct = $this->productRepository->getProduct($product);

                    return [
                        'quantity'      => $item['quantity'],
                        'price'         => 0,
                        'vat'           => 0,
                        'priceAfterVat' => 0,
                        'product'       => $pdkProduct,
                    ];
                },
                $wcOrderItems
            ),
            'orderPrice'            => $order->get_total(),
            'orderPriceAfterVat'    => $order->get_total() + $order->get_cart_tax(),
            'orderVat'              => $order->get_total_tax(),
            'recipient'             => $recipientService->createAddress($order, WcRecipientService::SHIPPING),
            'shipmentPrice'         => (float) $order->get_shipping_total(),
            'shipmentPriceAfterVat' => (float) $order->get_shipping_total(),
            'shipments'             => $this->getShipment($order),
            'shipmentVat'           => (float) $order->get_shipping_tax(),
        ]);
    }

    /**
     * @param  \WC_Order $order
     *
     * @return null|\MyParcelNL\Pdk\Shipment\Collection\ShipmentCollection
     * @throws \JsonException
     */
    private function getShipment(WC_Order $order): ?ShipmentCollection
    {
        $shipments = $order->get_meta(self::WC_ORDER_META_SHIPMENTS);

        if (! $shipments) {
            return null;
        }

        $shipmentCollection = new ShipmentCollection();

        foreach ($shipments as $shipmentId => $shipmentData) {
            $shipmentCollection->push(new Shipment($shipmentData));
        }

        return $shipmentCollection;
    }

    /**
     * @param  \MyParcelNL\Sdk\src\Adapter\DeliveryOptions\AbstractDeliveryOptionsAdapter $deliveryOptions
     *
     * @return array
     */
    private function getShipmentOptions(AbstractDeliveryOptionsAdapter $deliveryOptions, WC_Order $order): array
    {
        $shipmentOptions = $deliveryOptions->getShipmentOptions()
            ? $deliveryOptions->getShipmentOptions()
                ->toArray()
            : [];

        $labelDescription                     = $this->getLabelDescription($deliveryOptions, $order);
        $shipmentOptions['label_description'] = (new LabelDescriptionFormatter(
            $order, $labelDescription, $deliveryOptions
        ))->getFormattedLabelDescription();

        return $shipmentOptions;
    }
}
