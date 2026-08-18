<?php

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Tests\Mock;

use MyParcelNL\Pdk\Context\Model\ContextBag;
use MyParcelNL\Pdk\Context\Service\ContextService;
use RuntimeException;

/**
 * Context service that always fails to build a context.
 *
 * The checkout context calls the capabilities endpoint, which can fail for reasons the shop cannot
 * control: an empty destination country, an expired API key or an outage. Every caller must survive
 * such a failure without breaking the page it renders. Bind this stub to
 * {@see \MyParcelNL\Pdk\Context\Contract\ContextServiceInterface} to give a test that failure.
 */
final class MockThrowingContextService extends ContextService
{
    public const MESSAGE = 'capabilities unavailable';

    /**
     * @param  array $contexts
     * @param  array $data
     *
     * @return \MyParcelNL\Pdk\Context\Model\ContextBag
     */
    public function createContexts(array $contexts, array $data = []): ContextBag
    {
        throw new RuntimeException(self::MESSAGE);
    }
}
