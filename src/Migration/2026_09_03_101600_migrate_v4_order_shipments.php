<?php

declare(strict_types=1);

use MyParcelNL\Pdk\App\Installer\Migration\AbstractTimestampedMigration;
use MyParcelNL\Pdk\Carrier\Model\Carrier;
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

        /** @var OrdersMigration $ordersMigration */
        $ordersMigration = Pdk::get(OrdersMigration::class);
        $converted       = 0;

        // One order at a time: the conversion writes the order itself, so finishing each one before
        // starting the next keeps at most a single order half-converted if the run is interrupted.
        foreach ($orderIds as $orderId) {
            $order = wc_get_order($orderId);

            // An empty legacy value holds nothing to convert. Skipping leaves the order in the
            // query, which is why a run that converts nothing must not ask for another one.
            if (! $order || ! $order->get_meta(self::LEGACY_SHIPMENTS_KEY)) {
                continue;
            }

            $ordersMigration->migrateOrder([
                'orderIds'  => [$orderId],
                'chunk'     => 1,
                'lastChunk' => 1,
            ]);

            $this->resolveCarrierIds($orderId);
            $converted++;
        }

        if ($converted > 0 && count($orderIds) >= self::MAX_ORDERS_PER_RUN) {
            $this->markFailed('More orders with 4.x shipments remain; continuing on the next run.', [
                'migratedThisRun' => $converted,
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

        $changed = false;

        foreach ($shipments as $index => $shipment) {
            $legacyId = is_array($shipment) && is_array($shipment['carrier'] ?? null)
                ? ($shipment['carrier']['id'] ?? null)
                : null;

            if (! is_numeric($legacyId)) {
                continue;
            }

            // The static map, not the carrier repository: that repository reads the carriers stored
            // on the account, which an account refresh can leave empty, and a migration must not
            // depend on it.
            $carrierName = Carrier::v2NameFromLegacyId((int) $legacyId);

            if (! $carrierName) {
                continue;
            }

            $shipments[$index]['carrier'] = $carrierName;
            $changed                      = true;
        }

        if ($changed) {
            $order->update_meta_data(self::CURRENT_SHIPMENTS_KEY, $shipments);
            $order->save();
        }
    }

    /**
     * One page of orders that still hold 4.x shipments and have no current data that would be
     * overwritten. Both conditions live in the query: excluding the current keys is what keeps the
     * result set shrinking as orders are converted, and it avoids loading every order into memory.
     *
     * A present but empty current key counts as data - it was cleared on purpose - which is exactly
     * what NOT EXISTS expresses.
     *
     * @return int[]
     */
    private function getConvertibleOrderIds(): array
    {
        $metaQuery = [
            'relation' => 'AND',
            [
                'key'     => self::LEGACY_SHIPMENTS_KEY,
                'compare' => 'EXISTS',
            ],
        ];

        foreach (self::CURRENT_KEYS as $currentKey) {
            $metaQuery[] = [
                'key'     => $currentKey,
                'compare' => 'NOT EXISTS',
            ];
        }

        $orderIds = wc_get_orders([
            'limit'      => self::MAX_ORDERS_PER_RUN,
            'return'     => 'ids',
            'status'     => 'any',
            'orderby'    => 'ID',
            'order'      => 'ASC',
            'meta_query' => $metaQuery,
        ]);

        if (! is_array($orderIds)) {
            return [];
        }

        // 'return' => 'ids' yields ids, but not every data store honours it; accept orders too.
        return array_map(static function ($order): int {
            return is_object($order) && method_exists($order, 'get_id') ? (int) $order->get_id() : (int) $order;
        }, $orderIds);
    }
};
