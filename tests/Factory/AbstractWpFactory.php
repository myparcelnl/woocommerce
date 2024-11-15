<?php

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Tests\Factory;

use BadMethodCallException;
use MyParcelNL\Pdk\Tests\Factory\AbstractFactory;
use MyParcelNL\Sdk\src\Support\Str;
use MyParcelNL\WooCommerce\Tests\Factory\Contract\WpFactoryInterface;

/**
 * @template T
 * @implements \MyParcelNL\WooCommerce\Tests\Factory\Contract\WpFactoryInterface<T>
 */
abstract class AbstractWpFactory extends AbstractFactory implements WpFactoryInterface
{
    /**
     * @var array<string, T>
     */
    private $cache = [];

    /**
     * @param  mixed $name
     * @param  mixed $arguments
     *
     * @return $this
     */
    public function __call($name, $arguments)
    {
        if (Str::startsWith($name, 'with')) {
            $attribute = Str::snake(Str::after($name, 'with'));
            $value     = $arguments[0];

            return $this->with([$attribute => $value]);
        }

        throw new BadMethodCallException(sprintf('Method %s does not exist', $name));
    }

    /**
     * @return T
     */
    public function make()
    {
        $model      = $this->getClass();
        $attributes = $this->resolveAttributes();

        $cacheKey = sprintf('%s::%s', $model, md5(json_encode($attributes)));

        if (! isset($this->cache[$cacheKey])) {
            $this->cache[$cacheKey] = new $model($attributes);
        }

        return $this->cache[$cacheKey];
    }

    /**
     * @return T
     */
    public function store()
    {
        return $this->make();
    }

    /**
     * @param  array|\MyParcelNL\Pdk\Base\Support\Collection $data
     *
     * @return $this
     */
    public function with($data): WpFactoryInterface
    {
        $this->attributes = $this->attributes->merge($data);

        return $this;
    }
}