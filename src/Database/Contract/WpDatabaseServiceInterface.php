<?php

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Database\Contract;

use MyParcelNL\Pdk\Base\Support\Collection;

interface WpDatabaseServiceInterface
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

    /**
     * @param  string $table
     *
     * @return \MyParcelNL\Pdk\Base\Support\Collection
     */
    public function getAll(string $table): Collection;

    /**
     * @param  string $table
     * @param  array  $array
     *
     * @return void
     * @throws \Exception
     */
    public function insert(string $table, array $array): void;
}
