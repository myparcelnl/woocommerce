<?php

namespace WPO\WC\MyParcelBE\Entity;

use MyParcelNL\Sdk\src\Support\Arr;
use WCMPBE_Settings_Data;

defined('ABSPATH') or exit;

if (class_exists('\\WPO\\WC\\MyParcelbe\\Entity\\SettingsFieldArguments')) {
    return;
}

class SettingsFieldArguments
{
    private const CONDITION_DEFAULTS = [
        "type"      => "show",
        "set_value" => null,
    ];

    public const IGNORED_ARGUMENTS = [
        "callback",
        "condition",
        "default",
        "id",
        "label",
        "loop",
        "name",
        "type",
    ];

    public const ALTERNATIVE_IGNORED_ARGUMENTS = [
        "callback",
        "condition",
        "default",
        "type",
    ];

    public const ALLOWED_ARGUMENTS = [
        "append",
        "autocomplete",
        "autofocus",
        "class",
        "custom_attributes",
        "description",
        "help_text",
        "id",
        "input_class",
        "label",
        "label_class",
        "maxlength",
        "options",
        "placeholder",
        "priority",
        "required",
        "return",
        "type",
        "validate",
    ];

    /**
     * @var array
     */
    private $input;

    /**
     * @var string
     */
    private $prefix;

    /**
     * @var string
     */
    private $type;

    /**
     * @var mixed
     */
    private $name;

    /**
     * @var mixed
     */
    private $id;

    /**
     * @var array
     */
    private $arguments = [];

    /**
     * @var array
     */
    private $defaults = [
        "type"        => "text",
        "class"       => [],
        "input_class" => [],
        "label_class" => [],
    ];

    /**
     * @var string|null
     */
    private $description;

    /**
     * @var mixed
     */
    private $value;

    /**
     * @var string
     */
    private $default;

    /**
     * @var string
     */
    private $suffix;

    /**
     * SettingsFieldArguments constructor.
     *
     * @param array  $args   - The setting's arguments.
     * @param string $prefix - Optional prefix for name and conditions.
     * @param string $suffix - Optional suffix for name and conditions.
     *
     * @throws \Exception
     */
    public function __construct(array $args, string $prefix = '' , string $suffix = '')
    {
        $this->input  = $args;
        $this->prefix = $prefix;
        $this->suffix = $suffix;

        $this->name        = $this->wrap($this->getInputArgument("name"));
        $this->id          = $this->getInputArgument("id") ?? $this->name;
        $this->description = $this->getInputArgument("description");

        $this->setClass();
        $this->setType();
        $this->setDefault();
        $this->setConditions();

        $this->setArguments($this->input);
    }

    private function setType(): void
    {
        $type = $this->getInputArgument("type");

        switch ($type) {
            case "number":
                $this->addArgument("min", 0);
                $this->addArgument("step", 1);
                break;
            case "currency":
                $type = "number";
                $this->addArgument("step", 0.01);
                $this->addArgument("placeholder", "0,00");
                break;
            case "toggle":
                $type = "select";

                $this->addArgument("data-type", "toggle");
                $this->addArgument(
                    "options",
                    [
                        "1" => __("Enabled", "woocommerce-myparcelbe"),
                        "0" => __("Disabled", "woocommerce-myparcelbe"),
                    ]
                );
                break;
            case "select":
                $this->addArgument("options", $this->getInputArgument("options") ?? []);
                break;
        }

        $this->type = $type;
    }

    private function setClass(): void
    {
        $arr = [
            "class"       => $this->getInputArgument("class"),
            "input_class" => $this->getInputArgument("input_class"),
            "label_class" => $this->getInputArgument("label_class"),
        ];

        foreach ($arr as $class => $value) {
            if ($value) {
                $this->addArgument($class, is_array($value) ? $value : [$value]);
            }
        }
    }

    /**
     * If the setting has conditions set up the attributes so the JS can use them. Supports strings, arrays of
     * strings and associative arrays.
     */
    private function setConditions(): void
    {
        $conditionArgument = $this->getInputArgument("condition");
        $conditions        = [];

        if (! $conditionArgument) {
            return;
        }

        if (!is_array($conditionArgument) || Arr::isAssoc($conditionArgument)) {
            $conditions[] = $conditionArgument;
        } else {
            $conditions = $conditionArgument;
        }

        $conditionData = array_map([$this, "createCondition"], $conditions);
        $conditionData = $this->mergeConditions($conditionData);

        $this->addArgument("data-conditions", $conditionData);
    }

    /**
     * @param string $name
     *
     * @return mixed|null
     */
    public function getInputArgument(string $name)
    {
        return $this->input[$name] ?? $this->defaults[$name] ?? null;
    }

    /**
     * @param string $name
     *
     * @return mixed
     */
    public function getArgument(string $name)
    {
        return $this->arguments[$name] ?? null;
    }

    /**
     * Return the arguments formatted for woocommerce_form_field()
     *
     * @param bool $ignore
     *
     * @return array
     * @see \woocommerce_form_field
     */
    public function getArguments(bool $ignore = true): array
    {
        $arguments = [
            "id"   => $this->id,
            "type" => $this->type,
        ];

        foreach ($this->arguments as $arg => $value) {
            $ignoredArguments = $ignore ? self::IGNORED_ARGUMENTS : self::ALTERNATIVE_IGNORED_ARGUMENTS;

            if (in_array($arg, $ignoredArguments)) {
                continue;
            }

            if (in_array($arg, self::ALLOWED_ARGUMENTS)) {
                if (array_key_exists($arg, $arguments)) {
                    $arguments[$arg] = array_replace_recursive(
                        $arguments[$arg],
                        $value
                    );
                } else {
                    $arguments[$arg] = $value;
                }
            } else {
                if (! isset($arguments["custom_attributes"])) {
                    $arguments["custom_attributes"] = [];
                }

                if (is_array($value)) {
                    $value = htmlspecialchars(json_encode($value));
                }

                $arguments["custom_attributes"][$arg] = $value;
            }
        }

        return $arguments;
    }

    /**
     * Get the custom attributes as a string for use in HTML.
     *
     * @return string
     */
    public function getCustomAttributesAsString(): string
    {
        $attributes = [];

        foreach ($this->getCustomAttributes() ?? [] as $att => $value) {
            $attributes[] = "$att=\"$value\"";
        }

        return implode(" ", $attributes);
    }

    /**
     * @return array
     */
    public function getCustomAttributes(): array
    {
        $arguments = $this->getArguments();
        return $arguments['custom_attributes'] ?? [];
    }

    /**
     * Add an argument by key => value.
     *
     * @param string $key
     * @param        $value
     */
    private function addArgument(string $key, $value): void
    {
        $this->setArguments([$key => $value]);
    }

    /**
     * @param array $args
     */
    private function setArguments(array $args): void
    {
        foreach ($args as $arg => $value) {
            $this->arguments[$arg] = $value;
        }
    }

    /**
     * Set default arguments based on the type.
     */
    private function setDefault(): void
    {
        if (isset($this->input["default"])) {
            $this->default = $this->input["default"];
            return;
        }

        if (isset($this->input["type"]) && $this->input["type"] === "toggle") {
            $this->default = "0";
            return;
        }

        switch ($this->type) {
            case "number":
                $this->default = 0;
                break;
            case "text":
            case "textarea":
                $this->default = "";
                break;
            case "select":
                // Set first option as default value.
                if ($this->arguments["options"]) {
                    $this->default = Arr::first($this->arguments["options"]);
                } else {
                    $this->addArgument("options", []);
                }
                break;
        }
    }

    /**
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * @return mixed
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return array
     */
    public function getDefaults(): array
    {
        return $this->defaults;
    }

    /**
     * @return string
     */
    public function getDescription(): ?string
    {
        return $this->description;
    }

    /**
     * @return mixed
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * @return mixed
     */
    public function getDefault()
    {
        return $this->default;
    }

    /**
     * @param string $id
     */
    public function setId(string $id): void
    {
        $this->id = $id;
    }

    /**
     * @param mixed $name
     */
    public function setName($name): void
    {
        $this->name = $name;
    }

    /**
     * @param $value
     */
    public function setValue($value): void
    {
        $this->value = $value;
    }

    /**
     * @param string|array $condition
     *
     * @return array
     */
    private function createCondition($condition): array
    {
        if (is_array($condition)) {
            $parentName  = $this->wrap($condition['parent_name']);
            $parentValue = $condition['parent_value'] ?? WCMPBE_Settings_Data::ENABLED;

            $condition['parents'] = [
                $parentName => $parentValue,
            ];

            unset($condition['parent_name']);
            unset($condition['parent_value']);

            $newCondition = array_replace_recursive(
                self::CONDITION_DEFAULTS,
                $condition
            );
        } else {
            $parentName   = $this->wrap($condition);
            $newCondition = array_merge(
                self::CONDITION_DEFAULTS,
                [
                    "parents" => [
                        $parentName => WCMPBE_Settings_Data::ENABLED,
                    ],
                ]
            );
        }

        return $newCondition;
    }

    /**
     * @param string|null $text
     *
     * @return string
     */
    private function wrap(?string $text): ?string
    {
        if (! $text) {
            return null;
        }

        return $this->prefix . $text . $this->suffix;
    }

    /**
     * Merge multiple matching conditions (if their `type` and `set_value` are the same) into one condition, combining
     * all `parents` properties.
     *
     * @param array $conditionData
     *
     * @return array
     */
    private function mergeConditions(array $conditionData): array
    {
        $mergedConditions = [];

        foreach ($conditionData as $condition) {
            foreach ($mergedConditions as $key => $mergedCondition) {
                $typeMatches = $mergedCondition['type'] === $condition['type'];

                if ($typeMatches) {
                    $mergedConditions[$key]['parents'] = array_merge(
                        $mergedCondition['parents'],
                        $condition['parents']
                    );

                    // Continue the outer loop as well
                    continue 2;
                }
            }

            $mergedConditions[] = $condition;
        }

        return $mergedConditions;
    }
}
