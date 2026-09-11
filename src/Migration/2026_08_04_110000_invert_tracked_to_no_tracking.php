<?php

declare(strict_types=1);

use MyParcelNL\Pdk\App\Installer\Migration\AbstractTimestampedMigration;
use MyParcelNL\Pdk\Base\Contract\CronServiceInterface;
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
 * settings and stored order data are converted a page at a time by a scheduled pass that keeps
 * itself going until nothing is left.
 *
 * Trashed products and orders are left alone. Neither wc_get_products() nor wc_get_orders() documents
 * a way to include them, and reaching around those APIs is what WooCommerce warns against. A record
 * restored from trash keeps the old key, which reads as "not set" rather than as the wrong choice.
 *
 * Variations are converted through their parent, because the page holds only products that carry the
 * settings key themselves. A variation whose parent has no settings record is therefore not converted.
 * Saving a variable product in the admin writes a parent record, so reaching that case needs a
 * variation that got its settings another way, such as an import.
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
        $this->scheduleConversionPasses();
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
     * Product settings and stored order data are one record each, so converting them all inline would
     * time out a large shop. Each pass is started once here and then keeps itself going until its
     * query runs out of records.
     *
     * NoTrackingChunkMigrator owns those queries rather than this migration, because a later run has
     * to be able to find its own work: an anonymous migration class cannot be a cron callback.
     */
    private function scheduleConversionPasses(): void
    {
        /** @var CronServiceInterface $cronService */
        $cronService = Pdk::get(CronServiceInterface::class);

        $cronService->schedule(Pdk::get('migrateAction_NoTracking_ProductSettings'), time());
        $cronService->schedule(Pdk::get('migrateAction_NoTracking_Orders'), time());
    }
};
