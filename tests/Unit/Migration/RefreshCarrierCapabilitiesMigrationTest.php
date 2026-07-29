<?php

/** @noinspection StaticClosureCanBeUsedInspection */

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Migration;

use MyParcelNL\Pdk\App\Account\Contract\PdkAccountRepositoryInterface;
use MyParcelNL\Pdk\App\Installer\Contract\TimestampedMigrationInterface;
use MyParcelNL\Pdk\Carrier\Collection\CarrierCollection;
use MyParcelNL\Pdk\Carrier\Repository\CarrierCapabilitiesRepository;
use MyParcelNL\Pdk\Facade\Pdk;
use MyParcelNL\Pdk\SdkApi\Service\CoreApi\Shipment\CapabilitiesService;
use MyParcelNL\Pdk\Storage\Contract\StorageInterface;
use MyParcelNL\Pdk\Tests\Api\Response\ExampleGetAccountsResponse;
use MyParcelNL\Pdk\Tests\Bootstrap\MockApi;
use MyParcelNL\Pdk\Tests\Bootstrap\TestBootstrapper;
use MyParcelNL\WooCommerce\Tests\Uses\UsesMockWcPdkInstance;
use RuntimeException;
use function MyParcelNL\Pdk\Tests\mockPdkProperties;
use function MyParcelNL\Pdk\Tests\usesShared;

usesShared(new UsesMockWcPdkInstance());

/**
 * Loads the migration the same way the installer does: require the file and take the
 * returned anonymous-class instance.
 */
function loadRefreshCarrierCapabilitiesMigration(): TimestampedMigrationInterface
{
    return require __DIR__ . '/../../../src/Migration/2026_07_29_092726_refresh_carrier_capabilities.php';
}

it('is a timestamped migration the installer can discover', function () {
    $migration = loadRefreshCarrierCapabilitiesMigration();

    // The installer injects identity from the filename, so the migration must accept it
    // and report it back rather than deriving one itself.
    $migration->setIdentity('2026_07_29_092726_refresh_carrier_capabilities');

    expect($migration)->toBeInstanceOf(TimestampedMigrationInterface::class)
        ->and($migration->getId())->toBe('2026_07_29_092726_refresh_carrier_capabilities');
});

it('skips without failing when no account or shop is available', function () {
    /** @var PdkAccountRepositoryInterface $accountRepo */
    $accountRepo = Pdk::get(PdkAccountRepositoryInterface::class);

    // No account configured, so a forced refresh returns null. Skipping beats fataling:
    // a fresh install has nothing to refresh.
    loadRefreshCarrierCapabilitiesMigration()->up();

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

    // Throwing is the only way to retry: the installer marks a migration as applied straight
    // after up() returns, so swallowing the error would strand the old carrier data.
    expect(fn() => loadRefreshCarrierCapabilitiesMigration()->up())->toThrow(RuntimeException::class);
});

it('stores the freshly fetched carriers on the shop', function () {
    TestBootstrapper::hasAccount();
    // The migration forces an account refresh, which calls the accounts endpoint.
    MockApi::enqueue(new ExampleGetAccountsResponse());

    $stubRepo = new class(
        Pdk::get(StorageInterface::class),
        Pdk::get(CapabilitiesService::class)
    ) extends CarrierCapabilitiesRepository {
        public function getContractDefinitions(?string $carrier = null): CarrierCollection
        {
            return new CarrierCollection([
                [
                    'carrier' => 'POSTNL',
                    'options' => [
                        'insurance' => [
                            'min'     => ['amount' => 0, 'currency' => 'EUR'],
                            'max'     => ['amount' => 500_000, 'currency' => 'EUR'],
                            'default' => ['amount' => 0, 'currency' => 'EUR'],
                        ],
                    ],
                ],
            ]);
        }
    };

    mockPdkProperties([CarrierCapabilitiesRepository::class => $stubRepo]);

    loadRefreshCarrierCapabilitiesMigration()->up();

    /** @var PdkAccountRepositoryInterface $accountRepo */
    $accountRepo = Pdk::get(PdkAccountRepositoryInterface::class);
    $carrier     = $accountRepo->getAccount()
        ->shops->first()
        ->carriers->firstWhere('carrier', 'POSTNL');
    $insurance   = $carrier->options->getInsurance();

    // The stored shape is what the admin and the calculator read back, so the flat limits
    // have to survive being written to storage.
    expect($insurance->getMax()->getAmount())->toBe(500_000)
        ->and($insurance->getMin()->getAmount())->toBe(0)
        ->and($insurance->getInsuredAmount())->toBeNull();
});
