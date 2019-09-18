<?php

use WPO\WC\MyParcelBE\Entity\SettingsFieldArguments;

if (! defined('ABSPATH')) {
    exit;
} // Exit if accessed directly

if (class_exists('WCMP_Settings_Callbacks_Enhanced_Select')) {
    return;
}

class WCMP_Settings_Callbacks_Enhanced_Select
{
    /**
     * WCMP_Settings_Callbacks_Enhanced_Select constructor.
     *
     * @param array $args
     */
    public function __construct(array $args)
    {
        $class = new SettingsFieldArguments($args);

        if (isset($args["loop"])) {
            $this->createMultipleSearchBoxes($args["loop"], $class);
        } else {
            $value = get_option($class->getOptionId())[$class->getId()];

            $this->createSearchBox($class, "[{$class->getId()}]", $value);
        }
    }

    /**
     * @param array                  $loop
     * @param SettingsFieldArguments $class
     */
    public function createMultipleSearchBoxes(array $loop, SettingsFieldArguments $class): void
    {
        foreach ($loop as $id => $human) {
            printf('<h4 class="title">%s:</h4>', $human);
            $value = get_option($class->getOptionId())[$class->getId()][$id];

            $this->createSearchBox($class, "[{$class->getId()}][$id]", $value);
        }
    }

    /**
     * Shipping method search callback.
     *
     * @param SettingsFieldArguments $class
     * @param string                 $name
     * @param                        $value
     */
    public function createSearchBox(SettingsFieldArguments $class, string $name, $value): void
    {
        $args = $class->getArguments();

        printf(
            '<select id="%s"
                name="%s"
                style="width: 50%%;"
                class="wc-enhanced-select"
                multiple="multiple"
                data-placeholder="%s">',
            $args["id"],
            $class->getOptionId() . $name . "[]",
            $args["placeholder"] ?? ""
        );

        foreach ($args["options"] as $key => $label) {
            printf(
                "<option value=\"%s\"%s>%s</option>",
                esc_attr($key),
                selected(
                    in_array($key, $value),
                    true,
                    false
                ),
                esc_html($label)
            );
        }

        echo "</select>";
    }
}
