<?php

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\includes\Settings\Listener;

defined('ABSPATH') or die();

use MyParcelNL\WooCommerce\includes\Listener\AbstractSettingsListener;
use WCMYPA_Settings;

class ExportModeSettingsListener extends AbstractSettingsListener
{
    protected function getTriggerSetting(): string
    {
        return WCMYPA_Settings::SETTING_EXPORT_MODE;
    }
}
