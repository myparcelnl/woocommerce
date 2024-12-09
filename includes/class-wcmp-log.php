<?php

declare(strict_types=1);

if (! defined('ABSPATH')) {
    exit;
} // Exit if accessed directly

if (class_exists('WCMP_Log')) {
    return;
}

class WCMP_Log
{

    /**
     * Log data if the error logging setting is enabled.
     *
     * @param string ...$messages
     */
    public static function add(string ...$messages): void
    {
        if (! WCMYPA()->setting_collection->isEnabled(WCMYPA_Settings::SETTING_ERROR_LOGGING)) {
            return;
        }

        $message = implode("\n", $messages);

        // Starting with WooCommerce 2.7, logging can be grouped by context and severity.
        try {
            (wc_get_logger())->debug($message, ["source" => "wc-myparcel"]);
        } catch (Exception $e) {
            exit(esc_html($e));
        }
    }
}
