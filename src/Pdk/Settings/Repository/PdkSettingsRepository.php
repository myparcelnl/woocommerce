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
        [$id, $key] = explode('.', $name);

        $group = $this->retrieve($id, function () use ($id) {
            return get_option($this->getOptionName($id), null);
        });

        return $group[$key] ?? null;
    }

    /**
     * @param  \MyParcelNL\Pdk\Settings\Model\AbstractSettingsModel $settingsModel
     *
     * @return void
     * @throws \MyParcelNL\Pdk\Base\Exception\InvalidCastException
     */
    public function store(AbstractSettingsModel $settingsModel): void
    {
        update_option($this->getOptionName($settingsModel->getId()), $settingsModel->toArrayWithoutNull());
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
