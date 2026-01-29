<?php

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Migration;

use MyParcelNL\Pdk\Base\PdkBootstrapper;

final class Migration6_0_0 extends AbstractMigration
{
    public function getVersion(): string
    {
        return '6.0.0';
    }

    public function down(): void
    {
        $this->changeNamespace(PdkBootstrapper::PLUGIN_NAMESPACE, 'myparcelnl');
        // we do not support back-migration to 'myparcelbe' namespace, this needs to be done manually if needed
        \wp_cache_flush();
    }

    public function up(): void
    {
        $this->changeNamespace('myparcelbe', PdkBootstrapper::PLUGIN_NAMESPACE);
        $this->changeNamespace('myparcelnl', PdkBootstrapper::PLUGIN_NAMESPACE);
        \wp_cache_flush();
    }

    private function changeNamespace(string $from, string $to): void
    {
        global $wpdb;
        $originalSuppressErrors = $wpdb->suppress_errors;
        $wpdb->suppress_errors  = true;
        $wpdb->query(
            "UPDATE {$wpdb->prefix}options SET option_name = REPLACE(option_name, '{$from}_', '{$to}_') WHERE option_name LIKE '%{$from}_%';"
        );
        $wpdb->suppress_errors = $originalSuppressErrors;
    }
}
