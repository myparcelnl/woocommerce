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
    public function shipping_methods_package_types(array $args): void
    {
        include("class-wcmp-settings-callbacks-package-types.php");

        new WCMP_Settings_Callbacks_Package_Types($args);

        // Displays option description.
        if (isset($args["description"])) {
            $this->renderTooltip($args["description"]);
        }
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
     * @param $args
     * @param $optionId
     */
    public function renderField(SettingsFieldArguments $args, string $optionId): void
    {
        woocommerce_form_field(
            "{$optionId}[{$args->id}]",
            $args->getArguments(),
            get_option($optionId)[$args->id]
        );
    }
}

return new WCMP_Settings_Callbacks();
