<?php

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Logger;

use MyParcelNL\Pdk\Logger\AbstractLogger;

class WordPressLogger extends AbstractLogger
{
    /**
     * @param        $level
     * @param        $message
     * @param  array $context
     *
     * @return void
     */
    public function log($level, $message, array $context = []): void
    {
        if (! function_exists('wc_get_logger')) {
            return;
        }

        $logger = wc_get_logger();

        if (! $logger) {
            return;
        }

        $logger->log($level, $message, $context);
    }
}
