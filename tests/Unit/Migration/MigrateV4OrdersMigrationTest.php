<?php

/** @noinspection StaticClosureCanBeUsedInspection */

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Migration\V4Orders;

use MyParcelNL\Pdk\App\Installer\Contract\TimestampedMigrationInterface;
use MyParcelNL\Pdk\Facade\Pdk;
use MyParcelNL\WooCommerce\Migration\Pdk\OrdersMigration;
use MyParcelNL\WooCommerce\Tests\Mock\WordPressScheduledTasks;
use MyParcelNL\WooCommerce\Tests\Uses\UsesMockWcPdkInstance;
use function MyParcelNL\Pdk\Tests\usesShared;
use function MyParcelNL\WooCommerce\Tests\createWcOrder;

usesShared(new UsesMockWcPdkInstance());

/**
 * Loads the migration the same way the installer does.
 */
function loadMigrateV4OrdersMigration(): TimestampedMigrationInterface
{
    return require __DIR__ . '/../../../src/Migration/2026_11_09_120000_migrate_v4_orders.php';
}

it('is a timestamped migration the installer can discover', function () {
    $migration = loadMigrateV4OrdersMigration();

    $migration->setIdentity('2026_11_09_120000_migrate_v4_orders');

    expect($migration)->toBeInstanceOf(TimestampedMigrationInterface::class)
        ->and($migration->getId())->toBe('2026_11_09_120000_migrate_v4_orders');
});

it('schedules the order migration again for orders with 4.x data', function () {
    createWcOrder(['id' => 101, 'meta' => [OrdersMigration::LEGACY_META_SHIPMENTS => '{}']]);
    createWcOrder(['id' => 102, 'meta' => [OrdersMigration::LEGACY_META_DELIVERY_OPTIONS => '{}']]);
    createWcOrder(['id' => 103]);

    loadMigrateV4OrdersMigration()->up();

    $tasks = Pdk::get(WordPressScheduledTasks::class)->all();

    expect($tasks->count())->toBe(1)
        ->and($tasks->first()['callback'])->toBe(Pdk::get('migrateAction_5_0_0_Orders'))
        ->and($tasks->first()['args'][0]['orderIds'])->toBe([101, 102]);
});
