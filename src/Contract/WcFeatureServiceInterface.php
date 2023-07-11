<?php

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Contract;

interface WcFeatureServiceInterface
{
    /**
     * Whether HPOS is enabled in WooCommerce.
     */
    public function isUsingHpos(): bool;
}
