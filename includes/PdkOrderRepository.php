<?php

declare(strict_types=1);

use MyParcelNL\Pdk\Plugin\Model\PdkOrder;
use MyParcelNL\Pdk\Plugin\Repository\AbstractPdkOrderRepository;

/**
 *
 */
class PdkOrderRepository extends AbstractPdkOrderRepository
{
    /**
     * @param  PdkOrder ...$orders
     *
     * @return void
     */
    public function add(PdkOrder ...$orders): void
    {
        foreach ($orders as $order) {
            $this->save($order->externalIdentifier, $order);
        }
    }

    /**
     * @param  int|string $input
     *
     * @return \MyParcelNL\Pdk\Plugin\Model\PdkOrder
     */
    public function get($input): PdkOrder
    {
        $orderData = is_array($input) ? $input : ['externalIdentifier' => $input];

        return $this->retrieve((string) $orderData['externalIdentifier'], function () use ($orderData) {
            return new PdkOrder($orderData);
        });
    }
}
