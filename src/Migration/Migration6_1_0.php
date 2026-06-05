<?php

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Migration;

use MyParcelNL\Pdk\App\Account\Contract\PdkAccountRepositoryInterface;
use MyParcelNL\Pdk\Base\Contract\CronServiceInterface;
use MyParcelNL\Pdk\Carrier\Model\Carrier;
use MyParcelNL\Pdk\Carrier\Repository\CarrierCapabilitiesRepository;
use MyParcelNL\Pdk\Facade\Pdk;
use MyParcelNL\Pdk\Settings\Contract\PdkSettingsRepositoryInterface;
use WC_Order;

final class Migration6_1_0 extends AbstractMigration
{
    protected CarrierCapabilitiesRepository $carrierCapabilitiesRepository;
    protected PdkAccountRepositoryInterface $accountRepository;
    protected PdkSettingsRepositoryInterface $settingsRepository;
    protected CronServiceInterface $cronService;

    public function __construct(
        CarrierCapabilitiesRepository  $carrierCapabilitiesRepository,
        PdkAccountRepositoryInterface  $accountRepository,
        PdkSettingsRepositoryInterface $settingsRepository,
        CronServiceInterface           $cronService
    ) {
        $this->carrierCapabilitiesRepository = $carrierCapabilitiesRepository;
        $this->accountRepository             = $accountRepository;
        $this->settingsRepository            = $settingsRepository;
        $this->cronService                   = $cronService;
    }

    public function getVersion(): string
    {
        return '6.1.0';
    }

    public function down(): void {}

    public function up(): void
    {
        $this->migrateAccountData();
        $this->migrateCarrierSettings();
        $this->updateOrderData();
        $this->updateShipmentData();
    }

    /**
     * Replaces Account->Shop->Carriers with the new Capabilities data from the API
     * @return void
     */
    public function migrateAccountData(): void
    {
        $account = $this->accountRepository->getAccount(true);
        $shop    = $account && $account->shops ? $account->shops->first() : null;

        if (! $shop) {
            $this->debug('No account or shop available; skipping carrier capabilities migration.');

            return;
        }

        try {
            // Fetch the carrier definitions from the API
            $shop->carriers = $this->carrierCapabilitiesRepository->getContractDefinitions();
        } catch (\Throwable $exception) {
            // Re-throw so the installer does not bump the installed version, letting the
            // migration retry on the next load instead of leaving carrier data unfetched.
            $this->warning('Failed to fetch carrier definitions from the API; migration will retry.', [
                'exception' => $exception->getMessage(),
            ]);

            throw $exception;
        }

        // Store the updated account data
        $this->accountRepository->store($account);
    }

    /**
     * Replaces user-stored carrier settings so they will map to the new carrier definitions.
     * @return void
     */
    public function migrateCarrierSettings(): void
    {
        $settingsKey     = Pdk::get('createSettingsKey')('carrier');
        $currentSettings = $this->settingsRepository->get($settingsKey);

        if (empty($currentSettings) || ! is_array($currentSettings)) {
            return;
        }

        $legacyToNewMap = array_flip(Carrier::CARRIER_NAME_TO_LEGACY_MAP);

        $migratedSettings = [];
        foreach ($currentSettings as $legacyKey => $carrierData) {
            $newKey                    = $legacyToNewMap[$legacyKey] ?? $legacyKey;
            $migratedSettings[$newKey] = $carrierData;
        }

        $this->settingsRepository->store($settingsKey, $migratedSettings);
    }

    /**
     * Schedules chunked cron jobs to update the carrier field in all order data meta.
     * Fetches order IDs in pages to avoid loading all IDs into memory at once.
     *
     * @return void
     */
    public function updateOrderData(): void
    {
        $this->schedulePagedMigration(
            Pdk::get('metaKeyOrderData'),
            Pdk::get('migrateAction_6_1_0_Orders')
        );
    }

    /**
     * Cron callback: normalises the carrier field in _myparcelcom_order_data for a chunk of orders.
     * Handles all legacy formats:
     *   - plain lowercase string: "postnl"
     *   - old SDK object:         {"externalIdentifier": "postnl"}
     *   - transitional array:     {"carrier": "postnl", ...}
     *
     * @param  array $data
     *
     * @return void
     */
    public function migrateOrderChunk(array $data): void
    {
        $orderIds       = $data['orderIds'] ?? [];
        $chunk          = $data['chunk'] ?? null;
        $legacyToNewMap = array_flip(Carrier::CARRIER_NAME_TO_LEGACY_MAP);

        if (empty($orderIds)) {
            return;
        }

        $this->debug(
            sprintf(
                'Start order data migration for orders %d..%d (chunk %d)',
                $orderIds[0],
                $orderIds[count($orderIds) - 1],
                $chunk
            )
        );

        foreach ($orderIds as $orderId) {
            $order    = new WC_Order($orderId);
            $metaData = $order->get_meta(Pdk::get('metaKeyOrderData'));

            if (empty($metaData) || ! is_array($metaData)) {
                continue;
            }

            $parsed = $this->parseLegacyCarrier($metaData['deliveryOptions']['carrier'] ?? null);

            if (! $parsed) {
                continue;
            }

            [$legacyName] = $parsed;
            $metaData['deliveryOptions']['carrier'] = $legacyToNewMap[$legacyName] ?? $legacyName;

            $order->update_meta_data(Pdk::get('metaKeyOrderData'), $metaData);
            $order->save();

            $this->debug("Order $orderId carrier migrated.");
        }
    }

    /**
     * Schedules chunked cron jobs to update the carrier field in all shipment data meta.
     * Fetches order IDs in pages to avoid loading all IDs into memory at once.
     *
     * @return void
     */
    public function updateShipmentData(): void
    {
        $this->schedulePagedMigration(
            Pdk::get('metaKeyOrderShipments'),
            Pdk::get('migrateAction_6_1_0_Shipments')
        );
    }

    /**
     * Cron callback: normalises the carrier field in _myparcelcom_order_shipments for a chunk of orders.
     * Handles legacy formats where carrier was stored as an object:
     *   - old SDK object:    {"externalIdentifier": "postnl", ...}
     *   - transitional:      {"carrier": "postnl", ...}
     *   - plain string:      "postnl"
     *
     * Migrates to a plain string with the new carrier identifier: "POSTNL"
     *
     * @param  array $data
     *
     * @return void
     */
    public function migrateShipmentChunk(array $data): void
    {
        $orderIds       = $data['orderIds'] ?? [];
        $chunk          = $data['chunk'] ?? null;
        $legacyToNewMap = array_flip(Carrier::CARRIER_NAME_TO_LEGACY_MAP);

        if (empty($orderIds)) {
            return;
        }

        $this->debug(
            sprintf(
                'Start shipment data migration for orders %d..%d (chunk %d)',
                $orderIds[0],
                $orderIds[count($orderIds) - 1],
                $chunk
            )
        );

        foreach ($orderIds as $orderId) {
            $order     = new WC_Order($orderId);
            $shipments = $order->get_meta(Pdk::get('metaKeyOrderShipments'));

            if (empty($shipments) || ! is_array($shipments)) {
                continue;
            }

            $changed = false;

            foreach ($shipments as &$shipment) {
                $parsed = $this->parseLegacyCarrier($shipment['carrier'] ?? null);

                if ($parsed) {
                    [$legacyName, $contractId] = $parsed;
                    $newName = $legacyToNewMap[$legacyName] ?? $legacyName;

                    if ($newName !== $shipment['carrier']) {
                        $shipment['carrier'] = $newName;
                        $changed = true;
                    }

                    if ($contractId && ! isset($shipment['contractId'])) {
                        $shipment['contractId'] = $contractId;
                        $changed = true;
                    }
                }

                if (isset($shipment['deliveryOptions'])) {
                    $changed = $this->migrateCarrierField($shipment['deliveryOptions'], 'carrier', $legacyToNewMap) || $changed;
                }
            }
            unset($shipment);

            if (! $changed) {
                continue;
            }

            $order->update_meta_data(Pdk::get('metaKeyOrderShipments'), $shipments);
            $order->save();

            $this->debug("Order $orderId shipments migrated.");
        }
    }

    /**
     * Extracts the legacy carrier name from the various stored formats and strips the contract ID suffix.
     *
     * @param  mixed $carrier
     *
     * @return null|string[] [carrierName, contractId] or null if not parseable
     */
    private function parseLegacyCarrier($carrier): ?array
    {
        if (is_array($carrier)) {
            $raw = $carrier['externalIdentifier'] ?? ($carrier['carrier'] ?? null);
        } elseif (is_string($carrier)) {
            $raw = $carrier;
        } else {
            return null;
        }

        if (! is_string($raw)) {
            return null;
        }

        $parts      = explode(':', $raw, 2);
        $name       = $parts[0];
        $contractId = $parts[1] ?? null;

        return [$name, $contractId];
    }

    /**
     * Migrates a carrier field in-place from legacy formats to the new string identifier.
     *
     * @param  array  $data
     * @param  string $key
     * @param  array  $legacyToNewMap
     *
     * @return bool Whether the field was changed.
     */
    private function migrateCarrierField(array &$data, string $key, array $legacyToNewMap): bool
    {
        $parsed = $this->parseLegacyCarrier($data[$key] ?? null);

        if (! $parsed) {
            return false;
        }

        [$legacyName] = $parsed;
        $newName = $legacyToNewMap[$legacyName] ?? $legacyName;

        if ($newName === $data[$key]) {
            return false;
        }

        $data[$key] = $newName;

        return true;
    }

    /**
     * Fetches order IDs page by page and schedules a cron job for each page.
     *
     * @param  string $metaKey     The meta key to filter orders by.
     * @param  string $cronAction  The cron action name to schedule.
     *
     * @return void
     */
    private function schedulePagedMigration(string $metaKey, string $cronAction): void
    {
        $page       = 1;
        $chunkIndex = 0;
        $pageSize   = 100;

        do {
            /** @var int[] $orderIds */
            $orderIds = wc_get_orders([
                'limit'        => $pageSize,
                'paged'        => $page,
                'meta_key'     => $metaKey,
                'meta_compare' => 'EXISTS',
                'return'       => 'ids',
            ]);

            if (empty($orderIds)) {
                break;
            }

            $time         = time() + $chunkIndex * 5;
            $chunkContext = [
                'orderIds' => $orderIds,
                'chunk'    => $chunkIndex + 1,
            ];

            $this->cronService->schedule($cronAction, $time, $chunkContext);

            $this->debug('Scheduled migration chunk', [
                'action' => $cronAction,
                'time'   => $time,
                'chunk'  => $chunkContext,
            ]);

            $chunkIndex++;
            $page++;
        } while (count($orderIds) === $pageSize);
    }
}
