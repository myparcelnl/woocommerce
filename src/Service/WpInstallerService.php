<?php

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Service;

use MyParcelNL\Pdk\App\Installer\Service\InstallerService;
use MyParcelNL\Pdk\Facade\Pdk;
use MyParcelNL\Pdk\Settings\Contract\SettingsRepositoryInterface;

class WpInstallerService extends InstallerService
{
    /**
     * @return null|string
     */
    protected function getInstalledVersion(): ?string
    {
        $installedVersion =  parent::getInstalledVersion();

        if (null === $installedVersion) {
            $installedVersion = get_option('woocommerce_myparcel_version') ?: null;
        }

        return $installedVersion;
    }
}
