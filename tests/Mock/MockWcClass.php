<?php

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Tests\Mock;

use BadMethodCallException;

abstract class MockWcClass
{
    private const GETTER_PREFIX = 'get_';

    /**
     * @var array
     */
    protected $attributes;

    /**
     * @param  array $data
     */
    public function __construct(array $data = [])
    {
        $this->attributes = $data;
    }

    /**
     * @param $name
     * @param $arguments
     *
     * @return null|mixed
     */
    public function __call($name, $arguments)
    {
        if (strpos($name, self::GETTER_PREFIX) === 0) {
            $attribute = substr($name, strlen(self::GETTER_PREFIX));

            return $this->attributes[$attribute] ?? null;
        }

        throw new BadMethodCallException("Method $name does not exist");
    }

    /**
     * @param  string $key
     *
     * @return null|mixed
     */
    public function get_meta(string $key)
    {
        return $this->attributes['meta'][$key] ?? null;
    }
}
