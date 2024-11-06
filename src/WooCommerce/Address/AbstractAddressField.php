<?php

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\WooCommerce\Address;

use MyParcelNL\Pdk\Base\Support\Arr;
use MyParcelNL\Pdk\Facade\Language;
use MyParcelNL\Pdk\Facade\Pdk;
use MyParcelNL\Sdk\src\Support\Str;
use MyParcelNL\WooCommerce\Facade\Filter;
use MyParcelNL\WooCommerce\WooCommerce\Address\Contract\AddressFieldInterface;

abstract class AbstractAddressField implements AddressFieldInterface
{
    /**
     * Copied from WooCommerce
     *
     * @see \Automattic\WooCommerce\Blocks\Domain\Services\CheckoutFields::register_field_attributes
     */
    private const ALLOWED_BLOCKS_FIELD_ATTRIBUTES = [
        'maxLength',
        'readOnly',
        'pattern',
        'autocomplete',
        'autocapitalize',
        'title',
    ];

    /**
     * @return array<string, scalar>
     */
    public function getAttributes(): array
    {
        return [];
    }

    /**
     * @return array<string, scalar>
     */
    final public function getBlocksCheckoutAttributes(): array
    {
        $attributes = array_replace($this->getDefaultAttributes(), $this->getAttributes());

        return Arr::where($attributes, static function ($value, $key) {
            return in_array($key, self::ALLOWED_BLOCKS_FIELD_ATTRIBUTES, true)
                || Str::startsWith($key, 'data-')
                || Str::startsWith($key, 'aria-');
        });
    }

    /**
     * @return string
     */
    final public function getBlocksCheckoutId(): string
    {
        $appInfo = Pdk::getAppInfo();

        return sprintf('%s/%s', $appInfo->name, $this->getName());
    }

    /**
     * @return string[]
     */
    public function getClass(): array
    {
        return Filter::apply("{$this->getName()}Class");
    }

    /**
     * @return string
     */
    public function getId(): string
    {
        return Pdk::get($this->getName());
    }

    /**
     * @return int
     */
    public function getIndex(): int
    {
        return Filter::apply("{$this->getName()}Index");
    }

    /**
     * @return array<string, scalar>
     */
    final public function getLegacyCheckoutAttributes(): array
    {
        return array_replace($this->getDefaultAttributes(), $this->getAttributes());
    }

    /**
     * @return int
     */
    public function getPriority(): int
    {
        return Filter::apply("{$this->getName()}Priority");
    }

    /**
     * @return string
     */
    public function getTranslatedLabel(): string
    {
        return Language::translate($this->getLabel());
    }

    /**
     * @return bool
     */
    public function isHidden(): bool
    {
        return false;
    }

    /**
     * @return bool
     */
    public function isRequired(): bool
    {
        return false;
    }

    /**
     * @param  null|int $flags
     *
     * @return array
     */
    public function toArray(?int $flags = null): array
    {
        return [
            'id'         => $this->getId(),
            'label'      => $this->getLabel(),
            'name'       => $this->getName(),
            'hidden'     => $this->isHidden(),
            'required'   => $this->isRequired(),
            'attributes' => $this->getAttributes(),
        ];
    }

    /**
     * @return array<string, scalar>
     */
    private function getDefaultAttributes(): array
    {
        return [
            'autocomplete' => 'off',
        ];
    }
}
