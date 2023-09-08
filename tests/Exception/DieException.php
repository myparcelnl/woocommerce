<?php

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Tests\Exception;

use Exception;

final class DieException extends Exception
{
    /**
     * @param  string $message
     * @param  string $title
     */
    public function __construct(string $message = '', string $title = '')
    {
        parent::__construct(implode(': ', array_filter([$message, $title])));
    }
}
