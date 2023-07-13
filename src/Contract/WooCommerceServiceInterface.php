<?php

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Contract;

interface WooCommerceServiceInterface
{
    /**
     * Whether HPOS is enabled in WooCommerce.
     */
    public function isUsingHpos(): bool;

    /**
     * @return string
     */
    public function getVersion(): string;

    /**
     * @return bool
     */
    public function isActive(): bool;
}
