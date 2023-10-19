<?php
/** @noinspection PhpUndefinedMethodInspection */

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Tests\Mock;

/**
 * @extends \WC_Product
 */
class MockWcProduct extends MockWcClass
{
    protected $attributes = [
        'children'          => [],
        'weight'            => 0,
        'shipping_class_id' => 5,
    ];

    /**
     * @return int
     */
    public function get_shipping_class_id(): int
    {
        return $this->attributes['shipping_class_id'];
    }
}
