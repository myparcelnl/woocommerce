<?php

use WPO\WC\MyParcel\Entity\SettingsFieldArguments;

if (! defined('ABSPATH')) {
    exit;
} // Exit if accessed directly

if (class_exists('WCMP_Settings_Callbacks')) {
    return new WCMP_Settings_Callbacks();
}

class WCMP_Settings_Callbacks
{
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

        woocommerce_form_field(
            "{$optionId}[{$class->getId()}]",
            $arguments,
            get_option($optionId)[$class->getId()]
        );

        if (isset($arguments["append"])) {
            echo $arguments["append"];
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
