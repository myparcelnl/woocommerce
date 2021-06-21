<?php

use MyParcelNL\Sdk\src\Model\MyParcelRequest;

if (! defined("ABSPATH")) {
    exit;
} // Exit if accessed directly

if (class_exists("WCMP_Assets")) {
    return new WCMP_Assets();
}

class WCMP_Assets
{
    public function __construct()
    {
        add_action("admin_enqueue_scripts", [$this, "enqueueScripts"], 9999);
    }

    public function enqueueScripts(): void
    {
        global $post_type;
        $screen = get_current_screen();

        if ($post_type === "shop_order" || (is_object($screen) && strpos($screen->id, "wcmp") !== false)) {
            self::enqueue_admin_scripts_and_styles();
        }
    }

    /**
     * Load styles & scripts
     */
    public static function enqueue_admin_scripts_and_styles(): void
    {
        // WC2.3+ load all WC scripts for shipping_method search!
        if (version_compare(WOOCOMMERCE_VERSION, "2.3", ">=")) {
            wp_enqueue_script("woocommerce_admin");
            wp_enqueue_script("iris");

            if (! wp_script_is("wc-enhanced-select", "registered")) {
                $suffix = defined("SCRIPT_DEBUG") && SCRIPT_DEBUG ? "" : ".min";
                wp_register_script(
                    "wc-enhanced-select",
                    WC()->plugin_url() . "/assets/js/admin/wc-enhanced-select" . $suffix . ".js",
                    ["jquery", version_compare(WC()->version, "3.2.0", ">=") ? "selectWoo" : "select2"],
                    WC_VERSION
                );
            }
            wp_enqueue_script("wc-enhanced-select");
            wp_enqueue_script("jquery-ui-sortable");
            wp_enqueue_script("jquery-ui-autocomplete");
            wp_enqueue_style(
                "woocommerce_admin_styles",
                WC()->plugin_url() . "/assets/css/admin.css",
                [],
                WC_VERSION
            );
        }

        wp_enqueue_script("thickbox");
        wp_enqueue_style("thickbox");
        wp_enqueue_script(
            "wcmp-admin",
            WCMYPA()->plugin_url() . "/assets/js/wcmp-admin.js",
            ["jquery", "thickbox"],
            WC_MYPARCEL_NL_VERSION
        );

        wp_localize_script(
            "wcmp-admin",
            "wcmp",
            [
                "api_url"                => MyParcelRequest::REQUEST_URL,
                "actions"                => [
                    "export"        => WCMP_Export::EXPORT,
                    "export_return" => WCMP_Export::EXPORT_RETURN,
                    "export_order"  => WCMP_Export::EXPORT_ORDER,
                    "get_labels"    => WCMP_Export::GET_LABELS,
                    "modal_dialog"  => WCMP_Export::MODAL_DIALOG,
                ],
                "bulk_actions"           => [
                    "export"       => WCMYPA_Admin::BULK_ACTION_EXPORT,
                    "print"        => WCMYPA_Admin::BULK_ACTION_PRINT,
                    "export_print" => WCMYPA_Admin::BULK_ACTION_EXPORT_PRINT,
                ],
                "ajax_url"               => admin_url("admin-ajax.php"),
                "nonce"                  => wp_create_nonce(WCMYPA::NONCE_ACTION),
                "download_display"       => WCMYPA()->setting_collection->getByName(
                    WCMYPA_Settings::SETTING_DOWNLOAD_DISPLAY
                ),
                "ask_for_print_position" => WCMYPA()->setting_collection->isEnabled(
                    WCMYPA_Settings::SETTING_ASK_FOR_PRINT_POSITION
                ),
                "strings"                => [
                    "no_orders_selected" => __("You have not selected any orders!", "woocommerce-myparcel"),
                    "dialog"             => [
                        "return" => __("Export options", "woocommerce-myparcel")
                    ],
                ],
            ]
        );

        wp_enqueue_style(
            "wcmp-admin-styles",
            WCMYPA()->plugin_url() . "/assets/css/wcmp-admin-styles.css",
            [],
            WC_MYPARCEL_NL_VERSION,
            "all"
        );

        // Legacy styles (WC 2.1+ introduced MP6 style with larger buttons)
        if (version_compare(WOOCOMMERCE_VERSION, "2.1", "<=")) {
            wp_enqueue_style(
                "wcmp-admin-styles-legacy",
                WCMYPA()->plugin_url() . "/assets/css/wcmp-admin-styles-legacy.css",
                [],
                WC_MYPARCEL_NL_VERSION,
                "all"
            );
        }
    }
}

return new WCMP_Assets();
