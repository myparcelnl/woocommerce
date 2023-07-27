<?php
/** @noinspection PhpUnhandledExceptionInspection,StaticClosureCanBeUsedInspection */

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Tests\Unit\Pdk\Plugin\Repository;

use InvalidArgumentException;
use MyParcelNL\Pdk\App\Order\Contract\PdkOrderRepositoryInterface;
use MyParcelNL\Pdk\App\Order\Contract\PdkProductRepositoryInterface;
use MyParcelNL\Pdk\App\Order\Model\PdkOrder;
use MyParcelNL\Pdk\Facade\Pdk;
use MyParcelNL\WooCommerce\Pdk\Plugin\Repository\PdkOrderRepository;
use MyParcelNL\WooCommerce\Pdk\Product\Repository\WcPdkProductRepository;
use MyParcelNL\WooCommerce\Tests\Uses\UsesMockWcPdkInstance;
use Psr\Log\LoggerInterface;
use WC_Order;
use function DI\autowire;
use function MyParcelNL\Pdk\Tests\usesShared;
use function MyParcelNL\WooCommerce\Tests\getOrderDefaults;
use function Spatie\Snapshots\assertMatchesJsonSnapshot;

usesShared(
    new UsesMockWcPdkInstance([
        PdkProductRepositoryInterface::class => autowire(WcPdkProductRepository::class),
        PdkOrderRepositoryInterface::class   => autowire(PdkOrderRepository::class),
    ])
);



it('creates a valid pdk order', function (array $input) {
    /** @var \MyParcelNL\Pdk\App\Order\Contract\PdkOrderRepositoryInterface $orderRepository */
    $orderRepository = Pdk::get(PdkOrderRepositoryInterface::class);
    /** @var \MyParcelNL\Pdk\Tests\Bootstrap\MockLogger $logger */
    $logger = Pdk::get(LoggerInterface::class);

    $wcOrder  = new WC_Order($input);
    $pdkOrder = $orderRepository->get($wcOrder);

    expect($logger->getLogs())->toBe([]);

    assertMatchesJsonSnapshot(json_encode($pdkOrder->toArrayWithoutNull(), JSON_PRETTY_PRINT));
})->with('orders');

it('gets order via various inputs', function ($input) {
    /** @var \MyParcelNL\Pdk\App\Order\Contract\PdkOrderRepositoryInterface $orderRepository */
    $orderRepository = Pdk::get(PdkOrderRepositoryInterface::class);

    $pdkOrder = $orderRepository->get($input);

    expect($pdkOrder)->toBeInstanceOf(PdkOrder::class);
})->with([
    'string id' => ['1'],
    'int id'    => [1],
    'wc order'  => [new WC_Order(getOrderDefaults())],
    'post'      => [(object) ['ID' => 1]],
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
