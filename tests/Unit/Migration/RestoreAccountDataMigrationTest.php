<?php

/** @noinspection StaticClosureCanBeUsedInspection */

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Migration;

use MyParcelNL\Pdk\Account\Contract\AccountFeaturesServiceInterface;
use MyParcelNL\Pdk\Account\Service\PdkAccountFeaturesService;
use MyParcelNL\Pdk\App\Account\Contract\PdkAccountRepositoryInterface;
use MyParcelNL\Pdk\App\Installer\Contract\TimestampedMigrationInterface;
use MyParcelNL\Pdk\Base\Support\Collection;
use MyParcelNL\Pdk\Carrier\Collection\CarrierCollection;
use MyParcelNL\Pdk\Carrier\Model\Carrier;
use MyParcelNL\Pdk\Facade\AccountSettings;
use MyParcelNL\Pdk\Facade\Pdk;
use MyParcelNL\Pdk\SdkApi\Service\Iam\WhoamiService;
use MyParcelNL\Pdk\Settings\Contract\PdkSettingsRepositoryInterface;
use MyParcelNL\Pdk\Tests\Bootstrap\MockImplicationsService;
use MyParcelNL\Pdk\Tests\Bootstrap\MockWhoamiService;
use MyParcelNL\Pdk\Tests\Bootstrap\TestBootstrapper;
use MyParcelNL\Pdk\Tests\Uses\UsesSdkApiMock;
use MyParcelNL\Sdk\Client\Generated\IamApi\Model\FixedPrincipal;
use MyParcelNL\WooCommerce\Tests\Uses\UsesMockWcPdkInstance;
use RuntimeException;
use function MyParcelNL\Pdk\Tests\factory;
use function MyParcelNL\Pdk\Tests\mockPdkProperties;
use function MyParcelNL\Pdk\Tests\usesShared;

usesShared(new UsesMockWcPdkInstance(), new UsesSdkApiMock());

beforeEach(function () {
    MockWhoamiService::reset();
});

afterEach(function () {
    MockWhoamiService::reset();
});

function loadRestoreAccountDataMigration(): TimestampedMigrationInterface
{
    return require __DIR__ . '/../../../src/Migration/2026_09_03_130123_restore_account_data.php';
}

function seedAccountForAccountDataMigration(
    ?string $defaultCarrier,
    array $subscriptionFeatures = []
): PdkAccountRepositoryInterface
{
    TestBootstrapper::hasAccount();

    /** @var PdkAccountRepositoryInterface $accountRepository */
    $accountRepository = Pdk::get(PdkAccountRepositoryInterface::class);
    $account           = $accountRepository->getAccount();
    $shop              = $account->shops->first();

    $account->subscriptionFeatures = new Collection($subscriptionFeatures);
    $shop->id                      = 2100;
    $shop->defaultCarrier          = $defaultCarrier;
    $shop->carriers                = factory(CarrierCollection::class)
        ->push(
            factory(Carrier::class)->fromPostNL(),
            factory(Carrier::class)->fromDhlForYou()
        )
        ->make();

    $accountRepository->store($account);

    return $accountRepository;
}

it('is a timestamped migration the installer can discover', function () {
    $migration = loadRestoreAccountDataMigration();
    $migration->setIdentity('2026_09_03_130123_restore_account_data');

    expect($migration)->toBeInstanceOf(TimestampedMigrationInterface::class)
        ->and($migration->getId())->toBe('2026_09_03_130123_restore_account_data');
});

it('restores the implied carrier once and preserves existing subscription features', function () {
    $accountRepository = seedAccountForAccountDataMigration(null, ['existing_feature']);

    MockImplicationsService::setDefaultCarrierName('DHL_FOR_YOU');

    $migration = loadRestoreAccountDataMigration();
    $migration->up();
    $migration->up();

    $account = $accountRepository->getAccount();
    $shop    = $account->shops->first();

    expect($shop->carriers->first()->carrier)->toBe('POSTNL')
        ->and($shop->defaultCarrier)->toBe('DHL_FOR_YOU')
        ->and($account->subscriptionFeatures->toArray())->toBe(['existing_feature'])
        ->and(MockImplicationsService::getCallCount())->toBe(1);
});

it('preserves an available default carrier and restores subscription features', function () {
    $accountRepository = seedAccountForAccountDataMigration('POSTNL');

    MockImplicationsService::setDefaultCarrierName('DHL_FOR_YOU');
    MockWhoamiService::withFeatures([
        PdkAccountFeaturesService::FEATURE_LEGACY_ORDER_MANAGEMENT,
    ]);

    loadRestoreAccountDataMigration()->up();

    $account = $accountRepository->getAccount();

    expect($account->shops->first()->defaultCarrier)->toBe('POSTNL')
        ->and($account->subscriptionFeatures->toArray())->toBe([
            PdkAccountFeaturesService::FEATURE_LEGACY_ORDER_MANAGEMENT,
        ])
        ->and(AccountSettings::getEffectiveOrderMode())->toBe(AccountFeaturesServiceInterface::ORDER_MODE_V1)
        ->and(MockImplicationsService::getCallCount())->toBe(0);
});

it('retries subscription features after a temporary whoami failure', function () {
    $accountRepository = seedAccountForAccountDataMigration(null);

    MockImplicationsService::setDefaultCarrierName('DHL_FOR_YOU');

    $throwingWhoamiService = new class extends WhoamiService {
        public function __construct()
        {
        }

        public function getWhoami(): FixedPrincipal
        {
            throw new RuntimeException('IAM unavailable');
        }
    };

    $restoreWhoamiService = mockPdkProperties([WhoamiService::class => $throwingWhoamiService]);

    $failedMigration = loadRestoreAccountDataMigration();
    $failedMigration->up();

    $accountAfterFailure = $accountRepository->getAccount();

    expect($failedMigration->hasFailed())->toBeTrue()
        ->and($accountAfterFailure->shops->first()->defaultCarrier)->toBe('DHL_FOR_YOU')
        ->and($accountAfterFailure->subscriptionFeatures->isEmpty())->toBeTrue();

    $restoreWhoamiService();
    MockWhoamiService::withFeatures([
        PdkAccountFeaturesService::FEATURE_LEGACY_ORDER_MANAGEMENT,
    ]);

    $retryMigration = loadRestoreAccountDataMigration();
    $retryMigration->up();

    expect($retryMigration->hasFailed())->toBeFalse()
        ->and($accountRepository->getAccount()->subscriptionFeatures->toArray())->toBe([
            PdkAccountFeaturesService::FEATURE_LEGACY_ORDER_MANAGEMENT,
        ]);
});

it('replaces an unavailable default carrier with the valid implication', function () {
    $accountRepository = seedAccountForAccountDataMigration('UPS_STANDARD', ['existing_feature']);

    MockImplicationsService::setDefaultCarrierName('DHL_FOR_YOU');

    loadRestoreAccountDataMigration()->up();

    expect($accountRepository->getAccount()->shops->first()->defaultCarrier)->toBe('DHL_FOR_YOU')
        ->and(MockImplicationsService::getCallCount())->toBe(1);
});

it('does not guess a default carrier when no safe match is available', function (?string $resolvedCarrier) {
    $accountRepository = seedAccountForAccountDataMigration(null, ['existing_feature']);

    MockImplicationsService::setDefaultCarrierName($resolvedCarrier);

    $migration = loadRestoreAccountDataMigration();
    $migration->up();

    expect($accountRepository->getAccount()->shops->first()->defaultCarrier)->toBeNull()
        ->and(MockImplicationsService::getCallCount())->toBe(1)
        ->and($migration->hasFailed())->toBeFalse();
})->with([
    'no shipping rule implication' => null,
    'carrier is not available'     => 'UPS_STANDARD',
]);

it('skips when no account is stored', function () {
    loadRestoreAccountDataMigration()->up();

    expect(MockImplicationsService::getCallCount())->toBe(0);
});

it('skips when the api key is invalid', function () {
    seedAccountForAccountDataMigration(null);

    /** @var PdkSettingsRepositoryInterface $settingsRepository */
    $settingsRepository            = Pdk::get(PdkSettingsRepositoryInterface::class);
    $accountSettings               = $settingsRepository->all()->account;
    $accountSettings->apiKeyValid = false;
    $settingsRepository->storeSettings($accountSettings);

    loadRestoreAccountDataMigration()->up();

    expect(MockImplicationsService::getCallCount())->toBe(0);
});
