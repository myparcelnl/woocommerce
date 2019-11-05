<?php

namespace WPO\WC\MyParcelBE\Collections;

use MyParcelNL\Sdk\src\Support\Collection;
use WPO\WC\MyParcelBE\Entity\Setting;

defined('ABSPATH') or exit;

if (class_exists('\\WPO\\WC\\MyParcelBE\\Collections\\SettingsCollection')) {
    return;
}

/**
 * @mixin Setting
 */

/**
 * @property mixed getByName
 */
class SettingsCollection extends Collection
{
    /**
     * @param array  $rawSettings
     * @param string $type
     * @param string $carrierName
     */
    public function setSettingsByType(array $rawSettings = [], string $type = "", string $carrierName = null)
    {
        foreach ($rawSettings as $name => $value) {
            $setting = new Setting($name, $value, $type, $carrierName);
            $this->push($setting);
        }
    }

    /**
     * Check if a setting is enabled
     *
     * @param string $name
     *
     * @return bool
     */
    public function isEnabled(string $name): bool
    {
        /** @var Setting|null $setting */
        $setting = $this->where('name', $name)->first();

        if (! $setting) {
            return false;
        }

        return $setting->value;
    }

    /**
     * Search for a setting by name and value.
     *
     * @param string $name
     * @param string $value
     *
     * @return SettingsCollection
     */
    public function like(string $name, string $value): self
    {
        return $this->filter(
            function (Setting $item) use ($name, $value) {
                return false !== strpos($item->name, $value);
            }
        );
    }

    /**
     * @param string $name
     *
     * @return mixed|null
     */
    public function getByName(string $name)
    {
        /** @var Setting $setting */
        $setting = $this->where('name', $name)->first();

        return $setting->value ?? null;
    }

    /**
     * @param string $name
     *
     * @return bool
     */
    public function exists(string $name): bool
    {
        /** @var Setting $setting */
        $setting = $this->where('name', $name)->first();

        return (bool) $setting->value;
    }

    /**
     * @param string $name
     *
     * @return int
     */
    public function getIntegerByName(string $name): int
    {
        return (int) $this->getByName($name) ?? 0;
    }

    /**
     * @param string $name
     *
     * @return float
     */
    public function getFloatByName(string $name): float
    {
        $value = str_replace(',', '.', $this->getByName($name));
        return (float) $value ?? 0;
    }

    /**
     * @param string $name
     *
     * @return string
     */
    public function getStringByName(string $name): string
    {
        return $this->getByName($name) ?? '';
    }

    /**
     * @param string $name
     *
     * @return bool
     */
    public function getBooleanByName(string $name): bool
    {
        return $this->getByName($name) ?? false;
    }
}
