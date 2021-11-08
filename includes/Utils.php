<?php

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\includes;

defined('ABSPATH') or die();

class Utils
{
    /**
     * Recursive toArray function.
     *
     * @param  array $array
     *
     * @return array
     * @TODO sdk#326 replace usages with $collection->toArray().
     */
    public static function toArray(array $array): array
    {
        return array_map(static function ($item) {
            return is_object($item) && method_exists($item, 'toArray')
                ? self::toArray($item->toArray())
                : $item;
        }, $array);
    }
}
