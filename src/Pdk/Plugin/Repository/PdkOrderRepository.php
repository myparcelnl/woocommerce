<?php

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Pdk\Plugin\Repository;

use MyParcelNL\Pdk\App\Order\Contract\PdkProductRepositoryInterface;
use MyParcelNL\Pdk\App\Order\Model\PdkOrder;
use MyParcelNL\Pdk\App\Order\Model\PdkOrderLine;
use MyParcelNL\Pdk\App\Order\Repository\AbstractPdkOrderRepository;
use MyParcelNL\Pdk\Base\Service\CountryService;
use MyParcelNL\Pdk\Base\Support\Collection;
use MyParcelNL\Pdk\Facade\Logger;
use MyParcelNL\Pdk\Facade\Pdk;
use MyParcelNL\Pdk\Facade\Settings;
use MyParcelNL\Pdk\Settings\Model\GeneralSettings;
use MyParcelNL\Pdk\Settings\Model\LabelSettings;
use MyParcelNL\Pdk\Shipment\Collection\ShipmentCollection;
use MyParcelNL\Pdk\Shipment\Model\CustomsDeclaration;
use MyParcelNL\Pdk\Shipment\Model\CustomsDeclarationItem;
use MyParcelNL\Pdk\Storage\Contract\StorageInterface;
use MyParcelNL\Sdk\src\Support\Arr;
use MyParcelNL\Sdk\src\Support\Str;
use MyParcelNL\WooCommerce\Facade\Filter;
use MyParcelNL\WooCommerce\Factory\WcAddressAdapter;
use Throwable;
use WC_Order;
use WC_Order_Item_Product;
use WC_Product;

class PdkOrderRepository extends AbstractPdkOrderRepository
{
    private const DESCRIPTION_CUSTOMER_NOTE = '[CUSTOMER_NOTE]';
    private const DESCRIPTION_ORDER_NR      = '[ORDER_NR]';
    private const DESCRIPTION_PRODUCT_ID    = '[PRODUCT_ID]';
    private const DESCRIPTION_PRODUCT_NAME  = '[PRODUCT_NAME]';
    private const DESCRIPTION_PRODUCT_QTY   = '[PRODUCT_QTY]';
    private const DESCRIPTION_PRODUCT_SKU   = '[PRODUCT_SKU]';
    private const DESCRIPTION_PLACEHOLDERS  = [
        self::DESCRIPTION_CUSTOMER_NOTE,
        self::DESCRIPTION_ORDER_NR,
        self::DESCRIPTION_PRODUCT_ID,
        self::DESCRIPTION_PRODUCT_NAME,
        self::DESCRIPTION_PRODUCT_QTY,
        self::DESCRIPTION_PRODUCT_SKU,
    ];

    /**
     * @var \MyParcelNL\WooCommerce\Factory\WcAddressAdapter
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
     * @param  \MyParcelNL\WooCommerce\Factory\WcAddressAdapter                 $addressAdapter
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
     * @param  int|string|WC_Order $input
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
        $wcOrder       = $this->getWcOrder($order->externalIdentifier);
        $existingOrder = $this->get($order->externalIdentifier);

        if (serialize($order) !== serialize($existingOrder)) {
            update_post_meta($wcOrder->get_id(), Pdk::get('metaKeyOrderData'), $order->toStorableArray());
        }

        $this->saveShipments($wcOrder, $order);

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
            'items'    => array_values(
                $items->filter(function ($item) {
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

        $items     = $this->getWcOrderItems($order);
        $recipient = $this->addressAdapter->fromWcOrder($order);

        $orderData = [
            'externalIdentifier'    => $order->get_id(),
            'recipient'             => $recipient,
            'billingAddress'        => $this->addressAdapter->fromWcOrder($order, Pdk::get('wcAddressTypeBilling')),
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
                ->toArray(),
            'customsDeclaration'    => $this->countryService->isRow($recipient['cc'])
                ? $this->createCustomsDeclaration($order, $items)
                : null,
            'physicalProperties'    => $this->getPhysicalProperties($items),
            'orderPrice'            => $order->get_total(),
            'orderPriceAfterVat'    => (float) $order->get_total() + (float) $order->get_cart_tax(),
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
                $deliveryOptions['shipmentOptions']['labelDescription']
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
     * @return null|string
     */
    private function getOrderDate(WC_Order $order): ?string
    {
        $wcOrderCreated = $order->get_date_created();

        return $wcOrderCreated ? $wcOrderCreated->format('Y-m-d H:i:s') : null;
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
     * @return null|\MyParcelNL\Pdk\Shipment\Collection\ShipmentCollection
     */
    private function getShipments(WC_Order $order): ?ShipmentCollection
    {
        $shipments = $order->get_meta(Pdk::get('metaKeyShipments')) ?: null;

        return new ShipmentCollection($shipments);
    }

    /**
     * @param  int|string|WC_Order $input
     *
     * @return \WC_Order
     */
    private function getWcOrder($input): WC_Order
    {
        $order = $input;

        if (! is_a($input, WC_Order::class)) {
            return $this->retrieve("wc_order_$input", function () use ($input) {
                return new WC_Order($input);
            });
        }

        return $this->save("wc_order_{$order->get_id()}", $order);
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

    /**
     * @param  \WC_Order                                $wcOrder
     * @param  \MyParcelNL\Pdk\App\Order\Model\PdkOrder $order
     *
     * @return void
     */
    private function saveShipments(WC_Order $wcOrder, PdkOrder $order): void
    {
        $existingShipments = get_post_meta($wcOrder->get_id(), Pdk::get('metaKeyShipments'), true) ?: [];

        $order->shipments = (new ShipmentCollection($existingShipments))->mergeByKey($order->shipments, 'id');

        $shipmentsArray = $order->shipments->toStorableArray();

        update_post_meta($wcOrder->get_id(), Pdk::get('metaKeyShipments'), $shipmentsArray);

        $barcodes = array_filter(
            Arr::pluck($shipmentsArray, 'barcode'),
            static function ($item) { return null !== $item; }
        );

        if ($barcodes) {
            $prefix = Settings::get(GeneralSettings::BARCODE_IN_NOTE_TITLE, GeneralSettings::ID);

            $wcOrder->add_order_note($prefix . implode(', ', $barcodes));
        }
    }
}
