<?php

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\WooCommerce\Address\Contract;

interface AddressFieldInterface
{
    /**
     * @return array<string, scalar>
     */
    public function getAttributes(): array;

    /**
     * @return array<string, scalar>
     */
    public function getBlocksCheckoutAttributes(): array;

    /**
     * @return string[]
     */
    public function getClass(): array;

    /**
     * @return string
     */
    public function getId(): string;

    /**
     * @return int
     */
    public function getIndex(): int;

    /**
     * @return string
     */
    public function getLabel(): string;

    /**
     * @return array<string, scalar>
     */
    public function getLegacyCheckoutAttributes(): array;

    /**
     * @return string
     */
    public function getLocation(): string;

    /**
     * @return string
     */
    public function getName(): string;

    /**
     * @return int
     */
    public function getPriority(): int;

    /**
     * @return string
     */
    public function getTranslatedLabel(): string;

    /**
     * @return string
     */
    public function getType(): string;

    /**
     * @return bool
     */
    public function isHidden(): bool;

    /**
     * @return bool
     */
    public function isRequired(): bool;
}