<?php

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Service;

use MyParcelNL\Pdk\Account\Platform;
use MyParcelNL\Pdk\App\Installer\Contract\MigrationServiceInterface;
use MyParcelNL\Pdk\App\Installer\Service\InstallerService;
use MyParcelNL\Pdk\Facade\Pdk;
use MyParcelNL\Pdk\Settings\Contract\SettingsRepositoryInterface;
use MyParcelNL\WooCommerce\Pdk\Storage\WpOptionsStorage;

final class WpInstallerService extends InstallerService
{
    /**
     * @var \MyParcelNL\WooCommerce\Pdk\Storage\WpOptionsStorage
     */
    private $wpOptionsStorage;

    public function __construct(
        SettingsRepositoryInterface $settingsRepository,
        MigrationServiceInterface   $migrationService,
        WpOptionsStorage            $wpOptionsStorage
    ) {
        parent::__construct($settingsRepository, $migrationService);
        $this->wpOptionsStorage = $wpOptionsStorage;
    }

    /**
     * @return null|string
     */
    protected function getInstalledVersion(): ?string
    {
        return parent::getInstalledVersion() ?? $this->getLegacyInstalledVersion();
    }

    /**
     * This is not in the PDK config or the bootstrapper because it's legacy stuff.
     *
     * @return null|string
     */
    private function getLegacyInstalledVersion(): ?string
    {
        $legacyKey = $this->getLegacyVersionKey();

        if (! $legacyKey) {
            return null;
        }

        $legacyVersion = $this->wpOptionsStorage->get($legacyKey);

        return $legacyVersion ? (string) $legacyVersion : null;
    }

    /**
     * @return null|string
     */
    private function getLegacyVersionKey(): ?string
    {
        switch (Pdk::get('platform')) {
            case Platform::MYPARCEL_NAME:
                return 'woocommerce_myparcel_version';

            case Platform::SENDMYPARCEL_NAME:
                return 'woocommerce_myparcelbe_version';
        }

        return null;
    }
}
