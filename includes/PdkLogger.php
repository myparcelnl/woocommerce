<?php

declare(strict_types=1);

use MyParcelNL\Sdk\src\Support\Arr;
use MyParcelNL\Pdk\Logger\AbstractLogger;
use MyParcelNL\Sdk\src\Support\Str;

/**`
 *
 */
class PdkLogger extends AbstractLogger
{
    /**
     * Log levels, in order of severity.
     */
    private const LOG_LEVELS = WCMP_Log::LOG_LEVELS;

    /**
     * @param        $level
     * @param        $message
     * @param  array $context
     *
     * @return void
     */
    public function log($level, $message, array $context = []): void
    {
        $string = $this->createMessage($message, $context, $level);

        WCMP_Log::add($string);
    }

    /**
     * @param  \Throwable|array|string $message
     * @param  array                   $context
     * @param  string                  $level
     *
     * @return void
     */
    protected function createMessage($message, array $context, string $level): string
    {
        $output = $this->getOutput($message);
        $logContext = Arr::except($context, 'exception');

        if (! empty($logContext)) {
            $output .= "\nContext: " . json_encode($logContext, JSON_PRETTY_PRINT);
        }

        if (WCMP_Log::getLogLevel('debug') !== $level) {
            $output .= $this->getSource($context);
        }

        return $output;
    }

    /**
     * Get the first caller that's not a *Logger.php file.
     *
     * @return null|array
     */
    private function getCaller(): ?array
    {
        $backtrace = debug_backtrace();
        $caller    = current(
            array_filter(
                $backtrace,
                static function ($item) {
                    return isset($item['file'])
                        && ! Str::endsWith($item['file'], 'Logger.php')
                        && ! Str::contains($item['file'], 'Facade.php');
                }
            )
        );

        if (! $caller) {
            $caller = null;
        }

        return $caller;
    }

    /**
     * @return string
     */
    private function getLogDirectory(): string
    {
        return sprintf('%s/var/logs/%s', (new WCMYPA())->plugin_path(), WCMYPA::NAME);
    }

    /**
     * @param $level
     *
     * @return string
     */
    private function getLogFilename($level): string
    {
        return sprintf("%s/%s.log", $this->getLogDirectory(), $level);
    }

    /**
     * @param  \Throwable|array|string $message
     *
     * @return string
     */
    private function getOutput($message): string
    {
        $output = $message;

        if ($message instanceof Throwable) {
            $output = $message->getMessage();
        } elseif (! is_string($message)) {
            $output = (string) json_encode($message, JSON_PRETTY_PRINT);
        }

        return $output;
    }

    /**
     * @param  array $context
     *
     * @return string
     */
    private function getSource(array $context): string
    {
        $throwable = $context['exception'] ?? null;

        if ($throwable instanceof Throwable) {
            if (WP_DEBUG_LOG) {
                return sprintf(
                    "\nMessage: %s\nStack trace: %s",
                    $throwable->getMessage(),
                    $throwable->getTraceAsString()
                );
            }

            $file  = $throwable->getFile();
            $line = $throwable->getLine();
        } else {
            $caller = $this->getCaller();
            $file    = $caller['file'];
            $line   = $caller['line'];
        }

        return sprintf(' (%s:%s)', $file, $line);
    }
}
