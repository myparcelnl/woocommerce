<?php

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Pdk\Plugin\Repository;

use InvalidArgumentException;
use MyParcelNL\Pdk\App\Order\Contract\PdkProductRepositoryInterface;
use MyParcelNL\Pdk\App\Order\Model\PdkOrder;
use MyParcelNL\Pdk\App\Order\Model\PdkOrderLine;
use MyParcelNL\Pdk\App\Order\Repository\AbstractPdkOrderRepository;
use MyParcelNL\Pdk\Base\Service\CountryService;
use MyParcelNL\Pdk\Base\Support\Collection;
use MyParcelNL\Pdk\Facade\Logger;
use MyParcelNL\Pdk\Facade\Pdk;
use MyParcelNL\Pdk\Facade\Settings;
use MyParcelNL\Pdk\Fulfilment\Model\OrderNote;
use MyParcelNL\Pdk\Settings\Model\GeneralSettings;
use MyParcelNL\Pdk\Settings\Model\LabelSettings;
use MyParcelNL\Pdk\Shipment\Collection\ShipmentCollection;
use MyParcelNL\Pdk\Shipment\Model\CustomsDeclaration;
use MyParcelNL\Pdk\Shipment\Model\CustomsDeclarationItem;
use MyParcelNL\Pdk\Storage\Contract\StorageInterface;
use MyParcelNL\Sdk\src\Support\Str;
use MyParcelNL\WooCommerce\Adapter\WcAddressAdapter;
use MyParcelNL\WooCommerce\Facade\Filter;
use stdClass;
use Throwable;
use WC_DateTime;
use WC_Order;
use WC_Order_Item_Product;
use WC_Product;
use WP_Post;

class PdkOrderRepository extends AbstractPdkOrderRepository
{
    private const DESCRIPTION_CUSTOMER_NOTE = '[CUSTOMER_NOTE]';
    private const DESCRIPTION_ORDER_NR      = '[ORDER_NR]';
    private const DESCRIPTION_PLACEHOLDERS  = [
        self::DESCRIPTION_CUSTOMER_NOTE,
        self::DESCRIPTION_ORDER_NR,
        self::DESCRIPTION_PRODUCT_ID,
        self::DESCRIPTION_PRODUCT_NAME,
        self::DESCRIPTION_PRODUCT_QTY,
        self::DESCRIPTION_PRODUCT_SKU,
    ];
    private const DESCRIPTION_PRODUCT_ID    = '[PRODUCT_ID]';
    private const DESCRIPTION_PRODUCT_NAME  = '[PRODUCT_NAME]';
    private const DESCRIPTION_PRODUCT_QTY   = '[PRODUCT_QTY]';
    private const DESCRIPTION_PRODUCT_SKU   = '[PRODUCT_SKU]';

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
     * @param  \MyParcelNL\Pdk\App\Order\Model\PdkOrder $newOrder
     *
     * @return \MyParcelNL\Pdk\App\Order\Model\PdkOrder
     * @throws \MyParcelNL\Pdk\Base\Exception\InvalidCastException
     */
    public function update(PdkOrder $newOrder): PdkOrder
    {
        $wcOrder = $this->getWcOrder($newOrder->externalIdentifier);

        $newOrder->shipments = $this
            ->getShipments($wcOrder)
            ->mergeByKey($newOrder->shipments, 'id');

        $this->addBarcodesToOrderNote($wcOrder, $newOrder);

        update_post_meta($wcOrder->get_id(), Pdk::get('metaKeyOrderData'), $newOrder->toStorableArray());
        update_post_meta(
            $wcOrder->get_id(),
            Pdk::get('metaKeyOrderShipments'),
            $newOrder->shipments->toStorableArray()
        );

        return $this->save($newOrder->externalIdentifier, $newOrder);
    }

    /**
     * @param  \WC_Order                                $wcOrder
     * @param  \MyParcelNL\Pdk\App\Order\Model\PdkOrder $order
     *
     * @return void
     */
    private function addBarcodesToOrderNote(WC_Order $wcOrder, PdkOrder $order): void
    {
        $withBarcodes = $order->shipments->where('barcode', '!=', null);

        if ($withBarcodes->isNotEmpty()) {
            $prefix = Settings::get(GeneralSettings::BARCODE_IN_NOTE_TITLE, GeneralSettings::ID);

            $barcodeArray = $withBarcodes
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
     */
    private function getDataFromOrder(WC_Order $order): PdkOrder
    {
        $savedOrderData = $order->get_meta(Pdk::get('metaKeyOrderData')) ?: [];

        $items           = $this->getWcOrderItems($order);
        $shippingAddress = $this->addressAdapter->fromWcOrder($order);

        $orderData = [
            'externalIdentifier'    => $order->get_id(),
            'billingAddress'        => $this->addressAdapter->fromWcOrder($order, Pdk::get('wcAddressTypeBilling')),
            'customsDeclaration'    => $this->countryService->isRow($shippingAddress['cc'] ?? null)
                ? $this->createCustomsDeclaration($order, $items)
                : null,
            'deliveryOptions'       => $this->getDeliveryOptions(
                $savedOrderData['deliveryOptions'] ?? [],
                $order,
                $items
            ),
            'lines'                 => $items
                ->map(function (array $item) {
                    return new PdkOrderLine([
                        'quantity' => $item['item']->get_quantity(),
                        'price'    => (int) ((float) $item['item']->get_total() * 100),
                        'product'  => $item['pdkProduct'],
                    ]);
                })
                ->all(),
            'notes'                 => $this->getNotes($order),
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

        return new PdkOrder($orderData + $savedOrderData);
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
     * @param  array                                   $deliveryOptions
     * @param  \WC_Order                               $order
     * @param  \MyParcelNL\Pdk\Base\Support\Collection $items
     *
     * @return array
     */
    private function getDeliveryOptions(array $deliveryOptions, WC_Order $order, Collection $items): array
    {
        if (isset($deliveryOptions['shipmentOptions'])) {
            $deliveryOptions['shipmentOptions']['labelDescription'] = $this->getLabelDescription(
                $order,
                $items,
                $deliveryOptions['shipmentOptions']['labelDescription'] ?? null
            );
        }

        return (array) (Filter::apply('orderDeliveryOptions', $deliveryOptions ?? [], $order) ?? []);
    }

    /**
     * @param  \WC_Order                                       $order
     * @param  \MyParcelNL\Pdk\Base\Support\Collection|array[] $items
     * @param  null|string                                     $labelDescription
     *
     * @return string
     */
    private function getLabelDescription(WC_Order $order, Collection $items, ?string $labelDescription): string
    {
        $description = $labelDescription ?? Settings::get(LabelSettings::DESCRIPTION, LabelSettings::ID) ?? '';

        if (! Str::contains($description, self::DESCRIPTION_PLACEHOLDERS)) {
            return $description;
        }

        $productDetails = $items
            ->where('product', '!=', null)
            ->reduce(function (array $carry, array $item) {
                /** @var WC_Product $product */
                $product = $item['product'];

                $sku = $product->get_sku();

                $carry['ids'][]      = $product->get_id();
                $carry['names'][]    = $product->get_name();
                $carry['skus'][]     = empty($sku) ? '–' : $sku;
                $carry['quantity'][] = $item['item']->get_quantity();

                return $carry;
            }, []);

        return strtr(
            $description,
            [
                self::DESCRIPTION_CUSTOMER_NOTE => $order->get_customer_note(),
                self::DESCRIPTION_ORDER_NR      => $order->get_id(),
                self::DESCRIPTION_PRODUCT_ID    => implode(', ', $productDetails['ids'] ?? []),
                self::DESCRIPTION_PRODUCT_NAME  => implode(', ', $productDetails['names'] ?? []),
                self::DESCRIPTION_PRODUCT_QTY   => implode(', ', $productDetails['quantity'] ?? []),
                self::DESCRIPTION_PRODUCT_SKU   => implode(', ', $productDetails['skus'] ?? []),
            ]
        );
    }

    /**
     * @param  \WC_Order $order
     *
     * @return void
     */
    private function getNotes(WC_Order $order): array
    {
        return $this->retrieve(sprintf("notes_%s", $order->get_id()), function () use ($order) {
            $customerNote = $order->get_customer_note();
            $notes        = wc_get_order_notes(['order_id' => $order->get_id()]);
            $orderData    = $order->get_meta(Pdk::get('metaKeyOrderData'));

            if ($customerNote) {
                $notes[] = (object) [
                    'id'       => 'customer_note',
                    'content'  => $customerNote,
                    'added_by' => OrderNote::AUTHOR_CUSTOMER,
                ];
            }

            return (new Collection($notes))
                ->filter(static function (stdClass $note) {
                    return 'system' !== $note->added_by;
                })
                ->map(function (stdClass $note) use ($order, $orderData) {
                    $noteCreatedDate = $this->getDate($note->date_created ?? $order->get_date_created());
                    $apiIdentifier   = null;

                    if ($orderData && $orderData['notes']) {
                        $matchingNote  =
                            array_search($note->id, array_column($orderData['notes'], 'externalIdentifier'));
                        $apiIdentifier =
                            false !== $matchingNote ? $orderData['notes'][$matchingNote]['apiIdentifier'] : null;
                    }

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
                })
                ->values()
                ->all();
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
        if (method_exists($input, 'get_id')) {
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
