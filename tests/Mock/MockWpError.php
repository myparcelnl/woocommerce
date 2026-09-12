<?php

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Tests\Mock;

class MockWpError implements StaticMockInterface
{
    private $code;
    private $message;

    public function __construct($code = '', $message = '')
    {
        $this->code    = $code;
        $this->message = $message;
    }

    public function get_error_code()
    {
        return $this->code;
    }

    public function get_error_message()
    {
        return $this->message;
    }

    public static function reset(): void
    {
        // nothing to reset for this mock
    }
}
