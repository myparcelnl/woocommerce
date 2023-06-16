<?php

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Migration;

use MyParcelNL\Pdk\App\Api\Backend\PdkBackendActions;
use MyParcelNL\Pdk\Facade\Actions;
use MyParcelNL\Pdk\Facade\Settings;
use MyParcelNL\WooCommerce\Migration\Pdk\AbstractPdkMigration;
use MyParcelNL\WooCommerce\Migration\Pdk\OrdersMigration;
use MyParcelNL\WooCommerce\Migration\Pdk\ProductSettingsMigration;
use MyParcelNL\WooCommerce\Migration\Pdk\SettingsMigration;
use MyParcelNL\Pdk\Settings\Model\AccountSettings;
use Symfony\Component\HttpFoundation\Request;

/**
 * The PDK upgrade.
 */
final class Migration5_0_0 extends AbstractPdkMigration
{
    /**
     * @var array<class-string<\MyParcelNL\Pdk\App\Installer\Contract\MigrationInterface>>
     */
    private $migrations;

    /**
     * @param  \MyParcelNL\WooCommerce\Migration\Pdk\SettingsMigration        $settingsMigration
     * @param  \MyParcelNL\WooCommerce\Migration\Pdk\OrdersMigration          $ordersMigration
     * @param  \MyParcelNL\WooCommerce\Migration\Pdk\ProductSettingsMigration $productSettingsMigration
     */
    public function __construct(
        SettingsMigration        $settingsMigration,
        OrdersMigration          $ordersMigration,
        ProductSettingsMigration $productSettingsMigration
    ) {
        $this->migrations = [
            $settingsMigration,
            $ordersMigration,
            $productSettingsMigration,
        ];
    }

    public function down(): void
    {
        /** @var \MyParcelNL\Pdk\App\Installer\Contract\MigrationInterface $migration */
        foreach ($this->migrations as $migration) {
            $migration->down();
        }
    }

    public function up(): void
    {
        /** @var \MyParcelNL\Pdk\App\Installer\Contract\MigrationInterface $migration */
        foreach ($this->migrations as $migration) {
            $migration->up();
        }

        $apiKey = Settings::get(AccountSettings::API_KEY, AccountSettings::ID);

        $request = new Request();
        $request->initialize(
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

        Actions::execute($request);
    }
}
