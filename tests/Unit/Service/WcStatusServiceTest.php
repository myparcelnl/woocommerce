<?php
/** @noinspection StaticClosureCanBeUsedInspection */

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Service;

use MyParcelNL\Pdk\App\Order\Contract\OrderStatusServiceInterface;
use MyParcelNL\Pdk\Facade\Pdk;
use MyParcelNL\Pdk\Tests\Uses\UsesMockPdkInstance;
use MyParcelNL\WooCommerce\Pdk\Plugin\Service\WcStatusService;
use WC_Order;
use function DI\get;
use function MyParcelNL\Pdk\Tests\usesShared;
use function MyParcelNL\WooCommerce\Tests\wpFactory;

usesShared(
    new UsesMockPdkInstance([
        OrderStatusServiceInterface::class => get(WcStatusService::class),
    ])
);

it('changes order status', function () {
    /** @var OrderStatusServiceInterface $statusService */
    $statusService = Pdk::get(OrderStatusServiceInterface::class);

    wpFactory(WC_Order::class)
        ->withId(33)
        ->store();

    $statusService->updateStatus([33], 'completed');

    $updatedOrder = wc_get_order(33);

    expect($updatedOrder->get_status())
        ->toBe('completed');
});

