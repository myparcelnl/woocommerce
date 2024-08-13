<?php

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Tests\Mock;

//todo: Separate MockWpTerm from MockWcClass. It is a WP class, not a WC class.
// it should also be retrievable with wp_cache_get()

/**
 * @extends \Wp_term
 */
class MockWpTerm extends MockWcClass
{
}
