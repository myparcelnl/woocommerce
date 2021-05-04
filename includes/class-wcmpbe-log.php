<?php

if (! defined('ABSPATH')) {
    exit;
} // Exit if accessed directly

if (class_exists('WCMPBE_Log')) {
    return;
}

class WCMPBE_Log
{

    /**
     * Log data if the error logging setting is enabled.
     *
     * @param string ...$messages
     */
    public static function add(string ...$messages): void
    {
        if (! WCMYPABE()->setting_collection->isEnabled(WCMPBE_Settings::SETTING_ERROR_LOGGING)) {
            return;
        }

        $message = implode("\n", $messages);

        // Starting with WooCommerce 2.7, logging can be grouped by context and severity.
        if (class_exists("WC_Logger") && version_compare(WOOCOMMERCE_VERSION, "2.7", ">=")) {
            try {
                (wc_get_logger())->debug($message, ["source" => "wc-myparcelbe"]);
            } catch (Exception $e) {
                exit($e);
            }
            return;
        }

        if (class_exists("WC_Logger")) {
            $wc_logger = function_exists("wc_get_logger") ? wc_get_logger() : new WC_Logger();
            $wc_logger->add("wc-myparcelbe", $message);

            return;
        }

        // Old WC versions didn't have a logger
        // add file in upload folder - wp-content/uploads
        $upload_dir        = wp_upload_dir();
        $upload_base       = trailingslashit($upload_dir["basedir"]);
        $log_file          = $upload_base . "myparcelbe_log.txt";
        $current_date_time = date("Y-m-d H:i:s");
        $message           = $current_date_time . " " . $message . "n";
        file_put_contents($log_file, $message, FILE_APPEND);

        return;
    }
}
