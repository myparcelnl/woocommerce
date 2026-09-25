<?php

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Migration;

use MyParcelNL\Pdk\App\Account\Contract\PdkAccountRepositoryInterface;
use MyParcelNL\Pdk\Base\Contract\CronServiceInterface;
use MyParcelNL\Pdk\Carrier\Model\Carrier;
use MyParcelNL\Pdk\Carrier\Repository\CarrierCapabilitiesRepository;
use MyParcelNL\Pdk\Facade\Pdk;
use MyParcelNL\Pdk\Settings\Contract\PdkSettingsRepositoryInterface;
use Throwable;
use WC_Order;

final class Migration6_5_1 extends AbstractMigration
{
    private const MAX_CHUNK_RETRIES = 3;

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
        return '6.5.1';
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
        $accountSettings = $this->settingsRepository->all()->account;

        if (! $accountSettings->apiKey || ! $accountSettings->apiKeyValid) {
            $this->debug('No valid API key available; skipping carrier capabilities migration.');

            return;
        }

        // Keep plugin-managed account fields that are absent from the accounts API response.
        $account = $this->accountRepository->getAccount();
        // PHPStan types Account::$shops as a non-null ShopCollection, but the guard is kept
        // intentionally to stay safe against partial/corrupted account data during upgrade.
        // @phpstan-ignore booleanAnd.rightAlwaysTrue
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
            Pdk::get('migrateAction_6_5_1_Orders')
        );
    }

    /**
     * Normalises order data, optionally copying it from a legacy namespace.
     *
     * @param array $data
     */
    public function migrateOrderChunk(array $data): void
    {
        $this->migrateMetaChunk($data, Pdk::get('metaKeyOrderData'), false);
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
            Pdk::get('migrateAction_6_5_1_Shipments')
        );
    }

    /**
     * Normalises shipments, optionally copying them from a legacy namespace.
     *
     * @param array $data
     */
    public function migrateShipmentChunk(array $data): void
    {
        $this->migrateMetaChunk($data, Pdk::get('metaKeyOrderShipments'), true);
    }

    /**
     * Schedule the same chunks for data that the namespace change in 6.0.0 missed.
     * The source key stays in the job context; no legacy data is exposed under the
     * current key until the callback has normalised the complete value.
     */
    public function updateLegacyOrderMeta(): void
    {
        foreach (['_myparcelnl_', '_myparcelbe_'] as $prefix) {
            foreach ([
                'order_shipments' => Pdk::get('migrateAction_6_5_1_Shipments'),
                'order_data'      => Pdk::get('migrateAction_6_5_1_Orders'),
            ] as $suffix => $action) {
                $legacyKey = $prefix . $suffix;

                $this->schedulePagedMigration($legacyKey, $action, ['legacyMetaKey' => $legacyKey]);
            }
        }
    }

    /**
     * Recheck the destination when the job runs: an order may have been exported or
     * had its shipments removed after scheduling. Preserve even an empty current key.
     *
     * @param array  $data
     * @param string $currentKey
     * @param bool   $isList
     */
    private function migrateMetaChunk(array $data, string $currentKey, bool $isList): void
    {
        $sourceKey      = $data['legacyMetaKey'] ?? $currentKey;
        $failedOrderIds = [];

        foreach ($data['orderIds'] ?? [] as $orderId) {
            try {
                $this->migrateOrderMeta($orderId, $sourceKey, $currentKey, $isList);
            } catch (Throwable $exception) {
                $failedOrderIds[] = $orderId;
                $this->error('Could not migrate order meta.', [
                    'orderId'   => $orderId,
                    'from'      => $sourceKey,
                    'exception' => $exception->getMessage(),
                ]);
            }
        }

        if (empty($failedOrderIds)) {
            return;
        }

        $attempt = (int) ($data['attempt'] ?? 0);

        if ($attempt >= self::MAX_CHUNK_RETRIES) {
            $this->error('Order meta migration retry limit reached; manual retry required.', [
                'orderIds' => $failedOrderIds,
                'from'     => $sourceKey,
            ]);

            return;
        }

        $data['orderIds'] = $failedOrderIds;
        $data['attempt']  = $attempt + 1;
        $action = Pdk::get($isList ? 'migrateAction_6_5_1_Shipments' : 'migrateAction_6_5_1_Orders');

        try {
            $this->cronService->schedule($action, time() + 60 * $data['attempt'], $data);
        } catch (Throwable $exception) {
            $this->error('Could not schedule order meta migration retry; manual retry required.', [
                'orderIds'  => $failedOrderIds,
                'from'      => $sourceKey,
                'exception' => $exception->getMessage(),
            ]);
        }
    }

    private function migrateOrderMeta(int $orderId, string $sourceKey, string $currentKey, bool $isList): void
    {
        $order = wc_get_order($orderId);

        if (! $order instanceof WC_Order
            || ($sourceKey !== $currentKey && $order->meta_exists($currentKey))) {
            return;
        }

        $value = $order->get_meta($sourceKey);

        if (! is_array($value) || empty($value)) {
            return;
        }

        $normalized = $this->normalizeOrderMeta($value, $isList);

        if (null === $normalized) {
            $this->warning('Skipped order meta with malformed data or an unsupported carrier; original metadata retained.', [
                'orderId' => $orderId,
                'from'    => $sourceKey,
            ]);

            return;
        }

        if ($sourceKey === $currentKey && $normalized === $value) {
            return;
        }

        $order->update_meta_data($currentKey, $normalized);
        $order->save();

        $this->debug('Migrated order meta', [
            'orderId' => $orderId,
            'from'    => $sourceKey,
            'to'      => $currentKey,
        ]);
    }

    /**
     * A carrier lives in two places: on the record itself and on its delivery options. Shipments
     * hold a list of such records, order data holds a single one.
     *
     * Returns null as soon as one carrier has no supported name. The caller then writes nothing at
     * all, so the order keeps its legacy data and a future migration can convert it once we do
     * support that carrier. Writing it half-converted would leave the order unreadable instead.
     *
     * @param  array  $value
     * @param  bool   $isList
     *
     * @return null|array
     */
    private function normalizeOrderMeta(array $value, bool $isList): ?array
    {
        $records = $isList ? $value : [$value];

        foreach ($records as $index => $record) {
            if (! is_array($record)) {
                return null;
            }

            $record = $this->normalizeCarrierOn($record, $isList);

            if (null === $record) {
                return null;
            }

            if (isset($record['deliveryOptions'])) {
                if (! is_array($record['deliveryOptions'])) {
                    return null;
                }

                $deliveryOptions = $this->normalizeCarrierOn($record['deliveryOptions'], false);

                if (null === $deliveryOptions) {
                    return null;
                }

                $record['deliveryOptions'] = $deliveryOptions;
            }

            $records[$index] = $record;
        }

        return $isList ? $records : $records[0];
    }

    /**
     * Converts stored carrier formats to a supported V2 name before saving the record.
     * Missing carriers stay unchanged. Invalid carriers return null to skip the complete value.
     * Existing contract IDs win; new IDs use the Shipment (string) or DeliveryOptions (int) type.
     *
     * @param  array $record
     * @param  bool  $isShipment
     *
     * @return null|array
     */
    private function normalizeCarrierOn(array $record, bool $isShipment): ?array
    {
        $carrier = $record['carrier'] ?? null;

        if (null === $carrier || '' === $carrier || [] === $carrier) {
            return $record;
        }

        $name       = null;
        $contractId = null;

        if (is_array($carrier)) {
            $storedContractId = $carrier['contractId'] ?? ($carrier['contract_id'] ?? null);
            $contractId       = is_numeric($storedContractId) ? $storedContractId : null;

            if (isset($carrier['id']) && is_numeric($carrier['id'])) {
                // Deprecated UPS (id 8) follows the same UPS Standard mapping as the legacy name.
                $name = 8 === (int) $carrier['id'] ? 'UPS_STANDARD' : Carrier::v2NameFromLegacyId((int) $carrier['id']);
            }

            $carrier = $carrier['externalIdentifier'] ?? ($carrier['carrier'] ?? null);
        }

        if (is_string($carrier)) {
            $parts          = explode(':', $carrier, 2);
            $legacyToNewMap = ['ups' => 'UPS_STANDARD'] + array_flip(Carrier::CARRIER_NAME_TO_LEGACY_MAP);
            $name           = $name ?? ($legacyToNewMap[$parts[0]] ?? $parts[0]);

            if (null === $contractId && isset($parts[1]) && is_numeric($parts[1])) {
                $contractId = $parts[1];
            }
        }

        if (null === $name || ! Carrier::isSupported($name)) {
            // Retired carriers such as Instabox have no supported replacement. Preserve the
            // complete original value instead of assigning another carrier or losing shipments.
            return null;
        }

        $record['carrier'] = $name;

        if (null !== $contractId && ! isset($record['contractId'])) {
            $record['contractId'] = $isShipment ? (string) $contractId : (int) $contractId;
        }

        return $record;
    }

    /**
     * Fetches order IDs page by page and schedules a cron job for each page.
     *
     * @param  string $metaKey     The meta key to filter orders by.
     * @param  string $cronAction  The cron action name to schedule.
     * @param  array  $context     Extra context for the chunk callback.
     *
     * @return void
     */
    private function schedulePagedMigration(string $metaKey, string $cronAction, array $context = []): void
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
                'orderby'      => 'ID',
                'order'        => 'ASC',
            ]);

            if (empty($orderIds)) {
                break;
            }

            $time         = time() + $chunkIndex * 5;
            $chunkContext = [
                'orderIds' => $orderIds,
                'chunk'    => $chunkIndex + 1,
            ] + $context;

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
