<?php
/** @noinspection PhpUnhandledExceptionInspection,StaticClosureCanBeUsedInspection */

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Migration\Pdk;

use MyParcelNL\Pdk\App\Order\Contract\PdkProductRepositoryInterface;
use MyParcelNL\Pdk\Base\Service\CountryCodes;
use MyParcelNL\Pdk\Facade\Pdk;
use MyParcelNL\Pdk\Shipment\Model\DeliveryOptions;
use MyParcelNL\WooCommerce\Tests\Mock\WordPressOptions;
use MyParcelNL\WooCommerce\Tests\Mock\WordPressScheduledTasks;
use MyParcelNL\WooCommerce\Tests\Uses\UsesMockWcPdkInstance;
use function MyParcelNL\Pdk\Tests\usesShared;
use function MyParcelNL\WooCommerce\Tests\createWcProduct;
use function Spatie\Snapshots\assertMatchesJsonSnapshot;

usesShared(new UsesMockWcPdkInstance());

const SHIPPING_CLASS_BBP            = 3;
const SHIPPING_CLASS_DPZ            = 2;
const SHIPPING_CLASS_SAME_AS_PARENT = -1; // -1 is the actual value used in woocommerce

dataset('product settings migration data', [
    'simple product with all settings' => [
        'product'    => [
            'weight' => 2,
            'meta'   => [
                '_myparcel_age_check'         => 'no',
                '_myparcel_country_of_origin' => CountryCodes::CC_BB,
                '_myparcel_hs_code'           => '1234',
                '_virtual'                    => 'no',
                '_weight'                     => 0.30,
            ],
        ],
        'parent'     => null,
        'variations' => [],
    ],

    'simple product with some settings' => [
        'product'    => [
            'meta' => [
                '_myparcel_age_check' => 'yes',
                '_virtual'            => 'no',
                '_weight'             => 0.30,
            ],
        ],
        'parent'     => null,
        'variations' => [],
    ],

    'simple product for mailbox too heavy' => [
        'product'    => [
            'meta' => [
                '_virtual' => 'no',
                '_weight'  => 2.1,
            ],
        ],
        'parent'     => null,
        'variations' => [],
    ],

    'variable product' => [
        'product' => [
            'meta' => [
                '_myparcel_country_of_origin_variation' => CountryCodes::CC_AD,
                '_myparcel_hs_code_variation'           => '9090',
                '_virtual'                              => 'no',
                '_weight'                               => 0.3,
            ],
        ],

        'parent' => [
            'shipping_class' => SHIPPING_CLASS_BBP,
        ],

        'variations' => [
            [
                'shipping_class' => SHIPPING_CLASS_SAME_AS_PARENT,
            ],
        ],
    ],

    'variable product with different parent settings' => [
        'product' => [
            'meta' => [
                '_myparcel_country_of_origin_variation' => CountryCodes::CC_AD,
                '_myparcel_hs_code_variation'           => '5432',
                '_virtual'                              => 'no',
                '_weight'                               => 0.3,
            ],
        ],

        'parent' => [
            'shipping_class' => SHIPPING_CLASS_BBP,

            'meta' => [
                '_myparcel_age_check'         => 'no',
                '_myparcel_country_of_origin' => CountryCodes::CC_BB,
                '_myparcel_hs_code'           => '5432',
                '_virtual'                    => 'no',
                '_weight'                     => 2.1,
            ],
        ],

        'variations' => [
            [
                'shipping_class' => SHIPPING_CLASS_DPZ,
            ],
        ],
    ],
    'product that fits in mailbox a bazillion times'  => [
        'product'    => [
            'weight' => 0.0000001,
        ],
        'parent'     => null,
        'variations' => [],
    ],
]);

it('migrates pre v5.0.0 product settings', function (array $product, ?array $parent, array $variations) {
    /** @var \MyParcelNL\WooCommerce\Migration\Pdk\ProductSettingsMigration $migration */
    $migration = Pdk::get(ProductSettingsMigration::class);

    WordPressOptions::updateOption(SettingsMigration::LEGACY_OPTION_EXPORT_DEFAULTS_SETTINGS, [
        'shipping_methods_package_types' => [
            DeliveryOptions::PACKAGE_TYPE_DIGITAL_STAMP_NAME => ['flat_rate:0'],
            DeliveryOptions::PACKAGE_TYPE_MAILBOX_NAME       => ['flat_rate:0'],
        ],
    ]);

    $wcProduct       = createWcProduct($product);
    $parentWcProduct = $parent ? createWcProduct(array_replace($parent, ['children' => [$wcProduct->get_id()]])) : null;

    $migration->migrateProductSettings([
        'chunk'      => 1,
        'lastChunk'  => 1,
        'productIds' => [$wcProduct->get_id()],
    ]);

    /** @var PdkProductRepositoryInterface $repository */
    $repository = Pdk::get(PdkProductRepositoryInterface::class);

    $meta = array_map(function ($metaData) {
        return $metaData->get_data();
    }, $wcProduct->get_meta_data());

    $pdkProduct       = $repository->getProduct($wcProduct->get_id());
    $parentPdkProduct = $parentWcProduct
        ? $repository->getProduct($parentWcProduct->get_id())
        : null;

    assertMatchesJsonSnapshot(
        json_encode([
            'meta'          => $meta,
            'product'       => $pdkProduct->settings->toArrayWithoutNull(),
            'parentProduct' => $parentPdkProduct ? $parentPdkProduct->settings->toArrayWithoutNull() : null,
        ])
    );
})->with('product settings migration data');

it('schedules product migration in chunks', function () {
    /** @var \MyParcelNL\WooCommerce\Tests\Mock\WordPressScheduledTasks $tasks */
    $tasks = Pdk::get(WordPressScheduledTasks::class);
    /** @var \MyParcelNL\WooCommerce\Migration\Pdk\ProductSettingsMigration $productSettingsMigration */
    $productSettingsMigration = Pdk::get(ProductSettingsMigration::class);

    for ($i = 42331; $i < 42534; $i++) {
        createWcProduct(['id' => $i, 'children' => []]);
    }

    $productSettingsMigration->up();

    $allTasks = $tasks->all();

    expect($allTasks->count())
        ->toBe(3);

    $timestamps    = $allTasks->pluck('time');
    $chunkArgs     = $allTasks->pluck('args.0.chunk');
    $lastChunkArgs = $allTasks->pluck('args.0.lastChunk');

    foreach ($allTasks as $index => $task) {
        // Expect the chunk counts to be 1, 2, 3, 4 and the "lastChunk" value to be the max chunk count
        expect($chunkArgs[$index])
            ->toBe($index + 1)
            ->and($lastChunkArgs[$index])
            ->toBe(3);

        if (0 === $index) {
            continue;
        }

        // expect each chunk's schedule timestamp to be 5 seconds after the previous chunk's
        expect($task['time'])->toBe($timestamps[$index - 1] + 5);
    }
});

it('migrates a product when legacy default export settings are empty', function () {
    /** @var \MyParcelNL\WooCommerce\Migration\Pdk\ProductSettingsMigration $migration */
    $migration = Pdk::get(ProductSettingsMigration::class);

    WordPressOptions::updateOption(SettingsMigration::LEGACY_OPTION_EXPORT_DEFAULTS_SETTINGS, [
        'shipping_methods_package_types' => [
        ],
    ]);

    $wcProduct = createWcProduct([
        'weight' => 2,
        'meta'   => [
            '_myparcel_age_check'         => 'no',
            '_myparcel_country_of_origin' => CountryCodes::CC_BB,
            '_myparcel_hs_code'           => '1234',
            '_virtual'                    => 'no',
            '_weight'                     => 0.30,
        ],
    ]);

    $migration->migrateProductSettings([
        'chunk'      => 1,
        'lastChunk'  => 1,
        'productIds' => [$wcProduct->get_id()],
    ]);

    $product = Pdk::get(PdkProductRepositoryInterface::class)
        ->getProduct($wcProduct->get_id());

    expect($product)->toBeTruthy();
});

it('migrates a product when cart is null', function () {
    /** @var \MyParcelNL\WooCommerce\Migration\Pdk\ProductSettingsMigration $productSettingsMigration */
    $productSettingsMigration = Pdk::get(ProductSettingsMigration::class);

    $productSettingsMigration->down();

    expect(true)->toBeTrue();
});

