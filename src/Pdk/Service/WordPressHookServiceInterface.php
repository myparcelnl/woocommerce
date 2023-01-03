<?php

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Pdk\Service;

interface WordPressHookServiceInterface
{
    /**
     * Register the necessary actions and filters.
     *
     * @return void
     */
    public function initialize(): void;
}
