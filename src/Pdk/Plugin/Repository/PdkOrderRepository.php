<?php

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Pdk\Plugin\Repository;

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
use MyParcelNL\Sdk\src\Support\Arr;
use MyParcelNL\WooCommerce\Service\WcRecipientService;
use Throwable;
use WC_Order;
use WC_Order_Item;
use WC_Product;

class PdkOrderRepository extends AbstractPdkOrderRepository
{
    public const WC_ORDER_META_ORDER_DATA = 'myparcelnl_order_data';
    public const WC_ORDER_META_SHIPMENTS  = 'myparcelnl_order_shipments';

    /**
     * @var \MyParcelNL\Pdk\Product\Repository\AbstractProductRepository
     */
    private $productRepository;

    /**
     * @param  \MyParcelNL\Pdk\Storage\StorageInterface                     $storage
     * @param  \MyParcelNL\Pdk\Product\Repository\AbstractProductRepository $productRepository
     */
    public function __construct(
        StorageInterface          $storage,
        AbstractProductRepository $productRepository
    ) {
        parent::__construct($storage);
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

        return apply_filters('wc_myparcel_order_delivery_options', $meta, $order);
    }

    /**
     * @param  \MyParcelNL\Pdk\Plugin\Model\PdkOrder $order
     *
     * @return \MyParcelNL\Pdk\Plugin\Model\PdkOrder
     * @throws \MyParcelNL\Pdk\Base\Exception\InvalidCastException
     */
    public function update(PdkOrder $order): PdkOrder
    {
        update_post_meta(
            $order->externalIdentifier,
            self::WC_ORDER_META_ORDER_DATA,
            ['deliveryOptions' => $order->deliveryOptions->toArray()]
        );

        $existing = get_post_meta($order->externalIdentifier, self::WC_ORDER_META_SHIPMENTS, true);

        if ($existing) {
            $order->shipments = $order->shipments->filter(
                function (Shipment $shipment) use ($existing) {
                    return ! in_array($shipment->id, Arr::pluck($existing, 'id'), true);
                }
            )
                ->merge($existing);
        }

        update_post_meta(
            $order->externalIdentifier,
            self::WC_ORDER_META_SHIPMENTS,
            $order->shipments->map(function (Shipment $shipment) {
                return $shipment->toStorableArray();
            })->toArray()
        );

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

        $wcOrderItems   = $order->get_items();
        $wcOrderCreated = $order->get_date_created();

        return new PdkOrder([
            'orderDate'             => $wcOrderCreated ? $wcOrderCreated->getTimestamp() : null,
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
            'shipments'             => $this->getShipments($order),
            'shipmentVat'           => (float) $order->get_shipping_tax(),
        ]);
    }

    /**
     * @param  \WC_Order $order
     *
     * @return null|\MyParcelNL\Pdk\Shipment\Collection\ShipmentCollection
     */
    private function getShipments(WC_Order $order): ?ShipmentCollection
    {
        $shipments = $order->get_meta(self::WC_ORDER_META_SHIPMENTS) ?: null;

        return new ShipmentCollection($shipments);
    }
}
