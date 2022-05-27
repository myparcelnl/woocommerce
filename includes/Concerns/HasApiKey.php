<?php

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\includes\Concerns;

defined('ABSPATH') or die();

use Exception;
use WCMYPA_Settings;
use WPO\WC\MyParcel\Collections\SettingsCollection;

trait HasApiKey
{
    /**
     * @var null|string
     */
    private $apiKey;

    /**
     * @return null|string
     */
    public function getApiKey(): ?string
    {
        return $this->apiKey ?? $this->fetchApiKey();
    }

    /**
     * @return bool whether this has an api key
     */
    public function hasApiKey(): bool
    {
        return (bool) $this->getApiKey();
    }

    /**
     * @return string
     * @throws \Exception
     */
    protected function ensureHasApiKey(): string
    {
        if (! $this->getApiKey()) {
            throw new Exception('No API key found!');
        }

        return $this->getApiKey();
    }

    /**
     * @return string|null
     */
    private function fetchApiKey(): ?string
    {
        $this->apiKey = SettingsCollection::getInstance()->getByName(WCMYPA_Settings::SETTING_API_KEY);

        return $this->apiKey ?: null;
    }
}
