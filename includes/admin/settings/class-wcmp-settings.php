<?php

use MyParcelNL\Sdk\src\Model\Consignment\BpostConsignment;
use MyParcelNL\Sdk\src\Model\Consignment\DPDConsignment;
use MyParcelNL\Sdk\src\Support\Arr;

if (! defined('ABSPATH')) {
    exit;
} // Exit if accessed directly

if (class_exists('WCMP_Settings')) {
    return new WCMP_Settings();
}

/**
 * Create & render settings page
 */
class WCMP_Settings
{
    public const SETTINGS_MENU_SLUG = "wcmp_settings";

    public const SETTINGS_GENERAL         = "general";
    public const SETTINGS_CHECKOUT        = "checkout";
    public const SETTINGS_EXPORT_DEFAULTS = "export_defaults";
    public const SETTINGS_BPOST           = BpostConsignment::CARRIER_NAME;
    public const SETTINGS_DPD             = DPDConsignment::CARRIER_NAME;

    /**
     * General
     */
    public const SETTING_API_KEY                 = "api_key";
    public const SETTING_AUTOMATIC_ORDER_STATUS  = "automatic_order_status";
    public const SETTING_BARCODE_IN_NOTE         = "barcode_in_note";
    public const SETTING_BARCODE_IN_NOTE_TITLE   = "barcode_in_note_title";
    public const SETTING_DOWNLOAD_DISPLAY        = "download_display";
    public const SETTING_ERROR_LOGGING           = "error_logging";
    public const SETTING_KEEP_SHIPMENTS          = "keep_shipments";
    public const SETTING_LABEL_FORMAT            = "label_format";
    public const SETTING_ORDER_STATUS_AUTOMATION = "order_status_automation";
    public const SETTING_ASK_FOR_PRINT_POSITION  = "ask_for_print_position";
    public const SETTING_PROCESS_DIRECTLY        = "process_directly";
    public const SETTING_TRACK_TRACE_EMAIL       = "track_trace_email";
    public const SETTING_TRACK_TRACE_MY_ACCOUNT  = "track_trace_my_account";

    /**
     * Export defaults
     */
    public const SETTING_SHIPPING_METHODS_PACKAGE_TYPES = "shipping_methods_package_types";
    public const SETTING_CONNECT_EMAIL                  = "connect_email";
    public const SETTING_CONNECT_PHONE                  = "connect_phone";
    public const SETTING_LARGE_FORMAT                   = "large_format";
    public const SETTING_ONLY_RECIPIENT                 = "only_recipient";
    public const SETTING_SIGNATURE                      = "signature";
    public const SETTING_AGE_CHECK                      = "age_check";
    public const SETTING_RETURN                         = "return";
    public const SETTING_INSURED                        = "insured";
    public const SETTING_INSURED_AMOUNT                 = "insured_amount";
    public const SETTING_LABEL_DESCRIPTION              = "label_description";
    public const SETTING_EMPTY_PARCEL_WEIGHT            = "empty_parcel_weight";
    public const SETTING_HS_CODE                        = "hs_code";
    public const SETTING_PACKAGE_CONTENT                = "package_contents";

    /**
     * Checkout
     */
    public const SETTING_DELIVERY_OPTIONS_CUSTOM_CSS   = "delivery_options_custom_css";
    public const SETTING_DELIVERY_OPTIONS_DISPLAY      = "delivery_options_display";
    public const SETTING_DELIVERY_OPTIONS_ENABLED      = "delivery_options_enabled";
    public const SETTING_DELIVERY_OPTIONS_POSITION     = "delivery_options_position";
    public const SETTING_DELIVERY_TITLE                = "at_home_delivery";
    public const SETTING_HEADER_DELIVERY_OPTIONS_TITLE = "header_delivery_options_title";
    public const SETTING_PICKUP_TITLE                  = "pickup_title";
    public const SETTING_SIGNATURE_TITLE               = "signature_title";
    public const SETTING_STANDARD_TITLE                = "standard_title";
    public const SETTING_USE_SPLIT_ADDRESS_FIELDS      = "use_split_address_fields";

    /*
     * Carrier settings, these will be prefixed with carrier names.
     *
     * e.g. cutoff_time => bpost_cutoff_time/dpd_cutoff_time
     */

    // Defaults
    public const SETTING_CARRIER_DEFAULT_EXPORT_INSURED   = "export_insured";
    public const SETTING_CARRIER_DEFAULT_EXPORT_SIGNATURE = "export_signature";

    // Delivery options settings
    public const SETTING_CARRIER_CUTOFF_TIME          = "cutoff_time";
    public const SETTING_CARRIER_DELIVERY_DAYS_WINDOW = "delivery_days_window";
    public const SETTING_CARRIER_DELIVERY_ENABLED     = "delivery_enabled";
    public const SETTING_CARRIER_DROP_OFF_DAYS        = "drop_off_days";
    public const SETTING_CARRIER_DROP_OFF_DELAY       = "drop_off_delay";
    public const SETTING_CARRIER_PICKUP_ENABLED       = "pickup_enabled";
    public const SETTING_CARRIER_PICKUP_FEE           = "pickup_fee";
    public const SETTING_CARRIER_PICKUP_TITLE         = "pickup_title";
    public const SETTING_CARRIER_SIGNATURE_ENABLED    = "signature_enabled";
    public const SETTING_CARRIER_SIGNATURE_FEE        = "signature_fee";

    // TODO; Currently not implemented:
    public const SETTING_CARRIER_SATURDAY_DELIVERY_ENABLED = "saturday_delivery_enabled";
    public const SETTING_CARRIER_SATURDAY_DELIVERY_FEE     = "saturday_delivery_fee";

    // Preparation for NL (or more delivery options becoming available for BE)
    public const SETTING_CARRIER_ONLY_RECIPIENT_ENABLED   = "only_recipient_enabled";
    public const SETTING_CARRIER_ONLY_RECIPIENT_FEE       = "only_recipient_fee";
    public const SETTING_CARRIER_PICKUP_EXPRESS_ENABLED   = "pickup_express_enabled";
    public const SETTING_CARRIER_PICKUP_EXPRESS_FEE       = "pickup_express_fee";
    public const SETTING_CARRIER_DELIVERY_MORNING_ENABLED = "delivery_morning_enabled";
    public const SETTING_CARRIER_DELIVERY_MORNING_FEE     = "delivery_morning_fee";
    public const SETTING_CARRIER_DELIVERY_EVENING_ENABLED = "delivery_evening_enabled";
    public const SETTING_CARRIER_DELIVERY_EVENING_FEE     = "delivery_evening_fee";

    public function __construct()
    {
        add_action("admin_menu", [$this, "menu"]);
        add_filter(
            "plugin_action_links_" . WCMP()->plugin_basename,
            [
                $this,
                "add_settings_link",
            ]
        );

        /**
         * Add the new screen to the woocommerce screen ids to make wc tooltips work.
         */
        add_filter(
            "woocommerce_screen_ids",
            function ($ids) {
                $ids[] = "woocommerce_page_" . self::SETTINGS_MENU_SLUG;
                return $ids;
            }
        );

        // Create the admin settings
        require_once("class-wcmp-settings-data.php");

        // notice for WooCommerce MyParcel plugin
        add_action("woocommerce_myparcel_before_settings_page", [$this, "myparcel_country_notice"], 10, 1);
    }

    /**
     * Add settings item to WooCommerce menu
     */
    public function menu()
    {
        add_submenu_page(
            "woocommerce",
            __("MyParcel", "woocommerce-myparcel"),
            __("MyParcel", "woocommerce-myparcel"),
            "manage_options",
            self::SETTINGS_MENU_SLUG,
            [$this, "settings_page"]
        );
    }

    /**
     * Add settings link to plugins page
     *
     * @param array $links
     *
     * @return array
     */
    public function add_settings_link(array $links): array
    {
        $url = admin_url("admin.php?page=" . self::SETTINGS_MENU_SLUG);

        array_push(
            $links,
            sprintf(
                '<a href="%s">%s</a>',
                $url,
                __("Settings", "woocommerce-myparcel")
            )
        );

        return $links;
    }

    /**
     * Output the settings pages.
     */
    public function settings_page()
    {
        $settings_tabs = apply_filters(
            self::SETTINGS_MENU_SLUG . "_tabs",
            WCMP_Settings_Data::getTabs()
        );

        $active_tab = isset($_GET["tab"]) ? $_GET["tab"] : self::SETTINGS_GENERAL;
        ?>
      <div class="wrap woocommerce">
        <h1><?php _e("WooCommerce MyParcel Settings", "woocommerce-myparcel"); ?></h1>
        <h2 class="nav-tab-wrapper">
            <?php
            foreach ($settings_tabs as $tab_slug => $tab_title) :
                printf(
                    '<a href="?page='
                    . self::SETTINGS_MENU_SLUG
                    . '&tab=%1$s" class="nav-tab nav-tab-%1$s %2$s">%3$s</a>',
                    $tab_slug,
                    (($active_tab === $tab_slug) ? "nav-tab-active" : ""),
                    $tab_title
                );
            endforeach;
            ?>
        </h2>
          <?php do_action("woocommerce_myparcel_before_settings_page", $active_tab); ?>
        <form
          method="post"
          action="options.php"
          id="<?php echo self::SETTINGS_MENU_SLUG; ?>">
            <?php
            do_action("woocommerce_myparcel_before_settings", $active_tab);
            settings_fields(self::getOptionId($active_tab));
            $this->render_settings_sections(self::getOptionId($active_tab));
            do_action("woocommerce_myparcel_after_settings", $active_tab);

            submit_button();
            ?>
        </form>
          <?php do_action("woocommerce_myparcel_after_settings_page", $active_tab); ?>
      </div>
        <?php
    }

    /**
     * Show the user a notice if they might be using the wrong plugin.
     */
    public function myparcel_country_notice()
    {
        $base_country = WC()->countries->get_base_country();

        // save or check option to hide notice
        if (Arr::get($_GET, "myparcel_hide_be_notice")) {
            update_option("myparcel_hide_be_notice", true);
            $hide_notice = true;
        } else {
            $hide_notice = get_option("myparcel_hide_be_notice");
        }

        // link to hide message when one of the premium extensions is installed
        if (! $hide_notice && $base_country === "BE") {
            $myparcel_nl_link =
                '<a href="https://wordpress.org/plugins/woocommerce-myparcel/" target="blank">WC MyParcel Netherlands</a>';
            $text             = sprintf(
                __(
                    "It looks like your shop is based in Netherlands. This plugin is for MyParcel. If you are using MyParcel Netherlands, download the %s plugin instead!",
                    "woocommerce-myparcel"
                ),
                $myparcel_nl_link
            );
            $dismiss_button   = sprintf(
                '<a href="%s" style="display:inline-block; margin-top: 10px;">%s</a>',
                add_query_arg('myparcel_hide_be_notice', 'true'),
                __("Hide this message", "woocommerce-myparcel")
            );
            printf('<div class="notice notice-warning"><p>%s %s</p></div>', $text, $dismiss_button);
        }
    }

    /**
     * @param string $option
     *
     * @return string
     */
    public static function getOptionId(string $option)
    {
        return "woocommerce_myparcel_{$option}_settings";
    }

    /**
     * Render the settings sections. Mostly taken from the WordPress equivalent but done like this so parts can
     * be overridden/changed easily.
     *
     * @param string $page - Page ID
     *
     * @see \do_settings_sections
     */
    private function render_settings_sections(string $page)
    {
        global $wp_settings_sections, $wp_settings_fields;

        if (! isset($wp_settings_sections[$page])) {
            return;
        }

        foreach ((array) $wp_settings_sections[$page] as $section) {
            echo '<div class="wcmp__settings-section">';
            $id = Arr::get($section, "id");
            $title = Arr::get($section, "title");
            $callback = Arr::get($section, "callback");

            if ($title) {
                printf('<h2 id="%s">%s</h2>', $id, $title);
            }

            if ($callback) {
                call_user_func($callback, $section);
            }

            if (! isset($wp_settings_fields)
                || ! isset($wp_settings_fields[$page])
                || ! isset($wp_settings_fields[$page][$id])) {
                continue;
            }
            echo '<table class="form-table" role="presentation">';
            $this->render_settings_fields($page, $id);
            echo "</table>";
            echo "</div>";
        }
    }

    /**
     * Mostly copied from the WordPress function.
     *
     * @param $page
     * @param $section
     *
     * @see \do_settings_fields
     */
    private function render_settings_fields($page, $section): void
    {
        global $wp_settings_fields;

        if (! Arr::get($wp_settings_fields, "$page.$section")) {
            return;
        }

        foreach (Arr::get($wp_settings_fields, "$page.$section") as $field) {
            $class = Arr::get($field, "args.class") ?? "";

            if ($class) {
                $class = is_array($class) ? implode(" ", $class) : $class;
                $class = wc_implode_html_attributes(["class", esc_attr($class)]);
            }

            echo "<tr {$class}>";

            $helpText = Arr::get($field, "args.help_text");
            $label = Arr::get($field, "args.label_for");

            printf('<th scope="row""><label class="wcmp__white-space--nowrap" %s>%s%s</label></th>',
                   $label ? "for=\"" . esc_attr($label) . "\"" : "",
                   Arr::get($field, "title"),
                   $helpText ? wc_help_tip($helpText) : ""
            );

            // Pass the option id as argument
            Arr::set($field, "args.option_id", $page);

            echo '<td>';
            call_user_func(
                Arr::get($field, "callback"),
              Arr::get($field, "args")
            );
            echo '</td>';
            echo '</tr>';
        }
    }
}

return new WCMP_Settings();
