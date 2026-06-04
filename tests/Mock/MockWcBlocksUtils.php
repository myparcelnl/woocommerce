<?php

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Tests\Mock;

use WP_Post;

class MockWcBlocksUtils extends MockWcClass
{
    /**
     * Check if a given page contains a particular block.
     *
     * @param  int|WP_Post $page       Page post ID or post object.
     * @param  string      $block_name The name (id) of a block, e.g. `woocommerce/cart`.
     *
     * @return bool Boolean value if the page contains the block or not. Null in case the page does not exist.
     */
    public static function has_block_in_page($page, $block_name)
    {
        $data = MockWpCache::get($page, 'pages');

        return is_array($data) && ! empty($data['hasBlocks']);
    }
}
