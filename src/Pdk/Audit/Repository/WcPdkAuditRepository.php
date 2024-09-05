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
 */
class WcPdkAuditRepository extends Repository implements PdkAuditRepositoryInterface
{
    /**
     * @var \MyParcelNL\WooCommerce\Database\Contract\WpDatabaseServiceInterface
     */
    private $wpDatabaseService;

    /**
     * @param  \MyParcelNL\Pdk\Storage\Contract\StorageInterface                    $storage
     * @param  \MyParcelNL\WooCommerce\Database\Contract\WpDatabaseServiceInterface $wpDatabaseService
     */
    public function __construct(StorageInterface $storage, WpDatabaseServiceInterface $wpDatabaseService)
    {
        parent::__construct($storage);
        $this->wpDatabaseService = $wpDatabaseService;
    }

    /**
     * @return \MyParcelNL\Pdk\Audit\Collection\AuditCollection
     * @throws \Exception
     */
    public function all(): AuditCollection
    {
        return $this->retrieve('audits', function () {
            $audits = $this->wpDatabaseService->getAll(Pdk::get('tableNameAudits'));

            return new AuditCollection($audits->map(static function (array $audit): Audit {
                return new Audit([
                    'id'              => $audit['auditId'],
                    'arguments'       => json_decode($audit['arguments'], true),
                    'action'          => $audit['action'],
                    'model'           => $audit['model'],
                    'modelIdentifier' => $audit['modelIdentifier'],
                    'created'         => new DateTime($audit['created']),
                    'type'            => $audit['type'],
                ]);
            }));
        });
    }

    /**
     * @param  \MyParcelNL\Pdk\Audit\Model\Audit $audit
     *
     * @return void
     * @throws \Exception
     */
    public function store(Audit $audit): void
    {
        $this->wpDatabaseService->insert(Pdk::get('tableNameAudits'), [
            'auditId'         => $audit->id,
            'arguments'       => json_encode($audit->arguments),
            'action'          => $audit->action,
            'model'           => $audit->model,
            'modelIdentifier' => $audit->modelIdentifier,
            'created'         => $audit->created->format('Y-m-d H:i:s'),
            'type'            => $audit->type,
        ]);

        $this->save($audit->id, $audit);
    }
}
