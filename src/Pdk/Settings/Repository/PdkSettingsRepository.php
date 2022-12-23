<?php

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Pdk\Settings\Repository;

use MyParcelNL\Pdk\Facade\Pdk;
use MyParcelNL\Pdk\Settings\Model\AbstractSettingsModel;
use MyParcelNL\Pdk\Settings\Repository\AbstractSettingsRepository;
use MyParcelNL\Sdk\src\Support\Str;

class PdkSettingsRepository extends AbstractSettingsRepository
{
    /**
     * @param  string $name
     *
     * @return mixed
     */
    public function get(string $name)
    {
        return $this->retrieve($name, function () use ($name) {
            return get_option($this->getOptionName($name), null);
        });
    }

    /**
     * @param  \MyParcelNL\Pdk\Settings\Model\AbstractSettingsModel $settingsModel
     *
     * @return void
     */
    public function store(AbstractSettingsModel $settingsModel): void
    {
        // TODO: Implement store() method.
    }

    private function getOptionName(string $key): string
    {
        return strtr(':plugin_:name', [
            ':plugin' => Pdk::get('pluginName'),
            ':name'   => Str::snake($key),
        ]);
    }
}
