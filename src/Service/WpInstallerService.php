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
        $legacyKey = $this->getLegacyVersionKey();

        if (! $legacyKey) {
            return null;
        }

        $legacyVersion = get_option($legacyKey, null);

        return $legacyVersion ? (string) $legacyVersion : null;
    }

    /**
     * @return null|string
     * @noinspection MultipleReturnStatementsInspection
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
