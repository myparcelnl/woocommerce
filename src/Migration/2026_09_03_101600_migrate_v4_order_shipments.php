<?php

declare(strict_types=1);

use MyParcelNL\Pdk\App\Installer\Migration\AbstractTimestampedMigration;
use MyParcelNL\Pdk\Carrier\Contract\CarrierRepositoryInterface;
use MyParcelNL\Pdk\Facade\Pdk;
use MyParcelNL\WooCommerce\Migration\Pdk\OrdersMigration;

/**
 * Converts shipments that a 4.x plugin left behind under `_myparcel_shipments`.
 *
 * OrdersMigration (5.0.0) already converts that data, but it only looks at orders from the last
 * three months and schedules the work as chunked WP-Cron. On a shop where those chunks never ran -
 * or that upgraded more than three months after its last export - the shipments stay under the 4.x
 * key, so the order grid shows those orders as never exported and their track & trace links are
 * gone.
 *
 * The conversion itself is not repeated here: the orders are handed to OrdersMigration, which owns
 * it. That method overwrites the current keys unconditionally, so only orders that have none of
 * them are passed in - an order that has been exported since the upgrade must never be rewritten
 * from its 4.x history.
 */
return new class extends AbstractTimestampedMigration {
    private const LEGACY_SHIPMENTS_KEY = '_myparcel_shipments';

    private const CURRENT_SHIPMENTS_KEY = '_myparcelcom_order_shipments';

    private const CURRENT_KEYS = [self::CURRENT_SHIPMENTS_KEY, '_myparcelcom_order_data'];

    /**
     * Bounds a single run. When more work remains the migration reports failure, which leaves it
     * unrecorded so the installer picks it up again on the next load and continues.
     */
    private const MAX_ORDERS_PER_RUN = 100;

    public function up(): void
    {
        if (! function_exists('wc_get_orders')) {
            return;
        }

        $orderIds = $this->getConvertibleOrderIds();

        if (empty($orderIds)) {
            return;
        }

        $batch = array_slice($orderIds, 0, self::MAX_ORDERS_PER_RUN);

        /** @var OrdersMigration $ordersMigration */
        $ordersMigration = Pdk::get(OrdersMigration::class);

        // One order at a time: the conversion writes the order itself, so finishing each one before
        // starting the next keeps at most a single order half-converted if the run is interrupted.
        foreach ($batch as $orderId) {
            $ordersMigration->migrateOrder([
                'orderIds'  => [$orderId],
                'chunk'     => 1,
                'lastChunk' => 1,
            ]);

            $this->resolveCarrierIds($orderId);
        }

        if (count($orderIds) > count($batch)) {
            $this->markFailed('More orders with 4.x shipments remain; continuing on the next run.', [
                'migratedThisRun' => count($batch),
                'remaining'       => count($orderIds) - count($batch),
            ]);
        }
    }

    /**
     * The conversion carries the 4.x carrier over as {"id": 1}, which nothing in the plugin
     * resolves - the order would still show no shipments. Turn it into the carrier name.
     *
     * Running this again on an already resolved order changes nothing.
     */
    private function resolveCarrierIds(int $orderId): void
    {
        $order = wc_get_order($orderId);

        if (! $order) {
            return;
        }

        $shipments = $order->get_meta(self::CURRENT_SHIPMENTS_KEY);

        if (! is_array($shipments) || empty($shipments)) {
            return;
        }

        /** @var CarrierRepositoryInterface $carrierRepository */
        $carrierRepository = Pdk::get(CarrierRepositoryInterface::class);
        $changed           = false;

        foreach ($shipments as $index => $shipment) {
            $legacyId = is_array($shipment) && is_array($shipment['carrier'] ?? null)
                ? ($shipment['carrier']['id'] ?? null)
                : null;

            if (! is_numeric($legacyId)) {
                continue;
            }

            $carrier = $carrierRepository->findByLegacyId((int) $legacyId);

            if (! $carrier) {
                continue;
            }

            $shipments[$index]['carrier'] = $carrier->carrier;
            $changed                      = true;
        }

        if ($changed) {
            $order->update_meta_data(self::CURRENT_SHIPMENTS_KEY, $shipments);
            $order->save();
        }
    }

    /**
     * Orders that still hold 4.x shipments and have no current data that would be overwritten.
     *
     * @return int[]
     */
    private function getConvertibleOrderIds(): array
    {
        $orders = wc_get_orders([
            'limit'      => -1,
            'status'     => 'any',
            'meta_query' => [
                [
                    'key'     => self::LEGACY_SHIPMENTS_KEY,
                    'compare' => 'EXISTS',
                ],
            ],
        ]);

        if (! is_array($orders)) {
            return [];
        }

        $orderIds = [];

        foreach ($orders as $order) {
            $order = is_object($order) && method_exists($order, 'get_id')
                ? $order
                : wc_get_order((int) $order);

            if (! $order || ! $order->get_meta(self::LEGACY_SHIPMENTS_KEY)) {
                continue;
            }

            if ($this->hasCurrentData($order)) {
                continue;
            }

            $orderIds[] = (int) $order->get_id();
        }

        return $orderIds;
    }

    /**
     * @param  \WC_Order $order
     */
    private function hasCurrentData($order): bool
    {
        foreach (self::CURRENT_KEYS as $key) {
            // Present but empty still counts: that data was cleared on purpose.
            if ($order->meta_exists($key)) {
                return true;
            }
        }

        return false;
    }
};
