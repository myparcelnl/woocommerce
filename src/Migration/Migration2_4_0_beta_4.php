<?php

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Migration;

/**
 * - remove log file (now uses WC logger)
 */
class Migration2_4_0_beta_4 implements MigrationInterface
{
    public function down(): void
    {
    }

    public function getVersion(): string
    {
        return '2.4.0-beta-4';
    }

    public function up(): void
    {
        $uploadDir = wp_upload_dir();

        $logFile = sprintf('%smyparcel_log.txt', trailingslashit($uploadDir['basedir']));

        if (file_exists($logFile)) {
            unlink($logFile);
        }
    }
}

