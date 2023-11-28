<?php

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Migration\Pdk;

use MyParcelNL\WooCommerce\Database\Contract\DatabaseServiceInterface;

final class AuditsMigration extends AbstractPdkMigration
{
    /**
     * @var \MyParcelNL\WooCommerce\Database\Contract\DatabaseServiceInterface
     */
    private $databaseService;

    public function __construct(DatabaseServiceInterface $databaseService)
    {
        $this->databaseService = $databaseService;
    }

    public function down(): void
    {
        // Nothing to do here
    }

    /**
     * @return void
     */
    public function up(): void
    {
        $this->databaseService->createAuditsTable();
    }
}
