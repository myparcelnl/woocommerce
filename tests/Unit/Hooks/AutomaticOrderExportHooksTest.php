<?php
/** @noinspection StaticClosureCanBeUsedInspection,PhpUnhandledExceptionInspection */

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Hooks;

use MyParcelNL\Pdk\App\Order\Contract\PdkOrderRepositoryInterface;
use MyParcelNL\Pdk\Facade\Pdk;
use MyParcelNL\Pdk\Settings\Model\OrderSettings;
use MyParcelNL\Pdk\Tests\Api\Response\ExampleGetShipmentsResponse;
use MyParcelNL\Pdk\Tests\Bootstrap\MockApi;
use MyParcelNL\WooCommerce\Tests\Uses\UsesMockWcPdkInstance;
use WC_Order;
use function MyParcelNL\Pdk\Tests\factory;
use function MyParcelNL\Pdk\Tests\usesShared;
use function MyParcelNL\WooCommerce\Tests\wpFactory;

usesShared(new UsesMockWcPdkInstance());

it('exports order automatically', function () {
    factory(OrderSettings::class)
        ->withProcessDirectly('wc-completed')
        ->store();

    $orderFactory = wpFactory(WC_Order::class)->withShippingAddressInBelgium();

    $wcOrder = $orderFactory->make();

    $orderFactory->store();

    MockApi::enqueue(new ExampleGetShipmentsResponse());

    /** @var \MyParcelNL\WooCommerce\Hooks\AutomaticOrderExportHooks $class */
    $class = Pdk::get(AutomaticOrderExportHooks::class);

    $class->automaticExportOrder((int) $wcOrder->get_id(), 'pending', 'completed');

    $orderRepository = Pdk::get(PdkOrderRepositoryInterface::class);
    $pdkOrder        = $orderRepository->get((int) $wcOrder->get_id());

    expect($pdkOrder->shipments->count())->toBeGreaterThanOrEqual(1);
});

it('does not automatically export when status is not status from config', function () {
    factory(OrderSettings::class)
        ->withExportWithAutomaticStatus('completed')
        ->store();

    $orderFactory =
        wpFactory(WC_Order::class)->withShippingAddressInBelgium();

    $wcOrder = $orderFactory->make();

    $orderFactory->store();

    MockApi::enqueue(new ExampleGetShipmentsResponse());

    /** @var \MyParcelNL\WooCommerce\Hooks\AutomaticOrderExportHooks $class */
    $class = Pdk::get(AutomaticOrderExportHooks::class);

    $class->automaticExportOrder((int) $wcOrder->get_id(), 'pending', 'pending');

    $lastRequest = MockApi::getLastRequest();

    expect($lastRequest)->toBeNull();
});
