<?php
/** @noinspection PhpUndefinedMethodInspection */

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Tests\Mock;

use WP_Term;

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
        $classId = $this->get_shipping_class_id();
        $slug = '';
        if ($classId) {
            $term = get_term_by('id', $classId, 'product_shipping_class');
            if ($term instanceof WP_Term) {
                $slug = $term->slug;
            } elseif (is_array($term)) {
                $slug = $term['slug'] ?? null;
            }
        }

        return $slug;
    }
}
