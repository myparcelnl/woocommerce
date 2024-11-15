<?php

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Tests\Mock;

final class MockWpdb
{
    private const AUDITS = 'wp_myparcelnl_audits';

    /**
     * @var string
     */
    public $prefix = 'wp_';

    /**
     * @var array
     */
    private $db = [
        self::AUDITS => [],
    ];

    /**
     * @return string
     */
    public function get_charset_collate(): string
    {
        return 'utf8mb4_unicode_ci';
    }

    /**
     * @param  string $query
     *
     * @return array
     */
    public function get_results(string $query): array
    {
        $keys = array_keys($this->db);

        foreach ($keys as $key) {
            if (false !== strpos($query, $key)) {
                return $this->db[$key];
            }
        }

        return [];
    }

    /**
     * @param  string $query
     *
     * @return null|string
     */
    public function get_var(string $query): ?string
    {
        return $query;
    }

    /**
     * @param  string $tableName
     * @param  array  $data
     *
     * @return void
     */
    public function insert(string $tableName, array $data): void
    {
        $this->db[$tableName][] = $data;
    }
}
