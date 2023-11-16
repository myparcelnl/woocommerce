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
  id tinytext NOT NULL,
  arguments text NOT NULL,
  model tinytext NOT NULL,
  modelIdentifier tinytext NOT NULL,
  action tinytext NOT NULL,
  type tinytext NOT NULL,
  created datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
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
