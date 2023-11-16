<?php

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Tests\Mock;

use MyParcelNL\WooCommerce\Database\Service\DatabaseService;

final class MockDatabaseService extends DatabaseService
{
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
