<?php

declare(strict_types=1);

use MyParcelNL\Pdk\App\Account\Contract\PdkAccountRepositoryInterface;
use MyParcelNL\Pdk\App\Installer\Migration\AbstractTimestampedMigration;
use MyParcelNL\Pdk\Base\Support\Collection;
use MyParcelNL\Pdk\Facade\Logger;
use MyParcelNL\Pdk\Facade\Pdk;
use MyParcelNL\Pdk\SdkApi\Service\CoreApiPrivate\ShippingRule\ImplicationsService;
use MyParcelNL\Pdk\SdkApi\Service\Iam\WhoamiService;
use MyParcelNL\Pdk\Settings\Contract\PdkSettingsRepositoryInterface;

/**
 * Restores plugin-managed account data that was lost during a forced account refresh.
 */
return new class extends AbstractTimestampedMigration {
    public function up(): void
    {
        /** @var PdkSettingsRepositoryInterface $settingsRepository */
        $settingsRepository = Pdk::get(PdkSettingsRepositoryInterface::class);
        $accountSettings    = $settingsRepository->all()->account;

        if (! $accountSettings->apiKey || ! $accountSettings->apiKeyValid) {
            return;
        }

        /** @var PdkAccountRepositoryInterface $accountRepository */
        $accountRepository = Pdk::get(PdkAccountRepositoryInterface::class);
        $account           = $accountRepository->getAccount();

        if (! $account) {
            return;
        }

        $hasChanges = false;
        $shop       = $account->shops->first();

        $hasAvailableDefaultCarrier = $shop
            && $shop->defaultCarrier
            && $shop->carriers->contains('carrier', $shop->defaultCarrier);

        if ($shop && ! $hasAvailableDefaultCarrier) {
            if (! $shop->id) {
                Logger::warning('Cannot restore default carrier: no shop id is available.');
            } else {
                /** @var ImplicationsService $implicationsService */
                $implicationsService = Pdk::get(ImplicationsService::class);
                $defaultCarrier      = $implicationsService->getDefaultCarrierName($shop->id);

                if (null !== $defaultCarrier && $shop->carriers->contains('carrier', $defaultCarrier)) {
                    $shop->defaultCarrier = $defaultCarrier;
                    $hasChanges           = true;
                } else {
                    Logger::warning('Cannot safely restore the shop default carrier.', [
                        'shopId'  => $shop->id,
                        'carrier' => $defaultCarrier,
                    ]);
                }
            }
        }

        if ($account->subscriptionFeatures->isEmpty()) {
            try {
                /** @var WhoamiService $whoamiService */
                $whoamiService                 = Pdk::get(WhoamiService::class);
                $whoami                        = $whoamiService->getWhoami();
                $account->subscriptionFeatures = new Collection($whoami->getFeatures() ?? []);
                $hasChanges                    = true;
            } catch (\Throwable $exception) {
                // Keep the migration pending so a temporary IAM failure can be retried.
                $this->markFailed('Cannot restore subscription features.', [
                    'message' => $exception->getMessage(),
                    'class'   => get_class($exception),
                ]);
            }
        }

        if ($hasChanges) {
            $accountRepository->store($account);
        }
    }
};
