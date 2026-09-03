<?php

declare(strict_types=1);

use MyParcelNL\Pdk\App\Installer\Migration\AbstractTimestampedMigration;
use MyParcelNL\Pdk\Carrier\Model\Carrier;
use MyParcelNL\Pdk\Facade\Logger;

/**
 * Moves order meta left behind under the pre-6.0.0 namespaces.
 *
 * Migration6_0_0 renamed `myparcelnl_`/`myparcelbe_` to `myparcelcom_` in wp_options only, so order
 * meta kept its old key. Orders exported before that upgrade have their shipments under
 * `_myparcelnl_order_shipments` while the plugin reads `_myparcelcom_order_shipments`: the order
 * grid shows them as never exported and their track & trace links are gone.
 *
 * The value is normalised before it is written, never afterwards, so an order is either fully
 * migrated or untouched - a half-migrated order would be skipped forever by the guard below.
 * Orders that already have the current key are left alone, including when its value is an empty
 * array: that means the shipments were deliberately removed and must not come back.
 *
 * Reading and writing go through the order, so both the post-meta and the HPOS store are covered.
 */
return new class extends AbstractTimestampedMigration {
    private const CURRENT_PREFIX = '_myparcelcom_';

    private const LEGACY_PREFIXES = ['_myparcelnl_', '_myparcelbe_'];

    /**
     * Only the keys whose contents this migration understands and can normalise.
     */
    private const META_KEYS = ['order_shipments', 'order_data'];

    /**
     * Bounds a single run. When more work remains the migration reports failure, which leaves it
     * unrecorded so the installer picks it up again on the next load and continues where it
     * stopped - the guard makes already migrated orders free to skip.
     */
    private const MAX_ORDERS_PER_RUN = 250;

    /**
     * @var int
     */
    private $migrated = 0;

    public function up(): void
    {
        foreach (self::LEGACY_PREFIXES as $legacyPrefix) {
            foreach (self::META_KEYS as $metaKey) {
                $done = $this->migrateKey($legacyPrefix . $metaKey, self::CURRENT_PREFIX . $metaKey);

                if (! $done) {
                    $this->markFailed('Legacy order meta remains; continuing on the next run.', [
                        'migratedThisRun' => $this->migrated,
                    ]);

                    return;
                }
            }
        }
    }

    /**
     * Walks the orders one page at a time. A migrated order gains the current key and therefore
     * drops out of the next query, so the same page size keeps moving forward without an offset.
     *
     * @return bool False when the run limit was reached before finishing this key.
     */
    private function migrateKey(string $legacyKey, string $currentKey): bool
    {
        while ($this->migrated < self::MAX_ORDERS_PER_RUN) {
            $orderIds = $this->getOrderIds($legacyKey, $currentKey);

            if (empty($orderIds)) {
                return true;
            }

            $migratedThisPage = 0;

            foreach ($orderIds as $orderId) {
                if ($this->migrated >= self::MAX_ORDERS_PER_RUN) {
                    return false;
                }

                if ($this->migrateOrder((int) $orderId, $legacyKey, $currentKey)) {
                    $this->migrated++;
                    $migratedThisPage++;
                }
            }

            // Nothing on this page could be migrated - its rows lack the current key but hold
            // no usable value, so they would be returned again forever. Leave them be.
            if (0 === $migratedThisPage) {
                return true;
            }
        }

        return false;
    }

    /**
     * One page of orders that still hold the legacy key and do not have the current one yet.
     * Excluding the current key in the query is what bounds the result set: without it every
     * already migrated order would be fetched again on each run.
     *
     * @return int[]
     */
    private function getOrderIds(string $legacyKey, string $currentKey): array
    {
        $orderIds = wc_get_orders([
            'limit'      => self::MAX_ORDERS_PER_RUN,
            'return'     => 'ids',
            'status'     => 'any',
            'orderby'    => 'ID',
            'order'      => 'ASC',
            'meta_query' => [
                'relation' => 'AND',
                [
                    'key'     => $legacyKey,
                    'compare' => 'EXISTS',
                ],
                [
                    'key'     => $currentKey,
                    'compare' => 'NOT EXISTS',
                ],
            ],
        ]);

        if (! is_array($orderIds)) {
            return [];
        }

        // 'return' => 'ids' yields ids, but not every data store honours it; accept orders too.
        return array_map(static function ($order): int {
            return is_object($order) && method_exists($order, 'get_id') ? (int) $order->get_id() : (int) $order;
        }, $orderIds);
    }

    private function migrateOrder(int $orderId, string $legacyKey, string $currentKey): bool
    {
        $order = wc_get_order($orderId);

        if (! $order instanceof WC_Order) {
            return false;
        }

        // Present but empty still counts as present: those shipments were removed on purpose.
        if ($order->meta_exists($currentKey)) {
            return false;
        }

        $value = $order->get_meta($legacyKey);

        if (! is_array($value) || empty($value)) {
            return false;
        }

        $order->update_meta_data($currentKey, $this->normalize($value, $currentKey));
        $order->save();

        Logger::debug('Migrated legacy order meta', [
            'orderId' => $orderId,
            'from'    => $legacyKey,
            'to'      => $currentKey,
        ]);

        return true;
    }

    /**
     * Pre-6.0.0 data holds the carrier as an object such as {"externalIdentifier": "postnl:1"}.
     * Migration6_5_1 converts those shapes, but it never saw these orders - their data was under
     * the old key when it ran - so the conversion happens here, before the value is stored.
     *
     * @param  array  $value
     * @param  string $currentKey
     *
     * @return array
     */
    private function normalize(array $value, string $currentKey): array
    {
        if (self::CURRENT_PREFIX . 'order_shipments' === $currentKey) {
            foreach ($value as $index => $shipment) {
                if (is_array($shipment)) {
                    $value[$index] = $this->normalizeCarriers($shipment);
                }
            }

            return $value;
        }

        return $this->normalizeCarriers($value);
    }

    /**
     * Normalises the carrier on the record itself and on its delivery options.
     *
     * @param  array $record
     *
     * @return array
     */
    private function normalizeCarriers(array $record): array
    {
        if (array_key_exists('carrier', $record)) {
            $name = $this->toCarrierName($record['carrier']);

            if (null !== $name) {
                $record['carrier'] = $name;
            }
        }

        if (isset($record['deliveryOptions']) && is_array($record['deliveryOptions'])
            && array_key_exists('carrier', $record['deliveryOptions'])) {
            $name = $this->toCarrierName($record['deliveryOptions']['carrier']);

            if (null !== $name) {
                $record['deliveryOptions']['carrier'] = $name;
            }
        }

        return $record;
    }

    /**
     * Accepts every shape the plugin has stored: {"externalIdentifier": "postnl:1"},
     * {"carrier": "postnl"}, {"id": 1}, "postnl:1" and "postnl". Returns the current identifier, or
     * null when there is nothing usable to convert.
     *
     * The numeric id is resolved through the static map rather than the carrier repository: that
     * repository reads the carriers stored on the account, which an account refresh can leave
     * empty, and a migration must not depend on it.
     *
     * @param  mixed $carrier
     *
     * @return null|string
     */
    private function toCarrierName($carrier): ?string
    {
        if (is_array($carrier)) {
            if (isset($carrier['id']) && is_numeric($carrier['id'])) {
                return Carrier::v2NameFromLegacyId((int) $carrier['id']);
            }

            $raw = $carrier['externalIdentifier'] ?? ($carrier['carrier'] ?? null);
        } else {
            $raw = $carrier;
        }

        if (! is_string($raw) || '' === $raw) {
            return null;
        }

        $legacyName = explode(':', $raw, 2)[0];

        return array_flip(Carrier::CARRIER_NAME_TO_LEGACY_MAP)[$legacyName] ?? $legacyName;
    }
};
