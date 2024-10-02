<?php

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Logger;

use Automattic\WooCommerce\Utilities\LoggingUtil;
use MyParcelNL\Pdk\Facade\Pdk;
use MyParcelNL\Pdk\Logger\AbstractLogger;
use WC_Logger_Interface;

class WcLogger extends AbstractLogger
{
    /**
     * @return array
     */
    public function getLogFiles(): array
    {
        $logger = $this->getWcLogger();

        if (! $logger) {
            return parent::getLogFiles();
        }

        $logDirectory = trailingslashit(LoggingUtil::get_log_directory());
        $foundFiles   = glob(sprintf('%s%s-*.log', $logDirectory, $this->getLogPrefix()));

        if (! $foundFiles) {
            return parent::getLogFiles();
        }

        return $foundFiles;
    }

    /**
     * @param        $level
     * @param        $message
     * @param  array $context
     *
     * @return void
     */
    public function log($level, $message, array $context = []): void
    {
        $logger = $this->getWcLogger();

        if (! $logger) {
            return;
        }

        $json = count($context) ? PHP_EOL . json_encode($context, JSON_PRETTY_PRINT) : '';

        $logger->log($level, $message . $json, ['source' => $this->getLogPrefix()]);
    }

    /**
     * @return string
     */
    private function getLogPrefix(): string
    {
        return Pdk::getAppInfo()->name;
    }

    /**
     * @return null|\WC_Logger_Interface
     */
    private function getWcLogger(): ?WC_Logger_Interface
    {
        return function_exists('wc_get_logger') ? wc_get_logger() : null;
    }
}
