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

    private const PAGE_SIZE = 50;

    /**
     * Bounds both reads and writes to at most five pages per request. Counting pages instead of
     * successful migrations also bounds a shop that has many empty or malformed legacy values.
     */
    private const MAX_PAGES_PER_RUN = 5;

    /**
     * Remembers the page each key pair reached, so a resumed run does not walk the orders it
     * already handled. Orders keep their legacy key after migrating, which keeps the result set -
     * and therefore the paging - stable between runs.
     */
    private const CURSOR_OPTION = '_myparcelcom_migrate_legacy_order_meta_cursor';

    /**
     * @var int
     */
    private $migrated = 0;

    /**
     * @var int
     */
    private $scanned = 0;

    /**
     * @var int
     */
    private $pagesScanned = 0;

    public function up(): void
    {
        if (! function_exists('wc_get_orders')) {
            return;
        }

        foreach (self::LEGACY_PREFIXES as $legacyPrefix) {
            foreach (self::META_KEYS as $metaKey) {
                $done = $this->migrateKey($legacyPrefix . $metaKey, self::CURRENT_PREFIX . $metaKey);

                if (! $done) {
                    $this->markFailed('Legacy order meta remains; continuing on the next run.', [
                        'migratedThisRun' => $this->migrated,
                        'scannedThisRun'  => $this->scanned,
                    ]);

                    return;
                }
            }
        }

        $this->clearCursors();
    }

    /**
     * Walks the orders holding this legacy key page by page. The page always advances, so orders
     * that cannot be migrated - an empty or malformed legacy value - never block the ones behind
     * them. Scanned pages count towards the run limit, whether their orders migrate or not.
     *
     * @return bool False when the run limit was reached before finishing this key.
     */
    private function migrateKey(string $legacyKey, string $currentKey): bool
    {
        $page = $this->getCursor($legacyKey);

        while (true) {
            $orderIds = $this->getOrderIds($legacyKey, $page);

            if (empty($orderIds)) {
                // Remember where this key ran out, so a later run resumes at the end instead of
                // walking every page it already finished.
                $this->setCursor($legacyKey, $page);

                return true;
            }

            foreach ($orderIds as $orderId) {
                if ($this->migrateOrder($orderId, $legacyKey, $currentKey)) {
                    $this->migrated++;
                }
            }

            $page++;
            $this->pagesScanned++;
            $this->scanned += count($orderIds);
            $this->setCursor($legacyKey, $page);

            if ($this->pagesScanned >= self::MAX_PAGES_PER_RUN) {
                return false;
            }
        }
    }

    /**
     * One page of orders that hold the legacy key.
     *
     * Deliberately uses the flat meta_key/meta_compare arguments instead of a meta_query: the
     * legacy post-storage data store passes these through to WP_Query, while a meta_query is
     * silently ignored there and only honoured by HPOS. A meta_query combined with a limit would
     * therefore return the first N orders of the whole shop on a non-HPOS install.
     *
     * Already migrated orders stay in the result set - the legacy key is never removed - and are
     * skipped by the guard in migrateOrder().
     *
     * @return int[]
     */
    private function getOrderIds(string $legacyKey, int $page): array
    {
        $orderIds = wc_get_orders([
            'limit'        => self::PAGE_SIZE,
            'paged'        => $page,
            'return'       => 'ids',
            'status'       => 'any',
            'orderby'      => 'ID',
            'order'        => 'ASC',
            'meta_key'     => $legacyKey,
            'meta_compare' => 'EXISTS',
        ]);

        if (! is_array($orderIds)) {
            return [];
        }

        // 'return' => 'ids' yields ids, but not every data store honours it; accept orders too.
        // WooCommerce types the result as WC_Order[], hence the scalar check rather than a cast.
        $ids = array_map(static function ($order): int {
            // @phpstan-ignore function.impossibleType
            return (int) (is_scalar($order) ? $order : $order->get_id());
        }, $orderIds);

        return array_values(array_filter($ids));
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

        $normalized = $this->normalize($value, $currentKey);

        if (null === $normalized) {
            Logger::warning('Skipped legacy order meta with an unsupported carrier.', [
                'orderId' => $orderId,
                'from'    => $legacyKey,
            ]);

            return false;
        }

        $order->update_meta_data($currentKey, $normalized);
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
     * @return null|array Null when at least one stored carrier cannot be safely normalised.
     */
    private function normalize(array $value, string $currentKey): ?array
    {
        if (self::CURRENT_PREFIX . 'order_shipments' === $currentKey) {
            foreach ($value as $index => $shipment) {
                if (is_array($shipment)) {
                    $normalized = $this->normalizeCarriers($shipment);

                    if (null === $normalized) {
                        return null;
                    }

                    $value[$index] = $normalized;
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
     * @return null|array Null when a stored carrier cannot be safely normalised.
     */
    private function normalizeCarriers(array $record): ?array
    {
        $record = $this->normalizeCarrierOn($record);

        if (null === $record) {
            return null;
        }

        if (isset($record['deliveryOptions']) && is_array($record['deliveryOptions'])) {
            $record['deliveryOptions'] = $this->normalizeCarrierOn($record['deliveryOptions']);

            if (null === $record['deliveryOptions']) {
                return null;
            }
        }

        return $record;
    }

    /**
     * Replaces the carrier with its current identifier and keeps the contract that was encoded in
     * the legacy value: "postnl:42" carries contract 42, which is a separate attribute today and
     * would otherwise be lost. An existing contractId always wins.
     *
     * @param  array $record
     *
     * @return null|array Null when a non-empty carrier value cannot be safely normalised.
     */
    private function normalizeCarrierOn(array $record): ?array
    {
        if (! array_key_exists('carrier', $record)) {
            return $record;
        }

        $carrier = $record['carrier'];

        // A missing carrier intentionally falls back to the shop default at runtime.
        if (null === $carrier || '' === $carrier || [] === $carrier) {
            return $record;
        }

        $name = $this->toCarrierName($carrier);

        if (null === $name || ! Carrier::isSupported($name)) {
            return null;
        }

        $contractId = $this->toContractId($carrier);

        if (null !== $contractId && ! isset($record['contractId'])) {
            $record['contractId'] = $contractId;
        }

        $record['carrier'] = $name;

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

    /**
     * The contract encoded behind the colon in a legacy identifier, or on the carrier object.
     *
     * @param  mixed $carrier
     *
     * @return null|int
     */
    private function toContractId($carrier): ?int
    {
        if (is_array($carrier)) {
            $storedContractId = $carrier['contractId'] ?? ($carrier['contract_id'] ?? null);

            if (is_numeric($storedContractId)) {
                return (int) $storedContractId;
            }

            $raw = $carrier['externalIdentifier'] ?? ($carrier['carrier'] ?? null);
        } else {
            $raw = $carrier;
        }

        if (! is_string($raw)) {
            return null;
        }

        $parts = explode(':', $raw, 2);

        return isset($parts[1]) && is_numeric($parts[1]) ? (int) $parts[1] : null;
    }

    private function getCursor(string $legacyKey): int
    {
        $cursors = get_option(self::CURSOR_OPTION, []);

        return is_array($cursors) ? max(1, (int) ($cursors[$legacyKey] ?? 1)) : 1;
    }

    private function setCursor(string $legacyKey, int $page): void
    {
        $cursors = get_option(self::CURSOR_OPTION, []);

        if (! is_array($cursors)) {
            $cursors = [];
        }

        $cursors[$legacyKey] = $page;

        update_option(self::CURSOR_OPTION, $cursors);
    }

    private function clearCursors(): void
    {
        update_option(self::CURSOR_OPTION, []);
    }
};
