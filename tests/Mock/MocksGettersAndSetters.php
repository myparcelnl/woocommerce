<?php

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Tests\Mock;

use BadMethodCallException;
use MyParcelNL\Sdk\src\Support\Str;

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
