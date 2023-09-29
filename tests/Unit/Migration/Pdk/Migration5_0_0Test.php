<?php

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Migration\Pdk;

use GuzzleHttp\Psr7\Response;
use MyParcelNL\Pdk\Facade\Logger;
use MyParcelNL\Pdk\Facade\Pdk;
use MyParcelNL\Pdk\Facade\Settings;
use MyParcelNL\Pdk\Settings\Model\AccountSettings;
use MyParcelNL\Pdk\Tests\Bootstrap\MockApi;
use MyParcelNL\WooCommerce\Migration\Migration5_0_0;
use MyParcelNL\WooCommerce\Tests\Mock\WordPressOptions;
use MyParcelNL\WooCommerce\Tests\Uses\UsesMockWcPdkInstance;
use function MyParcelNL\Pdk\Tests\factory;
use function MyParcelNL\Pdk\Tests\usesShared;

usesShared(new UsesMockWcPdkInstance());

it('runs up migrations', function () {
    factory(AccountSettings::class)
        ->withApiKey('zomerhoed')
        ->make();
    WordPressOptions::updateOption('woocommerce_myparcel_general_settings', ['api_key' => 'zomerhoed']);

    $migration5 = Pdk::get(Migration5_0_0::class);
    $migration5->up();

    expect(Settings::get(AccountSettings::API_KEY, AccountSettings::ID))->toBe('zomerhoed');
});

it('completes even when api returns error', function () {
    factory(AccountSettings::class)
        ->withApiKey('winterpeen')
        ->make();
    WordPressOptions::updateOption('woocommerce_myparcel_general_settings', ['api_key' => 'winterpeen']);
    MockApi::enqueue(new Response(403, [], '[\'request_id\' => \'1\', \'errors\' => []]'));

    $migration5 = Pdk::get(Migration5_0_0::class);
    $migration5->up();

    expect(end(Logger::getLogs()))->toEqual([
        'level'   => 'warning',
        'message' => '[PDK]: Migration 5.0.0 (PDK) error',
        'context' => [
            'action'    => 'updateAccount',
            'migration' => 'MyParcelNL\WooCommerce\Migration\Migration5_0_0',
            'exception' => 'Request failed. Status code: 403. Errors: ',
        ],
    ]);
});
