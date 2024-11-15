<?php

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Tests\Mock;

/**
 * @extends \WP_Term
 */
class MockWpTerm extends MockWpClass
{
    /**
     * @param  int $termId
     * @param      $taxonomy
     *
     * @return false|\WP_Term|mixed
     */
    public static function get_instance(int $termId, $taxonomy)
    {
        return wp_cache_get($termId, 'terms');
    }
}
