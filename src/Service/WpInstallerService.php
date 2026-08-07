<?php

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Service;

use MyParcelNL\Pdk\App\Installer\Service\InstallerService;

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
     * This is not in the PDK config or the bootstrapper because it's legacy stuff.
     *
     * @return null|string
     */
    private function getLegacyInstalledVersion(): ?string
    {
        // Get the legacy installed version, prioritized by:
        // 1. v5 - nl
        // 2. v5 - be
        // 3. v4
        // Whichever matches first is used
        return get_option('_myparcelnl_installed_version', null) // v5 - nl
            ?? get_option('_myparcelbe_installed_version', null) // v5 - be
            ?? get_option('woocommerce_myparcel_version', null); // v4
    }
}
