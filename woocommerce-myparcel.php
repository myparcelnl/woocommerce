<?php
/** @noinspection AutoloadingIssuesInspection */

declare(strict_types=1);

namespace MyParcelNL\WooCommerce;

/*
Plugin Name: MyParcelNL
Plugin URI: https://github.com/myparcelnl/woocommerce
Description: Export your WooCommerce orders to MyParcel and print labels directly from the WooCommerce admin
Author: MyParcel
Author URI: https://myparcel.nl
Version: 5.0.0
License: MIT
License URI: http://www.opensource.org/licenses/mit-license.php
*/

use MyParcelNL\Pdk\Base\Pdk as PdkInstance;
use Throwable;

require(plugin_dir_path(__FILE__) . 'vendor/autoload.php');

$pluginLoader = new PluginLoader();

register_activation_hook(__FILE__, [$pluginLoader, 'install']);
register_deactivation_hook(__FILE__, [$pluginLoader, 'uninstall']);

if (! function_exists('\MyParcelNL\WooCommerce\initializePdk')) {
    /**
     * Initializes the PDK. Is defined here so it can use __FILE__ to get the plugin path and url.
     */
    function initializePdk(): void
    {
        try {
            $composerJson = json_decode(file_get_contents(__DIR__ . '/composer.json'), false);

            bootPdk(
                'myparcelnl',
                'MyParcel',
                $composerJson->version,
                plugin_dir_path(__FILE__),
                plugin_dir_url(__FILE__),
                constant('WP_DEBUG')
                    ? PdkInstance::MODE_DEVELOPMENT
                    : PdkInstance::MODE_PRODUCTION
            );
        } catch (Throwable $e) {
            handleFatalError([$e->getMessage()]);
        }
    }
}

if (! function_exists('\MyParcelNL\WooCommerce\handleFatalError')) {
    /**
     * Report a fatal error and gracefully disable the plugin.
     *
     * @param  array $errors
     */
    function handleFatalError(array $errors): void
    {
        add_action('admin_init', static function () use ($errors) {
            add_action('admin_notices', static function () use ($errors) {
                echo sprintf('<div class="error"><p>%s</p></div>', implode('<br>', $errors));
            });

            deactivate_plugins(plugin_basename(__FILE__));
        });
    }
}