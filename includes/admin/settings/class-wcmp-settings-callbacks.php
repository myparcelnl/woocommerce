<?php

use WPO\WC\MyParcelBE\Entity\SettingsFieldArguments;

if (! defined('ABSPATH')) {
    exit;
} // Exit if accessed directly

if (class_exists('WCMP_Settings_Callbacks')) {
    return new WCMP_Settings_Callbacks();
}

class WCMP_Settings_Callbacks
{
    /**
     * @return array
     */
    public static function getShippingMethods(): array
    {
        $shippingMethods     = [];
        $wc_shipping_methods = WC()->shipping->load_shipping_methods();

        if ($wc_shipping_methods) {
            foreach ($wc_shipping_methods as $key => $shipping_method) {
                // Automattic / WooCommerce Table Rate Shipping
                if ($key == 'table_rate' && class_exists('WC_Table_Rate_Shipping')
                    && class_exists('WC_Shipping_Zones')) {
                    $zones = WC_Shipping_Zones::get_zones();
                    foreach ($zones as $zone_data) {
                        if (isset($zone_data['id'])) {
                            $zone_id = $zone_data['id'];
                        } elseif (isset($zone_data['zone_id'])) {
                            $zone_id = $zone_data['zone_id'];
                        } else {
                            continue;
                        }
                        $zone         = WC_Shipping_Zones::get_zone($zone_id);
                        $zone_methods = $zone->get_shipping_methods(false);
                        foreach ($zone_methods as $key => $shipping_method) {
                            if ($shipping_method->id === 'table_rate'
                                && method_exists(
                                    $shipping_method,
                                    'get_shipping_rates'
                                )) {
                                $zone_table_rates = $shipping_method->get_shipping_rates();
                                foreach ($zone_table_rates as $zone_table_rate) {
                                    $rate_label =
                                        ! empty($zone_table_rate->rate_label) ? $zone_table_rate->rate_label
                                            : "{$shipping_method->title} ({$zone_table_rate->rate_id})";

                                    $shippingMethods["table_rate:{$shipping_method->instance_id}:{$zone_table_rate->rate_id}"] =
                                        "{$zone->get_zone_name()} - {$rate_label}";
                                }
                            }
                        }
                    }
                    continue;
                }

                // Bolder Elements Table Rate Shipping
                if ($key == 'betrs_shipping' && is_a($shipping_method, 'BE_Table_Rate_Method')
                    && class_exists('WC_Shipping_Zones')) {
                    $zones = WC_Shipping_Zones::get_zones();

                    foreach ($zones as $zone_data) {
                        if (isset($zone_data['id'])) {
                            $zone_id = $zone_data['id'];
                        } elseif (isset($zone_data['zone_id'])) {
                            $zone_id = $zone_data['zone_id'];
                        } else {
                            continue;
                        }
                        $zone         = WC_Shipping_Zones::get_zone($zone_id);
                        $zone_methods = $zone->get_shipping_methods(false);
                        foreach ($zone_methods as $key => $shipping_method) {
                            if ($shipping_method->id === 'betrs_shipping') {
                                $shipping_method_options = get_option(
                                    $shipping_method->id . '_options-' . $shipping_method->instance_id
                                );
                                if (isset($shipping_method_options['settings'])) {
                                    foreach ($shipping_method_options['settings'] as $zone_table_rate) {
                                        $rate_label =
                                            ! empty($zone_table_rate['title']) ? $zone_table_rate['title']
                                                : "{$shipping_method->title} ({$zone_table_rate['option_id']})";

                                        $shippingMethods["betrs_shipping_{$shipping_method->instance_id}-{$zone_table_rate['option_id']}"] =
                                            "{$zone->get_zone_name()} - {$rate_label}";
                                    }
                                }
                            }
                        }
                    }
                    continue;
                }
                $method_title          =
                    ! empty($wc_shipping_methods[$key]->method_title) ? $wc_shipping_methods[$key]->method_title
                        : $wc_shipping_methods[$key]->title;
                $shippingMethods[$key] = $method_title;

                // split flat rate by shipping class
                if (($key == 'flat_rate' || $key == 'legacy_flat_rate')
                    && version_compare(WOOCOMMERCE_VERSION, '2.4', '>=')) {
                    $shipping_classes = WC()->shipping->get_shipping_classes();
                    foreach ($shipping_classes as $shipping_class) {
                        if (! isset($shipping_class->term_id)) {
                            continue;
                        }
                        $id   = $shipping_class->term_id;
                        $name = esc_html("{$method_title} - {$shipping_class->name}");

                        $method_class                   = esc_attr($key) . ":" . $id;
                        $shippingMethods[$method_class] = $name;
                    }
                }
            }
        }

        return $shippingMethods;
    }

    /**
     * Validate options.
     *
     * @param array $input options to valid.
     *
     * @return array        validated options.
     */
    public function validate($input)
    {
        // Create our array for storing the validated options.
        $output = [];

        if (empty($input) || ! is_array($input)) {
            return $input;
        }

        // Loop through each of the incoming options.
        foreach ($input as $key => $value) {
            // Check to see if the current option has a value. If so, process it.
            if (isset($input[$key])) {
                if (is_array($input[$key])) {
                    foreach ($input[$key] as $sub_key => $sub_value) {
                        $output[$key][$sub_key] = $input[$key][$sub_key];
                    }
                } else {
                    $output[$key] = $input[$key];
                }
            }
        }

        // Return the array processing any additional functions filtered by this action.
        return apply_filters('wcmp_settings_validate_input', $input, $input);
    }

    /**
     * @param array $args
     */
    public function enhanced_select(array $args): void
    {
        include("class-wcmp-settings-callbacks-enhanced-select.php");

        new WCMP_Settings_Callbacks_Enhanced_Select($args);
    }

    /**
     * Echoes a woocommerce help tip.
     *
     * @param string $content - Can contain HTML.
     */
    private function renderTooltip(string $content): void
    {
        echo wc_help_tip($content, true);
    }

    /**
     * @param array $args
     */
    public function renderSection(array $args): void
    {
        if (isset($args["description"])) {
            echo "<p>{$args["description"]}</p>";
        }
    }

    /**
     * Output a WooCommerce style form field.
     *
     * @param SettingsFieldArguments $class
     * @param string                 $optionId
     */
    public function renderField(SettingsFieldArguments $class, string $optionId): void
    {
        $arguments = $class->getArguments();

        if (isset($arguments["description"])) {
            $description = $arguments["description"];
            unset ($arguments["description"]);
        }

        if (isset($arguments["parent"])) {
            echo "<hr>";
        }

        woocommerce_form_field(
            "{$optionId}[{$class->getId()}]",
            $arguments,
            get_option($optionId)[$class->getId()]
        );
        if (isset($arguments["parent"])) {
            echo "<hr>";
        }

        // Render the description here instead of inside the above function.
        if (isset($description)) {
            $this->renderDescription($description);
        }
    }

    /**
     * Get the order statuses as options array.
     *
     * @return array
     */
    public function get_order_status_options(): array
    {
        $order_statuses = [];

        if (version_compare(WOOCOMMERCE_VERSION, '2.2', '<')) {
            $statuses = (array) get_terms('shop_order_status', ['hide_empty' => 0, 'orderby' => 'id']);
            foreach ($statuses as $status) {
                $order_statuses[esc_attr($status->slug)] = esc_html__($status->name, 'woocommerce');
            }
        } else {
            $statuses = wc_get_order_statuses();
            foreach ($statuses as $status_slug => $status) {
                $status_slug = 'wc-' === substr($status_slug, 0, 3) ? substr($status_slug, 3) : $status_slug;

                $order_statuses[$status_slug] = $status;
            }
        }

        return $order_statuses;
    }

    /**
     * @param $description
     */
    private function renderDescription($description)
    {
        echo "<p>$description</p>";
    }
}

return new WCMP_Settings_Callbacks();
