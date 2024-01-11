<?php
/** @noinspection StaticClosureCanBeUsedInspection */

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Hooks;

use MyParcelNL\Pdk\App\Order\Contract\PdkOrderRepositoryInterface;
use MyParcelNL\Pdk\Facade\Pdk;
use MyParcelNL\WooCommerce\Pdk\Hooks\PdkCheckoutPlaceOrderHooks;
use MyParcelNL\WooCommerce\Tests\Uses\UsesMockWcPdkInstance;
use WC_Order;
use function MyParcelNL\Pdk\Tests\usesShared;
use function MyParcelNL\WooCommerce\Tests\wpFactory;

usesShared(new UsesMockWcPdkInstance());

it('saves delivery options for the blocks checkout', function ($orderId, $deliveryOptions) {
    $GLOBALS['HTTP_RAW_POST_DATA'] = json_encode([
        'extensions' => [
            'myparcelnl-delivery-options' => [
                'carrier'     => $deliveryOptions['carrier'],
                'packageType' => $deliveryOptions['packageType'],
            ],
        ],
    ]);

    $wcOrder            =
        wpFactory(WC_Order::class)
            ->withId($orderId)
            ->make();
    $checkoutHooksClass = Pdk::get(PdkCheckoutPlaceOrderHooks::class);

    $checkoutHooksClass->saveBlocksDeliveryOptions($wcOrder);

    $orderRepository = Pdk::get(PdkOrderRepositoryInterface::class);
    $pdkOrder        = $orderRepository->get($wcOrder->get_id());

    expect(
        $pdkOrder->getDeliveryOptions()
            ->getCarrier()
            ->getName()
    )
        ->toBe($deliveryOptions['carrier'])
        ->and(
            $pdkOrder->getDeliveryOptions()
                ->getPackageTypeId()
        )
        ->toBe($deliveryOptions['packageType']);
})->with([
    'postnl order' => [
        'orderId'         => 1,
        'deliveryOptions' => [
            'carrier'     => 'postnl',
            'packageType' => 1,
        ],
    ],
    'dhl order'    => [
        'orderId'         => 2,
        'deliveryOptions' => [
            'carrier'     => 'dhlforyou',
            'packageType' => 2,
        ],
    ],
]);
