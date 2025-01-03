<?php

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Tests\Factory\Contract;

use MyParcelNL\Pdk\Tests\Factory\Contract\FactoryInterface;
use WC_Data;

/**
 * @template T of WC_Data
 */
interface WpFactoryInterface extends FactoryInterface
{
    /**
     * @return class-string<T>
     */
    public function getClass(): string;

    /**
     * @return T
     * @noinspection ReturnTypeCanBeDeclaredInspection
     */
    public function store();
}
