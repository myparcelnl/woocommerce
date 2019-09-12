<?php

namespace WPO\WC\MyParcelBE\Entity;

use MyParcelNL\Sdk\src\Support\Arr;

defined('ABSPATH') or exit;

if (class_exists('\\WPO\\WC\\MyParcelbe\\Entity\\SettingsFieldArguments')) {
    return;
}

class SettingsFieldArguments
{
    /**
     * @var array
     */
    private $input;

    /**
     * @var array
     */
    public $class;

    /**
     * @var string
     */
    public $type;

    /**
     * @var mixed|null
     */
    public $parent;

    /**
     * @var mixed
     */
    public $name;

    /**
     * @var mixed
     */
    public $id;

    /**
     * @var array
     */
    public $arguments = [];

    /**
     * @var array
     */
    private $defaults = [
        "type"  => "text",
        "class" => [],
    ];
    private $condition;

    public function __construct(array $args)
    {
        $this->input = $args;

        $this->name = $this->getArgument("name");
        $this->id   = $this->getArgument("id");

        $this->setType();
        $this->setCondition();
        $this->setClass();

        $this->setArguments();
    }

    /**
     * @param null $class
     */
    private function addClass($class): void
    {
        array_push($this->class, $class);
    }

    private function setType(): void
    {
        $type = $this->getArgument("type");

        switch ($type) {
            case "toggle" :
                $type = "select";

                $this->arguments["options"] = [
                    "1" => "Enabled",
                    "0" => "Disabled",
                ];
                break;
            case 'select':
                $this->arguments["options"] = $this->getArgument("options") ?? [];
                break;
        }

        $this->type = $type;
    }

    private function setClass(): void
    {
        $class = $this->getArgument("class");

        $this->class = is_array($class) ? $class : [$class];
    }

    private function setCondition()
    {
        $condition = $this->getArgument("condition");

        if (! $condition) {
            return;
        }

        $conditionDefaults = [
            "type" => "show",
        ];

        if (is_array($condition)) {
            $this->condition = array_replace_recursive($conditionDefaults, $condition);
        } else {
            $this->condition = array_merge(
                $conditionDefaults,
                [
                    "name" => $condition,
                ]
            );
        }

        $this->addArgument("data-parent", $this->condition["name"]);
        $this->addArgument("data-parent-type", $this->condition["type"]);

        if (isset($this->condition["parent_value"])) {
            $this->addArgument("data-parent-value", $this->condition["parent_value"]);
        }
        if (isset($this->condition["set_value"])) {
            $this->addArgument("data-parent-set", $this->condition["set_value"]);
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
        } else if (array_key_exists($name, $this->defaults)) {
            return $this->defaults[$name];
        } else {
            return null;
        }
    }

    /**
     * @return array
     */
    public function getArguments(): array
    {
        $arguments = [
            "id"   => $this->id,
            "type" => $this->type,
        ];

        if ($this->class) {
            $arguments["class"] = $this->class;
        }

        return array_merge(
            $arguments,
            $this->arguments
        );
    }

    /**
     * @param string $key
     * @param        $value
     */
    private function addArgument(string $key, $value): void
    {
        $this->arguments[$key] = $value;
    }

    private function setArguments(): void
    {
        $args = $this->arguments;

        $ignoredArguments = [
            "name",
            "id",
            "class",
            "type",
            "label",
            "condition",
        ];

        $allowedArguments = [
            "type",
            "label",
            "description",
            "placeholder",
            "maxlength",
            "required",
            "autocomplete",
            "id",
            "class",
            "label_class",
            "input_class",
            "return",
            "options",
            "custom_attributes",
            "validate",
            "default",
            "autofocus",
            "priority",
        ];

        foreach ($args as $arg => $value) {
            if (in_array($arg, $ignoredArguments)) {
                unset($this->arguments[$arg]);
                continue;
            }

            if (in_array($arg, $allowedArguments)) {
                $this->arguments[$arg] = $value;
            } else {
                if (! isset($this->arguments["custom_attributes"])) {
                    $this->arguments["custom_attributes"] = [];
                }

                unset($this->arguments[$arg]);
                $this->arguments["custom_attributes"][$arg] = $value;
            }
        }
    }
}
