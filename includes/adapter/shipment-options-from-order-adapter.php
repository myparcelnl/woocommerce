<?php

use MyParcelNL\Sdk\src\Adapter\DeliveryOptions\AbstractDeliveryOptionsAdapter;
use MyParcelNL\Sdk\src\Adapter\DeliveryOptions\AbstractShipmentOptionsAdapter;
use MyParcelNL\Sdk\src\Model\Consignment\BpostConsignment;

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
        $shipmentOptionsAdapter = $originAdapter->getShipmentOptions() ?? null;
        $options                = $inputData['shipment_options'] ?? [];

        $this->signature      = (bool) ($options['signature'] ?? $shipmentOptionsAdapter->hasSignature() ?? false);
        $this->only_recipient = (bool) ($options['only_recipient'] ?? $shipmentOptionsAdapter->hasOnlyRecipient() ?? false);
        $this->insurance      = (int) ($options['insurance'] ?? $shipmentOptionsAdapter->getInsurance() ?? self::DEFAULT_INSURANCE);
    }
}
