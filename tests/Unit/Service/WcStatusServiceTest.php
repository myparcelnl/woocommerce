<?php
/** @noinspection StaticClosureCanBeUsedInspection */

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Service;

use MyParcelNL\Pdk\App\Order\Contract\OrderStatusServiceInterface;
use MyParcelNL\Pdk\Base\Contract\CronServiceInterface;
use MyParcelNL\Pdk\Facade\Pdk;
use MyParcelNL\Pdk\Tests\Uses\UsesMockPdkInstance;
use MyParcelNL\WooCommerce\Pdk\Plugin\Service\WcStatusService;
use MyParcelNL\WooCommerce\Tests\Mock\WordPressScheduledTasks;
use function DI\get;
use function MyParcelNL\Pdk\Tests\usesShared;
use function MyParcelNL\WooCommerce\Tests\createWcOrder;

usesShared(
    new UsesMockPdkInstance([
        OrderStatusServiceInterface::class => get(WcStatusService::class),
    ])
);

it('changes order status', function () {
    /** @var OrderStatusServiceInterface $statusService */
    $statusService = Pdk::get(OrderStatusServiceInterface::class);

    createWcOrder(['id' => 33]);

    $statusService->updateStatus([33], 'completed');

    $updatedOrder = wc_get_order(33);

    expect($updatedOrder->get_status())
        ->toBe('completed');
});

it('schedules jobs', function () {
    /** @var \MyParcelNL\WooCommerce\Tests\Mock\WordPressScheduledTasks $tasks */
    $tasks = Pdk::get(WordPressScheduledTasks::class);
    /** @var \MyParcelNL\Pdk\Base\Contract\CronServiceInterface $cronService */
    $cronService = Pdk::get(CronServiceInterface::class);

    $dispatchTimestamp = time() + 1000;

    $cronService->schedule('my_schedule_func', $dispatchTimestamp, 'arg1', 'arg2');

    $firstTask = $tasks
        ->all()
        ->first();

    expect($tasks->all())
        ->toHaveLength(1)
        ->and($firstTask['callback'])
        ->toBe('my_schedule_func')
        ->and($firstTask['time'])
        ->toBeLessThanOrEqual($dispatchTimestamp + 5)
        ->and($firstTask['time'])
        ->toBeGreaterThanOrEqual($dispatchTimestamp - 5)
        ->and($firstTask['args'])
        ->toBe(['arg1', 'arg2']);
});
