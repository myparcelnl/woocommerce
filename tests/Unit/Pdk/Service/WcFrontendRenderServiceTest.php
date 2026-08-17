<?php

/** @noinspection StaticClosureCanBeUsedInspection,PhpUnhandledExceptionInspection */

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Pdk\Service;

use MyParcelNL\Pdk\App\Cart\Model\PdkCart;
use MyParcelNL\Pdk\Context\Context;
use MyParcelNL\Pdk\Context\Contract\ContextServiceInterface;
use MyParcelNL\Pdk\Context\Model\ContextBag;
use MyParcelNL\Pdk\Context\Service\ContextService;
use MyParcelNL\Pdk\Facade\Pdk;
use MyParcelNL\Pdk\Frontend\Contract\FrontendRenderServiceInterface;
use MyParcelNL\Pdk\Settings\Model\CheckoutSettings;
use MyParcelNL\WooCommerce\Tests\Mock\MockThrowingContextService;
use MyParcelNL\WooCommerce\Tests\Uses\UsesMockWcPdkInstance;
use RuntimeException;
use function MyParcelNL\Pdk\Tests\mockPdkProperties;
use function MyParcelNL\Pdk\Tests\usesShared;

usesShared(new UsesMockWcPdkInstance());

it('closes its output buffer when the checkout context throws', function () {
    mockPdkProperties([ContextServiceInterface::class => new MockThrowingContextService()]);

    /** @var FrontendRenderServiceInterface $service */
    $service = Pdk::get(FrontendRenderServiceInterface::class);
    $level   = ob_get_level();

    expect(function () use ($service) {
        $service->renderDeliveryOptions(new PdkCart());
    })->toThrow(RuntimeException::class, MockThrowingContextService::MESSAGE);

    // A leaked buffer swallows the rest of the checkout page.
    expect(ob_get_level())->toBe($level);
});

it('closes its output buffer when the context disables delivery options', function () {
    mockPdkProperties([
        ContextServiceInterface::class => new class extends ContextService {
            public function createContexts(array $contexts, array $data = []): ContextBag
            {
                return new ContextBag([
                    Context::ID_CHECKOUT => [
                        'settings' => [CheckoutSettings::ENABLE_DELIVERY_OPTIONS => false],
                    ],
                ]);
            }
        },
    ]);

    /** @var FrontendRenderServiceInterface $service */
    $service = Pdk::get(FrontendRenderServiceInterface::class);
    $level   = ob_get_level();

    expect($service->renderDeliveryOptions(new PdkCart()))
        ->toBe('')
        ->and(ob_get_level())
        ->toBe($level);
});
