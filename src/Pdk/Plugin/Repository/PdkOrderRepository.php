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
use MyParcelNL\Pdk\Facade\Platform;
use MyParcelNL\Pdk\Facade\Settings;
use MyParcelNL\Pdk\Settings\Model\CustomsSettings;
use MyParcelNL\Pdk\Shipment\Collection\ShipmentCollection;
use MyParcelNL\Pdk\Shipment\Model\CustomsDeclarationItem;
use MyParcelNL\Pdk\Storage\Contract\StorageInterface;
use MyParcelNL\WooCommerce\Adapter\WcAddressAdapter;
use MyParcelNL\WooCommerce\Facade\Filter;
use MyParcelNL\WooCommerce\WooCommerce\Contract\WcOrderRepositoryInterface;
use Throwable;
use WC_DateTime;
use WC_Order;
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
    private $pdkProductRepository;

    /**
     * @var \MyParcelNL\WooCommerce\WooCommerce\Contract\WcOrderRepositoryInterface
     */
    private $wcOrderRepository;

    /**
     * @param  \MyParcelNL\Pdk\Storage\Contract\StorageInterface                       $storage
     * @param  \MyParcelNL\Pdk\App\Order\Contract\PdkProductRepositoryInterface        $pdkProductRepository
     * @param  \MyParcelNL\WooCommerce\WooCommerce\Contract\WcOrderRepositoryInterface $wcOrderRepository
     * @param  \MyParcelNL\Pdk\Base\Service\CountryService                             $countryService
     * @param  \MyParcelNL\WooCommerce\Adapter\WcAddressAdapter                        $addressAdapter
     */
    public function __construct(
        StorageInterface              $storage,
        PdkProductRepositoryInterface $pdkProductRepository,
        WcOrderRepositoryInterface    $wcOrderRepository,
        CountryService                $countryService,
        WcAddressAdapter              $addressAdapter
    ) {
        parent::__construct($storage);
        $this->pdkProductRepository = $pdkProductRepository;
        $this->wcOrderRepository    = $wcOrderRepository;
        $this->countryService       = $countryService;
        $this->addressAdapter       = $addressAdapter;
    }

    /**
     * @param  int|string|WC_Order|WP_Post $input
     *
     * @return \MyParcelNL\Pdk\App\Order\Model\PdkOrder
     */
    public function get($input): PdkOrder
    {
        $order = $this->wcOrderRepository->get($input);

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
        $wcOrder = $this->wcOrderRepository->get($order->externalIdentifier);

        $order->shipments = $this
            ->getShipments($wcOrder)
            ->mergeByKey($order->shipments, 'id');

        update_post_meta($wcOrder->get_id(), Pdk::get('metaKeyOrderData'), $order->toStorableArray());
        update_post_meta(
            $wcOrder->get_id(),
            Pdk::get('metaKeyOrderShipments'),
            $order->shipments->toStorableArray()
        );

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
            'contents' => Settings::get(CustomsSettings::PACKAGE_CONTENTS, CustomsSettings::ID),
            'invoice'  => $order->get_id(),
            'items'    => array_values(
                $items
                    ->filter(function ($item) {
                        return $item['product'] && ! $item['product']->is_virtual();
                    })
                    ->map(function ($item) {
                        return array_merge(
                            CustomsDeclarationItem::fromProduct($item['pdkProduct'])
                                ->toArray(),
                            ['amount' => $item['item']->get_quantity()]
                        );
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
        $items          = $this->getOrderItems($order);

        $shippingAddress = $this->addressAdapter->fromWcOrder($order);

        $savedOrderData['deliveryOptions'] = (array) (Filter::apply(
            'orderDeliveryOptions',
            $savedOrderData['deliveryOptions'] ?? [],
            $order
        ) ?? []);

        $isRow = $this->countryService->isRow($shippingAddress['cc'] ?? Platform::get('localCountry'));

        $orderData = [
            'externalIdentifier'    => $order->get_id(),
            'referenceIdentifier'   => $order->get_order_number(),
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

        return new PdkOrder(array_replace($orderData, $savedOrderData));
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
     *
     * @return \MyParcelNL\Pdk\Base\Support\Collection
     */
    private function getOrderItems(WC_Order $order): Collection
    {
        return $this->wcOrderRepository->getItems($order)
            ->map(function (array $item) {
                return array_merge(
                    $item,
                    [
                        'pdkProduct' => $item['product']
                            ? $this->pdkProductRepository->getProduct($item['product'])
                            : null,
                    ]
                );
            });
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
}
