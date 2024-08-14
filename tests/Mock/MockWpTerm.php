<?php

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Tests\Mock;

//todo: Separate MockWpTerm from MockWcClass. It is a WP class, not a WC class.
// it should also be retrievable with wp_cache_get()

/**
 * @extends \Wp_term
 */
class MockWpTerm
{
    /**
     * @param  int $term_id
     * @param      $taxonomy
     *  $taxonomy is only used if term is not in cache. Used to retrieve the term from the database.
     *
     * @return false|\WP_Term|mixed
     */
    public static function get_instance($term_id, $taxonomy)
    {
        return wp_cache_get($term_id, 'terms');
    }
}
