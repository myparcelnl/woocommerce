<?php

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Contract;

interface WooCommerceServiceInterface
{
    /**
     * @return string
     */
    public function getVersion(): string;

    /**
     * @return bool
     */
    public function isActive(): bool;

    /**
     * Whether the WooCommerce Blocks checkout is enabled.
     *
     * @return bool
     */
    public function isUsingBlocksCheckout(): bool;

    /**
     * Whether HPOS is enabled in WooCommerce.
     *
     * @return bool
     */
    public function isUsingHpos(): bool;
}
