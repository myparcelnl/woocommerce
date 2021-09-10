<?php

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\includes\Model;

defined('ABSPATH') or die();

use MyParcelNL\Sdk\src\Model\BaseModel;
use MyParcelNL\Sdk\src\Support\Str;

/**
 * @TODO sdk#326 move to sdk
 */
class Model extends BaseModel
{
    /**
     * @var string[]
     */
    protected $attributes = [];

    /**
     * @var object[]|array[]
     */
    private $data;

    /**
     * @param  array $data
     */
    public function __construct(array $data)
    {
        $this->fill($data);
    }

    /**
     * @param  mixed $key
     *
     * @return mixed
     */
    public function __get($key)
    {
        if (in_array($key, $this->attributes, true)) {
            return $this->data[$key];
        }

        return null;
    }

    /**
     * @param  mixed $key
     *
     * @return bool
     */
    public function __isset($key)
    {
        return isset($this->data[$key]);
    }

    /**
     * @param  mixed $key
     * @param  mixed $value
     *
     * @return void
     */
    public function __set($key, $value)
    {
        $this->data[$key] = $value;
    }

    /**
     * @return array
     */
    public function toArray(): array
    {
        $array = [];

        foreach ($this->attributes as $attribute) {
            $array[$attribute] = $this->{$attribute};
        }

        return $array;
    }

    /**
     * @param $key
     * @param $value
     *
     * @return mixed
     */
    protected function mutateAttribute($key, $value)
    {
        return $this->{'get' . Str::studly($key) . 'Attribute'}($value);
    }

    /**
     * @param  array $data
     *
     * @return void
     */
    private function fill(array $data): void
    {
        foreach ($this->attributes as $attribute) {
            $this->{$attribute} = $data[$attribute] ?? null;
        }
    }
}
