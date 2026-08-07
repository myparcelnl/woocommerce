<?php

declare(strict_types=1);

use MyParcelNL\Pdk\App\Installer\Migration\AbstractTimestampedMigration;
use MyParcelNL\Pdk\App\Installer\Service\PagedMigrationService;
use MyParcelNL\Pdk\App\Options\Definition\NoTrackingDefinition;
use MyParcelNL\Pdk\Facade\Logger;
use MyParcelNL\Pdk\Facade\Pdk;
use MyParcelNL\Pdk\Settings\Contract\PdkSettingsRepositoryInterface;
use MyParcelNL\WooCommerce\Migration\NoTrackingChunkMigrator;

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
    public function up(): void
    {
        try {
            $this->convert();
        } catch (Throwable $exception) {
            // Report rather than throw, so a failure cannot leave the shop unable to finish upgrading.
            // Anything already converted stays converted, so the retry picks up where this run stopped.
            $this->markFailed('Could not convert stored tracking choices to no tracking.', [
                'exception' => $exception->getMessage(),
                'class'     => get_class($exception),
            ]);
        }
    }

    private function convert(): void
    {
        $this->convertCarrierSettings();

        // Always queued, whatever the carrier settings held. A shop can carry the option per product or
        // per order without ever having set it per carrier, so these passes cannot hang off that result.
        $this->scheduleProductSettings();
        $this->scheduleOrders();
    }

    /**
     * Carrier settings are one stored blob keyed by carrier, so this runs inline. Carriers without the
     * old key are left alone, and the old key is dropped once converted, so running this twice is a
     * no-op rather than a second flip.
     */
    private function convertCarrierSettings(): void
    {
        /** @var PdkSettingsRepositoryInterface $settingsRepository */
        $settingsRepository = Pdk::get(PdkSettingsRepositoryInterface::class);

        $settingsKey = Pdk::get('createSettingsKey')('carrier');
        $settings    = $settingsRepository->get($settingsKey);

        if (empty($settings) || ! is_array($settings)) {
            return;
        }

        $legacyKey = NoTrackingChunkMigrator::LEGACY_TRACKED_KEY;
        $newKey    = (new NoTrackingDefinition())->getCarrierSettingsKey();
        $converted = [];

        foreach ($settings as $carrier => $carrierSettings) {
            if (! is_array($carrierSettings) || ! array_key_exists($legacyKey, $carrierSettings)) {
                continue;
            }

            $carrierSettings[$newKey] = NoTrackingChunkMigrator::invert($carrierSettings[$legacyKey]);
            unset($carrierSettings[$legacyKey]);

            $settings[$carrier] = $carrierSettings;
            $converted[]        = $carrier;
        }

        if (! $converted) {
            return;
        }

        $settingsRepository->store($settingsKey, $settings);

        Logger::debug('Inverted the tracking option in carrier settings', ['carriers' => $converted]);
    }

    /**
     * Orders are found by the order data key alone, which is enough: PdkOrderRepository writes an order's
     * own options and its shipments in one update, so an order holding shipments holds order data too.
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
     * Product settings are stored per product, so converting every one inline would time out a large
     * shop. Each chunk is handed to a registered action instead.
     */
    private function scheduleProductSettings(): void
    {
        /** @var PagedMigrationService $pagedMigrationService */
        $pagedMigrationService = Pdk::get(PagedMigrationService::class);

        $pagedMigrationService->schedulePages(
            Pdk::get('migrateAction_NoTracking_ProductSettings'),
            static function (int $page, int $pageSize): array {
                return wc_get_products([
                    'limit'        => $pageSize,
                    'page'         => $page,
                    'meta_key'     => Pdk::get('metaKeyProductSettings'),
                    'meta_compare' => 'EXISTS',
                    'return'       => 'ids',
                ]);
            }
        );
    }
};
