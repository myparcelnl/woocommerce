<?php

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Hooks;

interface WordPressHooksInterface
{
    /**
     * Register the necessary actions and filters.
     *
     * @return void
     */
    public function apply(): void;
}
