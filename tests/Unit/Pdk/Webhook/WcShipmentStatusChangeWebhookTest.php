<?php

/** @noinspection StaticClosureCanBeUsedInspection */

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Pdk\Webhook;

use MyParcelNL\Pdk\Account\Contract\AccountSettingsServiceInterface;
use MyParcelNL\Pdk\Account\Model\Account;
use MyParcelNL\Pdk\Account\Model\Shop;
use MyParcelNL\Pdk\App\Api\Backend\PdkBackendActions;
use MyParcelNL\Pdk\App\Api\Contract\PdkActionsServiceInterface;
use MyParcelNL\Pdk\App\Order\Collection\PdkOrderCollection;
use MyParcelNL\Pdk\App\Order\Contract\PdkOrderRepositoryInterface;
use MyParcelNL\Pdk\App\Order\Model\PdkOrder;
use MyParcelNL\Pdk\App\Webhook\Hook\ShipmentStatusChangeWebhook;
use MyParcelNL\Pdk\Base\Contract\ModelInterface;
use MyParcelNL\Pdk\Base\Support\Collection;
use MyParcelNL\Pdk\Carrier\Collection\CarrierCollection;
use MyParcelNL\Pdk\Facade\Pdk;
use MyParcelNL\Pdk\Settings\Model\OrderSettings;
use MyParcelNL\WooCommerce\Pdk\Plugin\Service\WcShipmentStatusWebhookService;
use MyParcelNL\WooCommerce\Tests\Uses\UsesMockWcPdkInstance;
use RuntimeException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use function DI\get;
use function MyParcelNL\Pdk\Tests\usesShared;

final class TestAccountFeaturesService
{
    /**
     * @var int
     */
    public static $orderModeVersion = WcShipmentStatusWebhookService::ORDER_MODE_SHIPMENTS;

    /**
     * @return int
     */
    public function getOrderModeVersion(): int
    {
        return self::$orderModeVersion;
    }
}

final class TestAccountSettingsService implements AccountSettingsServiceInterface
{
    /**
     * @return null|\MyParcelNL\Pdk\Account\Model\Account
     */
    public function getAccount(): ?Account
    {
        return null;
    }

    /**
     * @return \MyParcelNL\Pdk\Carrier\Collection\CarrierCollection
     */
    public function getCarrierOptions(): CarrierCollection
    {
        return new CarrierCollection();
    }

    /**
     * @return \MyParcelNL\Pdk\Carrier\Collection\CarrierCollection
     */
    public function getCarriers(): CarrierCollection
    {
        return new CarrierCollection();
    }

    /**
     * @return null|\MyParcelNL\Pdk\Account\Model\Shop
     */
    public function getShop(): ?Shop
    {
        return null;
    }

    /**
     * @return bool
     */
    public function hasAccount(): bool
    {
        return false;
    }

    /**
     * @return bool
     */
    public function hasCarrierSmallPackageContract(): bool
    {
        return false;
    }

    /**
     * @return bool
     */
    public function hasTaxFields(): bool
    {
        return false;
    }

    /**
     * @return bool
     */
    public function usesOrderMode(): bool
    {
        return TestAccountFeaturesService::$orderModeVersion > WcShipmentStatusWebhookService::ORDER_MODE_SHIPMENTS;
    }
}

final class TestPdkActionsService implements PdkActionsServiceInterface
{
    /**
     * @var array
     */
    public static $calls = [];

    /**
     * @param  string|\Symfony\Component\HttpFoundation\Request $action
     * @param  array                                            $parameters
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function execute($action, array $parameters = []): Response
    {
        self::$calls[] = compact('action', 'parameters');

        return new Response('', Response::HTTP_NO_CONTENT);
    }

    /**
     * @param  string|\Symfony\Component\HttpFoundation\Request $action
     * @param  array                                            $parameters
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function executeAutomatic($action, array $parameters = []): Response
    {
        return $this->execute($action, $parameters);
    }

    /**
     * @param  string $context
     *
     * @return $this
     */
    public function setContext(string $context): PdkActionsServiceInterface
    {
        return $this;
    }
}

final class TestPdkOrderRepository implements PdkOrderRepositoryInterface
{
    /**
     * @var array<string, \MyParcelNL\Pdk\App\Order\Model\PdkOrder>
     */
    public static $orders = [];

    /**
     * @var array<string, \MyParcelNL\Pdk\App\Order\Model\PdkOrder>
     */
    public static $ordersByApiIdentifier = [];

    /**
     * @var \MyParcelNL\Pdk\App\Order\Model\PdkOrder[]
     */
    public static $updatedOrders = [];

    /**
     * @param  mixed $input
     *
     * @return \MyParcelNL\Pdk\App\Order\Model\PdkOrder
     */
    public function get($input): PdkOrder
    {
        $id = is_array($input) ? $input['externalIdentifier'] : $input;

        return $this->find($id) ?? new PdkOrder(['externalIdentifier' => $id]);
    }

    /**
     * @param  int|string $id
     *
     * @return null|\MyParcelNL\Pdk\App\Order\Model\PdkOrder
     */
    public function find($id): ?PdkOrder
    {
        return self::$orders[(string) $id] ?? null;
    }

    /**
     * @param  array $ids
     *
     * @return \MyParcelNL\Pdk\Base\Support\Collection
     */
    public function findAll(array $ids): Collection
    {
        return new Collection(array_filter(array_map([$this, 'find'], $ids)));
    }

    /**
     * @param  int|string $id
     *
     * @return \MyParcelNL\Pdk\Base\Contract\ModelInterface
     */
    public function findOrFail($id): ModelInterface
    {
        $order = $this->find($id);

        if (! $order) {
            throw new RuntimeException(sprintf('Order "%s" not found.', $id));
        }

        return $order;
    }

    /**
     * @return \MyParcelNL\Pdk\Base\Support\Collection
     */
    public function all(): Collection
    {
        return new Collection(self::$orders);
    }

    /**
     * @param  int|string $id
     *
     * @return bool
     */
    public function exists($id): bool
    {
        return null !== $this->find($id);
    }

    /**
     * @param  string|string[] $orderIds
     *
     * @return \MyParcelNL\Pdk\App\Order\Collection\PdkOrderCollection
     */
    public function getMany($orderIds): PdkOrderCollection
    {
        return new PdkOrderCollection(array_map([$this, 'get'], (array) $orderIds));
    }

    /**
     * @param  string $uuid
     *
     * @return null|\MyParcelNL\Pdk\App\Order\Model\PdkOrder
     */
    public function getByApiIdentifier(string $uuid): ?PdkOrder
    {
        return self::$ordersByApiIdentifier[$uuid] ?? null;
    }

    /**
     * @param  \MyParcelNL\Pdk\App\Order\Model\PdkOrder $order
     *
     * @return \MyParcelNL\Pdk\App\Order\Model\PdkOrder
     */
    public function update(PdkOrder $order): PdkOrder
    {
        self::$orders[$order->externalIdentifier] = $order;
        self::$updatedOrders[]                    = $order;

        return $order;
    }

    /**
     * @param  \MyParcelNL\Pdk\App\Order\Collection\PdkOrderCollection $collection
     *
     * @return \MyParcelNL\Pdk\App\Order\Collection\PdkOrderCollection
     */
    public function updateMany(PdkOrderCollection $collection): PdkOrderCollection
    {
        return $collection->map([$this, 'update']);
    }

    /**
     * @param  string        $key
     * @param  null|callable $callback
     * @param  bool          $force
     *
     * @return mixed
     */
    public function retrieve(string $key, ?callable $callback = null, bool $force = false)
    {
        return self::$orders[$key] ?? ($callback ? $callback() : null);
    }

    /**
     * @param  string $key
     * @param  mixed  $data
     *
     * @return mixed
     */
    public function save(string $key, $data)
    {
        self::$orders[$key] = $data;

        return $data;
    }
}

usesShared(new UsesMockWcPdkInstance([
    'MyParcelNL\Pdk\Account\Contract\AccountFeaturesServiceInterface' => get(TestAccountFeaturesService::class),
    AccountSettingsServiceInterface::class                            => get(TestAccountSettingsService::class),
    PdkActionsServiceInterface::class                                 => get(TestPdkActionsService::class),
    PdkOrderRepositoryInterface::class                                => get(TestPdkOrderRepository::class),
]));

beforeEach(function () {
    TestAccountFeaturesService::$orderModeVersion  = WcShipmentStatusWebhookService::ORDER_MODE_SHIPMENTS;
    TestPdkActionsService::$calls                  = [];
    TestPdkOrderRepository::$orders                = [];
    TestPdkOrderRepository::$ordersByApiIdentifier = [];
    TestPdkOrderRepository::$updatedOrders         = [];
});

function handleShipmentStatusWebhook(array $payload): void
{
    /** @var \MyParcelNL\WooCommerce\Pdk\Webhook\WcShipmentStatusChangeWebhook $webhook */
    $webhook = Pdk::get(WcShipmentStatusChangeWebhook::class);

    $webhook->handle(new Request([], [], [], [], [], [], json_encode([
        'data' => [
            'hooks' => [$payload],
        ],
    ])));
}

it('overrides the pdk shipment status change webhook', function () {
    expect(Pdk::get(ShipmentStatusChangeWebhook::class))
        ->toBeInstanceOf(WcShipmentStatusChangeWebhook::class);
});

it('updates an existing order v2 shipment locally without fetching shipments from the api', function () {
    TestAccountFeaturesService::$orderModeVersion = WcShipmentStatusWebhookService::ORDER_MODE_V2;

    TestPdkOrderRepository::$orders['46'] = new PdkOrder([
        'externalIdentifier' => '46',
        'shipments'          => [
            [
                'id'      => 231032886,
                'barcode' => 'old-barcode',
                'status'  => 2,
            ],
        ],
    ]);

    handleShipmentStatusWebhook([
        'shipment_id'                   => 231032886,
        'status'                        => 4,
        'barcode'                       => '3SMYPA428613388',
        'shipment_reference_identifier' => '46',
        'order_id'                      => 'legacy-order-id',
    ]);

    $updatedOrder    = TestPdkOrderRepository::$updatedOrders[0];
    $updatedShipment = $updatedOrder->shipments->first();

    expect(TestPdkOrderRepository::$updatedOrders)
        ->toHaveLength(1)
        ->and($updatedShipment->status)
        ->toBe(4)
        ->and($updatedShipment->barcode)
        ->toBe('3SMYPA428613388')
        ->and($updatedShipment->orderId)
        ->toBe('46')
        ->and(TestPdkActionsService::$calls)
        ->toHaveLength(1)
        ->and(TestPdkActionsService::$calls[0]['action'])
        ->toBe(PdkBackendActions::UPDATE_ORDER_STATUS)
        ->and(TestPdkActionsService::$calls[0]['parameters']['orderIds'])
        ->toBe(['46'])
        ->and(TestPdkActionsService::$calls[0]['parameters']['setting'])
        ->toBe(OrderSettings::STATUS_WHEN_LABEL_SCANNED);
});

it('keeps using the legacy order id lookup for order v1', function () {
    TestAccountFeaturesService::$orderModeVersion = WcShipmentStatusWebhookService::ORDER_MODE_V1;

    TestPdkOrderRepository::$ordersByApiIdentifier['order-api-uuid'] = new PdkOrder([
        'externalIdentifier' => '197',
    ]);

    handleShipmentStatusWebhook([
        'shipment_id'                   => 231032886,
        'status'                        => 4,
        'shipment_reference_identifier' => '46',
        'order_id'                      => 'order-api-uuid',
    ]);

    expect(TestPdkOrderRepository::$updatedOrders)
        ->toHaveLength(0)
        ->and(TestPdkActionsService::$calls)
        ->toHaveLength(1)
        ->and(TestPdkActionsService::$calls[0]['action'])
        ->toBe(PdkBackendActions::UPDATE_SHIPMENTS)
        ->and(TestPdkActionsService::$calls[0]['parameters']['orderIds'])
        ->toBe(['197'])
        ->and(TestPdkActionsService::$calls[0]['parameters']['orderStatus'])
        ->toBe(OrderSettings::STATUS_WHEN_LABEL_SCANNED);
});

it('keeps using the shipment reference identifier for shipments mode', function () {
    TestAccountFeaturesService::$orderModeVersion = WcShipmentStatusWebhookService::ORDER_MODE_SHIPMENTS;

    handleShipmentStatusWebhook([
        'shipment_id'                   => 231032886,
        'status'                        => 4,
        'shipment_reference_identifier' => '46',
        'order_id'                      => 'order-api-uuid',
    ]);

    expect(TestPdkOrderRepository::$updatedOrders)
        ->toHaveLength(0)
        ->and(TestPdkActionsService::$calls)
        ->toHaveLength(1)
        ->and(TestPdkActionsService::$calls[0]['action'])
        ->toBe(PdkBackendActions::UPDATE_SHIPMENTS)
        ->and(TestPdkActionsService::$calls[0]['parameters']['orderIds'])
        ->toBe(['46'])
        ->and(TestPdkActionsService::$calls[0]['parameters']['orderStatus'])
        ->toBe(OrderSettings::STATUS_WHEN_LABEL_SCANNED);
});

it('skips order v2 updates when the shipment is not already stored on the referenced order', function () {
    TestAccountFeaturesService::$orderModeVersion = WcShipmentStatusWebhookService::ORDER_MODE_V2;

    TestPdkOrderRepository::$orders['46'] = new PdkOrder([
        'externalIdentifier' => '46',
        'shipments'          => [
            [
                'id'     => 123,
                'status' => 2,
            ],
        ],
    ]);

    handleShipmentStatusWebhook([
        'shipment_id'                   => 231032886,
        'status'                        => 4,
        'shipment_reference_identifier' => '46',
    ]);

    expect(TestPdkOrderRepository::$updatedOrders)
        ->toHaveLength(0)
        ->and(TestPdkActionsService::$calls)
        ->toHaveLength(0);
});
