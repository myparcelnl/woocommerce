<?php

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Migration;

interface Migration
{
    public function down(): void;

    public function getVersion(): string;

    public function up(): void;
}
