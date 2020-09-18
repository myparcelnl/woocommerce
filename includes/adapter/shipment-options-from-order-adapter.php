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

        $this->signature      = $this->isSignatureFromOptions($options, $shipmentOptionsAdapter);
        $this->only_recipient = $this->isOnlyRecipientFromOptions($options, $shipmentOptionsAdapter);
        $this->large_format   = $this->isLargeFormatFromOptions($options, $shipmentOptionsAdapter);
        $this->return         = $this->isReturnShipmentFromOptions($options, $shipmentOptionsAdapter);
        $this->age_check      = $this->isAgeCheckFromOptions($options, $shipmentOptionsAdapter);
        $this->insurance      = $this->isInsuranceFromOptions($options, $shipmentOptionsAdapter);
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
     * @return bool|null
     */
    private function isOnlyRecipientFromOptions(array $options, ?AbstractShipmentOptionsAdapter $shipmentOptionsAdapter): ?bool
    {
        if (key_exists('only_recipient', $options)) {
            return (bool) $options['only_recipient'];
        }
        if ($shipmentOptionsAdapter) {
            return $shipmentOptionsAdapter->hasOnlyRecipient();
        }

        return false;
    }

    /**
     * @param array                               $options
     * @param AbstractShipmentOptionsAdapter|null $shipmentOptionsAdapter
     *
     * @return bool|null
     */
    private function isLargeFormatFromOptions(array $options, ?AbstractShipmentOptionsAdapter $shipmentOptionsAdapter): ?bool
    {
        if (key_exists('large_format', $options)) {
            return (bool) $options['large_format'];
        }

        if ($shipmentOptionsAdapter) {
            return $shipmentOptionsAdapter->hasLargeFormat();
        }

        return false;
    }

    /**
     * @param array                               $options
     * @param AbstractShipmentOptionsAdapter|null $shipmentOptionsAdapter
     *
     * @return bool|null
     */
    private function isReturnShipmentFromOptions(array $options, ?AbstractShipmentOptionsAdapter $shipmentOptionsAdapter): ?bool
    {
        if (key_exists('return_shipment', $options)) {
            return (bool) $options['return_shipment'];
        }

        if ($shipmentOptionsAdapter) {
            return $shipmentOptionsAdapter->isReturn();
        }

        return false;
    }

    /**
     * @param array                               $options
     * @param AbstractShipmentOptionsAdapter|null $shipmentOptionsAdapter
     *
     * @return bool|null
     */
    private function isAgeCheckFromOptions(array $options, ?AbstractShipmentOptionsAdapter $shipmentOptionsAdapter): ?bool
    {
        if (key_exists('age_check', $options)) {
            return (bool) $options['age_check'];
        }

        if ($shipmentOptionsAdapter) {
            return $shipmentOptionsAdapter->hasAgeCheck();
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
        if (key_exists('insured', $options) && $options['insured']) {
            return (int) $options['insured_amount'];
        }

        if ($shipmentOptionsAdapter) {
            return $shipmentOptionsAdapter->getInsurance();
        }

        return self::DEFAULT_INSURANCE;
    }
}
