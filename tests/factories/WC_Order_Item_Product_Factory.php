<?php

declare(strict_types=1);

use MyParcelNL\Pdk\Tests\Factory\Contract\FactoryInterface;
use MyParcelNL\WooCommerce\Tests\Factory\AbstractWcDataFactory;
use MyParcelNL\WooCommerce\Tests\Factory\Contract\WcDataFactoryInterface;
use function MyParcelNL\WooCommerce\Tests\wpFactory;

/**
 * @template T of WC_Order_Item_Product
 * @method WC_Order_Item_Product make()
 * @method $this withProduct(WC_Product|WcDataFactoryInterface|array $product)
 * @method $this withQuantity(int $quantity)
 * @method $this withTotal(int $total)
 */
final class WC_Order_Item_Product_Factory extends AbstractWcDataFactory
{
    public function getClass(): string
    {
        return WC_Order_Item_Product::class;
    }

    protected function createDefault(): FactoryInterface
    {
        return $this
            ->withProduct(wpFactory(WC_Product::class))
            ->withQuantity(1)
            ->withTotal(0);
    }
}
