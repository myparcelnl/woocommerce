<?php
/** @noinspection PhpUnused */

declare(strict_types=1);

use MyParcelNL\Pdk\Tests\Factory\Contract\FactoryInterface;
use MyParcelNL\WooCommerce\Tests\Factory\AbstractWcDataFactory;

/**
 * @template T of WC_Order
 * @method $this withHeight(float $height)
 * @method $this withId(int $id)
 * @method $this withLength(float $length)
 * @method $this withMeta(array $meta)
 * @method $this withName(string $name)
 * @method $this withNeedsShipping(bool $bool)
 * @method $this withPrice(float $price)
 * @method $this withSku(string $sku)
 * @method $this withWeight(float $weight)
 * @method $this withWidth(float $width)
 * @method $this withSettings(array $settings)
 * @method $this withShippingClassId(int $id)
 * @extends \MyParcelNL\WooCommerce\Tests\Factory\AbstractWcDataFactory<T>
 */
final class WC_Product_Factory extends AbstractWcDataFactory
{
    public function getClass(): string
    {
        return WC_Product::class;
    }

    protected function createDefault(): FactoryInterface
    {
        return $this
            ->withName('Test product')
            ->withSku('WVS-0001')
            ->withNeedsShipping(true);
    }
}
