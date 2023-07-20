<?php

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Pdk\Storage;

use MyParcelNL\Pdk\Storage\Contract\StorageInterface;

class WpOptionsStorage implements StorageInterface
{
    /**
     * @param  string $storageKey
     *
     * @return void
     */
    public function delete(string $storageKey): void
    {
        update_option($storageKey, null);
    }

    /**
     * @param  string $storageKey
     *
     * @return mixed
     */
    public function get(string $storageKey)
    {
        return $this->getOption($storageKey);
    }

    /**
     * @param  string $storageKey
     *
     * @return bool
     */
    public function has(string $storageKey): bool
    {
        return get_option($storageKey, null) !== null;
    }

    /**
     * @param  string $storageKey
     * @param         $value
     *
     * @return void
     */
    public function set(string $storageKey, $value): void
    {
        update_option($storageKey, $value);
    }

    /**
     * @param  string $storageKey
     *
     * @return mixed
     */
    protected function getOption(string $storageKey)
    {
        return get_option($storageKey, null);
    }
}
