<?php

namespace migration;

abstract class WCMPBE_Upgrade_Migration
{
    /**
     * @var array
     */
    protected $optionSettingsMap = [];

    protected function __construct()
    {
        $this->import();
        $this->migrate();
        $this->setOptionSettingsMap();
        $this->save();
    }

    /**
     * Import any dependencies you might need using this function
     */
    protected function import(): void {}

    /**
     * Put the migration logic in this function.
     */
    protected function migrate(): void {}

    /**
     *
     */
    protected function setOptionSettingsMap(): void {}

    /**
     * @param array      $map
     * @param array|null $newSettings
     * @param array|null $oldSettings
     *
     * @return array|null
     */
    protected function migrateSettings(array $map, ?array $newSettings, array $oldSettings = null): ?array
    {
        $oldSettings = $oldSettings ?? $newSettings;

        if (! $oldSettings) {
            return null;
        }

        foreach ($map as $oldSetting => $newSetting) {
            if (array_key_exists($oldSetting, $oldSettings)) {
                $newSettings[$newSetting] = $oldSettings[$oldSetting];
            }

            if (array_key_exists($oldSetting, $newSettings)) {
                unset($newSettings[$oldSetting]);
            }
        }

        return $newSettings;
    }

    /**
     * Get settings array and replace the specified setting with a new value.
     *
     * @param array  $map
     * @param string $settingName
     * @param mixed  $newValue
     *
     * @return array
     */
    protected function replaceValue(array $map, string $settingName, $newValue): array
    {
        foreach ($map as $name => $value) {
            if ($name === $settingName) {
                $map[$name] = $newValue;
            }
        }

        return $map;
    }

    /**
     * @param array $map
     * @param array $newSettings
     *
     * @return array
     */
    protected function removeOldSettings(array $map, array $newSettings): array
    {
        foreach ($map as $oldSetting => $newSetting) {
            if (array_key_exists($oldSetting, $newSettings)) {
                unset($newSettings[$oldSetting]);
            }
        }
        return $newSettings;
    }

    protected function save()
    {
        foreach ($this->optionSettingsMap as $option => $settings) {
            if (get_option($option) === false) {
                add_option($option, $settings);
            } else {
                update_option($option, $settings);
            }
        }
    }

    /**
     * Get settings array. Falls back to empty array of get_option returns a falsy value. Not compatible with
     * non-array settings.
     *
     * @param string $settingName
     *
     * @return array
     */
    protected function getSettings(string $settingName): array
    {
        return get_option($settingName) ?: [];
    }
}
