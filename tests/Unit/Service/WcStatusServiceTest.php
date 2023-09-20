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

it('changes order status', function (bool $validOrder, int $orderId, $result) {
    /** @var OrderStatusServiceInterface $statusService */
    $statusService = Pdk::get(OrderStatusServiceInterface::class);

    wpFactory(WC_Order::class)
        ->withId(33)
        ->store();

    $statusService->updateStatus([$orderId], 'completed');

    $updatedOrder = wc_get_order(33);

    if (! $validOrder) {
        expect($updatedOrder->get_status())
            ->toThrow('');

        return;
    }

    expect($updatedOrder->get_status())
        ->toBe($result);
})->with([
        'valid order'   => [
            true,
            33,
            'completed',
        ],
        'invalid order' => [
            false,
            34,
            false,
        ],
    ]
);

it('returns all order statuses', function () {
    /** @var OrderStatusServiceInterface $statusService */
    $statusService = Pdk::get(OrderStatusServiceInterface::class);

    $statuses = $statusService->all();

    expect($statuses)
        ->toBe(
            [
                'wc-pending'    => 'Pending payment',
                'wc-processing' => 'Processing',
                'wc-on-hold'    => 'On hold',
                'wc-completed'  => 'Completed',
                'wc-cancelled'  => 'Cancelled',
                'wc-refunded'   => 'Refunded',
                'wc-failed'     => 'Failed',
            ]
        );
});
