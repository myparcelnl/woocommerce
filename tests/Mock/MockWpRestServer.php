<?php

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Tests\Mock;

use MyParcelNL\Sdk\src\Concerns\HasInstance;
use Symfony\Contracts\Service\ResetInterface;

/**
 * @see \WP_REST_Server
 */
class MockWpRestServer extends MockWpClass implements ResetInterface
{
    use HasInstance;

    public const CREATABLE = 'creatable';

    private $routes = [];

    /**
     * @return array
     */
    public function get_routes(): array
    {
        return $this->routes;
    }

    /**
     * @param  string $route_namespace
     * @param  string $route
     * @param  array  $args
     * @param  bool   $override
     *
     * @return void
     */
    public function register_route(
        string $route_namespace,
        string $route,
        array  $args = [],
        bool   $override = false
    ): void {
        $path = "$route_namespace/$route";

        $this->routes[$path] = [
            'override' => $override,
            'args'     => $args,
        ];
    }

    public function reset(): void
    {
        $this->routes = [];
    }
}
