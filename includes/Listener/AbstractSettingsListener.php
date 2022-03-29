<?php

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\includes\Listener;

defined('ABSPATH') or die();

abstract class AbstractSettingsListener extends AbstractListener
{
    /**
     * The name of the setting that triggers this listener.
     *
     * @return string
     */
    abstract protected function getTriggerSetting(): string;

    /**
     * @param  string $optionName
     * @param  mixed  $oldValues
     * @param  mixed  $newValues
     */
    public function handler(string $optionName, $oldValues, $newValues): void
    {
        $trigger = $this->getTriggerSetting();

        if (! is_array($newValues) || ! array_key_exists($trigger, $newValues)) {
            return;
        }

        $newValue = $newValues[$trigger];
        $oldValue = is_array($oldValues)
            ? $oldValues[$trigger] ?? null
            : null;

        if ($newValue !== $oldValue) {
            $this->trigger($optionName, $newValue, $oldValue);
        }
    }

    /**
     * @return void
     */
    public function listen(): void
    {
        add_action('updated_option', [$this, 'handler'], 10, 3);
    }
}
