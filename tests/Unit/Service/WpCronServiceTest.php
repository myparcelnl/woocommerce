<?php
/** @noinspection StaticClosureCanBeUsedInspection */

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Service;

use InvalidArgumentException;
use MyParcelNL\Pdk\Base\Contract\CronServiceInterface;
use MyParcelNL\Pdk\Facade\Pdk;
use MyParcelNL\WooCommerce\Tests\Mock\MockCallableClass;
use MyParcelNL\WooCommerce\Tests\Mock\WordPressScheduledTasks;
use MyParcelNL\WooCommerce\Tests\Uses\UsesMockWcPdkInstance;
use function MyParcelNL\Pdk\Tests\usesShared;

usesShared(new UsesMockWcPdkInstance());

it('dispatches jobs', function () {
    /** @var \MyParcelNL\WooCommerce\Tests\Mock\WordPressScheduledTasks $tasks */
    $tasks = Pdk::get(WordPressScheduledTasks::class);
    /** @var \MyParcelNL\Pdk\Base\Contract\CronServiceInterface $cronService */
    $cronService = Pdk::get(CronServiceInterface::class);

    $cronService->dispatch([new MockCallableClass(),'updateOption'], 'arg1', 'arg2');

    expect(get_option('arg1'))->toBe('arg2');
});

it('schedules jobs', function ($callback) {
    /** @var \MyParcelNL\WooCommerce\Tests\Mock\WordPressScheduledTasks $tasks */
    $tasks = Pdk::get(WordPressScheduledTasks::class);

    /** @var \MyParcelNL\Pdk\Base\Contract\CronServiceInterface $cronService */
    $cronService = Pdk::get(CronServiceInterface::class);

    $dispatchTimestamp = time() + 1000;

    $cronService->schedule($callback, $dispatchTimestamp, 'arg1', 'arg2');

    $task = $tasks
        ->all()
        ->first();

    $actions = get_option(Pdk::get('webhookAddActions'), []);
    $keys    = array_keys($actions);

    expect($actions)
        ->toHaveLength(count($actions))
        ->and($tasks->all())
        ->toHaveLength(1)
        ->and(Pdk::get('webhookActionName') . $task['callback'])
        ->toBe(end($keys))
        ->and($task['time'])
        ->toBeLessThanOrEqual($dispatchTimestamp + 5)
        ->and($task['time'])
        ->toBeGreaterThanOrEqual($dispatchTimestamp - 5)
        ->and($task['args'])
        ->toBe(['arg1', 'arg2']);

    unset($actions[Pdk::get('webhookActionName') . $task['callback']]);
    update_option(Pdk::get('webhookAddActions'), $actions);
})->with('callbacks');

it('throws exception when input is not a string or array', function () {
    /** @var \MyParcelNL\Pdk\Base\Contract\CronServiceInterface $cronService */
    $cronService = Pdk::get(CronServiceInterface::class);

    $cronService->dispatch(static function () {
        return 'test';
    }, 'arg', true);

    update_option(Pdk::get('webhookAddActions'), []);
})->throws(InvalidArgumentException::class);
