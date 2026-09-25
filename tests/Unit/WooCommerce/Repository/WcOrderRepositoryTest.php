<?php
/** @noinspection PhpUnhandledExceptionInspection,StaticClosureCanBeUsedInspection */

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\WooCommerce\Repository;

use MyParcelNL\Pdk\Facade\Pdk;
use MyParcelNL\WooCommerce\Tests\Mock\TrackingWcOrder;
use MyParcelNL\WooCommerce\Tests\Uses\UsesMockWcPdkInstance;
use MyParcelNL\WooCommerce\WooCommerce\Contract\WcOrderRepositoryInterface;
use WC_Order;
use function MyParcelNL\Pdk\Tests\usesShared;
use function MyParcelNL\WooCommerce\Tests\wpFactory;

usesShared(new UsesMockWcPdkInstance());

it('loads a fresh order without reusing the request cache', function () {
    $wcOrder = wpFactory(WC_Order::class)
        ->withStatus('pending')
        ->make();

    /** @var \MyParcelNL\WooCommerce\WooCommerce\Contract\WcOrderRepositoryInterface $repository */
    $repository = Pdk::get(WcOrderRepositoryInterface::class);

    $cachedOrder = $repository->get($wcOrder->get_id());
    $cachedOrder->update_status('completed');
    $repository->updateCache($cachedOrder);

    expect($repository->get($wcOrder->get_id())->get_status())->toBe('completed')
        ->and($repository->getFresh($wcOrder->get_id())->get_status())->toBe('pending');
});

it('updates the request cache with a saved order', function () {
    $wcOrder = wpFactory(WC_Order::class)
        ->withStatus('pending')
        ->make();

    /** @var \MyParcelNL\WooCommerce\WooCommerce\Contract\WcOrderRepositoryInterface $repository */
    $repository = Pdk::get(WcOrderRepositoryInterface::class);

    $freshOrder = $repository->getFresh($wcOrder->get_id());
    $freshOrder->update_status('completed');
    $repository->updateCache($freshOrder);

    expect($repository->get($wcOrder->get_id())->get_status())->toBe('completed');
});

it('preserves the concrete order class when loading a fresh order', function () {
    $wcOrder = new TrackingWcOrder(123);

    /** @var \MyParcelNL\WooCommerce\WooCommerce\Contract\WcOrderRepositoryInterface $repository */
    $repository = Pdk::get(WcOrderRepositoryInterface::class);
    $repository->updateCache($wcOrder);

    $freshOrder = $repository->getFresh($wcOrder->get_id());

    expect($freshOrder)->toBeInstanceOf(TrackingWcOrder::class)
        ->and($freshOrder)->not->toBe($wcOrder);
});
