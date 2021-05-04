<?php

if (! defined("ABSPATH")) {
    exit;
} // Exit if accessed directly

if (class_exists("WCMPBE_Assets")) {
    return new WCMPBE_Assets();
}

class WCMPBE_Assets
{
    public function __construct()
    {
        add_action("admin_enqueue_scripts", [$this, "enqueueScripts"], 9999);
    }

    public function enqueueScripts(): void
    {
        global $post_type;
        $screen = get_current_screen();

        if ($post_type === "shop_order" || (is_object($screen) && strpos($screen->id, "wcmpbe") !== false)) {
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
            "wcmpbe-admin",
            WCMYPABE()->plugin_url() . "/assets/js/wcmpbe-admin.js",
            ["jquery", "thickbox"],
            WC_MYPARCEL_BE_VERSION
        );

        wp_localize_script(
            "wcmpbe-admin",
            "wcmpbe",
            [
                "api_url"                => WCMPBE_Data::API_URL,
                "actions"                => [
                    "export"        => WCMPBE_Export::EXPORT,
                    "add_return"    => WCMPBE_Export::ADD_RETURN,
                    "add_shipments" => WCMPBE_Export::ADD_SHIPMENTS,
                    "get_labels"    => WCMPBE_Export::GET_LABELS,
                    "modal_dialog"  => WCMPBE_Export::MODAL_DIALOG,
                ],
                "bulk_actions"           => [
                    "export"       => WCMYPABE_Admin::BULK_ACTION_EXPORT,
                    "print"        => WCMYPABE_Admin::BULK_ACTION_PRINT,
                    "export_print" => WCMYPABE_Admin::BULK_ACTION_EXPORT_PRINT,
                ],
                "ajax_url"               => admin_url("admin-ajax.php"),
                "nonce"                  => wp_create_nonce(WCMYPABE::NONCE_ACTION),
                "download_display"       => WCMYPABE()->setting_collection->getByName(
                    WCMPBE_Settings::SETTING_DOWNLOAD_DISPLAY
                ),
                "ask_for_print_position" => WCMYPABE()->setting_collection->isEnabled(
                    WCMPBE_Settings::SETTING_ASK_FOR_PRINT_POSITION
                ),
                "strings"                => [
                    "no_orders_selected" => __("You have not selected any orders!", "woocommerce-myparcelbe"),
                    "dialog" => [
                        "return" => __("Export options", "woocommerce-myparcelbe")
                    ],
                ],
            ]
        );

        wp_enqueue_style(
            "wcmpbe-admin-styles",
            WCMYPABE()->plugin_url() . "/assets/css/wcmpbe-admin-styles.css",
            [],
            WC_MYPARCEL_BE_VERSION,
            "all"
        );

        // Legacy styles (WC 2.1+ introduced MP6 style with larger buttons)
        if (version_compare(WOOCOMMERCE_VERSION, "2.1", "<=")) {
            wp_enqueue_style(
                "wcmpbe-admin-styles-legacy",
                WCMYPABE()->plugin_url() . "/assets/css/wcmpbe-admin-styles-legacy.css",
                [],
                WC_MYPARCEL_BE_VERSION,
                "all"
            );
        }
    }
}

return new WCMPBE_Assets();
