<?php

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Contract;


interface WooCommerceServiceInterface
{
    /**
     * @return bool
     * @see https://stackoverflow.com/a/77950175
     */
    public function isUsingBlocksCheckout(): bool;

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
