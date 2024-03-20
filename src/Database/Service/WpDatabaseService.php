<?php

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Database\Service;

use MyParcelNL\Pdk\Base\Support\Collection;
use MyParcelNL\Pdk\Facade\Pdk;
use MyParcelNL\WooCommerce\Database\Contract\WpDatabaseServiceInterface;

class WpDatabaseService implements WpDatabaseServiceInterface
{
    public function createAuditsTable(): void
    {
        global $wpdb;

        $tableName      = $wpdb->prefix . Pdk::get('tableNameAudits');
        $charsetCollate = $wpdb->get_charset_collate();

        if ($this->tableExists($tableName)) {
            return;
        }

        // phpcs:ignore
        $sql = <<<EOF
CREATE TABLE $tableName (
  id int NOT NULL AUTO_INCREMENT,
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

    /**
     * @param  string $table
     *
     * @return \MyParcelNL\Pdk\Base\Support\Collection
     * @throws \Exception
     */
    public function getAll(string $table): Collection
    {
        global $wpdb;

        $tableName = $wpdb->prefix . $table;

        return new Collection(
            $wpdb->get_results(
                "SELECT * FROM $tableName ORDER BY created DESC",
                'ARRAY_A'
            )
        );
    }

    /**
     * @param  string $table
     * @param  array  $array
     *
     * @return void
     * @throws \Exception
     */
    public function insert(string $table, array $array): void
    {
        global $wpdb;

        $tableName = $wpdb->prefix . $table;

        $wpdb->insert($tableName, $array);
    }

    /**
     * @param  string $tableName
     *
     * @return bool
     */
    protected function tableExists(string $tableName): bool
    {
        global $wpdb;

        return $wpdb->get_var("SHOW TABLES LIKE '$tableName'") === $tableName;
    }
}
