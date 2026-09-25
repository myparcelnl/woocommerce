<?php

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Tests\Mock;

class MockWpError implements StaticMockInterface
{
    /**
     * @var string
     */
    private $code;

    /**
     * @var string
     */
    private $message;

    public function __construct(string $code = '', string $message = '')
    {
        $this->code    = $code;
        $this->message = $message;
    }

    public function get_error_code(): string
    {
        return $this->code;
    }

    public function get_error_message(): string
    {
        return $this->message;
    }

    public static function reset(): void
    {
        // nothing to reset for this mock
    }
}
