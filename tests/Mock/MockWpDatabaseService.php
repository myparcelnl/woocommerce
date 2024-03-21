<?php

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Tests\Mock;

use MyParcelNL\WooCommerce\Database\Service\WpDatabaseService;

final class MockWpDatabaseService extends WpDatabaseService
{
    /**
     * @var \MyParcelNL\WooCommerce\Tests\Mock\MockWpdb
     */
    protected $database;

    public function __construct()
    {
        $this->database = new MockWpdb();

        $GLOBALS['wpdb'] = $this->database;
    }

    /**
     * @param  string|string[] $sql
     *
     * @return array
     */
    public function executeSql($sql): array
    {
        return [];
    }

    /**
     * @return \MyParcelNL\WooCommerce\Tests\Mock\MockWpdb
     */
    public function getDb(): MockWpdb
    {
        return $this->database;
    }
}
