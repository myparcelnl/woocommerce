<?php

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Tests\Mock;

/**
 * @extends \WP_REST_Request
 * @see \WP_REST_Request
 */
class MockWpRestRequest extends BaseMock
{
    use MocksGettersAndSetters;

    /**
     * @param  string $method
     * @param  string $route
     * @param  array  $attributes
     */
    public function __construct(string $method = '', string $route = '', array $attributes = [])
    {
        $this->fill([
            'route'       => $route,
            'method'      => $method,
            'attributes'  => $attributes,
            'params'      => [],
            'headers'     => [],
            'file_params' => [],
        ]);
    }

    public function get_attributes(): array
    {
        return $this->attributes;
    }
}
