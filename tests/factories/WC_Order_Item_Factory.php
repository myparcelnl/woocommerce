<?php

declare(strict_types=1);

use MyParcelNL\Pdk\Tests\Factory\Contract\FactoryInterface;
use MyParcelNL\WooCommerce\Tests\Factory\AbstractWcDataFactory;

/**
 * @template T of WC_Order_Item
 * @method WC_Order_Item make()
 * @method $this withQuantity(int $quantity)
 * @method $this withTotal(int $total)
 */
final class WC_Order_Item_Factory extends AbstractWcDataFactory
{
    public function getClass(): string
    {
        return WC_Order_Item::class;
    }

    protected function createDefault(): FactoryInterface
    {
        return $this
            ->withQuantity(1)
            ->withTotal(0);
    }
}
