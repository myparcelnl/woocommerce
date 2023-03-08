<?php

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Logger;

use MyParcelNL\Pdk\Facade\Pdk;
use MyParcelNL\Pdk\Logger\AbstractLogger;

class WcLogger extends AbstractLogger
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
        $logger = wc_get_logger();

        if (! $logger) {
            return;
        }

        $json = count($context) ? PHP_EOL . json_encode($context, JSON_PRETTY_PRINT) : '';

        $logger->log($level, $message . $json, ['source' => Pdk::getAppInfo()->name]);
    }
}
