<?php

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Service;

use MyParcelNL\Pdk\App\Installer\Service\InstallerService;
use MyParcelNL\Pdk\Facade\Pdk;

final class WpInstallerService extends InstallerService
{
    /**
     * @return null|string
     */
    protected function getInstalledVersion(): ?string
    {
        $legacyKey = Pdk::get('legacyInstalledVersionKey');

        return parent::getInstalledVersion() ?? ($legacyKey ? get_option($legacyKey) : null);
    }
}
