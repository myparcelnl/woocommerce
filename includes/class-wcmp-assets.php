<?php

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
        add_action("admin_enqueue_scripts", [$this, "enqueue_admin_scripts_and_styles"], 9999);
    }

    /**
     * Load styles & scripts
     */
    public function enqueue_admin_scripts_and_styles()
    {
        global $post_type;
        $screen = get_current_screen();

        if ($post_type === "shop_order" || (is_object($screen) && strpos($screen->id, "wcmp") !== false)) {
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
                WCMP()->plugin_url() . "/assets/js/wcmp-admin.js",
                ["jquery", "thickbox"],
                WC_MYPARCEL_NL_VERSION
            );

            wp_localize_script(
                "wcmp-admin",
                "wcmp",
                [
                    "api_url"                => WCMP_Data::API_URL,
                    "actions"                => [
                        "export"        => WCMP_Export::EXPORT,
                        "add_return"    => WCMP_Export::ADD_RETURN,
                        "add_shipments" => WCMP_Export::ADD_SHIPMENTS,
                        "get_labels"    => WCMP_Export::GET_LABELS,
                        "modal_dialog"  => WCMP_Export::MODAL_DIALOG,
                    ],
                    "bulk_actions"           => [
                        "export"       => WCMP_Admin::BULK_ACTION_EXPORT,
                        "print"        => WCMP_Admin::BULK_ACTION_PRINT,
                        "export_print" => WCMP_Admin::BULK_ACTION_EXPORT_PRINT,
                    ],
                    "ajax_url"               => admin_url("admin-ajax.php"),
                    "nonce"                  => wp_create_nonce(WCMP::NONCE_ACTION),
                    "download_display"       => WCMP()->setting_collection->getByName(
                        WCMP_Settings::SETTING_DOWNLOAD_DISPLAY
                    ),
                    "ask_for_print_position" => WCMP()->setting_collection->isEnabled(
                        WCMP_Settings::SETTING_ASK_FOR_PRINT_POSITION
                    ),
                    "strings"                => [
                        "no_orders_selected" => __("You have not selected any orders!", "woocommerce-myparcel"),
                    ],
                ]
            );

            wp_enqueue_style(
                "wcmp-admin-styles",
                WCMP()->plugin_url() . "/assets/css/wcmp-admin-styles.css",
                [],
                WC_MYPARCEL_NL_VERSION,
                "all"
            );

            // Legacy styles (WC 2.1+ introduced MP6 style with larger buttons)
            if (version_compare(WOOCOMMERCE_VERSION, "2.1", "<=")) {
                wp_enqueue_style(
                    "wcmp-admin-styles-legacy",
                    WCMP()->plugin_url() . "/assets/css/wcmp-admin-styles-legacy.css",
                    [],
                    WC_MYPARCEL_NL_VERSION,
                    "all"
                );
            }
        }
    }
}

return new WCMP_Assets();
