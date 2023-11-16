<?php

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Pdk\Audit\Repository;

use MyParcelNL\Pdk\Audit\Collection\AuditCollection;
use MyParcelNL\Pdk\Audit\Contract\AuditRepositoryInterface;
use MyParcelNL\Pdk\Audit\Model\Audit;
use MyParcelNL\Pdk\Base\Repository\Repository;
use MyParcelNL\Pdk\Facade\Pdk;

class WcPdkAuditRepository extends Repository implements AuditRepositoryInterface
{
    /**
     * @return \MyParcelNL\Pdk\Audit\Collection\AuditCollection
     */
    public function all(): AuditCollection
    {
        global $wpdb;

        $tableName = $wpdb->prefix . Pdk::get('tableNameAudits');
        $audits    = $wpdb->get_results(
            "SELECT * FROM {$tableName} ORDER BY created DESC",
            'ARRAY_A'
        );

        return new AuditCollection($audits);
    }

    /**
     * @param  \MyParcelNL\Pdk\Audit\Model\Audit $audit
     *
     * @return void
     */
    public function store(Audit $audit): void
    {
        global $wpdb;

        $tableName = $wpdb->prefix . Pdk::get('tableNameAudits');

        $wpdb->insert(
            $tableName,
            [
                'id'              => $audit->id,
                'arguments'       => json_encode($audit->arguments),
                'action'          => $audit->action,
                'model'           => $audit->model,
                'modelIdentifier' => $audit->modelIdentifier,
                'created'         => $audit->created,
                'type'            => $audit->type,
            ]
        );

        $this->save($audit->id, $audit);
    }
}
