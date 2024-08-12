<?php

declare(strict_types=1);

use MyParcelNL\WooCommerce\Tests\Factory\AbstractWcDataFactory;

//todo: kijk of al deze methods nodig zijn.
// doe dit door in de checkout te kijken welke properties er bestaan.

/**
 * @template T of WC_Shipping_Method
 * @method WC_Shipping_Method make()
 * @method $this withId(int $id)
 */
final class WC_Shipping_Method_Factory extends AbstractWcDataFactory
{
    public function getClass(): string
    {
        return WC_Shipping_Method::class;
    }
}