<?php

/** @noinspection StaticClosureCanBeUsedInspection */

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Migration;

use MyParcelNL\Pdk\App\Account\Contract\PdkAccountRepositoryInterface;
use MyParcelNL\Pdk\App\Installer\Contract\TimestampedMigrationInterface;
use MyParcelNL\Pdk\Base\Support\Collection;
use MyParcelNL\Pdk\Carrier\Collection\CarrierCollection;
use MyParcelNL\Pdk\Carrier\Repository\CarrierCapabilitiesRepository;
use MyParcelNL\Pdk\Facade\Pdk;
use MyParcelNL\Pdk\SdkApi\Service\CoreApi\Shipment\CapabilitiesService;
use MyParcelNL\Pdk\Settings\Contract\PdkSettingsRepositoryInterface;
use MyParcelNL\Pdk\Storage\Contract\StorageInterface;
use MyParcelNL\Pdk\Tests\Api\Response\ExampleGetAccountsResponse;
use MyParcelNL\Pdk\Tests\Bootstrap\MockApi;
use MyParcelNL\Pdk\Tests\Bootstrap\TestBootstrapper;
use MyParcelNL\Pdk\Tests\SdkApi\MockSdkApiHandler;
use MyParcelNL\Pdk\Tests\SdkApi\Response\ExampleContractDefinitionsResponse;
use MyParcelNL\Pdk\Tests\Uses\UsesSdkApiMock;
use MyParcelNL\WooCommerce\Tests\Uses\UsesMockWcPdkInstance;
use RuntimeException;
use function MyParcelNL\Pdk\Tests\mockPdkProperties;
use function MyParcelNL\Pdk\Tests\usesShared;

usesShared(new UsesMockWcPdkInstance(), new UsesSdkApiMock());

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

    // A fresh install has no stored account to refresh.
    loadRefreshCarrierCapabilitiesMigration()->up();

    expect($accountRepo->getAccount())->toBeNull();
});

it('skips without failing when the api key is invalid', function () {
    TestBootstrapper::hasAccount();

    /** @var PdkSettingsRepositoryInterface $settingsRepository */
    $settingsRepository            = Pdk::get(PdkSettingsRepositoryInterface::class);
    $accountSettings               = $settingsRepository->all()->account;
    $accountSettings->apiKeyValid = false;
    $settingsRepository->storeSettings($accountSettings);

    $migration = loadRefreshCarrierCapabilitiesMigration();
    $migration->up();

    expect($migration->hasFailed())->toBeFalse();
});

it('preserves local account data while refreshing carrier capabilities', function () {
    TestBootstrapper::hasAccount();

    /** @var PdkAccountRepositoryInterface $accountRepo */
    $accountRepo = Pdk::get(PdkAccountRepositoryInterface::class);
    $account     = $accountRepo->getAccount();
    $shop        = $account->shops->first();

    $shop->defaultCarrier          = 'DHL_FOR_YOU';
    $account->subscriptionFeatures = new Collection(['some_feature']);
    $accountRepo->store($account);

    // A forced account refresh would consume this response and lose both local fields.
    MockApi::enqueue(new ExampleGetAccountsResponse());
    MockSdkApiHandler::enqueue(new ExampleContractDefinitionsResponse());

    loadRefreshCarrierCapabilitiesMigration()->up();

    $storedAccount = $accountRepo->getAccount();
    $storedShop    = $storedAccount->shops->first();

    expect($storedShop->defaultCarrier)->toBe('DHL_FOR_YOU')
        ->and($storedAccount->subscriptionFeatures->toArray())->toBe(['some_feature'])
        ->and($storedShop->carriers->first()->carrier)->toBe('POSTNL')
        ->and($storedShop->carriers->contains('carrier', 'DHL_FOR_YOU'))->toBeTrue()
        ->and(MockApi::getLastRequest())->toBeNull();
});

it('reports failure instead of throwing when fetching carrier definitions fails', function () {
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

    $migration = loadRefreshCarrierCapabilitiesMigration();
    $migration->up();

    // Reporting failure keeps the migration out of applied_migrations, so it is attempted again
    // on the next load. Reaching this line at all is the other half of the point: a carrier API
    // that is briefly unavailable no longer takes the page down with it.
    expect($migration->hasFailed())->toBeTrue();
});

dataset('insurance shapes from the api', [
    // What the API sends today. The nested wrapper carries different amounts, so if the wrong
    // set of limits ever survived, the assertions below would catch it.
    'flat limits alongside the deprecated nested wrapper' => [
        [
            'min'           => ['amount' => 0, 'currency' => 'EUR'],
            'max'           => ['amount' => 500_000, 'currency' => 'EUR'],
            'default'       => ['amount' => 0, 'currency' => 'EUR'],
            'insuredAmount' => [
                'min'     => ['amount' => 1, 'currency' => 'EUR'],
                'max'     => ['amount' => 2, 'currency' => 'EUR'],
                'default' => ['amount' => 3, 'currency' => 'EUR'],
            ],
        ],
    ],
    // What the API sends once the nested wrapper is removed.
    'flat limits only'                                   => [
        [
            'min'     => ['amount' => 0, 'currency' => 'EUR'],
            'max'     => ['amount' => 500_000, 'currency' => 'EUR'],
            'default' => ['amount' => 0, 'currency' => 'EUR'],
        ],
    ],
]);

it('stores only the flat insurance limits', function (array $insurance) {
    TestBootstrapper::hasAccount();
    // Goes through the real CapabilitiesService and repository, so the nested wrapper is
    // dropped by the code that actually does it rather than by a stub.
    MockSdkApiHandler::enqueue(new ExampleContractDefinitionsResponse([
        [
            'carrier'          => 'POSTNL',
            'packageTypes'     => ['PACKAGE'],
            'deliveryTypes'    => ['STANDARD_DELIVERY'],
            'transactionTypes' => ['B2C'],
            'options'          => [
                'insurance' => array_merge(
                    ['isSelectedByDefault' => false, 'isRequired' => false],
                    $insurance
                ),
            ],
        ],
    ]));

    loadRefreshCarrierCapabilitiesMigration()->up();

    /** @var PdkAccountRepositoryInterface $accountRepo */
    $accountRepo = Pdk::get(PdkAccountRepositoryInterface::class);
    $carrier     = $accountRepo->getAccount()
        ->shops->first()
        ->carriers->firstWhere('carrier', 'POSTNL');
    $stored      = $carrier->options->getInsurance();

    // Same stored result either way: the flat limits, and no nested wrapper left behind.
    expect($stored->getInsuredAmount())->toBeNull()
        ->and($stored->getMin()->getAmount())->toBe(0)
        ->and($stored->getMax()->getAmount())->toBe(500_000)
        ->and($stored->getDefault()->getAmount())->toBe(0);
})->with('insurance shapes from the api');
