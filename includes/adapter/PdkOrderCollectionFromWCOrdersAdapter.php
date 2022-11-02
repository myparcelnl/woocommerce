<?php

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\includes\adapter;

use MyParcelNL\Pdk\Facade\Pdk;
use MyParcelNL\Pdk\Plugin\Collection\PdkOrderCollection;
use MyParcelNL\WooCommerce\PdkOrderRepository;
use PdkLogger;
use WCMP_Log;
use WPO\WC\MyParcel\Compatibility\WC_Core as WCX;

/**
 *
 */
class PdkOrderCollectionFromWCOrdersAdapter
{
    /**
     * @var mixed
     */
    private $logger;

    /**
     * @var array
     */
    private $orderIds;

    /**
     * @var \MyParcelNL\Pdk\Plugin\Collection\PdkOrderCollection
     */
    private $pdkOrderCollection;

    /**
     * @param  array $orderIds
     */
    public function __construct(array $orderIds)
    {
        $this->logger             = Pdk::get(PdkLogger::class);
        $this->orderIds           = $orderIds;
        $this->pdkOrderCollection = new PdkOrderCollection();
    }

    /**
     * @return PdkOrderCollection
     * @throws \JsonException
     */
    public function convert(): PdkOrderCollection
    {
        if (is_null($this->orderIds)) {
            $this->logger->log(WCMP_Log::LOG_LEVELS['error'], 'No order ids found');
        }

        foreach ($this->orderIds as $orderId) {
            $this->pushPdkOrderToCollection($orderId);
        }

        return $this->pdkOrderCollection;
    }

    /**
     * @param $orderId
     *
     * @return void
     * @throws \JsonException
     * @throws \Exception
     */
    private function pushPdkOrderToCollection($orderId): void
    {
        $orderRepository = (Pdk::get(PdkOrderRepository::class));
        $pdkOrder = $orderRepository->get($orderId);
        $this->pdkOrderCollection->push($pdkOrder);
    }
}
