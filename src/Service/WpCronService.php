<?php

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Service;

use MyParcelNL\Pdk\Base\Contract\CronServiceInterface;

class WpCronService implements CronServiceInterface
{
    /**
     * @param  callable|string|callable-string $callback
     * @param                                  ...$args
     *
     * @return void
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
     */
    public function schedule($callback, int $timestamp, ...$args): void
    {
        wp_schedule_single_event($timestamp, $callback, $args);
    }
}
