<?php

/** @noinspection StaticClosureCanBeUsedInspection */

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Migration;

use MyParcelNL\Pdk\App\Options\Definition\NoTrackingDefinition;
use MyParcelNL\Pdk\App\Order\Contract\PdkProductRepositoryInterface;
use MyParcelNL\Pdk\Facade\Pdk;
use MyParcelNL\Pdk\Types\Service\TriStateService;
use MyParcelNL\WooCommerce\Tests\Uses\UsesMockWcPdkInstance;
use WC_Product;

use function MyParcelNL\Pdk\Tests\usesShared;
use function MyParcelNL\WooCommerce\Tests\wpFactory;

usesShared(new UsesMockWcPdkInstance());

/**
 * Writes the settings meta verbatim, so the legacy key survives. Going through the settings model would
 * drop it, since its attributes come from the option definitions and no longer include it.
 */
function givenProductWithStoredSettings(int $id, array $settings): void
{
    wpFactory(WC_Product::class)
        ->withId($id)
        ->withMeta([Pdk::get('metaKeyProductSettings') => $settings])
        ->make();
}

function migrateProductChunk(array $ids): void
{
    /** @var NoTrackingChunkMigrator $migrator */
    $migrator = Pdk::get(NoTrackingChunkMigrator::class);

    $migrator->migrateProductSettingsChunk(['ids' => $ids, 'chunk' => 1]);
}

function storedNoTracking(int $id): int
{
    /** @var PdkProductRepositoryInterface $productRepository */
    $productRepository = Pdk::get(PdkProductRepositoryInterface::class);

    $key = (new NoTrackingDefinition())->getProductSettingsKey();

    return $productRepository->getProduct($id)->settings->{$key};
}

it('turns tracking on into no tracking off', function () {
    // The merchant wanted tracking on this product, so the opt-out must end up switched off.
    givenProductWithStoredSettings(8001, [NoTrackingChunkMigrator::LEGACY_TRACKED_KEY => TriStateService::ENABLED]);

    migrateProductChunk([8001]);

    expect(storedNoTracking(8001))->toBe(TriStateService::DISABLED);
});

it('turns tracking off into no tracking on', function () {
    // The merchant did not want tracking, so the opt-out must end up switched on.
    givenProductWithStoredSettings(8002, [NoTrackingChunkMigrator::LEGACY_TRACKED_KEY => TriStateService::DISABLED]);

    migrateProductChunk([8002]);

    expect(storedNoTracking(8002))->toBe(TriStateService::ENABLED);
});

it('leaves inherit alone', function () {
    // Inherit means "not set", so inverting it would invent a choice the merchant never made.
    givenProductWithStoredSettings(8003, [NoTrackingChunkMigrator::LEGACY_TRACKED_KEY => TriStateService::INHERIT]);

    migrateProductChunk([8003]);

    expect(storedNoTracking(8003))->toBe(TriStateService::INHERIT);
});

it('leaves products that never stored the option alone', function () {
    givenProductWithStoredSettings(8004, ['exportSignature' => TriStateService::ENABLED]);

    migrateProductChunk([8004]);

    expect(storedNoTracking(8004))->toBe(TriStateService::INHERIT);
});

it('is safe to run twice', function () {
    // A second pass over the same product would otherwise flip tracking back on for a merchant who
    // deliberately switched it off.
    givenProductWithStoredSettings(8005, [NoTrackingChunkMigrator::LEGACY_TRACKED_KEY => TriStateService::DISABLED]);

    migrateProductChunk([8005]);
    migrateProductChunk([8005]);

    expect(storedNoTracking(8005))->toBe(TriStateService::ENABLED);
});

it('converts every product in the chunk', function () {
    givenProductWithStoredSettings(8006, [NoTrackingChunkMigrator::LEGACY_TRACKED_KEY => TriStateService::ENABLED]);
    givenProductWithStoredSettings(8007, [NoTrackingChunkMigrator::LEGACY_TRACKED_KEY => TriStateService::DISABLED]);

    migrateProductChunk([8006, 8007]);

    expect(storedNoTracking(8006))->toBe(TriStateService::DISABLED)
        ->and(storedNoTracking(8007))->toBe(TriStateService::ENABLED);
});

it('does nothing when the chunk holds no ids', function () {
    givenProductWithStoredSettings(8008, [NoTrackingChunkMigrator::LEGACY_TRACKED_KEY => TriStateService::ENABLED]);

    migrateProductChunk([]);

    // Still unset rather than flipped: the migrator converts the ids it was handed, not everything it
    // can find.
    expect(storedNoTracking(8008))->toBe(TriStateService::INHERIT);
});
