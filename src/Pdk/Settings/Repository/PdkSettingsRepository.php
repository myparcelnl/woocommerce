<?php

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Pdk\Settings\Repository;

use MyParcelNL\Pdk\Base\Support\Arr;
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
        $parts = explode('.', $name);

        if (count($parts) < 3) {
            $key = $parts[0];
        } else {
            $key = implode('_', array_slice($parts, 0, 2));
        }

        $group = $this->retrieve(sprintf('settings_%s', $key), function () use ($key) {
            return get_option($this->getOptionName($key), null);
        });

        return Arr::get($group, end($parts));
    }

    /**
     * @param  \MyParcelNL\Pdk\Settings\Model\AbstractSettingsModel $settingsModel
     *
     * @return void
     * @throws \MyParcelNL\Pdk\Base\Exception\InvalidCastException
     */
    public function store(AbstractSettingsModel $settingsModel): void
    {
        update_option($this->getOptionName($settingsModel->id), $settingsModel->toStorableArray());
    }

    /**
     * @param  string $key
     *
     * @return string
     */
    private function getOptionName(string $key): string
    {
        $appInfo = Pdk::getAppInfo();

        return strtr('_:plugin_:name', [
            ':plugin' => $appInfo['name'],
            ':name'   => Str::snake($key),
        ]);
    }
}
