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
            // Je moet hier iets maken zodat je de shipping class kan opvragen.
            // Daarvoor moet de shipping class wel bestaan dus misschien moet je die eerst nog maken in de test.
            // Wellicht moet er dus ook een mock shipping class komen.

            //Dit is even nep zodat ik verder kan met mijn leven.
            return 'bbp';

            //{
            // officiele woocommerce meuk:
            //            $term = get_term_by('id', $class_id, 'product_shipping_class');
            //
            //            if ($term && ! is_wp_error($term)) {
            //                return $term->slug;
            //            }
        }

        return '';
    }
}
