<?php

/** @noinspection PhpUnhandledExceptionInspection,StaticClosureCanBeUsedInspection */

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Hooks;

use Error;
use MyParcelNL\Pdk\App\Api\Backend\PdkBackendActions;
use MyParcelNL\Pdk\App\Api\Contract\PdkActionsServiceInterface;
use MyParcelNL\Pdk\App\Order\Contract\PdkOrderRepositoryInterface;
use MyParcelNL\Pdk\Facade\Pdk;
use MyParcelNL\Pdk\Settings\Model\OrderSettings;
use MyParcelNL\WooCommerce\Tests\Mock\MockWpActions;
use MyParcelNL\WooCommerce\Tests\Uses\UsesMockWcPdkInstance;
use MyParcelNL\WooCommerce\WooCommerce\Contract\WcOrderRepositoryInterface;
use RuntimeException;
use Symfony\Component\HttpFoundation\Response;
use Throwable;
use WC_Order;
use WC_Order_Item_Product;
use WC_Product;
use function MyParcelNL\Pdk\Tests\factory;
use function MyParcelNL\Pdk\Tests\usesShared;
use function MyParcelNL\WooCommerce\Tests\wpFactory;

usesShared(new UsesMockWcPdkInstance([
    PdkActionsServiceInterface::class => \DI\factory(function () {
        return test()->createMock(PdkActionsServiceInterface::class);
    }),
]));

beforeEach(function () {
    factory(OrderSettings::class)->withProcessDirectly('wc-processing')->store();

    $this->order   = wpFactory(WC_Order::class)->withId(123)->make();
    $this->hooks   = Pdk::get(AutomaticOrderExportHooks::class);
    $this->actions = Pdk::get(PdkActionsServiceInterface::class);
});

it('registers the current WooCommerce order as the fourth hook argument', function () {
    $this->hooks->apply();

    $registered = MockWpActions::get('woocommerce_order_status_changed');

    expect($registered)->toHaveCount(1)
        ->and($registered[0]['acceptedArgs'])->toBe(4);
});

it('does not start a nested export for the same order', function () {
    $this->actions->expects($this->once())
        ->method('executeAutomatic')
        ->with(PdkBackendActions::EXPORT_ORDERS, ['orderIds' => [123]])
        ->willReturnCallback(function () {
            $this->hooks->automaticExportOrder(123, 'pending', 'processing', $this->order);

            return new Response();
        });

    $this->hooks->automaticExportOrder(123, 'pending', 'processing', $this->order);
});

it('allows another order to export while the first order is exporting', function () {
    $otherOrder = wpFactory(WC_Order::class)->withId(456)->make();
    $exported   = [];

    $this->actions->expects($this->exactly(2))
        ->method('executeAutomatic')
        ->willReturnCallback(function ($action, array $parameters) use ($otherOrder, &$exported) {
            $orderId    = $parameters['orderIds'][0];
            $exported[] = $orderId;

            if (123 === $orderId) {
                $this->hooks->automaticExportOrder(456, 'pending', 'processing', $otherOrder);
            }

            return new Response();
        });

    $this->hooks->automaticExportOrder(123, 'pending', 'processing', $this->order);

    expect($exported)->toBe([123, 456]);
});

it('releases the in-flight guard after success', function () {
    // The action double does not persist autoExported. This isolates the lifetime of the guard.
    $this->actions->expects($this->exactly(2))->method('executeAutomatic')->willReturn(new Response());

    $this->hooks->automaticExportOrder(123, 'pending', 'processing', $this->order);
    $this->hooks->automaticExportOrder(123, 'pending', 'processing', $this->order);
});

it('allows a retry after an export throws', function (string $exceptionClass) {
    $failure = new $exceptionClass('Export failed');
    $calls   = 0;

    $this->actions->expects($this->exactly(2))
        ->method('executeAutomatic')
        ->willReturnCallback(function () use ($failure, &$calls) {
            if (1 === ++$calls) {
                throw $failure;
            }

            return new Response();
        });

    try {
        $this->hooks->automaticExportOrder(123, 'pending', 'processing', $this->order);
        $this->fail('The export failure must propagate.');
    } catch (Throwable $caught) {
        expect($caught)->toBe($failure);
    }

    $this->hooks->automaticExportOrder(123, 'pending', 'processing', $this->order);
})->with([RuntimeException::class, Error::class]);

it('uses current order data when the request cache contains an older order', function () {
    $repository = Pdk::get(WcOrderRepositoryInterface::class);
    $repository->get($this->order);

    $liveOrder = clone $this->order;
    $liveOrder->set_shipping_city('Rotterdam');

    $this->actions->expects($this->once())->method('executeAutomatic')->willReturnCallback(function () {
        $order = Pdk::get(PdkOrderRepositoryInterface::class)->get(123);

        expect($order->shippingAddress->city)->toBe('Rotterdam');

        return new Response();
    });

    $this->hooks->automaticExportOrder(123, 'pending', 'processing', $liveOrder);
});

it('checks the live order items even if the item cache was populated before products were added', function () {
    $repository = Pdk::get(WcOrderRepositoryInterface::class);
    $emptyOrder = clone $this->order;
    $emptyOrder->set_items([]);
    $repository->getItems($emptyOrder);

    $this->actions->expects($this->once())->method('executeAutomatic')->willReturn(new Response());

    $this->hooks->automaticExportOrder(123, 'pending', 'processing', $this->order);
});

it('skips pickup orders', function (string $method) {
    $this->order->set_shipping_methods([$method]);
    $this->actions->expects($this->never())->method('executeAutomatic');

    $this->hooks->automaticExportOrder(123, 'pending', 'processing', $this->order);
})->with(['local_pickup', 'pickup_location']);

it('skips an unconfigured or different status', function (string $configuredStatus, string $newStatus) {
    factory(OrderSettings::class)->withProcessDirectly($configuredStatus)->store();
    $this->actions->expects($this->never())->method('executeAutomatic');

    $this->hooks->automaticExportOrder(123, 'pending', $newStatus, $this->order);
})->with([
    ['wc-processing', 'completed'],
    ['', 'processing'],
]);

it('only automatically exports orders containing a product that needs shipping', function (array $needsShipping, bool $exports) {
    $items = array_map(function (bool $physical) {
        return wpFactory(WC_Order_Item_Product::class)
            ->withProduct(wpFactory(WC_Product::class)->withNeedsShipping($physical))
            ->make();
    }, $needsShipping);

    $this->order->set_items($items);
    $this->actions->expects($exports ? $this->once() : $this->never())
        ->method('executeAutomatic')->willReturn(new Response());

    $this->hooks->automaticExportOrder(123, 'pending', 'processing', $this->order);
})->with([
    'virtual only' => [[false, false], false],
    'physical'     => [[true], true],
    'mixed'        => [[false, true], true],
    'empty'        => [[], false],
]);
