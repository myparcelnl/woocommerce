<?php

declare(strict_types=1);

use MyParcelNL\Pdk\App\Installer\Migration\AbstractTimestampedMigration;
use MyParcelNL\Pdk\App\Installer\Service\PagedMigrationService;
use MyParcelNL\Pdk\App\Options\Definition\NoTrackingDefinition;
use MyParcelNL\Pdk\Facade\Logger;
use MyParcelNL\Pdk\Facade\Pdk;
use MyParcelNL\Pdk\Settings\Contract\PdkSettingsRepositoryInterface;
use MyParcelNL\Pdk\Types\Service\TriStateService;

/**
 * Converts stored tracking choices to the inverted no tracking option.
 *
 * The tracked option was replaced by its inverse, so every stored value has to flip: a merchant who had
 * tracking switched on must end up with the opt-out switched off. Reading an old value under the new key
 * would mean the opposite of what they chose, which is why this cannot be left to a read-time fallback.
 *
 * Only the carrier settings are converted here, because they are a single stored record. Product
 * settings and stored order data are converted per record in scheduled chunks.
 */
return new class extends AbstractTimestampedMigration {
    /**
     * The key the option used to be stored under.
     *
     * A literal on purpose: NoTrackingDefinition replaced TrackedDefinition, so there is no class left to
     * derive the old key from. Migrations name the historical keys they read.
     */
    private const LEGACY_TRACKED_KEY = 'exportTracked';

    /**
     * Flip the option in the carrier settings, which are one stored blob keyed by carrier.
     *
     * Runs inline: there is a single record to rewrite, however many carriers it holds. Carriers without
     * the old key are left alone, and the old key is dropped once converted, so running this twice is a
     * no-op rather than a second flip.
     */
    public function up(): void
    {
        try {
            $this->convert();
        } catch (Throwable $exception) {
            // Report rather than throw, so a failure cannot leave the shop unable to finish upgrading.
            // Anything already converted stays converted: the old key is dropped as each record is
            // written, so the retry picks up where this run stopped.
            $this->markFailed('Could not convert stored tracking choices to no tracking.', [
                'exception' => $exception->getMessage(),
                'class'     => get_class($exception),
            ]);
        }
    }

    private function convert(): void
    {
        /** @var PdkSettingsRepositoryInterface $settingsRepository */
        $settingsRepository = Pdk::get(PdkSettingsRepositoryInterface::class);

        $settingsKey = Pdk::get('createSettingsKey')('carrier');
        $settings    = $settingsRepository->get($settingsKey);

        if (empty($settings) || ! is_array($settings)) {
            $this->scheduleProductSettings();
            $this->scheduleOrders();

            return;
        }

        $newKey    = (new NoTrackingDefinition())->getCarrierSettingsKey();
        $converted = [];

        foreach ($settings as $carrier => $carrierSettings) {
            if (! is_array($carrierSettings) || ! array_key_exists(self::LEGACY_TRACKED_KEY, $carrierSettings)) {
                continue;
            }

            $carrierSettings[$newKey] = $this->invert($carrierSettings[self::LEGACY_TRACKED_KEY]);
            unset($carrierSettings[self::LEGACY_TRACKED_KEY]);

            $settings[$carrier] = $carrierSettings;
            $converted[]        = $carrier;
        }

        if (! $converted) {
            return;
        }

        $settingsRepository->store($settingsKey, $settings);

        Logger::debug('Inverted the tracking option in carrier settings', ['carriers' => $converted]);

        $this->scheduleProductSettings();
        $this->scheduleOrders();
    }

    /**
     * Queue the per-order pass.
     *
     * An order holds the option in two places, its own delivery options and each shipment created from
     * it, and both are converted in the same chunk. Orders are found by the order data key alone, which
     * is enough: PdkOrderRepository writes both stores in one update, so an order holding shipments
     * holds order data too.
     */
    private function scheduleOrders(): void
    {
        /** @var PagedMigrationService $pagedMigrationService */
        $pagedMigrationService = Pdk::get(PagedMigrationService::class);

        $pagedMigrationService->schedulePages(
            Pdk::get('migrateAction_NoTracking_Orders'),
            static function (int $page, int $pageSize): array {
                return wc_get_orders([
                    'limit'        => $pageSize,
                    'paged'        => $page,
                    'meta_key'     => Pdk::get('metaKeyOrderData'),
                    'meta_compare' => 'EXISTS',
                    'return'       => 'ids',
                ]);
            }
        );
    }

    /**
     * Queue the per-product pass.
     *
     * Product settings are stored per product, so converting every one inline would time out a large
     * shop. Each chunk is handed to a registered action instead, which is why the work itself lives in
     * NoTrackingChunkMigrator rather than here.
     */
    private function scheduleProductSettings(): void
    {
        /** @var PagedMigrationService $pagedMigrationService */
        $pagedMigrationService = Pdk::get(PagedMigrationService::class);

        $pagedMigrationService->schedulePages(
            Pdk::get('migrateAction_NoTracking_ProductSettings'),
            static function (int $page, int $pageSize): array {
                return array_map(static function (WC_Product $product): int {
                    return $product->get_id();
                }, wc_get_products([
                    'limit'        => $pageSize,
                    'page'         => $page,
                    'meta_key'     => Pdk::get('metaKeyProductSettings'),
                    'meta_compare' => 'EXISTS',
                    'return'       => 'objects',
                ]));
            }
        );
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
};
