<?php

declare(strict_types=1);

use MyParcelNL\Sdk\src\Adapter\DeliveryOptions\AbstractDeliveryOptionsAdapter;
use MyParcelNL\Sdk\src\Adapter\DeliveryOptions\AbstractShipmentOptionsAdapter;

class WCMP_ShipmentOptionsFromOrderAdapter extends AbstractShipmentOptionsAdapter
{
    private const DEFAULT_INSURANCE = 0;

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

        $this->signature         = $this->isSignatureFromOptions($options, $shipmentOptionsAdapter);
        $this->only_recipient    = $this->isOnlyRecipientFromOptions($options, $shipmentOptionsAdapter);
        $this->large_format      = $this->isLargeFormatFromOptions($options, $shipmentOptionsAdapter);
        $this->return            = $this->isReturnShipmentFromOptions($options, $shipmentOptionsAdapter);
        $this->age_check         = $this->isAgeCheckFromOptions($options, $shipmentOptionsAdapter);
        $this->insurance         = $this->isInsuranceFromOptions($options, $shipmentOptionsAdapter);
        $this->label_description = $this->getLabelDescriptionFromOptions($options, $shipmentOptionsAdapter);
        $this->same_day_delivery = $this->isSameDayDeliveryFromOptions($options, $shipmentOptionsAdapter);
    }

    /**
     * @param array                               $options
     * @param AbstractShipmentOptionsAdapter|null $shipmentOptionsAdapter
     *
     * @return bool|null
     */
    private function isSignatureFromOptions(array $options, ?AbstractShipmentOptionsAdapter $shipmentOptionsAdapter): ?bool
    {
        $valueFromOptions = (bool) ($options['signature'] ?? null);
        $valueFromAdapter = $shipmentOptionsAdapter ? $shipmentOptionsAdapter->hasSignature() : null;

        return $valueFromOptions ?? $valueFromAdapter;
    }

    /**
     * @param array                               $options
     * @param AbstractShipmentOptionsAdapter|null $shipmentOptionsAdapter
     *
     * @return bool|null
     */
    private function isSameDayDeliveryFromOptions(array $options, ?AbstractShipmentOptionsAdapter $shipmentOptionsAdapter): ?bool
    {
        $valueFromOptions = (bool) ($options['same_day_delivery'] ?? null);
        $valueFromAdapter = $shipmentOptionsAdapter ? $shipmentOptionsAdapter->isSameDayDelivery() : null;

        return $valueFromOptions ?? $valueFromAdapter;
    }

    /**
     * @param array                               $options
     * @param AbstractShipmentOptionsAdapter|null $shipmentOptionsAdapter
     *
     * @return bool|null
     */
    private function isOnlyRecipientFromOptions(array $options, ?AbstractShipmentOptionsAdapter $shipmentOptionsAdapter): ?bool
    {
        $valueFromOptions = (bool) ($options['only_recipient'] ?? null);
        $valueFromAdapter = $shipmentOptionsAdapter ? $shipmentOptionsAdapter->hasOnlyRecipient() : null;

        return $valueFromOptions ?? $valueFromAdapter;
    }

    /**
     * @param array                               $options
     * @param AbstractShipmentOptionsAdapter|null $shipmentOptionsAdapter
     *
     * @return bool|null
     */
    private function isLargeFormatFromOptions(array $options, ?AbstractShipmentOptionsAdapter $shipmentOptionsAdapter): ?bool
    {
        $valueFromOptions = (bool) ($options['large_format'] ?? null);
        $valueFromAdapter = $shipmentOptionsAdapter ? $shipmentOptionsAdapter->hasLargeFormat() : null;

        return $valueFromOptions ?? $valueFromAdapter;
    }

    /**
     * @param array                               $options
     * @param AbstractShipmentOptionsAdapter|null $shipmentOptionsAdapter
     *
     * @return bool|null
     */
    private function isReturnShipmentFromOptions(array $options, ?AbstractShipmentOptionsAdapter $shipmentOptionsAdapter): ?bool
    {
        $valueFromOptions = (bool) ($options['return_shipment'] ?? null);
        $valueFromAdapter = $shipmentOptionsAdapter ? $shipmentOptionsAdapter->isReturn() : null;

        return $valueFromOptions ?? $valueFromAdapter;
    }

    /**
     * @param array                               $options
     * @param AbstractShipmentOptionsAdapter|null $shipmentOptionsAdapter
     *
     * @return bool|null
     */
    private function isAgeCheckFromOptions(array $options, ?AbstractShipmentOptionsAdapter $shipmentOptionsAdapter): ?bool
    {
        $valueFromOptions = (bool) ($options['age_check'] ?? null);
        $valueFromAdapter = $shipmentOptionsAdapter ? $shipmentOptionsAdapter->hasAgeCheck() : null;

        return $valueFromOptions ?? $valueFromAdapter;
    }

    /**
     * @param array                               $options
     * @param AbstractShipmentOptionsAdapter|null $shipmentOptionsAdapter
     *
     * @return int|null
     */
    private function isInsuranceFromOptions(array $options, ?AbstractShipmentOptionsAdapter $shipmentOptionsAdapter): ?int
    {
        if (array_key_exists('insured', $options)) {
            if ($options['insured']) {
                return (int) $options['insured_amount'];
            }

            return self::DEFAULT_INSURANCE;
        }

        if ($shipmentOptionsAdapter) {
            return $shipmentOptionsAdapter->getInsurance();
        }

        return null;
    }

    /**
     * @param array                                                                           $options
     * @param \MyParcelNL\Sdk\src\Adapter\DeliveryOptions\AbstractShipmentOptionsAdapter|null $shipmentOptionsAdapter
     *
     * @return string|null
     */
    private function getLabelDescriptionFromOptions(
        array $options,
        ?AbstractShipmentOptionsAdapter $shipmentOptionsAdapter
    ): ?string {
        if (array_key_exists('label_description', $options)) {
            return $options['label_description'];
        }

        if ($shipmentOptionsAdapter) {
            return $shipmentOptionsAdapter->getLabelDescription();
        }

        return null;
    }
}
