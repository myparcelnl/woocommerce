<?php

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Database\Contract;

interface DatabaseServiceInterface
{
    /**
     * @return void
     */
    public function createAuditsTable(): void;

    /**
     * @param  string|string[] $sql
     *
     * @return array
     */
    public function executeSql($sql): array;
}
