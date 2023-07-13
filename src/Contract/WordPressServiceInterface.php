<?php

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Contract;

interface WordPressServiceInterface
{
    public function getVersion(): string;
}
