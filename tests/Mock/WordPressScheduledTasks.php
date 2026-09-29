<?php

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Tests\Mock;

use MyParcelNL\Pdk\Base\Support\Collection;

final class WordPressScheduledTasks
{
    /**
     * @var \MyParcelNL\Pdk\Base\Support\Collection
     */
    private static $tasks;

    /**
     * @var null|string
     */
    private static $failureCode;

    public function __construct()
    {
        self::$tasks       = new Collection();
        self::$failureCode = null;
    }

    /**
     * Make the next wp_schedule_single_event() call report this WordPress error code instead of
     * scheduling. Pass null to schedule normally again.
     */
    public function failWith(?string $code): void
    {
        self::$failureCode = $code;
    }

    public function failureCode(): ?string
    {
        return self::$failureCode;
    }

    /**
     * @param $callback
     * @param $time
     * @param $args
     *
     * @return void
     */
    public function add($callback, $time, $args): void
    {
        self::$tasks->push([
            'callback' => $callback,
            'time'     => $time,
            'args'     => $args,
        ]);
    }

    /**
     * @return \MyParcelNL\Pdk\Base\Support\Collection
     */
    public function all(): Collection
    {
        return self::$tasks;
    }

    /**
     * Drop every task for one hook, the way wp_unschedule_hook() does.
     *
     * @return int The number of tasks removed
     */
    public function clearHook(string $hook): int
    {
        $remaining = self::$tasks->filter(static function (array $task) use ($hook): bool {
            return $task['callback'] !== $hook;
        });

        $removed = self::$tasks->count() - $remaining->count();

        self::$tasks = new Collection($remaining->values()->all());

        return $removed;
    }
}
