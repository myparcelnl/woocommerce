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
            // Reporting failure leaves the migration unrecorded, so it is attempted again on the
            // next load. Throwing would do that too, but it would take the page down with it.
            $this->markFailed('Failed to refresh carrier capabilities.', [
                'message' => $exception->getMessage(),
                'file'    => $exception->getFile() . ':' . $exception->getLine(),
                'class'   => get_class($exception),
                'trace'   => $exception->getTraceAsString(),
            ]);

            return;
        }

        $accountRepository->store($account);
    }
};
