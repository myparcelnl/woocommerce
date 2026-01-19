<?php

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Tests\Mock;

use BadMethodCallException;
use MyParcelNL\Sdk\Support\Str;

trait MocksGettersAndSetters
{
    /**
     * @var string
     */
    private static $getterPrefix = 'get_';

    /**
     * @var string
     */
    private static $setterPrefix = 'set_';

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [];

    /**
     * @param $name
     * @param $arguments
     *
     * @return null|mixed
     */
    public function __call($name, $arguments)
    {
        if (Str::startsWith($name, ['is_', 'needs_'])) {
            $method = self::$getterPrefix . $name;

            return $this->{$method}();
        }

        if (Str::startsWith($name, self::$getterPrefix)) {
            return $this->getAttribute($name);
        }

        if (Str::startsWith($name, self::$setterPrefix)) {
            $this->setAttribute($name, $arguments[0]);

            return null;
        }

        throw new BadMethodCallException("Method $name does not exist");
    }

    /**
     * Dynamically retrieve attributes on the model.
     *
     * @param  string $key
     *
     * @return mixed
     */
    public function __get(string $key)
    {
        return $this->attributes[$key] ?? null;
    }

    /**
     * Determine if an attribute or relation exists on the model.
     *
     * @param  string $key
     *
     * @return bool
     */
    public function __isset(string $key)
    {
        return isset($this->attributes[$key]);
    }

    /**
     * Dynamically set attributes on the model.
     *
     * @param  string $key
     * @param  mixed  $value
     *
     * @return void
     */
    public function __set(string $key, $value): void
    {
        $this->attributes[$key] = $value;
    }

    /**
     * @return array<string, mixed>
     */
    public function getAttributes(): array
    {
        return $this->attributes;
    }

    /**
     * @param  array $data
     *
     * @return void
     */
    protected function fill(array $data): void
    {
        $this->attributes = array_replace($this->attributes, $data);
    }

    /**
     * @param  string $name
     *
     * @return null|mixed
     */
    private function getAttribute(string $name)
    {
        $attribute = $this->getAttributeName($name, self::$getterPrefix);

        return $this->attributes[$attribute] ?? null;
    }

    /**
     * @param  string $method
     * @param  string $prefix
     *
     * @return string
     */
    private function getAttributeName(string $method, string $prefix): string
    {
        return substr($method, strlen($prefix));
    }

    /**
     * @param  string $name
     * @param  mixed  $value
     *
     * @return void
     */
    private function setAttribute(string $name, $value): void
    {
        $attribute = $this->getAttributeName($name, self::$setterPrefix);

        $this->attributes[$attribute] = $value;
    }
}
