<?php

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Database\Service;

use MyParcelNL\Pdk\Facade\Pdk;
use MyParcelNL\WooCommerce\Database\Contract\DatabaseServiceInterface;

class DatabaseService implements DatabaseServiceInterface
{
    public function createAuditsTable(): void
    {
        global $wpdb;

        $tableName      = $wpdb->prefix . Pdk::get('tableNameAudits');
        $charsetCollate = $wpdb->get_charset_collate();

        // phpcs:ignore
        $sql = <<<EOF
CREATE TABLE $tableName (
  id int NOT NULL AUTO_INCREMENT PRIMARY KEY,
  auditId tinytext NOT NULL,
  arguments text NOT NULL,
  model tinytext NOT NULL,
  modelIdentifier tinytext NOT NULL,
  action tinytext NOT NULL,
  type tinytext NOT NULL,
  created tinytext NOT NULL,
  PRIMARY KEY  (id)
) $charsetCollate;
EOF;

        $this->executeSql($sql);
    }

    /**
     * @param  string|string[] $sql
     *
     * @return array
     */
    public function executeSql($sql): array
    {
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

        return dbDelta($sql);
    }
}
