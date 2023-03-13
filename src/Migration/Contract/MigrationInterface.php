<?php

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Migration\Contract;

interface MigrationInterface
{
    public function down(): void;

    public function getVersion(): string;

    public function up(): void;
}
