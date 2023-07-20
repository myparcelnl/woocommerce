<?php

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Pdk\Storage;

use MyParcelNL\Pdk\Storage\Contract\StorageInterface;
use RuntimeException;
use WC_Order;

final class WcOrderStorage implements StorageInterface
{
    /**
     * @param  string $storageKey
     *
     * @return void
     */
    public function delete(string $storageKey): void
    {
        throw new RuntimeException('Not implemented');
    }

    /**
     * @param  string $storageKey
     *
     * @return mixed
     */
    public function get(string $storageKey)
    {
        return new WC_Order($storageKey);
    }

    /**
     * @param  string $storageKey
     *
     * @return bool
     */
    public function has(string $storageKey): bool
    {
        return true;
    }

    /**
     * @param  string $storageKey
     * @param         $value
     *
     * @return void
     */
    public function set(string $storageKey, $value): void
    {
        throw new RuntimeException('Not implemented');
    }
}
