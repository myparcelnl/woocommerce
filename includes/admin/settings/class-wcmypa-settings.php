<?php

use MyParcelNL\Sdk\src\Support\Arr;

if (! defined('ABSPATH')) {
    exit;
} // Exit if accessed directly

if (class_exists('WCMYPA_Settings')) {
    return new WCMYPA_Settings();
}

/**
 * Create & render settings page
 */
class WCMYPA_Settings
{
    /*
     * Carrier settings, these will be prefixed with carrier names.
     *
     * e.g. cutoff_time => postnl_cutoff_time/dpd_cutoff_time
     */
    // Defaults
    // Delivery options settings

    // Saturday delivery
    // TODO; Currently not implemented
    // Monday delivery

    public function __construct()
    {
        add_action('admin_menu', [$this, 'menu']);
        add_filter(
            'plugin_action_links_' . WCMYPA()->pluginBasename,
            [
                $this,
                'add_settings_link',
            ]
        );

        /**
         * Add the new screen to the woocommerce screen ids to make wc tooltips work.
         */
        add_filter(
            'woocommerce_screen_ids',
            function ($ids) {
                $ids[] = 'woocommerce_page_' . 'wcmp_settings';

                return $ids;
            }
        );

        // Create the admin settings
        require_once('class-wcmp-settings-data.php');

        // notice for MyParcel plugin
        add_action('woocommerce_myparcel_before_settings_page', [$this, 'myparcel_country_notice'], 10, 1);
    }

    /**
     * @param  string $option
     *
     * @return string
     */
    public static function getOptionId(string $option): string
    {
        return "woocommerce_myparcel_{$option}_settings";
    }

    /**
     * @return string
     */
    public static function getSettingsUrl(): string
    {
        return admin_url('admin.php?page=' . 'wcmp_settings');
    }

    /**
     * @return bool whether the current script is running on one of this plugins own admin settings pages
     */
    public static function isViewingOwnSettingsPage(): bool
    {
        return (isset($_GET['page']) && 'wcmp_settings' === $_GET['page']);
    }

    /**
     * Add settings link to plugins page
     *
     * @param  array $links
     *
     * @return array
     */
    public function add_settings_link(array $links): array
    {
        $url     = self::getSettingsUrl();
        $links[] = sprintf(
            '<a href="%s">%s</a>',
            $url,
            __('Settings', 'woocommerce-myparcel')
        );

        return $links;
    }

    /**
     * Add settings item to WooCommerce menu
     */
    public function menu()
    {
        add_submenu_page(
            'woocommerce',
            __('MyParcel', 'woocommerce-myparcel'),
            __('MyParcel', 'woocommerce-myparcel'),
            'manage_options',
            'wcmp_settings',
            [$this, 'settings_page']
        );
    }

    /**
     * Show the user a notice if they might be using the wrong plugin.
     */
    public function myparcel_country_notice(): void
    {
        $base_country = WC()->countries->get_base_country();

        // save or check option to hide notice
        if (Arr::get($_GET, 'myparcel_hide_be_notice')) {
            update_option('myparcel_hide_be_notice', true);
            $hide_notice = true;
        } else {
            $hide_notice = get_option('myparcel_hide_be_notice');
        }

        // link to hide message when one of the premium extensions is installed
        if (! $hide_notice && $base_country === 'BE') {
            $myparcel_nl_link =
                '<a href="https://wordpress.org/plugins/woocommerce-myparcel/" target="blank">WC MyParcel Netherlands</a>';
            $text             = sprintf(
                __(
                    'It looks like your shop is based in Netherlands. This plugin is for MyParcel. If you are using MyParcel Netherlands, download the %s plugin instead!',
                    'woocommerce-myparcel'
                ),
                $myparcel_nl_link
            );
            $dismiss_button   = sprintf(
                '<a href="%s" style="display:inline-block; margin-top: 10px;">%s</a>',
                add_query_arg('myparcel_hide_be_notice', 'true'),
                __('Hide this message', 'woocommerce-myparcel')
            );
            printf('<div class="notice notice-warning"><p>%s %s</p></div>', $text, $dismiss_button);
        }
    }

    /**
     * Output the settings pages.
     */
    public function settings_page()
    {
        $settings_tabs = apply_filters(
            'wcmp_settings' . '_tabs',
            WCMP_Settings_Data::getTabs()
        );

        $active_tab = $_GET['tab'] ?? 'general';
        ?>
      <div class="wrap woocommerce">
        <h1><?php
            _e('MyParcel Settings', 'woocommerce-myparcel'); ?></h1>
        <h2 class="nav-tab-wrapper">
            <?php
            foreach ($settings_tabs as $tab_slug => $tab_title) :
                printf(
                    '<a href="?page='
                    . 'wcmp_settings'
                    . '&tab=%1$s" class="nav-tab nav-tab-%1$s %2$s">%3$s</a>',
                    $tab_slug,
                    (($active_tab === $tab_slug) ? 'nav-tab-active' : ''),
                    $tab_title
                );
            endforeach;
            ?>
        </h2>
          <?php
          do_action('woocommerce_myparcel_before_settings_page', $active_tab); ?>
        <form
          method="post"
          action="options.php"
          id="<?php
          echo 'wcmp_settings'; ?>">
            <?php
            do_action('woocommerce_myparcel_before_settings', $active_tab);
            settings_fields(self::getOptionId($active_tab));
            $this->render_settings_sections(self::getOptionId($active_tab));
            do_action('woocommerce_myparcel_after_settings', $active_tab);

            submit_button();
            ?>
        </form>
          <?php
          do_action('woocommerce_myparcel_after_settings_page', $active_tab); ?>
      </div>
        <?php
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
            $class = Arr::get($field, 'args.class') ?? '';

            if ($class) {
                $class = is_array($class) ? implode(' ', $class) : $class;
                $class = wc_implode_html_attributes(['class' => esc_attr($class)]);
            }

            echo "<tr {$class}>";

            $helpText = Arr::get($field, 'args.help_text');
            $label    = Arr::get($field, 'args.label_for');

            printf(
                '<th scope="row"><label class="wcmp__ws--nowrap" %s>%s%s</label></th>',
                $label ? "for=\"" . esc_attr($label) . "\"" : '',
                Arr::get($field, 'title'),
                $helpText ? wc_help_tip($helpText) : ''
            );

            // Pass the option id as argument
            Arr::set($field, 'args.option_id', $page);

            echo '<td>';
            call_user_func(
                Arr::get($field, 'callback'),
                Arr::get($field, 'args')
            );
            echo '</td>';
            echo '</tr>';
        }
    }

    /**
     * Render the settings sections. Mostly taken from the WordPress equivalent but done like this so parts can
     * be overridden/changed easily.
     *
     * @param  string $page - Page ID
     *
     * @see \do_settings_sections
     */
    private function render_settings_sections(string $page): void
    {
        global $wp_settings_sections, $wp_settings_fields;

        if (! isset($wp_settings_sections[$page])) {
            return;
        }

        foreach ((array) $wp_settings_sections[$page] as $section) {
            echo '<div class="wcmp__settings-section">';
            $id       = Arr::get($section, 'id');
            $title    = Arr::get($section, 'title');
            $callback = Arr::get($section, 'callback');

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
            echo '</table>';
            echo '</div>';
        }
    }
}

return new WCMYPA_Settings();
