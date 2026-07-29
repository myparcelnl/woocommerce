<?php

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Tests\Mock;

final class MockWpOptionsDatabase
{
    /**
     * @var string
     */
    public $last_error = '';

    /**
     * @var string
     */
    public $options = 'wp_options';

    /**
     * @var string
     */
    public $prefix = 'wp_';

    /**
     * @var array<string, array{autoload: string, option_id: int, option_value: mixed}>
     */
    private $data = [];

    /**
     * @var array<string, array{0: string, 1: array}>
     */
    private $preparedQueries = [];

    /**
     * @var null|array
     */
    private $transactionSnapshot;

    /**
     * @var null|array{operation: string, option: string}
     */
    private $failure;

    /**
     * @var array<int, array{operation: string, option: null|string}>
     */
    private $operations = [];

    /**
     * @var string[]
     */
    private $likePatterns = [];

    /**
     * @param  string $name
     * @param  mixed  $value
     * @param  string $autoload
     *
     * @return void
     */
    public function seedOption(string $name, $value, string $autoload = 'yes'): void
    {
        $this->data[$name] = [
            'autoload'     => $autoload,
            'option_id'    => count($this->data) + 1,
            'option_value' => $value,
        ];

        WordPressOptions::updateOption($name, $value);
    }

    /**
     * @return array<string, mixed>
     */
    public function getOptions(): array
    {
        return array_map(static function (array $row) {
            return $row['option_value'];
        }, $this->data);
    }

    public function getAutoload(string $name): ?string
    {
        return $this->data[$name]['autoload'] ?? null;
    }

    /**
     * @return array<int, array{operation: string, option: null|string}>
     */
    public function getOperations(): array
    {
        return $this->operations;
    }

    /**
     * @return string[]
     */
    public function getLikePatterns(): array
    {
        return $this->likePatterns;
    }

    public function failOn(string $operation, string $option): void
    {
        $this->failure = compact('operation', 'option');
    }

    public function esc_like(string $text): string
    {
        return addcslashes($text, '_%\\');
    }

    /**
     * @param  string $query
     * @param  mixed  ...$args
     *
     * @return string
     */
    public function prepare(string $query, ...$args): string
    {
        $key                         = '__prepared_' . count($this->preparedQueries);
        $this->preparedQueries[$key] = [$query, $args];

        return $key;
    }

    /**
     * @return string[]
     */
    public function get_col(string $preparedQuery): array
    {
        $this->last_error = '';

        [$query, $args] = $this->preparedQueries[$preparedQuery];
        $pattern        = (string) $args[0];
        $prefix         = $this->unescapeLike(substr($pattern, 0, -1));

        $this->likePatterns[] = $pattern;
        $this->operations[] = ['operation' => 'select', 'option' => $prefix];

        return array_values(array_filter(
            array_keys($this->data),
            static function (string $name) use ($prefix): bool {
                return 0 === strpos($name, $prefix);
            }
        ));
    }

    /**
     * @return null|int
     */
    public function get_var(string $preparedQuery): ?int
    {
        $this->last_error = '';

        [$query, $args] = $this->preparedQueries[$preparedQuery];
        $name           = (string) $args[0];

        return $this->data[$name]['option_id'] ?? null;
    }

    /**
     * @return false|int
     */
    public function query(string $query)
    {
        $this->last_error = '';
        $operation        = strtoupper(trim($query));
        $this->operations[] = ['operation' => strtolower($operation), 'option' => null];

        if ($this->shouldFail('query', $operation)) {
            return false;
        }

        if ('START TRANSACTION' === $operation) {
            $this->transactionSnapshot = $this->data;
        } elseif ('COMMIT' === $operation) {
            $this->transactionSnapshot = null;
        } elseif ('ROLLBACK' === $operation && null !== $this->transactionSnapshot) {
            $this->data = $this->transactionSnapshot;
            WordPressOptions::$options = $this->getOptions();
            $this->transactionSnapshot = null;
        }

        return 0;
    }

    /**
     * @param  string $table
     * @param  array  $data
     * @param  array  $where
     *
     * @return false|int
     */
    public function update(string $table, array $data, array $where)
    {
        $this->last_error = '';
        $source           = $where['option_name'];
        $target           = $data['option_name'];
        $this->operations[] = ['operation' => 'update', 'option' => $source];

        if ($this->shouldFail('update', $source)) {
            return false;
        }

        if (isset($this->data[$target])) {
            $this->last_error = 'Duplicate entry for option_name';

            return false;
        }

        if (! isset($this->data[$source])) {
            return 0;
        }

        $this->data[$target] = $this->data[$source];
        unset($this->data[$source]);

        WordPressOptions::deleteOption($source);
        WordPressOptions::updateOption($target, $this->data[$target]['option_value']);

        return 1;
    }

    /**
     * @param  string $table
     * @param  array  $where
     *
     * @return false|int
     */
    public function delete(string $table, array $where)
    {
        $this->last_error = '';
        $name             = $where['option_name'];
        $this->operations[] = ['operation' => 'delete', 'option' => $name];

        if ($this->shouldFail('delete', $name)) {
            return false;
        }

        if (! isset($this->data[$name])) {
            return 0;
        }

        unset($this->data[$name]);
        WordPressOptions::deleteOption($name);

        return 1;
    }

    private function shouldFail(string $operation, string $option): bool
    {
        if (['operation' => $operation, 'option' => $option] !== $this->failure) {
            return false;
        }

        $this->failure  = null;
        $this->last_error = 'Simulated database failure';

        return true;
    }

    private function unescapeLike(string $value): string
    {
        return strtr($value, [
            '\\\\' => '\\',
            '\\_'  => '_',
            '\\%'  => '%',
        ]);
    }
}
