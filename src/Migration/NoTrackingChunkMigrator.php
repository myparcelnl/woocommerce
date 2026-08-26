<?php

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Migration;

use MyParcelNL\Pdk\App\Options\Definition\NoTrackingDefinition;
use MyParcelNL\Pdk\App\Order\Contract\PdkProductRepositoryInterface;
use MyParcelNL\Pdk\Facade\Logger;
use MyParcelNL\Pdk\Facade\Pdk;
use MyParcelNL\Pdk\Types\Service\TriStateService;
use Throwable;
use WC_Product;

/**
 * Flips the tracking option on the records the migration converts in scheduled chunks.
 *
 * This class exists because a scheduled action has to resolve to something addressable in a later
 * request. A timestamped migration is an anonymous class, so it can schedule work but can never be the
 * callback for it. It also holds the pieces both halves need: the historical keys and the flip itself.
 */
class NoTrackingChunkMigrator
{
    /**
     * The keys the option used to be stored under, on a settings record and on a shipment's options.
     *
     * Literals on purpose: NoTrackingDefinition replaced TrackedDefinition, so there is no class left to
     * derive them from. Migrations name the historical keys they read.
     */
    public const LEGACY_TRACKED_KEY         = 'exportTracked';
    public const LEGACY_SHIPMENT_OPTION_KEY = 'tracked';

    /**
     * @var \MyParcelNL\Pdk\App\Order\Contract\PdkProductRepositoryInterface
     */
    private $productRepository;

    /**
     * @var string
     */
    private $shipmentOptionKey;

    /**
     * @var string
     */
    private $productSettingsKey;

    public function __construct(PdkProductRepositoryInterface $productRepository)
    {
        $this->productRepository = $productRepository;

        // Resolved once per chunk: the definition is stateless and a chunk touches many records.
        $definition               = new NoTrackingDefinition();
        $this->shipmentOptionKey  = $definition->getShipmentOptionsKey();
        $this->productSettingsKey = $definition->getProductSettingsKey();
    }

    /**
     * Flip an explicit choice, leaving "not set" alone.
     *
     * Inherit means the merchant never chose, so inverting it would invent a preference.
     *
     * @param  mixed $value Cast, because older stored settings hold these as strings
     */
    public static function invert($value): int
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

    /**
     * Cron callback: flip the option on one chunk of products.
     *
     * @param  array $data Chunk context as scheduled: ids under "ids", chunk number under "chunk"
     */
    public function migrateProductSettingsChunk(array $data): void
    {
        $this->migrateChunk($data, 'product', function (int $id): bool {
            return $this->migrateProduct($id);
        });
    }

    /**
     * Cron callback: flip the option on everything one chunk of orders stores.
     *
     * @param  array $data Chunk context as scheduled: ids under "ids", chunk number under "chunk"
     */
    public function migrateOrderChunk(array $data): void
    {
        $this->migrateChunk($data, 'order', function (int $id): bool {
            return $this->migrateOrder($id);
        });
    }

    /**
     * Convert one chunk, one record at a time.
     *
     * A chunk is scheduled once and never retried, so a record that cannot be converted is skipped and
     * named in the log rather than allowed to take the rest of its batch with it.
     */
    private function migrateChunk(array $data, string $type, callable $migrate): void
    {
        $ids       = $data['ids'] ?? [];
        $converted = 0;
        $failed    = 0;

        foreach ($ids as $id) {
            try {
                if ($migrate((int) $id)) {
                    $converted++;
                }
            } catch (Throwable $exception) {
                $failed++;

                Logger::error('Could not convert the tracking option on a record', [
                    'migration' => self::class,
                    'type'      => $type,
                    'id'        => (int) $id,
                    'exception' => $exception->getMessage(),
                    'class'     => get_class($exception),
                ]);
            }
        }

        Logger::debug('Converted the tracking option on a chunk of records', [
            'migration' => self::class,
            'type'      => $type,
            'chunk'     => $data['chunk'] ?? null,
            'records'   => count($ids),
            'converted' => $converted,
            'failed'    => $failed,
        ]);
    }

    /**
     * An order holds the option twice: once as the choice made for the order, which still drives a
     * re-export, and once per shipment created from it. Both are converted in one load and one save,
     * mirroring how PdkOrderRepository writes them.
     *
     * Converting the stored shipments is not rewriting history. Those records round-trip through the PDK
     * models, which no longer know the old key, so leaving it would drop it on the next read anyway.
     *
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

        if ($converted) {
            $order->save();
        }

        return $converted;
    }

    /**
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

        $options[$this->shipmentOptionKey] = self::invert($options[self::LEGACY_SHIPMENT_OPTION_KEY]);
        unset($options[self::LEGACY_SHIPMENT_OPTION_KEY]);

        $record['deliveryOptions']['shipmentOptions'] = $options;

        return true;
    }

    /**
     * Converts a product and every variation under it.
     *
     * A variation holds its own product settings, and a page of products holds the parents only:
     * wc_get_products() cannot return variations, because 'variation' is not one of the product types
     * it documents. ProductSettingsMigration reaches them the same way.
     *
     * @return bool Whether the product or any of its variations held an old value that was converted
     */
    private function migrateProduct(int $productId): bool
    {
        $wcProduct = wc_get_product($productId);

        if (! $wcProduct) {
            return false;
        }

        $converted = $this->convertStoredSettings($wcProduct);

        foreach ($wcProduct->get_children() as $childId) {
            $child = wc_get_product($childId);

            if ($child) {
                $converted = $this->convertStoredSettings($child) || $converted;
            }
        }

        return $converted;
    }

    /**
     * Saving rewrites the whole settings record from the model, which no longer knows the old key, so a
     * second run over the same record is a no-op rather than a second flip.
     *
     * @return bool Whether the record held an old value that was converted
     */
    private function convertStoredSettings(WC_Product $wcProduct): bool
    {
        // Read the raw meta rather than the settings model: the model's attributes come from the option
        // definitions, which no longer include the old key, so it cannot report the stored value.
        $stored = $wcProduct->get_meta(Pdk::get('metaKeyProductSettings'));

        if (! is_array($stored) || ! array_key_exists(self::LEGACY_TRACKED_KEY, $stored)) {
            return false;
        }

        $product = $this->productRepository->getProduct($wcProduct->get_id());

        $product->settings->setAttribute(
            $this->productSettingsKey,
            self::invert($stored[self::LEGACY_TRACKED_KEY])
        );

        $this->productRepository->update($product);

        return true;
    }
}
