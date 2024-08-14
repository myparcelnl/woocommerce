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

    /**
     * Returns the product shipping class SLUG.
     *
     * @return string
     */
    public function get_shipping_class(): string
    {
        $class_id = $this->get_shipping_class_id();
        if ($class_id) {
            // uiteindelijk moet je de slug string returnen. Geen class. Wp_term

            // Je moet hier iets maken zodat je de shipping class kan opvragen.
            // Daarvoor moet de shipping class wel bestaan dus misschien moet je die eerst nog maken in de test.
            // Wellicht moet er dus ook een mock shipping class komen.

            $term = get_term_by('id', $class_id, 'product_shipping_class');

            return $term->slug;

        }

        return '';
    }
}
