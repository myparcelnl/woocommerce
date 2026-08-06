<?php

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Migration;

use MyParcelNL\Pdk\App\Options\Definition\NoTrackingDefinition;
use MyParcelNL\Pdk\App\Order\Contract\PdkProductRepositoryInterface;
use MyParcelNL\Pdk\Facade\Logger;
use MyParcelNL\Pdk\Facade\Pdk;
use MyParcelNL\Pdk\Types\Service\TriStateService;
use Throwable;

/**
 * Flips the tracking option on records that are migrated in scheduled chunks.
 *
 * Carrier settings are one record and are converted by the migration itself. Product settings are stored
 * per product, so converting them all inline would time out a large shop. The migration schedules chunks
 * instead, and each chunk is dispatched back into this class.
 *
 * This class exists because a scheduled action has to resolve to something addressable in a later
 * request. A timestamped migration is an anonymous class, so it can schedule work but can never be the
 * callback for it.
 */
class NoTrackingChunkMigrator
{
    /**
     * The key the option used to be stored under.
     *
     * A literal on purpose: NoTrackingDefinition replaced TrackedDefinition, so there is no class left to
     * derive the old key from. Migrations name the historical keys they read.
     */
    public const LEGACY_TRACKED_KEY = 'exportTracked';

    /**
     * The key the option used to be stored under on a shipment's options, as opposed to on a settings
     * record. Also a literal, for the same reason.
     */
    public const LEGACY_SHIPMENT_OPTION_KEY = 'tracked';

    /**
     * @var \MyParcelNL\Pdk\App\Order\Contract\PdkProductRepositoryInterface
     */
    private $productRepository;

    public function __construct(PdkProductRepositoryInterface $productRepository)
    {
        $this->productRepository = $productRepository;
    }

    /**
     * Cron callback: flip the option on one chunk of products.
     *
     * Products without a stored choice are skipped, and the old key disappears because saving rewrites
     * the whole settings record from the model, which no longer knows that key. That makes a second run
     * over the same product a no-op rather than a second flip.
     *
     * @param  array $data Chunk context as scheduled: ids under "ids", chunk number under "chunk"
     */
    public function migrateProductSettingsChunk(array $data): void
    {
        $ids = $data['ids'] ?? [];

        if (empty($ids)) {
            return;
        }

        $converted = 0;
        $failed    = 0;

        foreach ($ids as $productId) {
            try {
                if ($this->migrateProduct((int) $productId)) {
                    $converted++;
                }
            } catch (Throwable $exception) {
                $failed++;
                $this->logRecordFailure('product', (int) $productId, $exception);
            }
        }

        Logger::debug('Converted the tracking option on a chunk of products', [
            'migration' => self::class,
            'chunk'     => $data['chunk'] ?? null,
            'products'  => count($ids),
            'converted' => $converted,
            'failed'    => $failed,
        ]);
    }

    /**
     * Cron callback: flip the option on everything one chunk of orders stores.
     *
     * An order holds the option twice: once as the choice made for the order, which still drives a
     * re-export, and once per shipment created from it. Both are handled in the same pass because
     * PdkOrderRepository writes them together in one update, so any order holding one holds the other,
     * and loading and saving each order once instead of twice halves the work.
     *
     * Converting the stored shipments is not rewriting history. Those records round-trip through the PDK
     * models — written with toStorableArray(), read back into a ShipmentCollection — and the models no
     * longer know the old key, so leaving it would drop it on the next read and erase it on the next
     * save. Flipping it states the same fact in the vocabulary the code now uses.
     *
     * @param  array $data Chunk context as scheduled: ids under "ids", chunk number under "chunk"
     */
    public function migrateOrderChunk(array $data): void
    {
        $ids = $data['ids'] ?? [];

        $converted = 0;
        $failed    = 0;

        foreach ($ids as $orderId) {
            try {
                if ($this->migrateOrder((int) $orderId)) {
                    $converted++;
                }
            } catch (Throwable $exception) {
                $failed++;
                $this->logRecordFailure('order', (int) $orderId, $exception);
            }
        }

        Logger::debug('Converted the tracking option on a chunk of orders', [
            'migration' => self::class,
            'chunk'     => $data['chunk'] ?? null,
            'orders'    => count($ids),
            'converted' => $converted,
            'failed'    => $failed,
        ]);
    }

    /**
     * @return bool Whether the order held an old value that was converted
     */
    private function migrateOrder(int $orderId): bool
    {
        $order = wc_get_order($orderId);

        if (! $order) {
            return false;
        }

        $orderDataKey = Pdk::get('metaKeyOrderData');
        $shipmentsKey = Pdk::get('metaKeyOrderShipments');

        $converted = false;

        $orderData = $order->get_meta($orderDataKey);

        if (is_array($orderData) && $this->invertShipmentOption($orderData)) {
            $order->update_meta_data($orderDataKey, $orderData);
            $converted = true;
        }

        $shipments = $order->get_meta($shipmentsKey);

        if (is_array($shipments) && $this->invertShipments($shipments)) {
            $order->update_meta_data($shipmentsKey, $shipments);
            $converted = true;
        }

        // One save for both stores, mirroring how the repository writes them.
        if ($converted) {
            $order->save();
        }

        return $converted;
    }

    /**
     * A chunk is scheduled once and never retried, so a record that cannot be converted is skipped and
     * named in the log rather than allowed to take the rest of its batch with it.
     */
    private function logRecordFailure(string $type, int $id, Throwable $exception): void
    {
        Logger::error('Could not convert the tracking option on a record', [
            'migration' => self::class,
            'type'      => $type,
            'id'        => $id,
            'exception' => $exception->getMessage(),
            'class'     => get_class($exception),
        ]);
    }

    /**
     * Flip the option on each stored shipment of an order, in place.
     *
     * @param  array $shipments Modified in place where a shipment held an old value
     *
     * @return bool Whether any shipment was converted
     */
    private function invertShipments(array &$shipments): bool
    {
        $converted = false;

        foreach ($shipments as &$shipment) {
            if (is_array($shipment) && $this->invertShipmentOption($shipment)) {
                $converted = true;
            }
        }
        unset($shipment);

        return $converted;
    }

    /**
     * Flip the option inside a record holding delivery options, in place.
     *
     * Both stored order data and each stored shipment nest the options the same way, because a Shipment
     * carries DeliveryOptions which carries ShipmentOptions.
     *
     * @param  array $record Modified in place when it held an old value
     *
     * @return bool Whether anything was converted
     */
    private function invertShipmentOption(array &$record): bool
    {
        $options = $record['deliveryOptions']['shipmentOptions'] ?? null;

        if (! is_array($options) || ! array_key_exists(self::LEGACY_SHIPMENT_OPTION_KEY, $options)) {
            return false;
        }

        $newKey = (new NoTrackingDefinition())->getShipmentOptionsKey();

        $options[$newKey] = $this->invert($options[self::LEGACY_SHIPMENT_OPTION_KEY]);
        unset($options[self::LEGACY_SHIPMENT_OPTION_KEY]);

        $record['deliveryOptions']['shipmentOptions'] = $options;

        return true;
    }

    /**
     * @return bool Whether the product held an old value that was converted
     */
    private function migrateProduct(int $productId): bool
    {
        $wcProduct = wc_get_product($productId);

        if (! $wcProduct) {
            return false;
        }

        // Read the raw meta rather than the settings model: the model's attributes come from the option
        // definitions, which no longer include the old key, so it cannot report the stored value.
        $stored = $wcProduct->get_meta(Pdk::get('metaKeyProductSettings'));

        if (! is_array($stored) || ! array_key_exists(self::LEGACY_TRACKED_KEY, $stored)) {
            return false;
        }

        $product = $this->productRepository->getProduct($productId);

        $product->settings->setAttribute(
            (new NoTrackingDefinition())->getProductSettingsKey(),
            $this->invert($stored[self::LEGACY_TRACKED_KEY])
        );

        $this->productRepository->update($product);

        return true;
    }

    /**
     * Flip an explicit choice, leaving "not set" alone.
     *
     * Inherit means the merchant never chose, so inverting it would invent a preference. Values are cast
     * because older stored settings hold them as strings.
     *
     * @param  mixed $value
     */
    private function invert($value): int
    {
        switch ((int) $value) {
            case TriStateService::ENABLED:
                return TriStateService::DISABLED;
            case TriStateService::DISABLED:
                return TriStateService::ENABLED;
            default:
                return TriStateService::INHERIT;
        }
    }
}
