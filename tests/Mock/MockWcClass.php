<?php

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Tests\Mock;

use BadMethodCallException;
use MyParcelNL\Pdk\Base\Support\Arr;
use MyParcelNL\Sdk\src\Support\Str;
use WC_Data;

abstract class MockWcClass extends WC_Data
{
    private const GETTER_PREFIX = 'get_';

    /**
     * @var array
     */
    protected $attributes;

    /**
     * @param  array|int|string $data - extra types to avoid type errors in real code.
     *
     * @noinspection PhpMissingParentConstructorInspection
     */
    public function __construct($data = [])
    {
        if (is_scalar($data)) {
            $data = ['id' => $data];
        }

        foreach ($data['meta'] ?? [] as $metaKey => $metaValue) {
            update_post_meta($data['id'], $metaKey, $metaValue);
        }

        Arr::forget($data, 'meta');

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
        if (Str::startsWith($name, ['is_', 'needs_'])) {
            $method = self::GETTER_PREFIX . $name;

            return $this->{$method}();
        }

        if (Str::startsWith($name, self::GETTER_PREFIX)) {
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
     * @see \WC_Data::get_meta()
     */
    public function get_meta($key = '', $single = true, $context = 'view')
    {
        return get_post_meta($this->get_id(), $key);
    }
}
