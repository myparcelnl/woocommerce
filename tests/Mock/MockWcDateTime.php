<?php

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Tests\Mock;

use DateTime;

/**
 * @extends \WC_DateTime
 */
class MockWcDateTime extends DateTime
{
    public function date($args): string
    {
        return $this->format($args);
    }
}
