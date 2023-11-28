<?php

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Tests\Mock;

final class MockWpGlobal
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
            if (strpos($query, $key) !== false) {
                return $this->db[$key];
            }
        }

        return [];
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
