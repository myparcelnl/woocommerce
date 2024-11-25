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

defined('ABSPATH') || exit;

define('MYPARCELNL_FILE', __FILE__);
define('MYPARCELNL_DIR', __DIR__);

require(plugin_dir_path(__FILE__) . 'vendor/autoload.php');

$pluginLoader = new PluginLoader();
$pluginLoader->load();
