<?php

namespace WPO\WC\MyParcelBE\Collections;

use MyParcelNL\Sdk\src\Support\Collection;
use WPO\WC\MyParcelBE\Entity\Setting;

defined( 'ABSPATH' ) or exit;

if ( ! class_exists( '\\WPO\\WC\\MyParcelBE\\Collections\\SettingsCollection' ) ) :
    /**
     * @mixin Setting
     */
    class SettingsCollection extends Collection {
        /**
         * @param array $rawSettings
         * @param string $type
         * @param int|null $carrierId
         */
        public function setSettingsByType(array $rawSettings, string $type, int $carrierId = null)
        {
            foreach ($rawSettings as $name => $value) {
                $setting = new Setting($name, $value, $type, $carrierId);
                $this->push($setting);
            }
        }
    }
endif; // Class exists check