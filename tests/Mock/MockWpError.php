<?php

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Tests\Mock;

use stdClass;

class MockWpError implements StaticMockInterface
{
    public static function reset(): void
    {
        // nothing to reset for this mock
    }
}
