<?php

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\includes\admin\settings;

use MyParcelNL\Pdk\Storage\AbstractStorage;

class WordPressOptionStorage extends AbstractStorage
{
    public function save(string $storageKey, array $item): bool
    {
        return update_option($storageKey, $item);
    }

    public function get(string $storageKey): array
    {
        return get_option($storageKey) ?: [];
    }

    public function delete(string $storageKey): bool
    {
        return delete_option($storageKey);
    }
}
