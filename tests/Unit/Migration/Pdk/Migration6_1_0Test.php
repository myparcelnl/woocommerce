<?php
/** @noinspection StaticClosureCanBeUsedInspection */

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Migration\Pdk;

use MyParcelNL\Pdk\App\Account\Contract\PdkAccountRepositoryInterface;
use MyParcelNL\Pdk\Carrier\Collection\CarrierCollection;
use MyParcelNL\Pdk\Carrier\Repository\CarrierCapabilitiesRepository;
use MyParcelNL\Pdk\Facade\Pdk;
use MyParcelNL\Pdk\SdkApi\Service\CoreApi\Shipment\CapabilitiesService;
use MyParcelNL\Pdk\Settings\Contract\PdkSettingsRepositoryInterface;
use MyParcelNL\Pdk\Storage\Contract\StorageInterface;
use MyParcelNL\Pdk\Tests\Bootstrap\TestBootstrapper;
use MyParcelNL\WooCommerce\Migration\Migration6_1_0;
use MyParcelNL\WooCommerce\Tests\Mock\MockWpMeta;
use MyParcelNL\WooCommerce\Tests\Mock\WordPressScheduledTasks;
use MyParcelNL\WooCommerce\Tests\Uses\UsesMockWcPdkInstance;
use RuntimeException;
use function MyParcelNL\Pdk\Tests\mockPdkProperties;
use function MyParcelNL\Pdk\Tests\usesShared;
use function MyParcelNL\WooCommerce\Tests\createWcOrder;

usesShared(new UsesMockWcPdkInstance());

// --- migrateAccountData (defensive) ---

it('does not fail account data migration when no account or shop is available', function () {
    /** @var PdkAccountRepositoryInterface $accountRepo */
    $accountRepo = Pdk::get(PdkAccountRepositoryInterface::class);

    /** @var Migration6_1_0 $migration */
    $migration = Pdk::get(Migration6_1_0::class);

    // No account configured (and a forced refresh has no valid API key), so getAccount()
    // returns null. The migration must skip gracefully instead of fataling.
    $migration->migrateAccountData();

    expect($accountRepo->getAccount())->toBeNull();
});

it('rethrows when fetching carrier definitions fails so the migration retries', function () {
    TestBootstrapper::hasAccount();

    $throwingRepo = new class(
        Pdk::get(StorageInterface::class),
        Pdk::get(CapabilitiesService::class)
    ) extends CarrierCapabilitiesRepository {
        public function getContractDefinitions(?string $carrier = null): CarrierCollection
        {
            throw new RuntimeException('API unavailable');
        }
    };

    mockPdkProperties([CarrierCapabilitiesRepository::class => $throwingRepo]);

    /** @var Migration6_1_0 $migration */
    $migration = Pdk::get(Migration6_1_0::class);

    expect(fn () => $migration->migrateAccountData())->toThrow(RuntimeException::class);
});

// --- migrateCarrierSettings ---

it('remaps legacy carrier setting keys to new format', function () {
    /** @var \MyParcelNL\Pdk\Tests\Bootstrap\MockSettingsRepository $settingsRepo */
    $settingsRepo = Pdk::get(PdkSettingsRepositoryInterface::class);
    $settingsKey  = Pdk::get('createSettingsKey')('carrier');

    $settingsRepo->store($settingsKey, [
        'postnl'           => ['delivery_enabled' => '1', 'pickup_enabled' => '1'],
        'dhlforyou'        => ['delivery_enabled' => '1'],
        'dhlparcelconnect' => ['delivery_enabled' => '0'],
    ]);

    /** @var Migration6_1_0 $migration */
    $migration = Pdk::get(Migration6_1_0::class);
    $migration->migrateCarrierSettings();

    $result = $settingsRepo->get($settingsKey);

    expect($result)
        ->toHaveKeys(['POSTNL', 'DHL_FOR_YOU', 'DHL_PARCEL_CONNECT'])
        ->and($result)->not->toHaveKey('postnl')
        ->and($result)->not->toHaveKey('dhlforyou')
        ->and($result)->not->toHaveKey('dhlparcelconnect')
        ->and($result['POSTNL'])->toBe(['delivery_enabled' => '1', 'pickup_enabled' => '1'])
        ->and($result['DHL_FOR_YOU'])->toBe(['delivery_enabled' => '1'])
        ->and($result['DHL_PARCEL_CONNECT'])->toBe(['delivery_enabled' => '0']);
});

it('does not fail when carrier settings are empty', function () {
    /** @var Migration6_1_0 $migration */
    $migration = Pdk::get(Migration6_1_0::class);
    $migration->migrateCarrierSettings();

    /** @var \MyParcelNL\Pdk\Tests\Bootstrap\MockSettingsRepository $settingsRepo */
    $settingsRepo = Pdk::get(PdkSettingsRepositoryInterface::class);
    $settingsKey  = Pdk::get('createSettingsKey')('carrier');

    expect($settingsRepo->get($settingsKey))->toBeEmpty();
});

it('preserves carrier settings that already use new key format', function () {
    /** @var \MyParcelNL\Pdk\Tests\Bootstrap\MockSettingsRepository $settingsRepo */
    $settingsRepo = Pdk::get(PdkSettingsRepositoryInterface::class);
    $settingsKey  = Pdk::get('createSettingsKey')('carrier');

    $settingsRepo->store($settingsKey, [
        'postnl' => ['delivery_enabled' => '1'],
        'BPOST'  => ['delivery_enabled' => '1'],
    ]);

    /** @var Migration6_1_0 $migration */
    $migration = Pdk::get(Migration6_1_0::class);
    $migration->migrateCarrierSettings();

    $result = $settingsRepo->get($settingsKey);

    expect($result)
        ->toHaveKeys(['POSTNL', 'BPOST'])
        ->and($result)->not->toHaveKey('postnl');
});

// --- updateOrderData / updateShipmentData (scheduling) ---

it('schedules order data migration', function () {
    /** @var WordPressScheduledTasks $tasks */
    $tasks = Pdk::get(WordPressScheduledTasks::class);

    for ($i = 1; $i <= 5; $i++) {
        createWcOrder(['id' => $i]);
    }

    /** @var Migration6_1_0 $migration */
    $migration = Pdk::get(Migration6_1_0::class);
    $migration->updateOrderData();

    $allTasks = $tasks->all();

    expect($allTasks->count())->toBeGreaterThanOrEqual(1)
        ->and($allTasks->pluck('callback'))->each->toBe(Pdk::get('migrateAction_6_1_0_Orders'))
        ->and($allTasks->first()['args'][0]['orderIds'])->not->toBeEmpty()
        ->and($allTasks->first()['args'][0]['chunk'])->toBe(1);
});

it('schedules shipment data migration', function () {
    /** @var WordPressScheduledTasks $tasks */
    $tasks = Pdk::get(WordPressScheduledTasks::class);

    for ($i = 1; $i <= 5; $i++) {
        createWcOrder(['id' => $i]);
    }

    /** @var Migration6_1_0 $migration */
    $migration = Pdk::get(Migration6_1_0::class);
    $migration->updateShipmentData();

    $allTasks = $tasks->all();

    expect($allTasks->count())->toBeGreaterThanOrEqual(1)
        ->and($allTasks->pluck('callback'))->each->toBe(Pdk::get('migrateAction_6_1_0_Shipments'));
});

// --- migrateOrderChunk (carrier normalization) ---

dataset('order carrier variants', [
    'plain legacy string' => [
        ['deliveryOptions' => ['carrier' => 'postnl']],
        'POSTNL',
    ],
    'legacy string with contract suffix' => [
        ['deliveryOptions' => ['carrier' => 'postnl:123']],
        'POSTNL',
    ],
    'object with externalIdentifier' => [
        ['deliveryOptions' => ['carrier' => ['externalIdentifier' => 'dhlforyou']]],
        'DHL_FOR_YOU',
    ],
    'object with carrier key' => [
        ['deliveryOptions' => ['carrier' => ['carrier' => 'dhlparcelconnect']]],
        'DHL_PARCEL_CONNECT',
    ],
]);

it('normalises the carrier field in order data', function (array $orderData, string $expectedCarrier) {
    $metaKey = Pdk::get('metaKeyOrderData');

    createWcOrder(['id' => 1, 'meta' => [$metaKey => $orderData]]);

    /** @var Migration6_1_0 $migration */
    $migration = Pdk::get(Migration6_1_0::class);
    $migration->migrateOrderChunk([
        'orderIds' => [1],
        'chunk'    => 1,
    ]);

    $updatedMeta = MockWpMeta::get(1, $metaKey);

    expect($updatedMeta['deliveryOptions']['carrier'])->toBe($expectedCarrier);
})->with('order carrier variants');

it('skips orders without order data meta', function () {
    createWcOrder(['id' => 1]);

    /** @var Migration6_1_0 $migration */
    $migration = Pdk::get(Migration6_1_0::class);
    $migration->migrateOrderChunk([
        'orderIds' => [1],
        'chunk'    => 1,
    ]);

    expect(MockWpMeta::get(1, Pdk::get('metaKeyOrderData')))->toBeNull();
});

// --- migrateShipmentChunk (carrier normalization) ---

dataset('shipment carrier variants', [
    'plain legacy string' => [
        [['carrier' => 'postnl']],
        [['carrier' => 'POSTNL']],
    ],
    'legacy string with contract suffix' => [
        [['carrier' => 'postnl:42']],
        [['carrier' => 'POSTNL', 'contractId' => '42']],
    ],
    'object with externalIdentifier' => [
        [['carrier' => ['externalIdentifier' => 'dhlforyou']]],
        [['carrier' => 'DHL_FOR_YOU']],
    ],
    'with nested deliveryOptions carrier' => [
        [['carrier' => 'postnl', 'deliveryOptions' => ['carrier' => 'postnl']]],
        [['carrier' => 'POSTNL', 'deliveryOptions' => ['carrier' => 'POSTNL']]],
    ],
]);

it('normalises the carrier field in shipment data', function (array $shipments, array $expected) {
    $metaKey = Pdk::get('metaKeyOrderShipments');

    createWcOrder(['id' => 1, 'meta' => [$metaKey => $shipments]]);

    /** @var Migration6_1_0 $migration */
    $migration = Pdk::get(Migration6_1_0::class);
    $migration->migrateShipmentChunk([
        'orderIds' => [1],
        'chunk'    => 1,
    ]);

    $updatedMeta = MockWpMeta::get(1, $metaKey);

    foreach ($expected as $index => $expectedShipment) {
        foreach ($expectedShipment as $key => $value) {
            expect($updatedMeta[$index][$key])->toBe($value);
        }
    }
})->with('shipment carrier variants');
