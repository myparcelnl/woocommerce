<?php

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Tests\Mock;

use MyParcelNL\Pdk\Base\Support\Arr;
use MyParcelNL\Pdk\Base\Support\Collection;

final class MockWpEnqueue implements StaticMockInterface
{
    /**
     * @var \MyParcelNL\Pdk\Base\Support\Collection
     */
    private static $queuedItems;

    /**
     * @param $handle
     * @param $src
     * @param $deps
     * @param $ver
     * @param $in_footer
     *
     * @return void
     */
    public static function add($handle, $src, $deps, $ver, $in_footer): void
    {
        $existing = array_filter(Arr::wrap(self::get($handle)));

        self::$queuedItems->put(
            $handle,
            array_merge($existing, [
                [
                    'src' => $src,
                    'deps' => $deps,
                    'ver' => $ver,
                    'in_footer' => $in_footer,
                ],
            ])
        );
    }

    /**
     * @return \MyParcelNL\Pdk\Base\Support\Collection
     */
    public static function all(): Collection
    {
        return self::getQueuedItems();
    }

    /**
     * @param  string $tag
     *
     * @return array
     */
    public static function get(string $tag): array
    {
        return self::getQueuedItems()
            ->get($tag, []);
    }

    public static function reset(): void
    {
        self::$queuedItems = new Collection();
    }

    public static function toArray(): array
    {
        return self::getQueuedItems()
            ->map(static function (array $actions) {
                return (new Collection(Arr::pluck($actions, 'function')))->map(static function ($function) {
                    if (! is_array($function)) {
                        return $function;
                    }

                    return implode('::', [get_class($function[0]), $function[1]]);
                });
            })
            ->toArray();
    }

    /**
     * @return \MyParcelNL\Pdk\Base\Support\Collection
     */
    private static function getQueuedItems(): Collection
    {
        if (null === self::$queuedItems) {
            self::reset();
        }

        return self::$queuedItems;
    }
}
