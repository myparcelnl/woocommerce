<?php
/** @noinspection PhpMissingReturnTypeInspection,PhpUnhandledExceptionInspection */

declare(strict_types=1);

use MyParcelNL\Pdk\Facade\Pdk;
use MyParcelNL\WooCommerce\Tests\Mock\MockWcData;
use MyParcelNL\WooCommerce\Tests\Mock\MockWpMeta;
use MyParcelNL\WooCommerce\Tests\Mock\WordPressOptions;
use MyParcelNL\WooCommerce\Tests\Mock\WordPressScheduledTasks;

/** @see \update_post_meta() */
function update_post_meta(int $postId, string $metaKey, $metaValue): bool
{
    MockWpMeta::update($postId, $metaKey, $metaValue);

    return true;
}

/** @see \get_post_meta() */
function get_post_meta(int $postId, string $metaKey)
{
    return MockWpMeta::get($postId, $metaKey);
}

/** @see \wc_get_product() */
function wc_get_product(int $postId): WC_Product
{
    return new WC_Product($postId);
}

/** @see \get_bloginfo() */
function get_bloginfo(string $name): string
{
    return '';
}

/** @see \get_option() */
function get_option(string $name, $default = false)
{
    return WordPressOptions::getOption($name, $default);
}

/** @see \update_option() */
function update_option(string $option, $value, $autoload = null)
{
    WordPressOptions::updateOption($option, $value, $autoload);
}

/** @see \apply_filters() */
function apply_filters($tag, $value)
{
    return $value;
}

/** @see \get_woocommerce_currency() */
function get_woocommerce_currency(): string
{
    return 'EUR';
}

/**
 * @return \stdClass[]
 * @see \wc_get_order_notes()
 */
function wc_get_order_notes($args = []): array
{
    $id = $args['order_id'] ?? null;

    if (! $id) {
        return [];
    }

    $item = MockWcData::get($id);

    return $item->getAttributes()['order_notes'] ?? [];
}

/** @see \wp_schedule_single_event() */
function wp_schedule_single_event($timestamp, $callback, $args)
{
    /** @var \MyParcelNL\WooCommerce\Tests\Mock\WordPressScheduledTasks $tasks */
    $tasks = Pdk::get(WordPressScheduledTasks::class);

    $tasks->add($callback, $timestamp, $args);
}

/** @see \wc_get_orders() */
function wc_get_orders($args)
{
    // create array of 324 wc_orders
    return array_map(
        static function () {
            return new WC_Order(['id' => random_int(1, 10000)]);
        },
        range(1, 324)
    );
}

/**@see \plugin_dir_path() */
function plugin_dir_path($file): string
{
    return __DIR__ . '/../';
}
