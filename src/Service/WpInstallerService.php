<?php

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Service;

use MyParcelNL\Pdk\App\Installer\Contract\MigrationServiceInterface;
use MyParcelNL\Pdk\App\Installer\Service\InstallerService;
use MyParcelNL\Pdk\Facade\Logger;
use MyParcelNL\Pdk\Settings\Contract\PdkSettingsRepositoryInterface;
use Throwable;

final class WpInstallerService extends InstallerService
{
    /**
     * @var \MyParcelNL\WooCommerce\Service\WpOptionNamespaceService
     */
    private $optionNamespaceService;

    public function __construct(
        PdkSettingsRepositoryInterface $settingsRepository,
        MigrationServiceInterface      $migrationService,
        WpOptionNamespaceService       $optionNamespaceService
    ) {
        parent::__construct($settingsRepository, $migrationService);

        $this->optionNamespaceService = $optionNamespaceService;
    }

    /**
     * Repair a deactivated v6 installation before InstallerService can return on an equal legacy version.
     * On multisite this intentionally repairs only the current site's options table. The existing
     * upgrade hook invokes this method on each site's own request without leaking the PDK memory
     * cache across switch_to_blog() calls.
     *
     * @param  mixed ...$args
     *
     * @return void
     */
    public function install(...$args): void
    {
        try {
            $this->optionNamespaceService->restoreDeactivatedV6Installation();
        } catch (Throwable $e) {
            Logger::error(
                'Could not restore MyParcel settings before installation.',
                ['error' => $e->getMessage()]
            );

            throw $e;
        }

        parent::install(...$args);
    }

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
