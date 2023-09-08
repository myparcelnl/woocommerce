<?php

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Tests\Mock;

use BadMethodCallException;
use MyParcelNL\Pdk\Base\Support\Arr;
use MyParcelNL\Sdk\src\Support\Str;
use WC_Data;

class MockWcSession extends MockWcClass
{
    /**
     * @param $key
     *
     * @return array
     */
    public function get($key): array
    {
        return [
            'rates' => [
                'flat_rate:0' => [],
            ]
        ];
    }
}
