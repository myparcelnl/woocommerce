<?php

use MyParcelNL\Sdk\src\Adapter\DeliveryOptions\AbstractDeliveryOptionsAdapter;
use MyParcelNL\Sdk\src\Adapter\DeliveryOptions\AbstractShipmentOptionsAdapter;

class WCMP_ShipmentOptionsFromOrderAdapter extends AbstractShipmentOptionsAdapter
{
    const DEFAULT_INSURANCE = 0;

    /**
     * WCMP_ShipmentOptionsFromOrderAdapter constructor.
     *
     * @param AbstractDeliveryOptionsAdapter|null $originAdapter
     * @param array                               $inputData
     */
    public function __construct(?AbstractDeliveryOptionsAdapter $originAdapter, array $inputData)
    {
        $shipmentOptionsAdapter = $originAdapter ? $originAdapter->getShipmentOptions() : null;
        $options                = $inputData['shipment_options'] ?? [];

        $this->signature      = (bool) ($options['signature'] ?? $shipmentOptionsAdapter ? $shipmentOptionsAdapter->hasSignature() : false);
        $this->only_recipient = (bool) ($options['only_recipient'] ?? $shipmentOptionsAdapter ? $shipmentOptionsAdapter->hasOnlyRecipient() : false);
        $this->insurance      = (int) ($options['insurance'] ?? $shipmentOptionsAdapter ? $shipmentOptionsAdapter->getInsurance() : self::DEFAULT_INSURANCE);
    }
}
