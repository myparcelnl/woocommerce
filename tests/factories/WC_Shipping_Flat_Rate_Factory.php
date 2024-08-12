<?php

declare(strict_types=1);

use MyParcelNL\WooCommerce\Tests\Factory\AbstractWcDataFactory;

/**
 * @template T of WC_Shipping_Method
 * @method WC_Shipping_Method make()
 * @method $this withId(int $id)
 */
final class WC_Shipping_Flat_Rate_Factory extends AbstractWcDataFactory
{
    /**
     * @return string
     */
    public function getClass(): string
    {
        return WC_Shipping_Flat_Rate::class;
    }
}