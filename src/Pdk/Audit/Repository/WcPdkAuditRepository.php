<?php

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Pdk\Audit\Repository;

use DateTime;
use MyParcelNL\Pdk\Audit\Collection\AuditCollection;
use MyParcelNL\Pdk\Audit\Contract\AuditRepositoryInterface;
use MyParcelNL\Pdk\Audit\Model\Audit;
use MyParcelNL\Pdk\Base\Repository\Repository;
use MyParcelNL\Pdk\Facade\Pdk;

class WcPdkAuditRepository extends Repository implements AuditRepositoryInterface
{
    /**
     * @return \MyParcelNL\Pdk\Audit\Collection\AuditCollection
     * @throws \Exception
     */
    public function all(): AuditCollection
    {
        global $wpdb;

        $tableName = $wpdb->prefix . Pdk::get('tableNameAudits');
        $audits    = $wpdb->get_results(
            "SELECT * FROM {$tableName} ORDER BY created DESC",
            'ARRAY_A'
        );

        $auditsCollection = new AuditCollection();

        foreach ($audits as $audit) {
            $auditsCollection->push(
                new Audit([
                    'id'              => $audit['auditId'],
                    'arguments'       => json_decode($audit['arguments'], true),
                    'action'          => $audit['action'],
                    'model'           => $audit['model'],
                    'modelIdentifier' => $audit['modelIdentifier'],
                    'created'         => new DateTime($audit['created']),
                    'type'            => $audit['type'],
                ])
            );
        }

        return $auditsCollection;
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
                'auditId'         => $audit->id,
                'arguments'       => json_encode($audit->arguments),
                'action'          => $audit->action,
                'model'           => $audit->model,
                'modelIdentifier' => $audit->modelIdentifier,
                'created'         => $audit->created->format('Y-m-d H:i:s'),
                'type'            => $audit->type,
            ]
        );

        $this->save($audit->id, $audit);
    }
}
