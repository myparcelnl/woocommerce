<?php

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Tests\Mock;

/**
 * @extends \WP_REST_Response
 * @see \WP_REST_Response
 */
class MockWpRestResponse extends BaseMock
{
    use MocksGettersAndSetters;

    /**
     * @param        $data
     * @param  int   $status
     * @param  array $headers
     */
    public function __construct($data = null, int $status = 200, array $headers = [])
    {
        $this->fill([
            'data'    => $data,
            'status'  => $status,
            'headers' => $headers,
        ]);
    }
}
