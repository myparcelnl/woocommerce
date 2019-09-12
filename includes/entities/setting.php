<?php

namespace WPO\WC\MyParcelBE\Entity;

defined('ABSPATH') or exit;

if (class_exists('\\WPO\\WC\\MyParcelbe\\Entity\\Setting')) {
    return;
}

/**
 * Use public fields because this is required for Laravel collections
 */
class Setting
{
    /**
     * @var string
     */
    public $name;

    /**
     * @var mixed
     */
    public $value;

    /**
     * @var string
     */
    public $type;

    /**
     * @var string|null
     */
    public $carrier;

    /**
     * Setting constructor.
     *
     * @param string      $name
     * @param mixed       $value
     * @param string      $type
     * @param string|null $carrier
     */
    public function __construct(string $name, $value, string $type, string $carrier = null)
    {
        $this->name    = $name;
        $this->value   = $value;
        $this->type    = $type;
        $this->carrier = $carrier;
    }
}
