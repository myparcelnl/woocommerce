<?php

declare(strict_types=1);

use MyParcelNL\Pdk\App\Account\Contract\PdkAccountRepositoryInterface;
use MyParcelNL\Pdk\App\Installer\Migration\AbstractTimestampedMigration;
use MyParcelNL\Pdk\Carrier\Repository\CarrierCapabilitiesRepository;
use MyParcelNL\Pdk\Facade\Logger;
use MyParcelNL\Pdk\Facade\Pdk;

/**
 * Re-fetches the stored carrier data so it holds the no tracking option.
 *
 * The capabilities response depends on the feature flag we now send: with it on, the API returns
 * "noTracking" and drops "tracked". Carrier data stored before the flag therefore holds the old option
 * and none of the new one, so the option would count as unavailable and never appear in the settings.
 *
 * Asking for fresh data is the point. The contract definitions are cached, and a cached pre-flag copy
 * would otherwise be re-stored unchanged, which is exactly the state this migration exists to replace.
 */
return new class extends AbstractTimestampedMigration {
    public function up(): void
    {
        /** @var PdkAccountRepositoryInterface $accountRepository */
        $accountRepository = Pdk::get(PdkAccountRepositoryInterface::class);
        $account           = $accountRepository->getAccount(true);
        // PHPStan types Account::$shops as a non-null ShopCollection, but the guard is kept
        // intentionally to stay safe against partial or corrupted account data during an upgrade.
        // @phpstan-ignore booleanAnd.rightAlwaysTrue
        $shop = $account && $account->shops ? $account->shops->first() : null;

        if (! $shop) {
            Logger::debug('No account or shop available; skipping carrier capabilities refresh.');

            return;
        }

        /** @var CarrierCapabilitiesRepository $capabilitiesRepository */
        $capabilitiesRepository = Pdk::get(CarrierCapabilitiesRepository::class);

        try {
            $shop->carriers = $capabilitiesRepository->getContractDefinitions(null, true);
        } catch (Throwable $exception) {
            // Re-throw so the migration is not marked as applied, letting it retry on the next load
            // instead of leaving the carriers without the new option. Until it succeeds the option
            // simply does not appear, which is the safe direction: tracking stays on.
            Logger::warning('Failed to refresh carrier capabilities; migration will retry.', [
                'exception' => $exception->getMessage(),
            ]);

            throw $exception;
        }

        $accountRepository->store($account);
    }
};
