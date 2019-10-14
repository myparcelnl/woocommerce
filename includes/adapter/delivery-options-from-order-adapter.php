<?php

use MyParcelNL\Sdk\src\Adapter\DeliveryOptions\AbstractDeliveryOptionsAdapter;

class WCMP_DeliveryOptionsFromOrderAdapter extends AbstractDeliveryOptionsAdapter
{
    /**
     * WCMP_DeliveryOptionsFromOrderAdapter constructor.
     *
     * @param AbstractDeliveryOptionsAdapter|null $originAdapter
     * @param array                               $inputData
     */
    public function __construct(?AbstractDeliveryOptionsAdapter $originAdapter, array $inputData = [])
    {
        $this->carrier         = $inputData['carrier'] ?? $originAdapter->getCarrier() ?? null;
        $this->date            = $originAdapter->getDate() ?? null;
        $this->deliveryType    = $originAdapter->getDeliveryType() ?? null;
        $this->shipmentOptions = new WCMP_ShipmentOptionsFromOrderAdapter($originAdapter, $inputData);
        $this->pickupLocation  = $originAdapter->getPickupLocation() ?? null;
    }
}
