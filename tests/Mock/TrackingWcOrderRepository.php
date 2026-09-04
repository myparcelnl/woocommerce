<?php

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Tests\Mock;

use MyParcelNL\Pdk\Base\Support\Collection;
use MyParcelNL\WooCommerce\WooCommerce\Contract\WcOrderRepositoryInterface;
use WC_Order;

final class TrackingWcOrderRepository implements WcOrderRepositoryInterface
{
    /**
     * @var \WC_Order
     */
    private $cachedOrder;

    /**
     * @var \WC_Order
     */
    private $freshOrder;

    /**
     * @var int
     */
    private $freshOrderCallCount = 0;

    /**
     * @var null|\WC_Order
     */
    private $lastCacheUpdate;

    public function __construct(WC_Order $cachedOrder, WC_Order $freshOrder)
    {
        $this->cachedOrder = $cachedOrder;
        $this->freshOrder  = $freshOrder;
    }

    public function get($input): WC_Order
    {
        return $this->cachedOrder;
    }

    public function getFresh($input): WC_Order
    {
        $this->freshOrderCallCount++;

        return $this->freshOrder;
    }

    public function find($id): ?WC_Order
    {
        return $this->cachedOrder;
    }

    public function updateCache(WC_Order $order): void
    {
        $this->cachedOrder     = $order;
        $this->lastCacheUpdate = $order;
    }

    public function getItems($input): Collection
    {
        return new Collection();
    }

    public function hasLocalPickup($input): bool
    {
        return false;
    }

    public function getFreshOrderCallCount(): int
    {
        return $this->freshOrderCallCount;
    }

    public function getLastCacheUpdate(): ?WC_Order
    {
        return $this->lastCacheUpdate;
    }
}
