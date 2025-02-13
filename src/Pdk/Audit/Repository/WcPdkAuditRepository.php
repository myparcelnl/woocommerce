<?php

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Pdk\Audit\Repository;

use DateTime;
use MyParcelNL\Pdk\Audit\Collection\AuditCollection;
use MyParcelNL\Pdk\Audit\Contract\PdkAuditRepositoryInterface;
use MyParcelNL\Pdk\Audit\Model\Audit;
use MyParcelNL\Pdk\Base\Repository\Repository;
use MyParcelNL\Pdk\Facade\Pdk;
use MyParcelNL\Pdk\Storage\Contract\StorageInterface;
use MyParcelNL\WooCommerce\Database\Contract\WpDatabaseServiceInterface;

/**
 * @final
 * @deprecated This class is deprecated and will be removed in the next major release.
 */
class WcPdkAuditRepository extends Repository implements PdkAuditRepositoryInterface
{
    /**
     * @return \MyParcelNL\Pdk\Audit\Collection\AuditCollection
     * @throws \Exception
     * @deprecated This method is a no-op, retained for compatibility only.
     */
    public function all(): AuditCollection
    {
        return new AuditCollection([]);
    }

    /**
     * @deprecated This method is a no-op, retained for compatibility only.
     * @param  \MyParcelNL\Pdk\Audit\Model\Audit $audit
     *
     * @return void
     * @throws \Exception
     */
    public function store(Audit $audit): void
    {
        // no-op
    }
}
