<?php

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\includes\Listener;

defined('ABSPATH') or die();

abstract class AbstractListener
{
    /**
     * @var callable
     */
    protected $callback;

    /**
     * @param  callable $callback
     */
    public function __construct(callable $callback)
    {
        $this->callback = $callback;
    }

    /**
     * @return void
     */
    abstract public function listen(): void;

    /**
     * When the listener instance hears its call, trigger the callback.
     *
     * @param  mixed ...$arguments
     *
     * @return void
     */
    protected function trigger(...$arguments): void
    {
        call_user_func($this->callback, ...$arguments);
    }
}
