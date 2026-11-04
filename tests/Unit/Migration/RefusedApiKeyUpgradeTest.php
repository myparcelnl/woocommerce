<?php

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Migration;

use MyParcelNL\Pdk\App\Account\Contract\PdkAccountRepositoryInterface;
use MyParcelNL\Pdk\Carrier\Collection\CarrierCollection;
use MyParcelNL\Pdk\Carrier\Repository\CarrierCapabilitiesRepository;
use MyParcelNL\Pdk\Facade\Pdk;
use MyParcelNL\Pdk\SdkApi\Service\CoreApi\Shipment\CapabilitiesService;
use MyParcelNL\Pdk\Settings\Contract\PdkSettingsRepositoryInterface;
use MyParcelNL\Pdk\Storage\Contract\StorageInterface;
use MyParcelNL\Pdk\Tests\Bootstrap\TestBootstrapper;
use MyParcelNL\Pdk\Tests\SdkApi\MockSdkApiHandler;
use MyParcelNL\Pdk\Tests\SdkApi\Response\ExampleContractDefinitionsResponse;
use MyParcelNL\Pdk\Tests\Uses\UsesSdkApiMock;
use MyParcelNL\Sdk\Client\Generated\CoreApi\ApiException;
use MyParcelNL\WooCommerce\Pdk\Plugin\Installer\WcMigrationService;
use MyParcelNL\WooCommerce\Service\WpInstallerService;
use MyParcelNL\WooCommerce\Tests\Uses\UsesMockWcPdkInstance;
use function MyParcelNL\Pdk\Tests\mockPdkProperties;
use function MyParcelNL\Pdk\Tests\usesShared;

usesShared(new UsesMockWcPdkInstance(), new UsesSdkApiMock());

it('retries the carrier fetch on each run while the api refuses the key, without blocking the upgrade', function () {
    $refreshId    = '2026_07_29_092726_refresh_carrier_capabilities';
    $migrationDir = realpath(__DIR__ . '/../../../src/Migration');

    TestBootstrapper::hasAccount();

    $settingsRepository = Pdk::get(PdkSettingsRepositoryInterface::class);
    $appliedKey         = Pdk::get('settingKeyAppliedMigrations');

    $repository = new class(
        Pdk::get(StorageInterface::class),
        Pdk::get(CapabilitiesService::class)
    ) extends CarrierCapabilitiesRepository {
        public bool $refuseKey = true;
        public int  $calls     = 0;

        public function getContractDefinitions(?string $carrier = null, bool $fresh = false): CarrierCollection
        {
            $this->calls++;

            if ($this->refuseKey) {
                throw new ApiException('[401] Client error', 401);
            }

            return parent::getContractDefinitions($carrier, $fresh);
        }
    };

    $restore = mockPdkProperties([
        'migrationDirectory'                 => $migrationDir,
        CarrierCapabilitiesRepository::class => $repository,
    ]);

    // An upgrade from 6.5.0 on which everything except Migration6_5_1 and the refresh is already applied.
    $migrationService = new WcMigrationService();
    $timestampedIds   = array_map(static function (string $path): string {
        return pathinfo($path, PATHINFO_FILENAME);
    }, glob($migrationDir . '/2*.php'));

    $settingsRepository->store(Pdk::get('settingKeyInstalledVersion'), '6.5.0');
    $settingsRepository->store($appliedKey, array_values(array_diff(
        array_merge($migrationService->getUpgradeMigrations(), $timestampedIds),
        [Migration6_5_1::class, $refreshId]
    )));

    $installer = new WpInstallerService($settingsRepository, $migrationService);

    // First request: Migration6_5_1 is recorded, the refresh migration fails and stays pending.
    $installer->install();

    expect($settingsRepository->get($appliedKey))
        ->toContain(Migration6_5_1::class)
        ->not->toContain($refreshId)
        ->and($repository->calls)->toBe(1)
        ->and($installer->hasPendingMigrations())->toBeTrue();

    // Second request: the key is still refused, so only the refresh migration tries again.
    $installer->install();

    expect($settingsRepository->get($appliedKey))->not->toContain($refreshId)
        ->and($repository->calls)->toBe(2);

    // Third request: the key works again, so the refresh migration stores the carriers and is recorded.
    $repository->refuseKey = false;
    MockSdkApiHandler::enqueue(new ExampleContractDefinitionsResponse());

    $installer->install();

    $carriers = Pdk::get(PdkAccountRepositoryInterface::class)
        ->getAccount()
        ->shops->first()->carriers;

    expect($settingsRepository->get($appliedKey))->toContain($refreshId)
        ->and($repository->calls)->toBe(3)
        ->and($installer->hasPendingMigrations())->toBeFalse()
        ->and($carriers->contains('carrier', 'POSTNL'))->toBeTrue();

    $restore();
});
