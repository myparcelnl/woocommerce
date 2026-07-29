<?php

declare(strict_types=1);

use MyParcelNL\Pdk\App\Account\Contract\PdkAccountRepositoryInterface;
use MyParcelNL\Pdk\App\Installer\Migration\AbstractTimestampedMigration;
use MyParcelNL\Pdk\Carrier\Repository\CarrierCapabilitiesRepository;
use MyParcelNL\Pdk\Facade\Logger;
use MyParcelNL\Pdk\Facade\Pdk;

/**
 * Re-fetches the stored carrier data so insurance limits are in the flat format.
 *
 * Carrier data stored before this release holds insurance limits in the nested wrapper the
 * MyParcel API is removing. The PDK now reads the flat limits, which are absent from that older
 * data, so insurance would be unavailable until something refreshed it. Fetching the contract
 * definitions again rewrites the stored carriers in the shape the PDK expects.
 */
return new class extends AbstractTimestampedMigration {
    public function up(): void
    {
        /** @var PdkAccountRepositoryInterface $accountRepository */
        $accountRepository = Pdk::get(PdkAccountRepositoryInterface::class);
        $account           = $accountRepository->getAccount(true);
        // PHPStan types Account::$shops as a non-null ShopCollection, but the guard is kept
        // intentionally to stay safe against partial/corrupted account data during upgrade.
        // @phpstan-ignore booleanAnd.rightAlwaysTrue
        $shop = $account && $account->shops ? $account->shops->first() : null;

        if (! $shop) {
            Logger::debug('No account or shop available; skipping carrier capabilities refresh.');

            return;
        }

        /** @var CarrierCapabilitiesRepository $capabilitiesRepository */
        $capabilitiesRepository = Pdk::get(CarrierCapabilitiesRepository::class);

        try {
            $shop->carriers = $capabilitiesRepository->getContractDefinitions();
        } catch (Throwable $exception) {
            // Re-throw so the migration is not marked as applied, letting it retry on the next
            // load instead of leaving the carriers on the old shape.
            Logger::warning('Failed to refresh carrier capabilities; migration will retry.', [
                'exception' => $exception->getMessage(),
            ]);

            throw $exception;
        }

        $accountRepository->store($account);
    }
};
