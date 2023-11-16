<?php
/** @noinspection StaticClosureCanBeUsedInspection */

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Service;

use InvalidArgumentException;
use MyParcelNL\Pdk\Base\Contract\CronServiceInterface;
use MyParcelNL\Pdk\Facade\Pdk;
use MyParcelNL\WooCommerce\Tests\Mock\WordPressScheduledTasks;
use MyParcelNL\WooCommerce\Tests\Uses\UsesMockWcPdkInstance;
use function MyParcelNL\Pdk\Tests\usesShared;

usesShared(new UsesMockWcPdkInstance());

it('dispatches jobs', function () {
    /** @var \MyParcelNL\WooCommerce\Tests\Mock\WordPressScheduledTasks $tasks */
    $tasks = Pdk::get(WordPressScheduledTasks::class);
    /** @var \MyParcelNL\Pdk\Base\Contract\CronServiceInterface $cronService */
    $cronService = Pdk::get(CronServiceInterface::class);

    $cronService->dispatch('my_dispatch_func', 'arg1', 2, 'arg3');

    $firstTask = $tasks
        ->all()
        ->first();

    expect($tasks->all())
        ->toHaveLength(1)
        ->and($firstTask['callback'])
        ->toBe('my_dispatch_func')
        ->and($firstTask['time'])
        ->toBeLessThanOrEqual(time() + 5)
        ->and($firstTask['time'])
        ->toBeGreaterThanOrEqual(time() - 5)
        ->and($firstTask['args'])
        ->toBe(['arg1', 2, 'arg3']);
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

    expect(Pdk::get('webhookActionName') . $task['callback'])
        ->toBe($keys[0])
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
})->throws(InvalidArgumentException::class);
