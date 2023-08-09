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

    public function __construct()
    {
        self::$tasks = new Collection();
    }

    public function add($callback, $time, $args): void
    {
        self::$tasks->push([
            'callback' => $callback,
            'time'     => $time,
            'args'     => $args,
        ]);
    }

    public function all(): Collection
    {
        return self::$tasks;
    }
}
