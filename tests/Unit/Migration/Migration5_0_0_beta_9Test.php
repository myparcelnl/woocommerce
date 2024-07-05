<?php
/** @noinspection StaticClosureCanBeUsedInspection */

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Migration;

use MyParcelNL\Pdk\Facade\Pdk;
use MyParcelNL\Pdk\Facade\Settings;
use MyParcelNL\Pdk\Settings\Model\CheckoutSettings;
use MyParcelNL\WooCommerce\Tests\Uses\UsesMockWcPdkInstance;
use function MyParcelNL\Pdk\Tests\factory;
use function MyParcelNL\Pdk\Tests\usesShared;

usesShared(new UsesMockWcPdkInstance());

it('migrates allowed shipping methods', function () {
    factory(CheckoutSettings::class)
        ->withAllowedShippingMethods(['flat_rate:1', 'free_shipping:1', 'local_pickup:1'])
        ->store();

    /** @var \MyParcelNL\WooCommerce\Migration\Pdk\SettingsMigration $migration */
    $migration = Pdk::get(Migration5_0_0_beta_9::class);
    $migration->up();

    $allSettings = Settings::all();

    expect($allSettings->checkout->allowedShippingMethods->toArray())->toEqual([
        '-1'            => [],
        'package'       => [
            'flat_rate:1',
            'free_shipping:1',
            'local_pickup:1',
        ],
        'package_small' => [],
        'mailbox'       => [],
        'digital_stamp' => [],
        'letter'        => [],
    ]);
});
