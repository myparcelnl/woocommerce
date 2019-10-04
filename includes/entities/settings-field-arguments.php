<?php

namespace WPO\WC\MyParcelBE\Entity;

use MyParcelNL\Sdk\src\Support\Arr;

defined('ABSPATH') or exit;

if (class_exists('\\WPO\\WC\\MyParcelbe\\Entity\\SettingsFieldArguments')) {
    return;
}

class SettingsFieldArguments
{
    public const IGNORED_ARGUMENTS = [
        "callback",
        "condition",
        "default",
        "id",
        "label",
        "loop",
        "name",
        "option_id",
        "type",
    ];

    public const ALTERNATIVE_IGNORED_ARGUMENTS = [
        "callback",
        "condition",
        "default",
        "option_id",
        "type",
    ];

    public const ALLOWED_ARGUMENTS = [
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
    private $input = [];

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
     * @var string
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
     * @var mixed|null
     */
    private $option_id;

    /**
     * SettingsFieldArguments constructor.
     *
     * @param array $args - The setting's arguments.
     */
    public function __construct(array $args)
    {
        $this->input     = $args;
        $this->option_id = $args["option_id"] ?? null;

        $this->name        = $this->getArgument("name");
        $this->id          = $this->getArgument("id");
        $this->description = $this->getArgument("description");

        $this->setClass();
        $this->setType();
        $this->setDefault();
        $this->setCondition();

        $this->setArguments($this->input);
    }

    private function setType(): void
    {
        $type = $this->getArgument("type");

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
            case "toggle" :
                $type = "select";

                $this->addArgument(
                    "options",
                    [
                        "1" => __("Enabled", "woocommerce-myparcelbe"),
                        "0" => __("Disabled", "woocommerce-myparcelbe"),
                    ]
                );
                break;
            case "select":
                $this->addArgument("options", $this->getArgument("options") ?? []);
                break;
        }

        $this->type = $type;
    }

    private function setClass(): void
    {
        $arr = [
            "class"       => $this->getArgument("class"),
            "input_class" => $this->getArgument("input_class"),
            "label_class" => $this->getArgument("label_class"),
        ];

        foreach ($arr as $class => $value) {
            if ($value) {
                $this->addArgument($class, is_array($value) ? $value : [$value]);
            }
        }
    }

    /**
     * If the setting has a condition array set up the attributes so the JS can use them.
     */
    private function setCondition(): void
    {
        $conditionArgument = $this->getArgument("condition");

        if (! $conditionArgument) {
            return;
        }

        $conditionDefaults = [
            "type" => "show",
        ];

        if (is_array($conditionArgument)) {
            $condition = array_replace_recursive($conditionDefaults, $conditionArgument);
        } else {
            $condition = array_merge(
                $conditionDefaults,
                [
                    "name" => $conditionArgument,
                ]
            );
        }

        $this->addArgument("data-parent", $condition["name"]);
        $this->addArgument("data-parent-type", $condition["type"]);

        if (isset($condition["parent_value"])) {
            if (is_array($condition["parent_value"])) {
                $this->addArgument("data-parent-value", implode(';', $condition["parent_value"]) . ";");
            } else {
                $this->addArgument("data-parent-value", $condition["parent_value"]);
            }
        }

        if (isset($condition["set_value"])) {
            $this->addArgument("data-parent-set", $condition["set_value"]);
        }
    }

    /**
     * @param string $name
     *
     * @return mixed|null
     */
    private function getArgument(string $name)
    {
        if (isset($this->input[$name])) {
            return $this->input[$name];
        } elseif (array_key_exists($name, $this->defaults)) {
            return $this->defaults[$name];
        } else {
            return null;
        }
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
            $array = $ignore ? self::IGNORED_ARGUMENTS : self::ALTERNATIVE_IGNORED_ARGUMENTS;

            if (in_array($arg, $array)) {
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

                $arguments["custom_attributes"][$arg] = $value;
            }
        }

        return $arguments;
    }

    /**
     * Get the custom attributes as a string.
     *
     * @return string
     */
    public function getCustomAttributes(): string
    {
        $arguments  = $this->getArguments();
        $attributes = [];

        foreach ($arguments["custom_attributes"] ?? [] as $att => $value) {
            $attributes[] = "$att=\"$value\"";
        }

        return implode(" ", $attributes);
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
     * To push one or more values to an array type argument.
     *
     * @param string $argument
     * @param string ...$items
     */
    private function pushArgument(string $argument, string ...$items): void
    {
        if (! isset($this->arguments[$argument])) {
            $this->arguments[$argument] = [];
        }

        array_push($this->arguments[$argument], ...$items);
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
     * @return string|null
     */
    public function getOptionId(): ?string
    {
        return $this->option_id;
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
    public function getDescription(): string
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
}
