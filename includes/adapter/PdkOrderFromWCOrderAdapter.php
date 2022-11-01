<?php

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\includes\adapter;

use MyParcelNL\Pdk\Base\Service\WeightService;
use MyParcelNL\Sdk\src\Model\Consignment\AbstractConsignment;
use WC_Order;
use WCMYPA_Admin;
use WCMYPA_Settings;
use WC_Order_Item;

/**
 *
 */
class PdkOrderFromWCOrderAdapter
{
    public const DEFAULT_BELGIAN_INSURANCE = 500;

    /**
     * @var \WC_Order
     */
    private $order;

    /**
     * @param  \WC_Order $order
     */
    public function __construct(WC_Order $order)
    {
        $this->order = $order;
    }

    /**
     * @return \WC_Order
     */
    public function getOrder(): WC_Order
    {
        return $this->order;
    }
}
