<?php

use MyParcelNL\WooCommerce\Tests\Factory\AbstractWcDataFactory;

/**
 * @template T of WC_Shipping_Zone
 * @method WC_Shipping_Zone make()
 * @method $this withId(int $id)
 * @method $this withData(array $data)
 */
class WC_Shipping_Zone_Factory extends AbstractWcDataFactory
{
    /**
     * @inheritDoc
     */
    public function getClass(): string
    {
        return WC_Shipping_Zone::class;
    }
}
