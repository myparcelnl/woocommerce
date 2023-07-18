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
     * @param  array|int|string $data - extra types to avoid type errors in real code.
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
     * @return null|mixed|string
     */
    public function get_id()
    {
        return $this->attributes['id'] ?? null;
    }

    /**
     * @param  mixed $key
     * @param  mixed $single
     * @param  mixed $context
     *
     * @return null|mixed
     * @see          \WC_Data::get_meta()
     * @noinspection PhpUnusedParameterInspection
     */
    public function get_meta($key = '', $single = true, $context = 'view')
    {
        return $this->attributes['meta'][$key] ?? null;
    }
}
