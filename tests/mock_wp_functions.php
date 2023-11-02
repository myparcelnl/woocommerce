<?php
/** @noinspection PhpMissingReturnTypeInspection,PhpUnhandledExceptionInspection */

declare(strict_types=1);

use MyParcelNL\Pdk\Facade\Pdk;
use MyParcelNL\WooCommerce\Tests\Exception\DieException;
use MyParcelNL\WooCommerce\Tests\Mock\MockWpActions;
use MyParcelNL\WooCommerce\Tests\Mock\MockWpMeta;
use MyParcelNL\WooCommerce\Tests\Mock\MockWpUser;
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

/** @see \get_bloginfo() */
function get_bloginfo(string $name): string
{
    return '';
}

/** @see \get_locale() */
function get_locale(): string
{
    return 'nl_NL';
}

/** @see \wp_die() */
function wp_die(string $message = '', string $title = '', array $args = [])
{
    throw new DieException($message, $title);
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

/** @see \wp_schedule_single_event() */
function wp_schedule_single_event($timestamp, $callback, $args)
{
    /** @var \MyParcelNL\WooCommerce\Tests\Mock\WordPressScheduledTasks $tasks */
    $tasks = Pdk::get(WordPressScheduledTasks::class);

    $tasks->add($callback, $timestamp, $args);
}

/**@see \plugin_dir_path() */
function plugin_dir_path($file): string
{
    return __DIR__ . '/../';
}

/**@see \plugin_dir_url() */
function plugin_dir_url($file): string
{
    return sprintf('https://example.com/plugins/%s', plugin_basename($file));
}

/**@see \plugin_basename() */
function plugin_basename($file): string
{
    return basename($file, '.php');
}

/**@see \add_filter() */
function add_filter($tag, $functionToAdd, $priority = 10, $acceptedArgs = 1)
{
    MockWpActions::add($tag, $functionToAdd, $priority, $acceptedArgs);
}

/**@see \add_action() */
function add_action($tag, $functionToAdd, $priority = 10, $acceptedArgs = 1)
{
    add_filter($tag, $functionToAdd, $priority, $acceptedArgs);
}

/**@see \register_activation_hook() */
function register_activation_hook($file, $function)
{
    add_action(sprintf('activate_%s', plugin_basename($file)), $function);
}

/**@see \register_deactivation_hook() */
function register_deactivation_hook($file, $function)
{
    add_action(sprintf('deactivate_%s', plugin_basename($file)), $function);
}

/**@see \is_user_logged_in() */
function is_user_logged_in()
{
    return MockWpUser::isLoggedIn();
}

/**@see \wp_get_current_user() */
function wp_get_current_user()
{
    return MockWpUser::get();
}

/**@see \__return_false() */
function __return_false(): bool
{
    return false;
}

/**@see \__return_true() */
function __return_true(): bool
{
    return true;
}
