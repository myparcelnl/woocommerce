<?php

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Migration\Pdk;

use MyParcelNL\Pdk\App\Installer\Contract\MigrationInterface;
use MyParcelNL\Pdk\Facade\Logger;

abstract class AbstractPdkMigration implements MigrationInterface
{
    public function getVersion(): string
    {
        return '5.0.0';
    }

    protected function debug(string $message, array $context = []): void
    {
        Logger::debug($message, $context + $this->getCommonLogContext());
    }

    protected function error(string $message, array $context = []): void
    {
        Logger::error($message, $context + $this->getCommonLogContext());
    }

    protected function info(string $message, array $context = []): void
    {
        Logger::info($message, $context + $this->getCommonLogContext());
    }

    protected function warning(string $message, array $context = []): void
    {
        Logger::warning($message, $context + $this->getCommonLogContext());
    }

    /**
     * @return string[]
     */
    private function getCommonLogContext(): array
    {
        return ['migration' => static::class];
    }
}
