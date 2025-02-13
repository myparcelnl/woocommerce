<?php

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Migration;

use MyParcelNL\Pdk\App\Order\Contract\PdkOrderRepositoryInterface;
use MyParcelNL\Pdk\Audit\Service\AuditService;
use MyParcelNL\WooCommerce\Database\Contract\WpDatabaseServiceInterface;
use MyParcelNL\WooCommerce\Migration\Pdk\AbstractPdkMigration;

final class Migration5_2_1 extends AbstractPdkMigration
{
    /**
     * @var \MyParcelNL\Pdk\Audit\Service\AuditService
     */
    private $auditService;

    /**
     * @var \MyParcelNL\WooCommerce\Database\Contract\WpDatabaseServiceInterface
     */
    private $databaseService;

    /**
     * @var \MyParcelNL\Pdk\App\Order\Contract\PdkOrderRepositoryInterface
     */
    private $pdkOrderRepository;

    public function __construct(
        WpDatabaseServiceInterface  $databaseService,
        PdkOrderRepositoryInterface $pdkOrderRepository,
        AuditService                $auditService
    ) {
        $this->databaseService    = $databaseService;
        $this->pdkOrderRepository = $pdkOrderRepository;
        $this->auditService       = $auditService;
    }

    public function getVersion(): string
    {
        return '5.2.1';
    }

    /**
     * Up to 5.2.0 we had an audits table, restore it when migrating down, to stay compatible with the php code
     */
    public function down(): void
    {
        $this->databaseService->createAuditsTable();
    }

    public function up(): void
    {
        // move auto exported flag from audits table to pdk order
        $this->auditService->migrateExportedPropertyToOrders($this->pdkOrderRepository);
        // get rid of the audits table
        $this->databaseService->dropAuditsTable();
    }
}
