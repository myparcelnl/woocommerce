<?php

namespace migration;

class WCMP_Upgrade_Migration
{
    /**
     * @param array      $map
     * @param array      $newSettings
     * @param array|null $oldSettings
     *
     * @return array
     */
    protected function migrateSettings(array $map, array $newSettings, array $oldSettings = null): array
    {
        $oldSettings = $oldSettings ?? $newSettings;

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
}
