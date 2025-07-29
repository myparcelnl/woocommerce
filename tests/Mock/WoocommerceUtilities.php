<?php
namespace Automattic\WooCommerce\Utilities;

if (!class_exists('Automattic\WooCommerce\Utilities\OrderUtil')) {
    class OrderUtil
    {
        public static function custom_orders_table_usage_is_enabled()
        {
            return true;
        }
    }
}
