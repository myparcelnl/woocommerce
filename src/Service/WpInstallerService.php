<?php

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Service;

use MyParcelNL\Pdk\Account\Platform;
use MyParcelNL\Pdk\App\Installer\Service\InstallerService;
use MyParcelNL\Pdk\Facade\Pdk;

final class WpInstallerService extends InstallerService
{
    /**
     * @return null|string
     */
    protected function getInstalledVersion(): ?string
    {
        return parent::getInstalledVersion() ?: $this->getLegacyInstalledVersion();
    }

    /**
     * Override because a null version will re-trigger a migration or overwrite all options with defaults
     * when deactivating and re-activating the plugin in WordPress.
     *
     * @param  null|string $version
     *
     * @return void
     */
    protected function updateInstalledVersion(?string $version): void
    {
        if (! $version) {
            return;
        }

        parent::updateInstalledVersion($version);
    }

    /**
     * This is not in the PDK config or the bootstrapper because it's legacy stuff.
     *
     * @return null|string
     */
    private function getLegacyInstalledVersion(): ?string
    {
        return get_option('woocommerce_myparcel_version', null);
    }
}
