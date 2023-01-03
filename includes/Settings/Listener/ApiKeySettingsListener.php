<?php

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\includes\Settings\Listener;

defined('ABSPATH') or die();

use MyParcelNL\WooCommerce\includes\Listener\AbstractSettingsListener;

class ApiKeySettingsListener extends AbstractSettingsListener
{
    protected function getTriggerSetting(): string
    {
        return 'api_key';
    }
}
