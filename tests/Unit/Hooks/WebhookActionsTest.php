<?php
/** @noinspection StaticClosureCanBeUsedInspection */

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Hooks;

use MyParcelNL\Pdk\Facade\Pdk;
use MyParcelNL\WooCommerce\Tests\Mock\MockWpActions;
use MyParcelNL\WooCommerce\Tests\Uses\UsesMockWcPdkInstance;
use function MyParcelNL\Pdk\Tests\usesShared;

usesShared(new UsesMockWcPdkInstance());

it('adds webhook actions correctly', function ($callback) {
    $key = Pdk::get('webhookActionName') . md5(uniqid('', true));

    update_option(Pdk::get('webhookAddActions'), [
        $key => $callback,
    ]);

    /** @var \MyParcelNL\WooCommerce\Hooks\WebhookActions $class */
    $class = Pdk::get(WebhookActions::class);
    $class->apply();

    $webhookActions = get_option(Pdk::get('webhookAddActions'));

    expect($webhookActions)
        ->toHaveKey($key)
        ->and($webhookActions[$key])
        ->toBe($callback)
        ->and($key)
        ->toMatch('/^' . Pdk::get('webhookActionName') . '.*/');

    $wpActions = MockWpActions::all();

    expect($wpActions)
        ->toHaveKey($key)
        ->and($wpActions[$key])
        ->toBe([
            [
                'function'     => $callback,
                'priority'     => 10,
                'acceptedArgs' => 1,
            ],
        ]);

    /** @var \MyParcelNL\WooCommerce\Hooks\RanWebhookActions $afterAction */
    $afterAction = Pdk::get(RanWebhookActions::class);
    $afterAction->apply();

    $webhookActions = get_option(Pdk::get('webhookAddActions'));

    expect($webhookActions)
        ->toBeEmpty();
})->with('callbacks');

it('does nothing when null is passed', function () {
    /** @var \MyParcelNL\WooCommerce\Hooks\WebhookActions $class */
    $class = Pdk::get(WebhookActions::class);
    $class->apply();

    $actions = get_option(Pdk::get('webhookAddActions'));

    expect($actions)->toBeEmpty();
});

it('ran but action didnt', function () {
    update_option(Pdk::get('webhookAddActions'), [
        'test' => 'test',
    ]);

    $afterAction = Pdk::get(RanWebhookActions::class);
    $afterAction->apply();

    $webhookActions = get_option(Pdk::get('webhookAddActions'));

    expect($webhookActions)
        ->toHaveKey('test')
        ->and($webhookActions['test'])
        ->toBe('test');
});
