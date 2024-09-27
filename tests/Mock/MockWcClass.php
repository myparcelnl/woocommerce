<?php

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Tests\Mock;

use MyParcelNL\Pdk\Base\Support\Arr;
use WC_Data;

abstract class MockWcClass extends WC_Data
{
    use MocksGettersAndSetters;

    /**
     * @param  array|int|string $data - extra types to avoid type errors in real code.
     *
     * @throws \Throwable
     */
    public function __construct($data = [])
    {
        if (is_scalar($data)) {
            $data = ['id' => $data];
        }

        $id = $data['id'] ?? null;

        if ($id && MockWcData::has($id)) {
            $existing = MockWcData::get($id);
            $data     = $existing->getAttributes();
        }

        $this->fill($data);
    }

    /**
     * @return null|int|string
     */
    public function get_id()
    {
        return $this->attributes['id'] ?? null;
    }

    /**
     * @param  mixed $key
     * @param  mixed $single
     * @param  mixed $context
     *
     * @return null|mixed
     * @see \WC_Data::get_meta()
     */
    public function get_meta($key = '', $single = true, $context = 'view')
    {
        return get_post_meta($this->get_id(), $key);
    }

    /**
     * @return array of objects.
     * @throws \Throwable
     * @see \WC_Product::get_meta_data()
     */
    public function get_meta_data(): array
    {
        return MockWpMeta::toWcMetaData($this->get_id());
    }

    /**
     * @return void
     */
    public function save(): void
    {
        // do nothing
    }

    /**
     * @return void
     */
    public function save_meta_data(): void
    {
        // do nothing
    }

    /**
     * @param  int|string $id
     *
     * @return void
     */
    public function set_id($id): void
    {
        $this->attributes['id'] = $id;
    }

    /**
     * @param  string       $key
     * @param  array|string $value
     * @param  int          $meta_id
     *
     * @return void
     */
    public function update_meta_data($key, $value, $meta_id = 0): void
    {
        MockWpMeta::update($this->get_id(), $key, $value);
    }

    /**
     * @param  array $data
     *
     * @return void
     */
    protected function fill(array $data): void
    {
        $this->attributes = array_replace($this->attributes, Arr::except($data, 'meta'));

        $created = MockWcData::create($this);

        foreach ($data['meta'] ?? [] as $metaKey => $metaValue) {
            update_post_meta($created->get_id(), $metaKey, $metaValue);
        }
    }

    /**
     * Dynamically retrieve attributes on the model.
     *
     * @param  string $key
     *
     * @return mixed
     */
    public function __get(string $key)
    {
        return $this->attributes[$key] ?? null;
    }

    /**
     * Dynamically set attributes on the model.
     *
     * @param  string $key
     * @param  mixed  $value
     *
     * @return void
     */
    public function __set(string $key, $value): void
    {
        $this->attributes[$key] = $value;
    }

    /**
     * Determine if an attribute or relation exists on the model.
     *
     * @param  string $key
     *
     * @return bool
     */
    public function __isset(string $key)
    {
        return isset($this->attributes[$key]);
    }
}
