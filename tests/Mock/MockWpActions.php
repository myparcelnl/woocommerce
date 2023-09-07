<?php

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Tests\Mock;

use MyParcelNL\Pdk\Base\Support\Arr;
use MyParcelNL\Pdk\Base\Support\Collection;

final class MockWpActions implements StaticMockInterface
{
    /**
     * @var \MyParcelNL\Pdk\Base\Support\Collection
     */
    public static $actions;

    public function __construct()
    {
        self::reset();
    }

    /**
     * @param  string          $tag
     * @param  callable|string $functionToAdd
     * @param  int             $priority
     * @param  int             $acceptedArgs
     *
     * @return void
     */
    public static function add(string $tag, $functionToAdd, int $priority = 10, int $acceptedArgs = 1): void
    {
        $existing = array_filter(Arr::wrap(self::get($tag)));

        self::$actions->put(
            $tag,
            array_merge($existing, [
                [
                    'function'     => $functionToAdd,
                    'priority'     => $priority,
                    'acceptedArgs' => $acceptedArgs,
                ],
            ])
        );
    }

    /**
     * @return \MyParcelNL\Pdk\Base\Support\Collection
     */
    public static function all(): Collection
    {
        return self::getActions();
    }

    /**
     * @param  string $tag
     * @param  mixed  ...$args
     *
     * @return void
     */
    public static function execute(string $tag, ...$args): void
    {
        $actions = self::get($tag);

        foreach ($actions as $action) {
            call_user_func_array($action['function'], $args);
        }
    }

    public static function reset(): void
    {
        self::$actions = new Collection();
    }

    public static function toArray(): array
    {
        return self::getActions()
            ->map(static function (array $actions) {
                return (new Collection(Arr::pluck($actions, 'function')))->map(static function ($function) {
                    if (is_array($function)) {
                        return implode('::', [get_class($function[0]), $function[1]]);
                    }

                    return $function;
                });
            })
            ->toArray();
    }

    /**
     * @param  string $tag
     *
     * @return array
     */
    protected static function get(string $tag): array
    {
        return self::getActions()
            ->get($tag, []);
    }

    /**
     * @return \MyParcelNL\Pdk\Base\Support\Collection
     */
    protected static function getActions(): Collection
    {
        if (null === self::$actions) {
            self::reset();
        }

        return self::$actions;
    }
}
