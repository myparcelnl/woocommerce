<?php

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Migration;

use MyParcelNL\Pdk\App\Api\Backend\PdkBackendActions;
use MyParcelNL\Pdk\Facade\Actions;
use MyParcelNL\Pdk\Facade\Settings;
use MyParcelNL\Pdk\Settings\Model\AccountSettings;
use MyParcelNL\WooCommerce\Migration\Pdk\AbstractPdkMigration;
use MyParcelNL\WooCommerce\Migration\Pdk\AuditsMigration;
use MyParcelNL\WooCommerce\Migration\Pdk\OrdersMigration;
use MyParcelNL\WooCommerce\Migration\Pdk\ProductSettingsMigration;
use MyParcelNL\WooCommerce\Migration\Pdk\SettingsMigration;
use Symfony\Component\HttpFoundation\Request;
use Throwable;

/**
 * The PDK upgrade.
 */
final class Migration5_0_0 extends AbstractPdkMigration
{
    /**
     * @var array
     */
    private $migrations;

    /**
     * @param  \MyParcelNL\WooCommerce\Migration\Pdk\SettingsMigration        $settingsMigration
     * @param  \MyParcelNL\WooCommerce\Migration\Pdk\OrdersMigration          $ordersMigration
     * @param  \MyParcelNL\WooCommerce\Migration\Pdk\ProductSettingsMigration $productSettingsMigration
     * @param  \MyParcelNL\WooCommerce\Migration\Pdk\AuditsMigration          $auditsMigration
     */
    public function __construct(
        SettingsMigration        $settingsMigration,
        OrdersMigration          $ordersMigration,
        ProductSettingsMigration $productSettingsMigration,
        AuditsMigration          $auditsMigration
    ) {
        $this->migrations = [
            $settingsMigration,
            $ordersMigration,
            $productSettingsMigration,
            $auditsMigration,
        ];
    }

    /**
     * @return void
     */
    public function down(): void
    {
        /** @var \MyParcelNL\Pdk\App\Installer\Contract\MigrationInterface $migration */
        foreach ($this->migrations as $migration) {
            $migration->down();
        }
    }

    /**
     * @return void
     */
    public function up(): void
    {
        /** @var \MyParcelNL\Pdk\App\Installer\Contract\MigrationInterface $migration */
        foreach ($this->migrations as $migration) {
            $migration->up();
        }

        $apiKey = Settings::get(AccountSettings::API_KEY, AccountSettings::ID);

        $request = new Request(
            [],
            ['action' => PdkBackendActions::UPDATE_ACCOUNT],
            [],
            [],
            [],
            [],
            json_encode([
                'data' => [
                    'account_settings' => [
                        'apiKey' => $apiKey,
                    ],
                ],
            ])
        );

        try {
            Actions::execute($request);
        } catch (Throwable $e) {
            $this->warning(
                'Migration 5.0.0 (PDK) error',
                ['action' => PdkBackendActions::UPDATE_ACCOUNT, 'exception' => $e->getMessage()]
            );
        }
    }
}
