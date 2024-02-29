<?php

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Tests\Mock;

class BaseMock
{
    /**
     * @param $name
     * @param $arguments
     *
     * @return static
     */
    public static function __callStatic($name, $arguments)
    {
        return new static();
    }

    /**
     * @param $name
     * @param $arguments
     *
     * @return $this
     */
    public function __call($name, $arguments)
    {
        return $this;
    }
}
