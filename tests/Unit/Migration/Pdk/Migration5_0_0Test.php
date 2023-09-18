<?php

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Migration\Pdk;

use GuzzleHttp\Psr7\Response;
use MyParcelNL\Pdk\Facade\Logger;
use MyParcelNL\Pdk\Facade\Pdk;
use MyParcelNL\Pdk\Facade\Settings;
use MyParcelNL\Pdk\Settings\Contract\SettingsRepositoryInterface;
use MyParcelNL\Pdk\Settings\Model\AccountSettings;
use MyParcelNL\Pdk\Tests\Bootstrap\MockApi;
use MyParcelNL\Pdk\Tests\Bootstrap\MockSettingsRepository;
use MyParcelNL\WooCommerce\Migration\Migration5_0_0;
use MyParcelNL\WooCommerce\Tests\Mock\WordPressOptions;
use MyParcelNL\WooCommerce\Tests\Uses\UsesMockWcPdkInstance;
use function DI\autowire;
use function MyParcelNL\Pdk\Tests\usesShared;

usesShared(
    new UsesMockWcPdkInstance([
        SettingsRepositoryInterface::class => autowire(MockSettingsRepository::class)->constructor([
            AccountSettings::ID => [
                AccountSettings::API_KEY => 'winterpeen',
            ],
        ]),
    ])
);

it('runs up migrations', function () {
    WordPressOptions::updateOption('woocommerce_myparcel_general_settings', ['api_key' => 'winterpeen']);

    $migration5 = Pdk::get(Migration5_0_0::class);
    $migration5->up();

    expect(Settings::get(AccountSettings::API_KEY, AccountSettings::ID))->toBe('winterpeen');
});

it('completes even when api returns error', function () {
    WordPressOptions::updateOption('woocommerce_myparcel_general_settings', ['api_key' => 'winterpeen']);
    MockApi::enqueue(new Response(403, [], '[\'request_id\' => \'1\', \'errors\' => []]'));

    $migration5 = Pdk::get(Migration5_0_0::class);
    $migration5->up();

    expect(end(Logger::getLogs()))->toEqual([
        'level'   => 'warning',
        'message' => '[PDK]: Request failed. Status code: 403. Errors: ',
        'context' => [
            'action'    => 'updateAccount',
            'migration' => 'MyParcelNL\WooCommerce\Migration\Migration5_0_0',
        ],
    ]);
});

