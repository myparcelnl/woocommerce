<?php

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Service;

use InvalidArgumentException;
use RuntimeException;
use MyParcelNL\Pdk\Base\Contract\CronServiceInterface;
use MyParcelNL\Pdk\Facade\Pdk;

class WpCronService implements CronServiceInterface
{
    /**
     * The code wp_schedule_single_event() reports when an identical event is already waiting.
     */
    private const DUPLICATE_EVENT_CODE = 'duplicate_event';

    /**
     * @param  callable|string|callable-string $callback
     * @param                                  ...$args
     *
     * @return void
     * @throws \Exception
     */
    public function dispatch($callback, ...$args): void
    {
        if (! is_string($callback) && ! is_array($callback)) {
            throw new InvalidArgumentException('Invalid callback');
        }

        $callback(...$args);
    }

    /**
     * @param  callable|string|callable-string $callback
     * @param  int                             $timestamp
     * @param                                  ...$args
     *
     * @return void
     * @throws \RuntimeException When WordPress refused to schedule the event.
     * @throws \Exception
     */
    public function schedule($callback, int $timestamp, ...$args): void
    {
        $hook = $callback;

        if (is_callable($callback)) {
            $hook = md5(uniqid('', true));

            update_option(Pdk::get('webhookAddActions'), $this->getActions($callback, $hook));
        }

        $result = wp_schedule_single_event($timestamp, $hook, $args, true);

        // An identical event that is still waiting is the outcome the caller asked for, so it is not
        // a failure. Reporting it as one would keep a migration retrying work it already queued.
        if (is_wp_error($result) && self::DUPLICATE_EVENT_CODE === $result->get_error_code()) {
            return;
        }

        if (is_wp_error($result)) {
            throw new RuntimeException(
                sprintf('Could not schedule "%s": %s', $hook, $result->get_error_message())
            );
        }

        // A filter can refuse an event by returning false, without saying why.
        if (false === $result) {
            throw new RuntimeException(sprintf('Could not schedule "%s".', $hook));
        }
    }

    /**
     * @param $callback
     * @param $hook
     *
     * @return array|string
     */
    private function getActions($callback, $hook)
    {
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
     * @return array
     */
    private function validateArrayCallback(array $callback): array
    {
        $class  = $callback[0] ?? null;
        $method = $callback[1] ?? null;

        return [$class ? get_class($class) : null, $method];
    }
}
