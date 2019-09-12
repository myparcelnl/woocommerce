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

    const EXTRA_WIDTH_FOR_NUMBER_INPUT = 200;
    const STEPS_NUMBER_INPUT_FIELDS    = "0.01";

    /**
     * Checkbox callback.
     * args:
     *   option_name - name of the main option
     *   id          - key of the setting
     *   value       - value if not 1 (optional)
     *   default     - default setting (optional)
     *   description - description (optional)
     *
     * @param $args
     *
     * @return void.
     */
    public function checkbox($args)
    {
        extract($this->normalize_settings_args($args));

        $args["type"] = "checkbox";

        $this->renderField($args);

        /**
         *
         */
        // output description.
        if (isset($description)) {
            $this->renderTooltip($description);
        }
    }

    /**
     * Text input callback.
     * args:
     *   option_name - name of the main option
     *   id          - key of the setting
     *   size        - size of the text input (em)
     *   default     - default setting (optional)
     *   description - description (optional)
     *   type        - type (optional)
     *
     * @param $args
     *
     * @return void.
     */
    public function text_input($args)
    {
        extract($this->normalize_settings_args($args));
        if (empty($type)) {
            $type = 'text';
        }

        if ($type == 'number') {
            $width = ($size) + self::EXTRA_WIDTH_FOR_NUMBER_INPUT;
            $style = "width: {$width}px";
            $step  = self::STEPS_NUMBER_INPUT_FIELDS;
        } else {
            $style = '';
            $step  = '';
        }

        printf(
            '<input type="%1$s" id="%2$s" name="%3$s" value="%4$s" size="%5$s" step="%6$s" placeholder="%7$s" class="%8$s" style="%9$s"/>',
            $type,
            $id,
            $setting_name,
            $current,
            $size,
            $step,
            $placeholder,
            $class,
            $style
        );

        // output description.
        if (isset($description)) {
            $this->renderTooltip($description);
        }
    }

//    /**
//     * Color picker callback.
//     * args:
//     *   option_name - name of the main option
//     *   id          - key of the setting
//     *   size        - size of the text input (em)
//     *   default     - default setting (optional)
//     *   description - description (optional)
//     *
//     * @param $args
//     *
//     * @return void.
//     */
//    public function color_picker($args)
//    {
//        extract($this->normalize_settings_args($args));
//
//        printf(
//            '<input type="text" id="%1$s" name="%2$s" value="%3$s" size="%4$s" class="wcmp-color-picker %5$s"/>',
//            $id,
//            $setting_name,
//            $current,
//            $size,
//            $class
//        );
//
//        // output description.
//        if (isset($description)) {
//            $this->showTooltip($description);
//        }
//    }

    /**
     * Textarea callback.
     * args:
     *   option_name - name of the main option
     *   id          - key of the setting
     *   width       - width of the text input (em)
     *   height      - height of the text input (lines)
     *   default     - default setting (optional)
     *   description - description (optional)
     *
     * @param $args
     *
     * @return void.
     */
    public function textarea($args)
    {
        extract($this->normalize_settings_args($args));

        // output tooltip.
        if (isset($description)) {
            $this->renderTooltip($description);
        }

        printf(
            '<textarea id=" % 1$s" name=" % 2$s" cols=" % 4$s" rows=" % 5$s" placeholder=" % 6$s"/>%3$s</textarea>',
            $id,
            $setting_name,
            $current,
            $width,
            $height,
            $placeholder
        );
    }

    /**
     * Select element callback.
     *
     * @param array $args Field arguments.
     *
     * @return string      Select field.
     */
    public function select($args)
    {
        extract($this->normalize_settings_args($args));

        printf('<select id=" % 1$s" name=" % 2$s" class=" % 3$s">', $id, $setting_name, $class);

        foreach ($options as $key => $label) {
            printf('<option value=" % s" %s>%s</option>', $key, selected($current, $key, false), $label);
        }

        echo '</select>';

        if (isset($custom)) {
            printf('<div class=" % 1$s_custom custom">', $id);

            switch ($custom['type']) {
                case 'text_element_callback':
                    $this->text_input($custom['args']);
                    break;
                case 'multiple_text_element_callback':
                    $this->multiple_text_input($custom['args']);
                    break;
                default:
                    break;
            }
            echo '</div>';
        }

        // Displays option description.
        if (isset($args['description'])) {
            printf('<p class="description">%s</p>', $args['description']);
        }
    }

    /**
     * Multiple text element callback.
     *
     * @param array $args Field arguments.
     *
     * @return string       Text input field.
     */
    public function multiple_text_input($args)
    {
        extract($this->normalize_settings_args($args));

        if (! empty($header)) {
            echo "<p><strong>{$header}</strong>:</p>";
        }

        foreach ($fields as $name => $field) {
            $label       = $field['label'];
            $size        = $field['size'];
            $placeholder = isset($field['placeholder']) ? $field['placeholder'] : '';

            if (isset($field['label_width'])) {
                $style = sprintf('style="display:inline - block; width:%1$s;"', $field['label_width']);
            } else {
                $style = '';
            }

            $suffix = isset($field['box_number']) ? $field['box_number'] : '';

            // output field label
            printf('<label for=" % 1$s_ % 2$s" %3$s>%4$s</label>', $id, $name, $style, $label);

            // output field
            $field_current = isset($current[$name]) ? $current[$name] : '';
            printf(
                '<input type="text" id="%1$s_%3$s" name="%2$s[%3$s]" value="%4$s" size="%5$s" placeholder="%6$s"/>%7$s<br/>',
                $id,
                $setting_name,
                $name,
                $field_current,
                $size,
                $placeholder,
                $suffix
            );
        }

        // Displays option description.
        if (isset($args['description'])) {
            printf('<p class="description">%s</p>', $args['description']);
        }
    }

    public function shipping_methods_package_types($args)
    {
        extract($this->normalize_settings_args($args));
        foreach ($package_types as $package_type => $package_type_title) {
            printf('<div class="package_type_title">%s:<div>', $package_type_title);
            $args['package_type'] = $package_type;
            unset($args['description']);
            $this->shipping_method_search($args);
        }
        // Displays option description.
        if (isset($description)) {
            $this->renderTooltip($description);
        }
    }

    // Shipping method search callback.
    public function shipping_method_search($args)
    {
        extract($this->normalize_settings_args($args));

        if (isset($package_type)) {
            $setting_name = "{$setting_name}[{$package_type}]";
            $current      = isset($current[$package_type]) ? $current[$package_type] : '';
        }

        // get shipping methods
        $available_shipping_methods = [];
        $shipping_methods           = WC()->shipping->load_shipping_methods();

        if ($shipping_methods) {
            foreach ($shipping_methods as $key => $shipping_method) {
                // Automattic / WooCommerce Table Rate Shipping
                if ($key == 'table_rate' && class_exists('WC_Table_Rate_Shipping')
                    && class_exists(
                        'WC_Shipping_Zones'
                    )) {
                    $zones = WC_Shipping_Zones::get_zones();
                    foreach ($zones as $zone_data) {
                        if (isset($zone_data['id'])) {
                            $zone_id = $zone_data['id'];
                        } else if (isset($zone_data['zone_id'])) {
                            $zone_id = $zone_data['zone_id'];
                        } else {
                            continue;
                        }
                        $zone         = WC_Shipping_Zones::get_zone($zone_id);
                        $zone_methods = $zone->get_shipping_methods(false);
                        foreach ($zone_methods as $key => $shipping_method) {
                            if ($shipping_method->id == 'table_rate'
                                && method_exists(
                                    $shipping_method,
                                    'get_shipping_rates'
                                )) {
                                $zone_table_rates = $shipping_method->get_shipping_rates();
                                foreach ($zone_table_rates as $zone_table_rate) {
                                    $rate_label                                                                                           =
                                        ! empty($zone_table_rate->rate_label) ? $zone_table_rate->rate_label
                                            : "{$shipping_method->title} ({$zone_table_rate->rate_id})";
                                    $available_shipping_methods["table_rate:{$shipping_method->instance_id}:{$zone_table_rate->rate_id}"] =
                                        "{$zone->get_zone_name()} - {$rate_label}";
                                }
                            }
                        }
                    }
                    continue;
                }

                // Bolder Elements Table Rate Shipping
                if ($key == 'betrs_shipping' && is_a($shipping_method, 'BE_Table_Rate_Method')
                    && class_exists(
                        'WC_Shipping_Zones'
                    )) {
                    $zones = WC_Shipping_Zones::get_zones();
                    foreach ($zones as $zone_data) {
                        if (isset($zone_data['id'])) {
                            $zone_id = $zone_data['id'];
                        } else if (isset($zone_data['zone_id'])) {
                            $zone_id = $zone_data['zone_id'];
                        } else {
                            continue;
                        }
                        $zone         = WC_Shipping_Zones::get_zone($zone_id);
                        $zone_methods = $zone->get_shipping_methods(false);
                        foreach ($zone_methods as $key => $shipping_method) {
                            if ($shipping_method->id == 'betrs_shipping') {
                                $shipping_method_options = get_option(
                                    $shipping_method->id . '_options-' . $shipping_method->instance_id
                                );
                                if (isset($shipping_method_options['settings'])) {
                                    foreach ($shipping_method_options['settings'] as $zone_table_rate) {
                                        $rate_label                                                                                                   =
                                            ! empty($zone_table_rate['title']) ? $zone_table_rate['title']
                                                : "{$shipping_method->title} ({$zone_table_rate['option_id']})";
                                        $available_shipping_methods["betrs_shipping_{$shipping_method->instance_id}-{$zone_table_rate['option_id']}"] =
                                            "{$zone->get_zone_name()} - {$rate_label}";
                                    }
                                }
                            }
                        }
                    }
                    continue;
                }
                $method_title                     =
                    ! empty($shipping_methods[$key]->method_title) ? $shipping_methods[$key]->method_title
                        : $shipping_methods[$key]->title;
                $available_shipping_methods[$key] = $method_title;

                // split flat rate by shipping class
                if (($key == 'flat_rate' || $key == 'legacy_flat_rate')
                    && version_compare(WOOCOMMERCE_VERSION, '2.4', '>=')) {
                    $shipping_classes = WC()->shipping->get_shipping_classes();
                    foreach ($shipping_classes as $shipping_class) {
                        if (! isset($shipping_class->term_id)) {
                            continue;
                        }
                        $id                                        = $shipping_class->term_id;
                        $name                                      =
                            esc_html("{$method_title} - {$shipping_class->name}");
                        $method_class                              = esc_attr($key) . ":" . $id;
                        $available_shipping_methods[$method_class] = $name;
                    }
                }
            }
        }

        ?>
        <select id="<?php echo $id; ?>"
                name="<?php echo $setting_name; ?>[]"
                style="width: 50%;"
                class="wc-enhanced-select"
                multiple="multiple"
                data-placeholder="<?php echo $placeholder; ?>">
            <?php
            $shipping_methods_selected = (array) $current;

            $shipping_methods = WC()->shipping->load_shipping_methods();
            if ($available_shipping_methods) {
                foreach ($available_shipping_methods as $key => $label) {
                    echo '<option value="' . esc_attr($key) . '"' . selected(
                            in_array($key, $shipping_methods_selected),
                            true,
                            false
                        ) . '>' . esc_html($label) . '</option>';
                }
            }
            ?>
        </select>
        <?php
        /**
         *
         */
        // Displays option description.
        if (isset($description)) {
            $this->renderTooltip($description);
        }
    }

    /**
     * @param array $args
     *
     * @return array
     */
    public function normalize_settings_args(array $args): array
    {
        $args['value'] = isset($args['value']) ? $args['value'] : 1;

        $args['placeholder'] = isset($args['placeholder']) ? $args['placeholder'] : '';
        $args['class']       = isset($args['class']) ? $args['class'] : '';

        // get main settings array
        $option = get_option($args['name']);

        $args['setting_name'] = "{$args['name']}[{$args['id']}]";

        // copy current option value if set
        if (isset($option[$args['id']])) {
            $args['current'] = $option[$args['id']];
        }

        // fallback to default or empty if no value in option
        if (! isset($args['current'])) {
            $args['current'] = isset($args['default']) ? $args['default'] : '';
        }

        return $args;
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
     * Echoes a woocommerce help tip.
     *
     * @param string $content - Can contain HTML.
     */
    private function renderTooltip(string $content): void
    {
        echo wc_help_tip($content, true);
    }

    /**
     * Output a WooCommerce style form field.
     *
     * @param $args
     */
    public function renderField($args)
    {
        $args = new SettingsFieldArguments($args);

        if (isset($args->helpText)) {
            $this->renderTooltip($args->helpText);
        }

        woocommerce_form_field(
            "{$args->name}[{$args->id}]",
            $args->getArguments()
        );
    }
}

return new WCMP_Settings_Callbacks();
