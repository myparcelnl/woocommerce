<?php

use WPO\WC\MyParcelBE\Entity\SettingsFieldArguments;

if (! defined('ABSPATH')) {
    exit;
} // Exit if accessed directly

if (class_exists('WCMPBE_Settings_Callbacks_Enhanced_Select')) {
    return;
}

class WCMPBE_Settings_Callbacks_Enhanced_Select
{
    /**
     * WCMPBE_Settings_Callbacks_Enhanced_Select constructor.
     *
     * @param \WPO\WC\MyParcelBE\Entity\SettingsFieldArguments $class
     *
     * @throws Exception
     */
    public function __construct(SettingsFieldArguments $class)
    {
        if ($class->getArgument('loop')) {
            $this->createMultipleSearchBoxes($class->getArgument('loop'), $class);
        } else {
            $optionId = self::getOptionId($class);
            $value    = get_option($optionId)[$class->getId()];

            $this->createSearchBox($class, null, $value);
        }
    }

    /**
     * @param array                  $loop
     * @param SettingsFieldArguments $class
     *
     * @throws Exception
     */
    public function createMultipleSearchBoxes(array $loop, SettingsFieldArguments $class): void
    {
        foreach ($loop as $id => $human) {
            $value       = null;
            $newClass    = clone $class;
            $optionId    = self::getOptionId($newClass);
            $optionValue = get_option($optionId)[$newClass->getId()];

            printf('<h4 class="title">%s:</h4>', $human);

            if (array_key_exists($id, $optionValue)) {
                $value = $optionValue[$id];
            }

            $newClass->setId($optionId . '_' . $id);
            $this->createSearchBox($newClass, $id, $value ?? []);
        }
    }

    /**
     * Shipping method search callback.
     *
     * @param SettingsFieldArguments $class
     * @param string|null            $id
     * @param                        $value
     */
    public function createSearchBox(SettingsFieldArguments $class, ?string $id, $value): void
    {
        $args = $class->getArguments();

        printf(
            '<select id="%s"
                name="%s"
                class="wc-enhanced-select"
                multiple="multiple"
                data-placeholder="%s"
                %s>',
            $class->getId(),
            $class->getName() . ($id ? "[$id][]" : "[]"),
            $args["placeholder"] ?? "",
            $class->getCustomAttributesAsString()
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

    /**
     * @param SettingsFieldArguments $class
     *
     * @return string|null
     */
    private static function getOptionId(SettingsFieldArguments $class): ?string
    {
        preg_match('/(\w+)\[/', $class->getName(), $matches);

        if (!isset($matches[1])) {
            return $class->getName();
        }

        return $matches[1];
    }
}
