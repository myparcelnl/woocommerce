<?php

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Service;

use InvalidArgumentException;
use MyParcelNL\Pdk\Base\Contract\CronServiceInterface;
use MyParcelNL\Pdk\Facade\Pdk;

class WpCronService implements CronServiceInterface
{
    /**
     * @param  callable|string|callable-string $callback
     * @param                                  ...$args
     *
     * @return void
     * @throws \Exception
     */
    public function dispatch($callback, ...$args): void
    {
        $this->schedule($callback, time(), ...$args);
    }

    /**
     * @param  callable|string|callable-string $callback
     * @param  int                             $timestamp
     * @param                                  ...$args
     *
     * @return void
     * @throws \Exception
     */
    public function schedule($callback, int $timestamp, ...$args): void
    {
        $hook = $callback;

        if (is_callable($callback)) {
            $hook = md5(uniqid('', true));

            update_option(Pdk::get('webhookAddActions'), $this->getActions($callback, $hook));
        }

        wp_schedule_single_event($timestamp, $hook, $args);
    }

    /**
     * @param $callback
     * @param $hook
     *
     * @return array|string
     */
    private function getActions($callback, $hook)
    {
        if (! is_string($callback) && ! is_array($callback)) {
            throw new InvalidArgumentException('Invalid callback');
        }

        $callable = $callback;

        if (is_array($callback)) {
            $callable = $this->validateArrayCallback($callback);
        }

        $actions = get_option(Pdk::get('webhookAddActions'), []);

        $hookAction           = Pdk::get('webhookActionName') . $hook;
        $actions[$hookAction] = $callable;

        return $actions;
    }

    /**
     * @param  array $callback
     *
     * @return callable
     */
    private function validateArrayCallback(array $callback): callable
    {
        $class  = $callback[0] ?? null;
        $method = $callback[1] ?? null;

        return [$class ? get_class($class) : null, $method];
    }
}
