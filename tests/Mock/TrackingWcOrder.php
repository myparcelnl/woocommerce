<?php

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Tests\Mock;

use RuntimeException;
use WC_Order;

final class TrackingWcOrder extends WC_Order
{
    /**
     * @var bool
     */
    private $failOnSave = false;

    /**
     * @var int
     */
    private $saveCount = 0;

    public function failOnSave(): self
    {
        $this->failOnSave = true;

        return $this;
    }

    public function getSaveCount(): int
    {
        return $this->saveCount;
    }

    public function save(): void
    {
        $this->saveCount++;

        if ($this->failOnSave) {
            throw new RuntimeException('A cached order must not be saved');
        }
    }
}
