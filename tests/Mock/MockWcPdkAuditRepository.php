<?php
/** @noinspection PhpDocFinalChecksInspection */

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Tests\Mock;

use MyParcelNL\WooCommerce\Pdk\Audit\Repository\WcPdkAuditRepository;
use Symfony\Contracts\Service\ResetInterface;

final class MockWcPdkAuditRepository extends WcPdkAuditRepository implements ResetInterface
{
    public function reset(): void
    {
        $this->storage->delete('audits');
    }
}
