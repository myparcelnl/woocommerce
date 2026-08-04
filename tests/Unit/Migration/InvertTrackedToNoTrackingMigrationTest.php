<?php

/** @noinspection StaticClosureCanBeUsedInspection */

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Migration;

use MyParcelNL\Pdk\App\Installer\Contract\TimestampedMigrationInterface;
use MyParcelNL\Pdk\App\Options\Definition\NoTrackingDefinition;
use MyParcelNL\Pdk\Facade\Pdk;
use MyParcelNL\Pdk\Settings\Contract\PdkSettingsRepositoryInterface;
use MyParcelNL\Pdk\Types\Service\TriStateService;
use MyParcelNL\WooCommerce\Tests\Uses\UsesMockWcPdkInstance;
use RuntimeException;

use function MyParcelNL\Pdk\Tests\mockPdkProperties;
use function MyParcelNL\Pdk\Tests\usesShared;

usesShared(new UsesMockWcPdkInstance());

const MIGRATION_ID = '2026_07_30_153531_invert_tracked_to_no_tracking';

/** The key the option used to be stored under, before NoTrackingDefinition replaced TrackedDefinition. */
const LEGACY_TRACKED_KEY = 'exportTracked';

/**
 * Loads the migration the same way the installer does: require the file and take the returned
 * anonymous-class instance.
 */
function loadInvertMigration(): TimestampedMigrationInterface
{
    return require __DIR__ . '/../../../src/Migration/' . MIGRATION_ID . '.php';
}

function noTrackingKey(): string
{
    return (new NoTrackingDefinition())->getCarrierSettingsKey();
}

function carrierSettingsRepository(): PdkSettingsRepositoryInterface
{
    return Pdk::get(PdkSettingsRepositoryInterface::class);
}

function storeCarrierSettings(array $settings): void
{
    carrierSettingsRepository()->store(Pdk::get('createSettingsKey')('carrier'), $settings);
}

function readCarrierSettings(): array
{
    return carrierSettingsRepository()->get(Pdk::get('createSettingsKey')('carrier')) ?: [];
}

it('is a timestamped migration the installer can discover', function () {
    $migration = loadInvertMigration();

    // The installer injects identity from the filename, so the migration must accept it and report it
    // back rather than deriving one itself.
    $migration->setIdentity(MIGRATION_ID);

    expect($migration)->toBeInstanceOf(TimestampedMigrationInterface::class)
        ->and($migration->getId())->toBe(MIGRATION_ID);
});

it('reports failure without throwing when the settings cannot be read', function () {
    // A failure must not leave the shop unable to finish upgrading. Anything already converted stays
    // converted, because the old key is dropped as each record is written.
    mockPdkProperties([
        PdkSettingsRepositoryInterface::class => new class {
            public function get(string $key)
            {
                throw new RuntimeException('Settings unavailable');
            }
        },
    ]);

    $migration = loadInvertMigration();

    $migration->up();

    expect($migration->hasFailed())->toBeTrue();
});

it('turns tracking on into no tracking off', function () {
    // The merchant wanted tracking, so the opt-out must end up switched off.
    storeCarrierSettings(['POSTNL' => [LEGACY_TRACKED_KEY => TriStateService::ENABLED]]);

    loadInvertMigration()->up();

    expect(readCarrierSettings()['POSTNL'][noTrackingKey()])->toBe(TriStateService::DISABLED);
});

it('turns tracking off into no tracking on', function () {
    // The merchant did not want tracking, so the opt-out must end up switched on.
    storeCarrierSettings(['POSTNL' => [LEGACY_TRACKED_KEY => TriStateService::DISABLED]]);

    loadInvertMigration()->up();

    expect(readCarrierSettings()['POSTNL'][noTrackingKey()])->toBe(TriStateService::ENABLED);
});

it('leaves inherit alone', function () {
    // Inherit means "not set". Inverting it would invent a choice the merchant never made.
    storeCarrierSettings(['POSTNL' => [LEGACY_TRACKED_KEY => TriStateService::INHERIT]]);

    loadInvertMigration()->up();

    expect(readCarrierSettings()['POSTNL'][noTrackingKey()])->toBe(TriStateService::INHERIT);
});

it('drops the old key once it has been converted', function () {
    storeCarrierSettings(['POSTNL' => [LEGACY_TRACKED_KEY => TriStateService::ENABLED]]);

    loadInvertMigration()->up();

    expect(readCarrierSettings()['POSTNL'])->not->toHaveKey(LEGACY_TRACKED_KEY);
});

it('is safe to run twice', function () {
    // Running an inversion twice would otherwise flip tracking back on for merchants who deliberately
    // switched it off.
    storeCarrierSettings(['POSTNL' => [LEGACY_TRACKED_KEY => TriStateService::DISABLED]]);

    loadInvertMigration()->up();
    loadInvertMigration()->up();

    expect(readCarrierSettings()['POSTNL'][noTrackingKey()])->toBe(TriStateService::ENABLED);
});

it('leaves carriers without the old key untouched', function () {
    storeCarrierSettings([
        'POSTNL'      => [LEGACY_TRACKED_KEY => TriStateService::ENABLED],
        'DHL_FOR_YOU' => ['exportSignature' => TriStateService::ENABLED],
    ]);

    loadInvertMigration()->up();

    expect(readCarrierSettings()['DHL_FOR_YOU'])->toBe(['exportSignature' => TriStateService::ENABLED]);
});

it('leaves other settings on the same carrier untouched', function () {
    storeCarrierSettings([
        'POSTNL' => [
            LEGACY_TRACKED_KEY => TriStateService::ENABLED,
            'exportSignature'  => TriStateService::ENABLED,
        ],
    ]);

    loadInvertMigration()->up();

    expect(readCarrierSettings()['POSTNL']['exportSignature'])->toBe(TriStateService::ENABLED);
});

it('does nothing when no carrier settings are stored at all', function () {
    loadInvertMigration()->up();

    expect(readCarrierSettings())->toBe([]);
});
