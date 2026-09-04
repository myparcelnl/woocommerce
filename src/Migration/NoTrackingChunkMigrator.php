<?php

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Migration;

use MyParcelNL\Pdk\App\Options\Definition\NoTrackingDefinition;
use MyParcelNL\Pdk\App\Order\Contract\PdkProductRepositoryInterface;
use MyParcelNL\Pdk\Base\Contract\CronServiceInterface;
use MyParcelNL\Pdk\Facade\Logger;
use MyParcelNL\Pdk\Facade\Pdk;
use MyParcelNL\Pdk\Settings\Contract\PdkSettingsRepositoryInterface;
use MyParcelNL\Pdk\Types\Service\TriStateService;
use Throwable;
use WC_Product;

/**
 * Converts the tracking option on stored records, one page per scheduled run.
 *
 * This class exists because a scheduled action has to resolve to something addressable in a later
 * request. A timestamped migration is an anonymous class, so it can schedule work but can never be the
 * callback for it. So the queries live here too, not in the migration: a run has to find its own work
 * to be able to resume.
 *
 * Each run books its successor before it converts anything, and drops that successor once its query
 * comes back empty. A run killed by a timeout or a fatal therefore still has a successor waiting, and
 * that successor re-runs the query rather than a frozen list of ids, so it picks up whatever is left.
 * Mirrors WCS_Background_Updater, which reschedules first for the same reason.
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
     * How many records one run converts, and how long the next run waits. The delay keeps a shop from
     * spending every request on the migration, at the cost of taking longer to finish.
     */
    private const PAGE_SIZE            = 100;
    private const SECONDS_BETWEEN_RUNS = 60;

    /**
     * Where the order pass keeps its place. Products need no cursor: a converted product no longer
     * matches its query, so every run asks for the first page again. Orders keep their order data key
     * whatever the option holds, so that pass has to remember how far it got.
     */
    public const CURSOR_ORDERS = 'noTrackingMigrationOrderPage';

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

    /**
     * @var \MyParcelNL\Pdk\Base\Contract\CronServiceInterface
     */
    private $cronService;

    /**
     * @var \MyParcelNL\Pdk\Settings\Contract\PdkSettingsRepositoryInterface
     */
    private $settingsRepository;

    public function __construct(
        PdkProductRepositoryInterface   $productRepository,
        CronServiceInterface            $cronService,
        PdkSettingsRepositoryInterface  $settingsRepository
    ) {
        $this->productRepository  = $productRepository;
        $this->cronService        = $cronService;
        $this->settingsRepository = $settingsRepository;

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
     * Cron callback: convert one page of products, and leave a successor booked until none are left.
     */
    public function migrateProductSettingsChunk(): void
    {
        $cronAction = Pdk::get('migrateAction_NoTracking_ProductSettings');

        $this->scheduleNextRun($cronAction);

        $ids = $this->findProducts();

        if (empty($ids)) {
            $this->stopPass($cronAction, 'product');

            return;
        }

        $this->convertPage($ids, 'product', function (int $id): bool {
            return $this->migrateProduct($id);
        });
    }

    /**
     * Cron callback: convert one page of orders, and leave a successor booked until none are left.
     */
    public function migrateOrderChunk(): void
    {
        $cronAction = Pdk::get('migrateAction_NoTracking_Orders');

        $this->scheduleNextRun($cronAction);

        $page = (int) ($this->settingsRepository->get(self::CURSOR_ORDERS) ?: 1);
        $ids  = $this->findOrders($page);

        if (empty($ids)) {
            $this->stopPass($cronAction, 'order');
            $this->settingsRepository->store(self::CURSOR_ORDERS, null);

            return;
        }

        $this->convertPage($ids, 'order', function (int $id): bool {
            return $this->migrateOrder($id);
        });

        // Advanced only now the page is converted. A run killed while converting repeats this page on
        // the next attempt, rather than stepping over it.
        $this->settingsRepository->store(self::CURSOR_ORDERS, $page + 1);
    }

    /**
     * Drop the successor booked at the start of the run, which ends the chain.
     */
    private function stopPass(string $cronAction, string $type): void
    {
        wp_unschedule_hook($cronAction);

        Logger::debug('No records left to convert', [
            'migration' => self::class,
            'type'      => $type,
        ]);
    }

    /**
     * A record that cannot be converted is skipped and named in the log, rather than allowed to take
     * the rest of its page with it.
     */
    private function convertPage(array $ids, string $type, callable $convert): void
    {
        $converted = 0;
        $failed    = 0;

        foreach ($ids as $id) {
            try {
                if ($convert((int) $id)) {
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

        Logger::debug('Converted the tracking option on a page of records', [
            'migration' => self::class,
            'type'      => $type,
            'records'   => count($ids),
            'converted' => $converted,
            'failed'    => $failed,
        ]);
    }

    /**
     * Losing the successor means the records left over wait for the next upgrade run instead of the
     * next minute, so it is reported rather than passed over.
     */
    private function scheduleNextRun(string $cronAction): void
    {
        try {
            $this->cronService->schedule($cronAction, time() + self::SECONDS_BETWEEN_RUNS);
        } catch (Throwable $exception) {
            Logger::error('Could not book the next run of the no tracking migration', [
                'migration' => self::class,
                'action'    => $cronAction,
                'exception' => $exception->getMessage(),
                'class'     => get_class($exception),
            ]);
        }
    }

    /**
     * Products that still hold the old key. Converting drops it, so a converted product falls out of
     * this query and the first page is always the work that is left.
     */
    private function findProducts(): array
    {
        return wc_get_products([
            'limit'        => self::PAGE_SIZE,
            'page'         => 1,
            'meta_key'     => Pdk::get('metaKeyProductSettings'),
            'meta_value'   => sprintf('"%s"', self::LEGACY_TRACKED_KEY),
            'meta_compare' => 'LIKE',
            'return'       => 'ids',
        ]);
    }

    /**
     * One page of orders holding stored order data.
     *
     * The query cannot narrow to the old key the way the product one does: an order keeps its order
     * data whatever the option holds, and the key can sit on a stored shipment while the order's own
     * options no longer have it. So this pass walks every order and remembers its page instead.
     */
    private function findOrders(int $page): array
    {
        return wc_get_orders([
            'limit'        => self::PAGE_SIZE,
            'paged'        => $page,
            'meta_key'     => Pdk::get('metaKeyOrderData'),
            'meta_compare' => 'EXISTS',
            'return'       => 'ids',
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
