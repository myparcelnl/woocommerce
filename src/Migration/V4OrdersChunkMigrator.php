<?php

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Migration;

use MyParcelNL\WooCommerce\Migration\Pdk\OrdersMigration;

/**
 * Handles one chunk of the timestamped migration that repairs the 4.x orders.
 *
 * This class exists because a timestamped migration is an anonymous class, so it can schedule work but
 * can never be the callback for it.
 *
 * OrdersMigration writes the format of 5.0.0. Migration6_5_1 picks its orders when it is scheduled, and
 * at that moment a 4.x order has no PDK data yet, so it never converts these orders. That also holds on
 * a shop that upgrades from 4.x for the first time. So a chunk first migrates the 4.x data and then
 * runs the carrier normalisation of 6.5.1 on all its orders. Both steps leave an order alone when it is
 * already up to date, so an order that the 5.0.0 chunk migrated first is only normalised here.
 */
final class V4OrdersChunkMigrator
{
    /**
     * @var \MyParcelNL\WooCommerce\Migration\Pdk\OrdersMigration
     */
    private $ordersMigration;

    /**
     * @var \MyParcelNL\WooCommerce\Migration\Migration6_5_1
     */
    private $carrierMigration;

    /**
     * @param  \MyParcelNL\WooCommerce\Migration\Pdk\OrdersMigration $ordersMigration
     * @param  \MyParcelNL\WooCommerce\Migration\Migration6_5_1      $carrierMigration
     */
    public function __construct(OrdersMigration $ordersMigration, Migration6_5_1 $carrierMigration)
    {
        $this->ordersMigration  = $ordersMigration;
        $this->carrierMigration = $carrierMigration;
    }

    /**
     * @param  array $data
     *
     * @return void
     * @throws \Exception
     */
    public function migrateChunk(array $data): void
    {
        $orderIds = $data['orderIds'] ?? [];

        if (! $orderIds) {
            return;
        }

        $this->ordersMigration->migrateOrder($data);

        $this->carrierMigration->migrateOrderChunk(['orderIds' => $orderIds]);
        $this->carrierMigration->migrateShipmentChunk(['orderIds' => $orderIds]);
    }
}
