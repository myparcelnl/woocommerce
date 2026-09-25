<?php
/** @noinspection StaticClosureCanBeUsedInspection,PhpUnhandledExceptionInspection */

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Hooks;

use MyParcelNL\Pdk\App\Api\Contract\PdkActionsServiceInterface;
use MyParcelNL\Pdk\App\Order\Contract\PdkOrderRepositoryInterface;
use MyParcelNL\Pdk\Facade\Pdk;
use MyParcelNL\Pdk\Settings\Model\OrderSettings;
use MyParcelNL\Pdk\Tests\Api\Response\ExampleGetShipmentsResponse;
use MyParcelNL\Pdk\Tests\Bootstrap\MockApi;
use MyParcelNL\Pdk\Tests\Bootstrap\TestBootstrapper;
use MyParcelNL\WooCommerce\Tests\Uses\UsesMockWcPdkInstance;
use RuntimeException;
use Symfony\Component\HttpFoundation\Response;
use WC_Order;
use function MyParcelNL\Pdk\Tests\factory;
use function MyParcelNL\Pdk\Tests\mockPdkProperties;
use function MyParcelNL\Pdk\Tests\usesShared;
use function MyParcelNL\WooCommerce\Tests\wpFactory;

final class ReentrantActionsService implements PdkActionsServiceInterface
{
    /**
     * @var null|callable
     */
    private $callback;

    /**
     * @var array<int, array{action: mixed, parameters: array}>
     */
    private $calls = [];

    public function execute($action, array $parameters = []): Response
    {
        return $this->recordCall($action, $parameters);
    }

    public function executeAutomatic($action, array $parameters = []): Response
    {
        return $this->recordCall($action, $parameters);
    }

    public function setContext(string $context): PdkActionsServiceInterface
    {
        return $this;
    }

    /**
     * @return array<int, array{action: mixed, parameters: array}>
     */
    public function getCalls(): array
    {
        return $this->calls;
    }

    public function setCallback(?callable $callback): void
    {
        $this->callback = $callback;
    }

    private function recordCall($action, array $parameters): Response
    {
        $this->calls[] = compact('action', 'parameters');

        if ($this->callback) {
            ($this->callback)();
        }

        return new Response();
    }
}

usesShared(new UsesMockWcPdkInstance());

beforeEach(function () {
    TestBootstrapper::hasAccount();
});

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

it('ignores a nested automatic export for the same order', function () {
    factory(OrderSettings::class)
        ->withProcessDirectly('wc-processing')
        ->store();

    $wcOrder = wpFactory(WC_Order::class)->make();
    $service = new ReentrantActionsService();

    mockPdkProperties([PdkActionsServiceInterface::class => $service]);

    /** @var \MyParcelNL\WooCommerce\Hooks\AutomaticOrderExportHooks $class */
    $class = Pdk::get(AutomaticOrderExportHooks::class);

    $service->setCallback(function () use ($class, $wcOrder) {
        $class->automaticExportOrder((int) $wcOrder->get_id(), 'pending', 'processing');
    });

    $class->automaticExportOrder((int) $wcOrder->get_id(), 'pending', 'processing');

    expect($service->getCalls())->toHaveCount(1);
});

it('allows a nested automatic export for another order', function () {
    factory(OrderSettings::class)
        ->withProcessDirectly('wc-processing')
        ->store();

    $firstOrder  = wpFactory(WC_Order::class)->make();
    $secondOrder = wpFactory(WC_Order::class)->make();
    $service     = new ReentrantActionsService();

    mockPdkProperties([PdkActionsServiceInterface::class => $service]);

    /** @var \MyParcelNL\WooCommerce\Hooks\AutomaticOrderExportHooks $class */
    $class = Pdk::get(AutomaticOrderExportHooks::class);

    $service->setCallback(function () use ($class, $secondOrder, $service) {
        $service->setCallback(null);
        $class->automaticExportOrder((int) $secondOrder->get_id(), 'pending', 'processing');
    });

    $class->automaticExportOrder((int) $firstOrder->get_id(), 'pending', 'processing');

    expect($service->getCalls())->toHaveCount(2)
        ->and($service->getCalls()[1]['parameters']['orderIds'])->toBe([(int) $secondOrder->get_id()]);
});

it('releases the automatic export guard after an exception', function () {
    factory(OrderSettings::class)
        ->withProcessDirectly('wc-processing')
        ->store();

    $wcOrder = wpFactory(WC_Order::class)->make();
    $service = new ReentrantActionsService();

    mockPdkProperties([PdkActionsServiceInterface::class => $service]);

    /** @var \MyParcelNL\WooCommerce\Hooks\AutomaticOrderExportHooks $class */
    $class = Pdk::get(AutomaticOrderExportHooks::class);

    $service->setCallback(function () use ($service) {
        $service->setCallback(null);

        throw new RuntimeException('Export failed');
    });

    expect(function () use ($class, $wcOrder) {
        $class->automaticExportOrder((int) $wcOrder->get_id(), 'pending', 'processing');
    })->toThrow(RuntimeException::class, 'Export failed');

    $class->automaticExportOrder((int) $wcOrder->get_id(), 'pending', 'processing');

    expect($service->getCalls())->toHaveCount(2);
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
