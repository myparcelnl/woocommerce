<?php
/** @noinspection StaticClosureCanBeUsedInspection,PhpUnhandledExceptionInspection */

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Tests\Pdk\Plugin\Repository;

use MyParcelNL\Pdk\App\Api\Backend\PdkBackendActions;
use MyParcelNL\Pdk\Audit\Contract\AuditRepositoryInterface;
use MyParcelNL\Pdk\Facade\Actions;
use MyParcelNL\Pdk\Facade\Pdk;
use MyParcelNL\Pdk\Settings\Model\OrderSettings;
use MyParcelNL\Pdk\Tests\Api\Response\ExampleGetShipmentsResponse;
use MyParcelNL\Pdk\Tests\Bootstrap\MockApi;
use MyParcelNL\WooCommerce\Tests\Mock\MockWpGlobal;
use MyParcelNL\WooCommerce\Tests\Uses\UsesMockWcPdkInstance;
use WC_Order;
use function MyParcelNL\Pdk\Tests\factory;
use function MyParcelNL\Pdk\Tests\usesShared;
use function MyParcelNL\WooCommerce\Tests\wpFactory;

usesShared(new UsesMockWcPdkInstance());

$GLOBALS['wpdb'] = new MockWpGlobal();

it('adds audits', function () {
    factory(OrderSettings::class)
        ->withConceptShipments(true)
        ->store();

    $orderFactory = wpFactory(WC_Order::class)->withShippingAddressInBelgium();

    $wcOrder = $orderFactory->make();

    $orderFactory->store();

    MockApi::enqueue(new ExampleGetShipmentsResponse());

    Actions::executeAutomatic(PdkBackendActions::EXPORT_ORDERS, [
        'orderIds' => [$wcOrder->get_id()],
    ]);

    $auditRepository = Pdk::get(AuditRepositoryInterface::class);

    $audit = $auditRepository->all()
        ->first();

    expect($audit->action)
        ->toBe(PdkBackendActions::EXPORT_ORDERS)
        ->and($audit->id)
        ->toMatch('/^[0-9a-f]{14}\.[0-9a-f]{8}$/')
        ->and($audit->modelIdentifier)
        ->toBe((string) $wcOrder->get_id());
});
