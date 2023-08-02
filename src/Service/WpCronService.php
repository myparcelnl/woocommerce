<?php

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Service;

use MyParcelNL\Pdk\Base\Contract\CronServiceInterface;

class WpCronService implements CronServiceInterface
{
    /**
     * @param  callable $callback
     * @param           ...$args
     *
     * @return void
     */
    public function dispatch(callable $callback, ...$args): void
    {
        $this->schedule($callback, time(), ...$args);
    }

    /**
     * @param  callable $callback
     * @param  int      $timestamp
     * @param           ...$args
     *
     * @return void
     */
    public function schedule(callable $callback, int $timestamp, ...$args): void
    {
        wp_schedule_single_event($timestamp, $callback, $args);
    }
}
