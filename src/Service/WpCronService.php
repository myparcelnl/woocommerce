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
        $hook = $callback;

        if (is_callable($callback)) {
            $hook                                  =
                md5(serialize($callback)); // TODO static closure is not serializable
            $allActions                            = get_option('my_parcel_all_actions', []);
            $allActions['my_parcel_hook_' . $hook] = $callback;
            update_option('my_parcel_all_actions', $allActions);
        }

        wp_schedule_single_event($timestamp, $hook, $args);
    }
}
