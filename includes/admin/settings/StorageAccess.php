<?php

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\includes\admin\settings;

use MyParcelNL\Pdk\Repository\AccountRepository;
use MyParcelNL\Pdk\Repository\AccountSettingsRepository;
use MyParcelNL\Pdk\Storage\AbstractStorage;
use WCMYPA_Settings;
use WPO\WC\MyParcel\Collections\SettingsCollection;

class StorageAccess extends AbstractStorage
{
    private $apiKey;

    public const STORAGE_KEYS = [
        AccountRepository::class         => 'woocommerce_myparcel_account',
        AccountSettingsRepository::class => 'woocommerce_myparcel_account_settings',
    ];

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

    public function getApiKey(): ?string
    {
        if (! $this->apiKey) {
            $this->apiKey = $this->fetchApiKey();
        }

        return $this->apiKey;
    }

    public function hasApiKey(): bool
    {
        return (bool) $this->getApiKey();
    }

    protected function fetchApiKey(): ?string
    {
        return SettingsCollection::getInstance()->getByName(WCMYPA_Settings::SETTING_API_KEY);
    }
}
