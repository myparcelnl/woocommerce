<?php
/** @noinspection StaticClosureCanBeUsedInspection */

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Hooks;

use MyParcelNL\Pdk\App\Order\Contract\PdkOrderRepositoryInterface;
use MyParcelNL\Pdk\Base\PdkBootstrapper;
use MyParcelNL\Pdk\Facade\Pdk;
use MyParcelNL\Pdk\Tests\Bootstrap\TestBootstrapper;
use MyParcelNL\Sdk\Client\Generated\CoreApi\Model\RefCapabilitiesSharedCarrierV2;
use MyParcelNL\WooCommerce\Pdk\Hooks\PdkCheckoutPlaceOrderHooks;
use MyParcelNL\WooCommerce\Tests\Uses\UsesMockWcPdkInstance;
use WC_Order;
use function MyParcelNL\Pdk\Tests\usesShared;
use function MyParcelNL\WooCommerce\Tests\wpFactory;

usesShared(new UsesMockWcPdkInstance());

beforeEach(function () {
    TestBootstrapper::hasAccount();
});

it('saves delivery options for the blocks checkout', function ($orderId, $deliveryOptions, $expectedCarrier) {
    $namespace = PdkBootstrapper::PLUGIN_NAMESPACE;
    $body      = json_encode([
        'extensions' => [
            "$namespace-delivery-options" => [
                'carrier'     => $deliveryOptions['carrier'],
                'packageType' => $deliveryOptions['packageType'],
            ],
        ],
    ]);

    // The Store API hook passes a WP_REST_Request; the selection is read from its raw body.
    $request = new class($body) {
        /** @var string */
        private $body;

        public function __construct(string $body)
        {
            $this->body = $body;
        }

        public function get_body(): string
        {
            return $this->body;
        }
    };

    $wcOrder            =
        wpFactory(WC_Order::class)
            ->withId($orderId)
            ->make();
    $checkoutHooksClass = Pdk::get(PdkCheckoutPlaceOrderHooks::class);

    $checkoutHooksClass->saveBlocksDeliveryOptions($wcOrder, $request);

    $orderRepository = Pdk::get(PdkOrderRepositoryInterface::class);
    $pdkOrder        = $orderRepository->get($wcOrder->get_id());

    expect($pdkOrder->getDeliveryOptions()->getCarrier()->carrier)
        ->toBe($expectedCarrier)
        ->and(
            $pdkOrder->getDeliveryOptions()
                ->getPackageTypeId()
        )
        ->toBe($deliveryOptions['packageType']);
})->with([
    'dhl order'    => [
        'orderId'         => 2,
        'deliveryOptions' => [
            'carrier'     => 'dhlforyou',
            'packageType' => 2,
        ],
        'expectedCarrier' => RefCapabilitiesSharedCarrierV2::DHL_FOR_YOU,
    ],
    'postnl order' => [
        'orderId'         => 1,
        'deliveryOptions' => [
            'carrier'     => 'postnl',
            'packageType' => 1,
        ],
        'expectedCarrier' => RefCapabilitiesSharedCarrierV2::POSTNL,
    ],
]);

it('saves delivery options when the checkout body contains escaped characters', function () {
    $namespace = PdkBootstrapper::PLUGIN_NAMESPACE;

    /**
     * A quote anywhere in the checkout is sent as \" in the raw body. The raw body is not slashed by
     * WordPress, so stripping slashes from it breaks the JSON and silently loses the selection.
     */
    $body = json_encode([
        'customer_note' => 'Lever af bij de "achterdeur"',
        'extensions'    => [
            "$namespace-delivery-options" => [
                'carrier'     => 'dhlforyou',
                'packageType' => 1,
            ],
        ],
    ]);

    $request = new class($body) {
        /** @var string */
        private $body;

        public function __construct(string $body)
        {
            $this->body = $body;
        }

        public function get_body(): string
        {
            return $this->body;
        }
    };

    $wcOrder = wpFactory(WC_Order::class)
        ->withId(3)
        ->make();

    Pdk::get(PdkCheckoutPlaceOrderHooks::class)->saveBlocksDeliveryOptions($wcOrder, $request);

    $pdkOrder = Pdk::get(PdkOrderRepositoryInterface::class)->get($wcOrder->get_id());

    expect($pdkOrder->getDeliveryOptions()->getCarrier()->carrier)
        ->toBe(RefCapabilitiesSharedCarrierV2::DHL_FOR_YOU);
});
