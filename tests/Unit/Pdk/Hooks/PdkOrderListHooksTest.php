<?php

/** @noinspection PhpUnhandledExceptionInspection,StaticClosureCanBeUsedInspection */

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Pdk\Hooks;

use MyParcelNL\Pdk\App\Order\Model\PdkOrder;
use MyParcelNL\Pdk\Facade\Pdk;
use MyParcelNL\Pdk\Tests\Bootstrap\TestBootstrapper;
use MyParcelNL\WooCommerce\Pdk\Plugin\Repository\PdkOrderRepository;
use MyParcelNL\WooCommerce\Tests\Uses\UsesMockWcPdkInstance;
use MyParcelNL\WooCommerce\WooCommerce\Contract\WcOrderRepositoryInterface;
use Psr\Log\LoggerInterface;
use RuntimeException;
use WC_Order;
use function MyParcelNL\Pdk\Tests\usesShared;
use function MyParcelNL\WooCommerce\Tests\wpFactory;

usesShared(new UsesMockWcPdkInstance());

beforeEach(function () {
    TestBootstrapper::hasAccount();
});

it('renders nothing when the pdk order cannot be retrieved and logs', function () {
    // A repository whose getForOrderList blows up (e.g. carrier unavailable on the order).
    $throwingRepository = new class extends PdkOrderRepository {
        /** @noinspection PhpMissingParentConstructorInspection */
        public function __construct() {}

        public function getForOrderList(WC_Order $order): PdkOrder
        {
            throw new RuntimeException('Carrier unavailable');
        }
    };

    $hooks = new PdkOrderListHooks($throwingRepository, Pdk::get(WcOrderRepositoryInterface::class));

    /** @var \MyParcelNL\Pdk\Tests\Bootstrap\MockLogger $logger */
    $logger = Pdk::get(LoggerInterface::class);

    ob_start();
    $hooks->renderPdkOrderListItem(Pdk::get('orderListColumnName'), wpFactory(WC_Order::class)->make());
    $output = ob_get_clean();

    // The column renders nothing (early return) and the failure is logged once at debug level.
    expect($output)->toBe('')
        ->and($logger->getLogs('debug'))->toHaveCount(1)
        ->and($logger->getLogs('debug')[0]['message'])->toContain('Could not retrieve PDK order for order list');
});

it('renders the order list column for a healthy order', function () {
    /** @var PdkOrderListHooks $hooks */
    $hooks = Pdk::get(PdkOrderListHooks::class);

    /** @var \MyParcelNL\Pdk\Tests\Bootstrap\MockLogger $logger */
    $logger = Pdk::get(LoggerInterface::class);

    ob_start();
    $hooks->renderPdkOrderListItem(Pdk::get('orderListColumnName'), wpFactory(WC_Order::class)->make());
    $output = ob_get_clean();

    // The try succeeds, so nothing is logged and the column markup is rendered.
    expect($output)->not->toBe('')
        ->and($logger->getLogs('debug'))->toBe([]);
});
