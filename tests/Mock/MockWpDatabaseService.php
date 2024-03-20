<?php

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Tests\Mock;

use MyParcelNL\WooCommerce\Database\Service\WpDatabaseService;

final class MockWpDatabaseService extends WpDatabaseService
{
    public function __construct()
    {
        $GLOBALS['wpdb'] = new MockWpdb();
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
}
