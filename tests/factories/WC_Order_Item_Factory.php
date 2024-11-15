<?php
/** @noinspection PhpUnused */

declare(strict_types=1);

use MyParcelNL\Pdk\Tests\Factory\Contract\FactoryInterface;
use MyParcelNL\WooCommerce\Tests\Factory\AbstractWcDataFactory;

/**
 * @template T of WC_Order_Item
 * @method $this withQuantity(int $quantity)
 * @method $this withTotal(int $total)
 * @extends \MyParcelNL\WooCommerce\Tests\Factory\AbstractWcDataFactory<T>
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
