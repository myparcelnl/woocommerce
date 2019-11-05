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
        $options                = $inputData['shipment_options'] ?? $inputData;

        $this->signature = $this->isSignatureFromOptions($options, $shipmentOptionsAdapter);
        $this->insurance = $this->isInsuranceFromOptions($options, $shipmentOptionsAdapter);
    }

    /**
     * @param array                               $options
     * @param AbstractShipmentOptionsAdapter|null $shipmentOptionsAdapter
     *
     * @return bool|null
     */
    private function isSignatureFromOptions(array $options, ?AbstractShipmentOptionsAdapter $shipmentOptionsAdapter): ?bool
    {
        if (key_exists('signature', $options)) {
            return (bool) $options['signature'];
        }

        if ($shipmentOptionsAdapter) {
            return $shipmentOptionsAdapter->hasSignature();
        }

        return false;
    }

    /**
     * @param array                               $options
     * @param AbstractShipmentOptionsAdapter|null $shipmentOptionsAdapter
     *
     * @return int|null
     */
    private function isInsuranceFromOptions(array $options, ?AbstractShipmentOptionsAdapter $shipmentOptionsAdapter): ?int
    {
        if (key_exists('insurance', $options)) {
            return (int) $options['insurance'];
        }

        if ($shipmentOptionsAdapter) {
            return $shipmentOptionsAdapter->getInsurance();
        }

        return self::DEFAULT_INSURANCE;
    }
}
