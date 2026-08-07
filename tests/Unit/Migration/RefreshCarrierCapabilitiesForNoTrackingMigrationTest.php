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
use MyParcelNL\Pdk\Tests\SdkApi\MockSdkApiHandler;
use MyParcelNL\Pdk\Tests\SdkApi\Response\ExampleContractDefinitionsResponse;
use MyParcelNL\WooCommerce\Tests\Uses\UsesMockWcPdkInstance;
use RuntimeException;

use function MyParcelNL\Pdk\Tests\mockPdkProperties;
use function MyParcelNL\Pdk\Tests\usesShared;

usesShared(new UsesMockWcPdkInstance());

const REFRESH_MIGRATION_ID = '2026_08_04_132333_refresh_carrier_capabilities_for_no_tracking';

/**
 * Loads the migration the same way the installer does: require the file and take the returned
 * anonymous-class instance.
 */
function loadRefreshMigration(): TimestampedMigrationInterface
{
    return require __DIR__ . '/../../../src/Migration/' . REFRESH_MIGRATION_ID . '.php';
}

it('is a timestamped migration the installer can discover', function () {
    $migration = loadRefreshMigration();

    $migration->setIdentity(REFRESH_MIGRATION_ID);

    expect($migration)->toBeInstanceOf(TimestampedMigrationInterface::class)
        ->and($migration->getId())->toBe(REFRESH_MIGRATION_ID);
});

it('runs after the migration that inverts the stored option', function () {
    // The refresh has to happen once the feature flag is being sent, and timestamped migrations run in
    // filename order, so this one has to sort later than the inversion.
    expect(REFRESH_MIGRATION_ID > INVERT_MIGRATION_ID)->toBeTrue();
});

it('skips without failing when no account or shop is available', function () {
    /** @var PdkAccountRepositoryInterface $accountRepository */
    $accountRepository = Pdk::get(PdkAccountRepositoryInterface::class);

    // A fresh install has nothing to refresh, so skipping beats fataling.
    loadRefreshMigration()->up();

    expect($accountRepository->getAccount())->toBeNull();
});

it('asks for fresh contract definitions rather than the cached copy', function () {
    TestBootstrapper::hasAccount();
    MockApi::enqueue(new ExampleGetAccountsResponse());

    $spy = new class(
        Pdk::get(StorageInterface::class),
        Pdk::get(CapabilitiesService::class)
    ) extends CarrierCapabilitiesRepository {
        /** @var null|bool */
        public $freshRequested;

        public function getContractDefinitions(?string $carrier = null, bool $fresh = false): CarrierCollection
        {
            $this->freshRequested = $fresh;

            return new CarrierCollection();
        }
    };

    mockPdkProperties([CarrierCapabilitiesRepository::class => $spy]);

    loadRefreshMigration()->up();

    // Passing false would re-store the pre-flag copy, which is the exact state this migration exists to
    // replace.
    expect($spy->freshRequested)->toBeTrue();
});

it('stores carriers that carry the new option', function () {
    TestBootstrapper::hasAccount();
    // The migration forces an account refresh, which calls the accounts endpoint.
    MockApi::enqueue(new ExampleGetAccountsResponse());
    // Goes through the real service and repository, so the response is mapped by the code that actually
    // does it rather than by a stub.
    MockSdkApiHandler::enqueue(new ExampleContractDefinitionsResponse([
        [
            'carrier'          => 'POSTNL',
            'packageTypes'     => ['PACKAGE'],
            'deliveryTypes'    => ['STANDARD_DELIVERY'],
            'transactionTypes' => ['B2C'],
            'options'          => [
                'noTracking' => ['isSelectedByDefault' => false, 'isRequired' => false],
            ],
        ],
    ]));

    loadRefreshMigration()->up();

    /** @var PdkAccountRepositoryInterface $accountRepository */
    $accountRepository = Pdk::get(PdkAccountRepositoryInterface::class);
    $carrier           = $accountRepository->getAccount()
        ->shops->first()
        ->carriers->firstWhere('carrier', 'POSTNL');

    expect($carrier->options->getNoTracking())->not->toBeNull()
        ->and($carrier->options->getNoTracking()->getIsSelectedByDefault())->toBeFalse();
});

it('reports failure without throwing when the account cannot be refreshed', function () {
    // The forced account refresh calls the API, so it can fail on its own before the carrier definitions
    // are ever asked for. That path has to report failure too, rather than abort the upgrade.
    mockPdkProperties([
        PdkAccountRepositoryInterface::class => new class {
            public function getAccount(bool $fresh = false)
            {
                throw new RuntimeException('Accounts endpoint unavailable');
            }
        },
    ]);

    $migration = loadRefreshMigration();

    $migration->up();

    expect($migration->hasFailed())->toBeTrue();
});

it('reports failure without throwing when fetching carrier definitions fails', function () {
    TestBootstrapper::hasAccount();
    // The migration forces an account refresh, which calls the accounts endpoint. Without a queued
    // response the mock throws, which is how the earlier version of this test passed without ever
    // reaching the repository below.
    MockApi::enqueue(new ExampleGetAccountsResponse());

    $throwingRepo = new class(
        Pdk::get(StorageInterface::class),
        Pdk::get(CapabilitiesService::class)
    ) extends CarrierCapabilitiesRepository {
        public function getContractDefinitions(?string $carrier = null, bool $fresh = false): CarrierCollection
        {
            throw new RuntimeException('API unavailable');
        }
    };

    mockPdkProperties([CarrierCapabilitiesRepository::class => $throwingRepo]);

    $migration = loadRefreshMigration();

    // Throwing would abort the upgrade and have the shop retry a fatal on every load. Reporting failure
    // keeps the migration unrecorded, so the work is attempted again without blocking anything.
    $migration->up();

    expect($migration->hasFailed())->toBeTrue();
});
