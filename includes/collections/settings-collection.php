<?php

declare(strict_types=1);

namespace WPO\WC\MyParcel\Collections;

defined('ABSPATH') or die();

use MyParcelNL\Sdk\src\Support\Collection;
use MyParcelNL\WooCommerce\includes\Concerns\HasInstance;
use WC_Tax;
use WPO\WC\MyParcel\Entity\Setting;

if (class_exists('\\WPO\\WC\\MyParcel\\Collections\\SettingsCollection')) {
    return;
}

/**
 * @property mixed getByName
 */
class SettingsCollection extends Collection
{
    use HasInstance;

    /**
     * @param  array       $rawSettings
     * @param  string      $type
     * @param  null|string $carrierName
     */
    public function setSettingsByType(array $rawSettings = [], string $type = "", string $carrierName = null): void
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
        return (bool) $this->getByName($name);
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
        return (int) ($this->getByName($name) ?? 0);
    }

    /**
     * @param string $name
     *
     * @return float
     */
    public function getFloatByName(string $name): float
    {
        $setting = (string) $this->getByName($name);

        return (float) str_replace(',', '.', $setting ?? '0');
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
     * If prices are displayed with VAT/tax included, add the taxes to the price. Otherwise just pass the base price
     * as all taxes will be combined and shown in separate "Tax" fees.
     *
     * Never use this as a base price, this is only for displaying a total price including taxes.
     *
     * @param string $name
     *
     * @return float
     */
    public function getPriceByName(string $name): float
    {
        $basePrice           = $this->getFloatByName($name);
        $displayIncludingTax = WC()->cart->display_prices_including_tax();

        if ($displayIncludingTax) {
            $taxRates = WC_Tax::get_shipping_tax_rates();
            $taxes    = WC_Tax::calc_shipping_tax($basePrice, $taxRates);
            $sumTaxes = array_sum($taxes);

            return $basePrice + $sumTaxes;
        }

        return $basePrice;
    }
}
