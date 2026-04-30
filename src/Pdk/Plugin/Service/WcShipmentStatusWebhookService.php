<?php

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Pdk\Plugin\Service;

use MyParcelNL\Pdk\App\Api\Backend\PdkBackendActions;
use MyParcelNL\Pdk\App\Order\Contract\PdkOrderRepositoryInterface;
use MyParcelNL\Pdk\App\Order\Model\PdkOrder;
use MyParcelNL\Pdk\Facade\Actions;
use MyParcelNL\Pdk\Facade\Logger;
use MyParcelNL\Pdk\Facade\Pdk;
use MyParcelNL\Pdk\Settings\Model\OrderSettings;
use MyParcelNL\Pdk\Shipment\Model\Shipment;

final class WcShipmentStatusWebhookService
{
    public const ORDER_MODE_SHIPMENTS = 0;
    public const ORDER_MODE_V1        = 1;
    public const ORDER_MODE_V2        = 2;

    private const ACCOUNT_FEATURES_SERVICE = 'MyParcelNL\Pdk\Account\Contract\AccountFeaturesServiceInterface';

    /**
     * @var \MyParcelNL\Pdk\App\Order\Contract\PdkOrderRepositoryInterface
     */
    private $pdkOrderRepository;

    /**
     * @param  \MyParcelNL\Pdk\App\Order\Contract\PdkOrderRepositoryInterface $pdkOrderRepository
     */
    public function __construct(PdkOrderRepositoryInterface $pdkOrderRepository)
    {
        $this->pdkOrderRepository = $pdkOrderRepository;
    }

    /**
     * @param  array $content
     *
     * @return void
     */
    public function handle(array $content): void
    {
        $orderModeVersion = $this->getOrderModeVersion();

        if (self::ORDER_MODE_V2 === $orderModeVersion) {
            $this->handleOrderV2($content);

            return;
        }

        $orderIds = self::ORDER_MODE_V1 === $orderModeVersion
            ? $this->getOrderIdsForOrderV1($content)
            : $this->getOrderIdsFromShipmentReference($content);

        if (empty($orderIds)) {
            $this->logSkippedWebhook('Skipping shipment status change webhook without a valid order identifier', $content);

            return;
        }

        $this->updateShipmentsFromApi($orderIds, $content);
    }

    /**
     * @return int
     */
    private function getOrderModeVersion(): int
    {
        $featuresService = Pdk::get(self::ACCOUNT_FEATURES_SERVICE);

        return (int) $featuresService->getOrderModeVersion();
    }

    /**
     * @param  array $content
     *
     * @return string[]
     */
    private function getOrderIdsForOrderV1(array $content): array
    {
        $apiIdentifier = $this->getTrimmedValue($content, 'order_id');

        if ('' === $apiIdentifier || ! method_exists($this->pdkOrderRepository, 'getByApiIdentifier')) {
            return [];
        }

        /** @var null|\MyParcelNL\Pdk\App\Order\Model\PdkOrder $order */
        $order = call_user_func([$this->pdkOrderRepository, 'getByApiIdentifier'], $apiIdentifier);

        if (! $order) {
            return [];
        }

        $externalIdentifier = $order->externalIdentifier;

        return $externalIdentifier ? [$externalIdentifier] : [];
    }

    /**
     * @param  array $content
     *
     * @return string[]
     */
    private function getOrderIdsFromShipmentReference(array $content): array
    {
        $orderId = $this->getTrimmedValue($content, 'shipment_reference_identifier');

        return '' === $orderId ? [] : [$orderId];
    }

    /**
     * @param  array $content
     *
     * @return void
     */
    private function handleOrderV2(array $content): void
    {
        $orderId = $this->getTrimmedValue($content, 'shipment_reference_identifier');

        if ('' === $orderId) {
            $this->logSkippedWebhook('Skipping order v2 shipment status webhook without a shipment reference identifier', $content);

            return;
        }

        $shipmentId = $this->getShipmentId($content);

        if (null === $shipmentId) {
            $this->logSkippedWebhook('Skipping order v2 shipment status webhook without a shipment id', $content);

            return;
        }

        $order = $this->pdkOrderRepository->find($orderId);

        if (! $order) {
            $this->logSkippedWebhook('Skipping order v2 shipment status webhook for unknown WooCommerce order', $content);

            return;
        }

        $shipment = $this->getShipmentFromOrder($order, $shipmentId);

        if (! $shipment) {
            $this->logSkippedWebhook('Skipping order v2 shipment status webhook for unknown shipment', $content);

            return;
        }

        $externalIdentifier = $order->externalIdentifier;

        if (! $externalIdentifier) {
            $this->logSkippedWebhook('Skipping order v2 shipment status webhook for an order without external identifier', $content);

            return;
        }

        $this->updateShipmentFromWebhook($shipment, $externalIdentifier, $content);
        $this->pdkOrderRepository->update($order);

        $this->updateOrderStatus([$externalIdentifier], $content);
    }

    /**
     * @param  \MyParcelNL\Pdk\App\Order\Model\PdkOrder $order
     * @param  int                                      $shipmentId
     *
     * @return null|\MyParcelNL\Pdk\Shipment\Model\Shipment
     */
    private function getShipmentFromOrder(PdkOrder $order, int $shipmentId): ?Shipment
    {
        return $order->shipments->first(function (Shipment $shipment) use ($shipmentId) {
            return (int) $shipment->id === $shipmentId;
        });
    }

    /**
     * @param  array $content
     * @param  string $key
     *
     * @return string
     */
    private function getTrimmedValue(array $content, string $key): string
    {
        return isset($content[$key]) ? trim((string) $content[$key]) : '';
    }

    /**
     * @param  array $content
     *
     * @return null|int
     */
    private function getShipmentId(array $content): ?int
    {
        if (! isset($content['shipment_id']) || '' === trim((string) $content['shipment_id'])) {
            return null;
        }

        return (int) $content['shipment_id'];
    }

    /**
     * @param  string[] $orderIds
     * @param  array    $content
     *
     * @return void
     */
    private function updateShipmentsFromApi(array $orderIds, array $content): void
    {
        Actions::execute(PdkBackendActions::UPDATE_SHIPMENTS, [
            'orderIds'                      => $orderIds,
            'shipmentIds'                   => [$content['shipment_id']],
            'orderStatus'                   => OrderSettings::getStatus((int) ($content['status'] ?? null)),
            'linkFirstShipmentToFirstOrder' => true,
        ]);
    }

    /**
     * @param  \MyParcelNL\Pdk\Shipment\Model\Shipment   $shipment
     * @param  string                                    $orderId
     * @param  array                                     $content
     *
     * @return void
     */
    private function updateShipmentFromWebhook(Shipment $shipment, string $orderId, array $content): void
    {
        $shipment->orderId = $orderId;

        if (array_key_exists('status', $content)) {
            $shipment->status = (int) $content['status'];
        }

        $barcode = $this->getTrimmedValue($content, 'barcode');

        if ('' !== $barcode) {
            $shipment->barcode = $barcode;
        }
    }

    /**
     * @param  string[] $orderIds
     * @param  array    $content
     *
     * @return void
     */
    private function updateOrderStatus(array $orderIds, array $content): void
    {
        Actions::execute(PdkBackendActions::UPDATE_ORDER_STATUS, [
            'orderIds' => $orderIds,
            'setting'  => OrderSettings::getStatus((int) ($content['status'] ?? null)),
        ]);
    }

    /**
     * @param  string $message
     * @param  array  $content
     *
     * @return void
     */
    private function logSkippedWebhook(string $message, array $content): void
    {
        Logger::debug($message, [
            'shipment_id'                   => $content['shipment_id'] ?? null,
            'order_id'                      => $content['order_id'] ?? null,
            'shipment_reference_identifier' => $content['shipment_reference_identifier'] ?? null,
        ]);
    }
}
