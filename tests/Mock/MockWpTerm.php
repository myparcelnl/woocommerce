<?php

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Tests\Mock;

/**
 * @extends \Wp_term
 */
class MockWpTerm
{
    /**
     * @param  int $termId
     * @param      $taxonomy
     *  $taxonomy is only used if term is not in cache. Used to retrieve the term from the database.
     *  in this test $taxonomy is not used. But it is here for completeness.
     *
     * @return false|\WP_Term|mixed
     */
    public static function get_instance(int $termId, $taxonomy)
    {
        return wp_cache_get($termId, 'terms');
    }
}
