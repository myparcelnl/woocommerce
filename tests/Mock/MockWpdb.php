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
     * @var string
     */
    public $options = 'wp_options';

    /**
     * @var string
     */
    public $last_error = '';

    /**
     * @var null|string
     */
    public $preparedOptionPattern;

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
     * @param  string $text
     *
     * @return string
     */
    public function esc_like(string $text): string
    {
        return addcslashes($text, '_%\\');
    }

    /**
     * @param  string $query
     * @param  string $value
     *
     * @return string
     */
    public function prepare(string $query, string $value): string
    {
        $this->preparedOptionPattern = $value;

        return $query;
    }

    /**
     * @param  string $query
     *
     * @return string[]
     */
    public function get_col(string $query): array
    {
        $prefix = rtrim(str_replace('\\_', '_', $this->preparedOptionPattern ?? ''), '%');

        return array_values(array_filter(
            array_keys(WordPressOptions::$options),
            static function (string $optionName) use ($prefix): bool {
                return 0 === strpos($optionName, $prefix);
            }
        ));
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
