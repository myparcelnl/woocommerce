<?php

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Pdk\Plugin\Repository;

use InvalidArgumentException;
use MyParcelNL\Pdk\App\Order\Collection\PdkOrderNoteCollection;
use MyParcelNL\Pdk\App\Order\Contract\PdkProductRepositoryInterface;
use MyParcelNL\Pdk\App\Order\Model\PdkOrder;
use MyParcelNL\Pdk\App\Order\Model\PdkOrderLine;
use MyParcelNL\Pdk\App\Order\Repository\AbstractPdkOrderRepository;
use MyParcelNL\Pdk\Base\Service\CountryService;
use MyParcelNL\Pdk\Base\Support\Collection;
use MyParcelNL\Pdk\Facade\Logger;
use MyParcelNL\Pdk\Facade\Pdk;
use MyParcelNL\Pdk\Facade\Platform;
use MyParcelNL\Pdk\Facade\Settings;
use MyParcelNL\Pdk\Fulfilment\Model\OrderNote;
use MyParcelNL\Pdk\Settings\Model\GeneralSettings;
use MyParcelNL\Pdk\Shipment\Collection\ShipmentCollection;
use MyParcelNL\Pdk\Shipment\Model\CustomsDeclaration;
use MyParcelNL\Pdk\Shipment\Model\CustomsDeclarationItem;
use MyParcelNL\Pdk\Storage\Contract\StorageInterface;
use MyParcelNL\WooCommerce\Adapter\WcAddressAdapter;
use MyParcelNL\WooCommerce\Facade\Filter;
use stdClass;
use Throwable;
use WC_DateTime;
use WC_Order;
use WC_Order_Item_Product;
use WP_Post;

class PdkOrderRepository extends AbstractPdkOrderRepository
{
    /**
     * @var \MyParcelNL\WooCommerce\Adapter\WcAddressAdapter
     */
    private $addressAdapter;

    /**
     * @var \MyParcelNL\Pdk\Base\Service\CountryService
     */
    private $countryService;

    /**
     * @var \MyParcelNL\Pdk\App\Order\Contract\PdkProductRepositoryInterface
     */
    private $productRepository;

    /**
     * @param  \MyParcelNL\Pdk\Storage\Contract\StorageInterface                $storage
     * @param  \MyParcelNL\Pdk\App\Order\Contract\PdkProductRepositoryInterface $productRepository
     * @param  \MyParcelNL\Pdk\Base\Service\CountryService                      $countryService
     * @param  \MyParcelNL\WooCommerce\Adapter\WcAddressAdapter                 $addressAdapter
     */
    public function __construct(
        StorageInterface              $storage,
        PdkProductRepositoryInterface $productRepository,
        CountryService                $countryService,
        WcAddressAdapter              $addressAdapter
    ) {
        parent::__construct($storage);
        $this->productRepository = $productRepository;
        $this->countryService    = $countryService;
        $this->addressAdapter    = $addressAdapter;
    }

    /**
     * @param  int|string|WC_Order|WP_Post $input
     *
     * @return \MyParcelNL\Pdk\App\Order\Model\PdkOrder
     */
    public function get($input): PdkOrder
    {
        $order = $this->getWcOrder($input);

        return $this->retrieve((string) $order->get_id(), function () use ($order) {
            try {
                return $this->getDataFromOrder($order);
            } catch (Throwable $exception) {
                Logger::error(
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
     * @param  \MyParcelNL\Pdk\App\Order\Model\PdkOrder $order
     *
     * @return \MyParcelNL\Pdk\App\Order\Model\PdkOrder
     * @throws \MyParcelNL\Pdk\Base\Exception\InvalidCastException
     */
    public function update(PdkOrder $order): PdkOrder
    {
        $wcOrder = $this->getWcOrder($order->externalIdentifier);

        $order->shipments = $this
            ->getShipments($wcOrder)
            ->mergeByKey($order->shipments, 'id');

        $this->addBarcodesToOrderNote($wcOrder, $order);

        update_post_meta($wcOrder->get_id(), Pdk::get('metaKeyOrderData'), $order->toStorableArray());
        update_post_meta(
            $wcOrder->get_id(),
            Pdk::get('metaKeyOrderShipments'),
            $order->shipments->toStorableArray()
        );

        return $this->save($order->externalIdentifier, $order);
    }

    /**
     * @param  \WC_Order                                $wcOrder
     * @param  \MyParcelNL\Pdk\App\Order\Model\PdkOrder $order
     *
     * @return void
     */
    private function addBarcodesToOrderNote(WC_Order $wcOrder, PdkOrder $order): void
    {
        $hasBarcodeAndIsNotReturn =
            $order->shipments->where('barcode', '!=', null)
                ->where('isReturn', false);

        if ($hasBarcodeAndIsNotReturn->isNotEmpty()) {
            $prefix = Settings::get(GeneralSettings::BARCODE_IN_NOTE_TITLE, GeneralSettings::ID);

            $barcodeArray = $hasBarcodeAndIsNotReturn
                ->pluck('barcode')
                ->toArray();

            $wcOrder->add_order_note($prefix . implode(', ', $barcodeArray));
        }
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
            'items'    => array_values(
                $items
                    ->filter(function ($item) {
                        return $item['product'] && ! $item['product']->is_virtual();
                    })
                    ->map(function ($item) {
                        return CustomsDeclarationItem::fromProduct($item['pdkProduct']);
                    })
                    ->toArray()
            ),
        ];
    }

    /**
     * @param  \WC_Order $order
     *
     * @return \MyParcelNL\Pdk\App\Order\Model\PdkOrder
     * @throws \Exception
     * @noinspection PhpCastIsUnnecessaryInspection
     */
    private function getDataFromOrder(WC_Order $order): PdkOrder
    {
        $savedOrderData = $order->get_meta(Pdk::get('metaKeyOrderData')) ?: [];

        $items           = $this->getWcOrderItems($order);
        $shippingAddress = $this->addressAdapter->fromWcOrder($order);

        $savedOrderData['deliveryOptions'] = (array) (Filter::apply(
            'orderDeliveryOptions',
            $savedOrderData['deliveryOptions'] ?? [],
            $order
        ) ?? []);

        $isRow = $this->countryService->isRow($shippingAddress['cc'] ?? Platform::get('localCountry'));

        $orderData = [
            'externalIdentifier'    => $order->get_id(),
            'billingAddress'        => $this->addressAdapter->fromWcOrder($order, Pdk::get('wcAddressTypeBilling')),
            'customsDeclaration'    => $isRow
                ? $this->createCustomsDeclaration($order, $items)
                : null,
            'lines'                 => $items
                ->map(function (array $item) {
                    return new PdkOrderLine([
                        'quantity' => $item['item']->get_quantity(),
                        'price'    => (int) ((float) $item['item']->get_total() * 100),
                        'product'  => $item['pdkProduct'],
                    ]);
                })
                ->all(),
            'notes'                 => $this->getNotes($order, $savedOrderData['notes'] ?? []),
            'physicalProperties'    => $this->getPhysicalProperties($items),
            'shippingAddress'       => $shippingAddress,
            'orderPrice'            => $order->get_total(),
            'orderPriceAfterVat'    => (float) $order->get_total() + (float) $order->get_cart_tax(),
            'orderVat'              => $order->get_total_tax(),
            'shipmentPrice'         => (float) $order->get_shipping_total(),
            'shipmentPriceAfterVat' => (float) $order->get_shipping_total(),
            'shipments'             => $this->getShipments($order),
            'shipmentVat'           => (float) $order->get_shipping_tax(),
            'orderDate'             => $this->getDate($order->get_date_created()),
        ];

        return new PdkOrder(array_replace($savedOrderData, $orderData));
    }

    /**
     * @param  null|\WC_DateTime $date
     *
     * @return null|string
     */
    private function getDate(?WC_DateTime $date): ?string
    {
        if (! $date) {
            return null;
        }

        return $date->date('Y-m-d H:i:s');
    }

    /**
     * @param  \WC_Order $order
     * @param  array     $existingNotes
     *
     * @return PdkOrderNoteCollection
     */
    private function getNotes(WC_Order $order, array $existingNotes): PdkOrderNoteCollection
    {
        return $this->retrieve(sprintf('notes_%s', $order->get_id()), function () use ($existingNotes, $order) {
            $collection = new PdkOrderNoteCollection($existingNotes);

            $customerNote = $order->get_customer_note();
            $notes        = wc_get_order_notes(['order_id' => $order->get_id()]);

            if ($customerNote) {
                $notes[] = (object) [
                    'id'       => 'customer_note',
                    'content'  => $customerNote,
                    'added_by' => OrderNote::AUTHOR_CUSTOMER,
                ];
            }

            $newNotes = (new Collection($notes))
                ->filter(static function (stdClass $note) {
                    return 'system' !== $note->added_by;
                })
                ->map(function (stdClass $note) use ($order) {
                    $noteCreatedDate = $this->getDate($note->date_created ?? $order->get_date_created());
                    $apiIdentifier   = null;

                    return [
                        'apiIdentifier'      => $apiIdentifier,
                        'externalIdentifier' => $note->id,
                        'author'             => OrderNote::AUTHOR_CUSTOMER === $note->added_by
                            ? OrderNote::AUTHOR_CUSTOMER
                            : OrderNote::AUTHOR_WEBSHOP,
                        'note'               => $note->content ?? null,
                        'createdAt'          => $noteCreatedDate,
                        'updatedAt'          => $noteCreatedDate,
                    ];
                });

            return $collection->mergeByKey($newNotes, 'externalIdentifier');
        });
    }

    /**
     * @param  \MyParcelNL\Pdk\Base\Support\Collection $items
     *
     * @return array
     */
    private function getPhysicalProperties(Collection $items): array
    {
        return [
            'weight' => $items
                ->where('product', '!=', null)
                ->reduce(static function (float $acc, $item) {
                    $quantity = $item['item']->get_quantity();
                    $weight   = $item['product']->get_weight();

                    if (is_numeric($quantity) && is_numeric($weight)) {
                        $acc += $quantity * $weight;
                    }

                    return $acc;
                }, 0),
        ];
    }

    /**
     * @param  \WC_Order $order
     *
     * @return \MyParcelNL\Pdk\Shipment\Collection\ShipmentCollection
     */
    private function getShipments(WC_Order $order): ShipmentCollection
    {
        return $this->retrieve(
            "wc_order_shipments_{$order->get_id()}",
            function () use ($order): ShipmentCollection {
                $shipments = $order->get_meta(Pdk::get('metaKeyOrderShipments')) ?: null;

                return new ShipmentCollection($shipments);
            }
        );
    }

    /**
     * @param  int|string|WC_Order|\WP_Post $input
     *
     * @return \WC_Order
     */
    private function getWcOrder($input): WC_Order
    {
        if (is_object($input) && method_exists($input, 'get_id')) {
            $id = $input->get_id();
        } elseif (is_object($input) && isset($input->ID)) {
            $id = $input->ID;
        } else {
            $id = $input;
        }

        if (! is_scalar($id)) {
            throw new InvalidArgumentException('Invalid input');
        }

        return $this->retrieve("wc_order_$id", function () use ($input, $id) {
            if (is_a($input, WC_Order::class)) {
                return $input;
            }

            return new WC_Order($id);
        });
    }

    /**
     * @param  \WC_Order $order
     *
     * @return \MyParcelNL\Pdk\Base\Support\Collection
     */
    private function getWcOrderItems(WC_Order $order): Collection
    {
        return new Collection(
            array_map(function ($item) {
                $product = $item instanceof WC_Order_Item_Product ? $item->get_product() : null;

                return [
                    'item'       => $item,
                    'product'    => $product,
                    'pdkProduct' => $product ? $this->productRepository->getProduct($product) : null,
                ];
            }, array_values($order->get_items() ?? []))
        );
    }
}
