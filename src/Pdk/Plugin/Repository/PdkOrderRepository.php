<?php

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Pdk\Plugin\Repository;

use DateTime;
use MyParcelNL\Pdk\Base\Service\CountryService;
use MyParcelNL\Pdk\Base\Support\Collection;
use MyParcelNL\Pdk\Facade\DefaultLogger;
use MyParcelNL\Pdk\Plugin\Model\PdkOrder;
use MyParcelNL\Pdk\Plugin\Model\PdkOrderLine;
use MyParcelNL\Pdk\Plugin\Repository\AbstractPdkOrderRepository;
use MyParcelNL\Pdk\Product\Repository\AbstractProductRepository;
use MyParcelNL\Pdk\Shipment\Collection\ShipmentCollection;
use MyParcelNL\Pdk\Shipment\Model\CustomsDeclaration;
use MyParcelNL\Pdk\Shipment\Model\CustomsDeclarationItem;
use MyParcelNL\Pdk\Storage\StorageInterface;
use MyParcelNL\WooCommerce\Service\WcRecipientService;
use Throwable;
use WC_Order;
use WC_Order_Item_Product;
use function apply_filters;

class PdkOrderRepository extends AbstractPdkOrderRepository
{
    public const WC_ORDER_META_ORDER_DATA = 'myparcelnl_order_data';
    public const WC_ORDER_META_SHIPMENTS  = 'myparcelnl_order_shipments';

    /**
     * @var \MyParcelNL\Pdk\Base\Service\CountryService
     */
    private $countryService;

    /**
     * @var \MyParcelNL\Pdk\Product\Repository\AbstractProductRepository
     */
    private $productRepository;

    /**
     * @var \MyParcelNL\WooCommerce\Service\WcRecipientService
     */
    private $recipientService;

    /**
     * @param  \MyParcelNL\Pdk\Storage\StorageInterface                     $storage
     * @param  \MyParcelNL\Pdk\Product\Repository\AbstractProductRepository $productRepository
     * @param  \MyParcelNL\WooCommerce\Service\WcRecipientService           $recipientService
     * @param  \MyParcelNL\Pdk\Base\Service\CountryService                  $countryService
     */
    public function __construct(
        StorageInterface          $storage,
        AbstractProductRepository $productRepository,
        WcRecipientService        $recipientService,
        CountryService            $countryService
    ) {
        parent::__construct($storage);
        $this->productRepository = $productRepository;
        $this->recipientService  = $recipientService;
        $this->countryService    = $countryService;
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
     * @return \MyParcelNL\Pdk\Base\Support\Collection
     */
    public function getWcOrderItems(WC_Order $order): Collection
    {
        return (new Collection(
            array_map(function ($item) {
                $product = $item instanceof WC_Order_Item_Product ? $item->get_product() : null;

                return [
                    'item'       => $item,
                    'product'    => $product,
                    'pdkProduct' => $product ? $this->productRepository->getProduct($product) : null,
                ];
            }, $order->get_items())
        ));
    }

    /**
     * @param  \MyParcelNL\Pdk\Plugin\Model\PdkOrder $order
     *
     * @return \MyParcelNL\Pdk\Plugin\Model\PdkOrder
     * @throws \MyParcelNL\Pdk\Base\Exception\InvalidCastException
     * @throws \Exception
     */
    public function update(PdkOrder $order): PdkOrder
    {
        $existingOrder = $this->get($order->externalIdentifier);
        $diff          = array_diff_assoc($order->toArray(), $existingOrder->toArray());

        if (! empty($diff)) {
            $this->updateOrder($order);
        }

        if ($order->shipments->contains('updated', null)) {
            $this->saveShipments($order);
        }

        return $this->save($order->externalIdentifier, $order);
    }

    /**
     * @param  \WC_Order                               $order
     * @param  \MyParcelNL\Pdk\Base\Support\Collection $items
     *
     * @return array
     */
    private function createCustomsDeclaration(WC_Order $order, Collection $items): array
    {
        return [
            'contents' => CustomsDeclaration::CONTENTS_COMMERCIAL_GOODS,
            'invoice'  => $order->get_id(),
            'items'    => $items
                ->filter(function ($item) {
                    return $item['product'] && ! $item['product']->is_virtual();
                })
                ->map(function ($item) {
                    return CustomsDeclarationItem::fromProduct($item['pdkProduct']);
                })
                ->toArray(),
        ];
    }

    /**
     * @param  \WC_Order $order
     *
     * @return \MyParcelNL\Pdk\Plugin\Model\PdkOrder
     * @throws \ErrorException
     * @throws \JsonException
     * @throws \MyParcelNL\Pdk\Base\Exception\InvalidCastException
     */
    private function getDataFromOrder(WC_Order $order): PdkOrder
    {
        $savedOrderData  = $order->get_meta(self::WC_ORDER_META_ORDER_DATA) ?: [];
        $deliveryOptions = apply_filters(
            'wc_myparcel_order_delivery_options',
            $savedOrderData['deliveryOptions'] ?? [],
            $order
        );

        $items     = $this->getWcOrderItems($order);
        $recipient = $this->recipientService->createAddress($order, WcRecipientService::SHIPPING);

        $orderData = [
            'externalIdentifier'    => $order->get_id(),
            'recipient'             => $recipient,
            'billingAddress'        => $this->recipientService->createAddress($order, WcRecipientService::BILLING),
            'deliveryOptions'       => $deliveryOptions,
            'lines'                 => $items
                ->map(function (array $item) {
                    return new PdkOrderLine([
                        'quantity' => $item['item']->get_quantity(),
                        'price'    => (int) ((float) $item['item']->get_total() * 100),
                        'vat'      => (int) ((float) $item['item']->get_total_tax() * 100),
                        'product'  => $item['pdkProduct'],
                    ]);
                })
                ->toArray(),
            'customsDeclaration'    => $this->countryService->isRow($recipient['cc'])
                ? $this->createCustomsDeclaration($order, $items)
                : null,
            'orderPrice'            => $order->get_total(),
            'orderPriceAfterVat'    => $order->get_total() + $order->get_cart_tax(),
            'orderVat'              => $order->get_total_tax(),
            'shipmentPrice'         => (float) $order->get_shipping_total(),
            'shipmentPriceAfterVat' => (float) $order->get_shipping_total(),
            'shipments'             => $this->getShipments($order),
            'shipmentVat'           => (float) $order->get_shipping_tax(),
            'orderDate'             => $this->getOrderDate($order),
        ];

        return new PdkOrder($orderData + $savedOrderData);
    }

    /**
     * @param  \WC_Order $order
     *
     * @return null|string
     */
    private function getOrderDate(WC_Order $order): ?string
    {
        $wcOrderCreated = $order->get_date_created();

        return $wcOrderCreated ? $wcOrderCreated->format('Y-m-d H:i:s') : null;
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

    /**
     * @param  \MyParcelNL\Pdk\Plugin\Model\PdkOrder $order
     *
     * @return void
     */
    private function saveShipments(PdkOrder $order): void
    {
        $existingShipments = get_post_meta($order->externalIdentifier, self::WC_ORDER_META_SHIPMENTS, true);

        $order->shipments = (new ShipmentCollection($existingShipments))
            ->mergeByKey($order->shipments, 'externalIdentifier')
            ->each(function ($shipment) {
                $shipment->updated = new DateTime();
            });

        update_post_meta(
            $order->externalIdentifier,
            self::WC_ORDER_META_SHIPMENTS,
            $order->shipments->toStorableArray()
        );

        $order->shipments  = (new ShipmentCollection($existingShipments))->mergeByKey(
            $order->shipments,
            'externalIdentifier'
        );

        $wcOrder           = wc_get_order($order->externalIdentifier);
        $existingShipments = $wcOrder->get_meta(self::WC_ORDER_META_SHIPMENTS);
        $order->shipments  = (new ShipmentCollection($existingShipments))->mergeByKey(
            $order->shipments,
            'externalIdentifier'
        );

        $wcOrder->update_meta_data(self::WC_ORDER_META_SHIPMENTS, $order->shipments->toStorableArray());
        $wcOrder->save_meta_data();

        $trackTraces = $order->shipments->pluck('barcode')->toArrayWithoutNull();

        if ($trackTraces) {
            // TODO: Use setting for note prefix
            $prefix = '';
            $wcOrder->add_order_note($prefix . implode(', ', $trackTraces));
        }
    }

    /**
     * @param  \MyParcelNL\Pdk\Plugin\Model\PdkOrder $order
     *
     * @return void
     * @throws \MyParcelNL\Pdk\Base\Exception\InvalidCastException
     */
    private function updateOrder(PdkOrder $order): void
    {
        update_post_meta($order->externalIdentifier, self::WC_ORDER_META_ORDER_DATA, $order->toStorableArray());
    }
}
