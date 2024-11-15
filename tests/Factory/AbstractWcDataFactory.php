<?php

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Tests\Factory;

use MyParcelNL\WooCommerce\Tests\Mock\MockWcData;

/**
 * @template T of \WC_Data
 * @extends \MyParcelNL\WooCommerce\Tests\Factory\AbstractWpFactory<T>
 */
abstract class AbstractWcDataFactory extends AbstractWpFactory
{
    /**
     * @inheritDoc
     */
    public function store()
    {
        $model = parent::store();

        MockWcData::save($model);

        return $model;
    }

    /**
     * @param  array $meta
     *
     * @return $this
     */
    public function withMeta(array $meta): self
    {
        return $this->with(['meta' => array_replace($this->attributes['meta'] ?? [], $meta)]);
    }
}
