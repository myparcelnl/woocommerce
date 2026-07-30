<?php

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Migration;

use MyParcelNL\Pdk\Base\PdkBootstrapper;
use MyParcelNL\WooCommerce\Service\WpOptionNamespaceService;
use Throwable;

final class Migration6_0_0 extends AbstractMigration
{
    /**
     * @var \MyParcelNL\WooCommerce\Service\WpOptionNamespaceService
     */
    private $optionNamespaceService;

    public function __construct(WpOptionNamespaceService $optionNamespaceService)
    {
        $this->optionNamespaceService = $optionNamespaceService;
    }

    public function getVersion(): string
    {
        return '6.0.0';
    }

    public function down(): void
    {
        try {
            $this->optionNamespaceService->migrateOptions(
                PdkBootstrapper::PLUGIN_NAMESPACE,
                'myparcelnl',
                WpOptionNamespaceService::CONFLICT_REPLACE_TARGET
            );
            // We do not support back-migration to 'myparcelbe'. This needs to be done manually if needed.
        } catch (Throwable $e) {
            $this->error('Could not migrate options back to the legacy namespace.', ['error' => $e->getMessage()]);
            // The failure should be visible in the logs, but must not block plugin deactivation.
        }
    }

    public function up(): void
    {
        try {
            // NL has priority when both v5 namespaces are present, matching getLegacyInstalledVersion().
            $this->optionNamespaceService->migrateOptions(
                'myparcelnl',
                PdkBootstrapper::PLUGIN_NAMESPACE,
                WpOptionNamespaceService::CONFLICT_KEEP_TARGET
            );
            $this->optionNamespaceService->migrateOptions(
                'myparcelbe',
                PdkBootstrapper::PLUGIN_NAMESPACE,
                WpOptionNamespaceService::CONFLICT_KEEP_TARGET
            );
        } catch (Throwable $e) {
            $this->error('Could not migrate options to the current namespace.', ['error' => $e->getMessage()]);

            throw $e;
        }
    }
}
