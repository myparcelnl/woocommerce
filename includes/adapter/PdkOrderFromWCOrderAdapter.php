<?php

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\includes\adapter;

use Exception;
use MyParcelNL\Pdk\Base\Service\CountryService;
use MyParcelNL\Pdk\Base\Service\WeightService;
use MyParcelNL\Pdk\Fulfilment\Model\Product;
use MyParcelNL\Pdk\Plugin\Collection\PdkOrderLineCollection;
use MyParcelNL\Pdk\Plugin\Model\PdkOrder;
use MyParcelNL\Pdk\Plugin\Model\PdkOrderLine;
use MyParcelNL\Pdk\Shipment\Collection\CustomsDeclarationItemCollection;
use MyParcelNL\Pdk\Shipment\Model\CustomsDeclaration;
use MyParcelNL\Pdk\Shipment\Model\CustomsDeclarationItem;
use MyParcelNL\Pdk\Shipment\Model\DeliveryOptions;
use MyParcelNL\Pdk\Shipment\Model\ShipmentOptions;
use MyParcelNL\Pdk\Shipment\Service\DeliveryDateService;
use MyParcelNL\Sdk\src\Model\Consignment\AbstractConsignment;
use MyParcelNL\Sdk\src\Model\PickupLocation;
use MyParcelNL\Sdk\src\Model\Recipient;
use MyParcelNL\WooCommerce\Helper\ExportRow;
use WC_Order;
use WCMP_Log;
use WCMP_Shipping_Methods;
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

    /**
     * @return void
     * @throws \JsonException
     */
    public function getWeight(): float
    {
        $weight = $this->getExtraOptions()['weight'] ?? null;

        if (null === $weight && $this->order->meta_exists(WCMYPA_Admin::META_ORDER_WEIGHT)) {
            $weight = $this->order->get_meta(WCMYPA_Admin::META_ORDER_WEIGHT);
        }

        return (float) $weight;
    }

    /**
     * @return array
     * @throws \JsonException
     */
    private function getExtraOptions(): array
    {
        return WCMYPA_Admin::getExtraOptionsFromOrder($this->order);
    }

    /**
     * @return int
     * @throws \JsonException
     */
    public function getColloAmount(): int
    {
        return (int) ($this->getExtraOptions()['collo_amount'] ?? 1);
    }

    /**
     * @return int
     * @throws \MyParcelNL\Sdk\src\Exception\ValidationException|\JsonException
     * @throws \Exception
     */
    public function getDigitalStampRangeWeight(): int
    {
        $weight       = 0;
        $extraOptions = $this->getExtraOptions();
        if (AbstractConsignment::PACKAGE_TYPE_DIGITAL_STAMP_NAME===$this->getPdkOrder()->deliveryOptions->packageType) {
            $emptyWeight = (float) WCMYPA()->settingCollection->getByName(
                WCMYPA_Settings::SETTING_EMPTY_DIGITAL_STAMP_WEIGHT
            );

            $weight += $emptyWeight;
        }

        $savedWeight   = $extraOptions['digital_stamp_weight'] ?? null;
        $orderWeight   = $this->getWeight();
        $defaultWeight = WCMYPA()->settingCollection->getByName(
            WCMYPA_Settings::SETTING_CARRIER_DIGITAL_STAMP_DEFAULT_WEIGHT
        ) ?: null;
        $weight        = (float) ($savedWeight ?? $defaultWeight ?? $orderWeight);

        return WeightService::convertToDigitalStamp((int) $weight);
    }

}
