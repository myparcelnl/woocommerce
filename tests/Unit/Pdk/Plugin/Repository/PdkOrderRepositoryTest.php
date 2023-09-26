<?php
/** @noinspection PhpUnhandledExceptionInspection,StaticClosureCanBeUsedInspection */

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Pdk\Plugin\Repository;

use InvalidArgumentException;
use MyParcelNL\Pdk\App\Order\Contract\PdkOrderRepositoryInterface;
use MyParcelNL\Pdk\App\Order\Model\PdkOrder;
use MyParcelNL\Pdk\Facade\Pdk;
use MyParcelNL\WooCommerce\Tests\Uses\UsesMockWcPdkInstance;
use Psr\Log\LoggerInterface;
use WC_Order;
use WC_Order_Factory;
use function MyParcelNL\Pdk\Tests\usesShared;
use function MyParcelNL\WooCommerce\Tests\wpFactory;
use function Spatie\Snapshots\assertMatchesJsonSnapshot;

usesShared(new UsesMockWcPdkInstance());

it('creates a valid pdk order', function (WC_Order_Factory $factory) {
    /** @var \MyParcelNL\Pdk\App\Order\Contract\PdkOrderRepositoryInterface $orderRepository */
    $orderRepository = Pdk::get(PdkOrderRepositoryInterface::class);
    /** @var \MyParcelNL\Pdk\Tests\Bootstrap\MockLogger $logger */
    $logger = Pdk::get(LoggerInterface::class);

    $wcOrder  = $factory->make();
    $pdkOrder = $orderRepository->get($wcOrder);

    expect($logger->getLogs())->toBe([]);

    assertMatchesJsonSnapshot(json_encode($pdkOrder->toArrayWithoutNull(), JSON_PRETTY_PRINT));
})->with('orders');

it('gets order via various inputs', function ($input) {
    wpFactory(WC_Order::class)
        ->with(['id' => 123])
        ->make();

    /** @var \MyParcelNL\Pdk\App\Order\Contract\PdkOrderRepositoryInterface $orderRepository */
    $orderRepository = Pdk::get(PdkOrderRepositoryInterface::class);

    $pdkOrder = $orderRepository->get($input);

    expect($pdkOrder)->toBeInstanceOf(PdkOrder::class);
})->with([
    'string id' => function () {
        return '123';
    },
    'int id'    => function () {
        return 123;
    },
    'wc order'  => function () {
        return new WC_Order(['id' => 123]);
    },
    'post'      => function () {
        return (object) ['ID' => 123];
    },
]);

it('handles errors', function ($input) {
    /** @var \MyParcelNL\Pdk\App\Order\Contract\PdkOrderRepositoryInterface $orderRepository */
    $orderRepository = Pdk::get(PdkOrderRepositoryInterface::class);

    $orderRepository->get($input);
})
    ->throws(InvalidArgumentException::class)
    ->with([
            'unrecognized object input' => function () {
                return (object) ['foo' => 'bar'];
            },

            'array' => function () {
                return ['id' => 1];
            },
        ]
    );
