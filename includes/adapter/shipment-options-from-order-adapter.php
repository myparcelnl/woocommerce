<?php

declare(strict_types=1);

use MyParcelNL\Sdk\src\Adapter\DeliveryOptions\AbstractDeliveryOptionsAdapter;
use MyParcelNL\Sdk\src\Adapter\DeliveryOptions\AbstractShipmentOptionsAdapter;

class WCMP_ShipmentOptionsFromOrderAdapter extends AbstractShipmentOptionsAdapter
{
    private const DEFAULT_INSURANCE = 0;

    private const PROPERTY_SHIPMENT_OPTIONS_METHOD_MAP = [
        'age_check'         => 'hasAgeCheck',
        'large_format'      => 'hasLargeFormat',
        'only_recipient'    => 'hasOnlyRecipient',
        'return'            => 'isReturn',
        'same_day_delivery' => 'isSameDayDelivery',
        'signature'         => 'hasSignature',
        'hide_sender'       => 'hasHideSender',
        'extra_assurance'   => 'hasExtraAssurance',
    ];

    /**
     * @param AbstractDeliveryOptionsAdapter|null $originAdapter
     * @param array                               $inputData
     */
    public function __construct(?AbstractDeliveryOptionsAdapter $originAdapter, array $inputData)
    {
        $adapter = $originAdapter ? $originAdapter->getShipmentOptions() : null;
        $options = $inputData['shipment_options'] ?? $inputData;

        $this->insurance         = $this->isInsuranceFromOptions($options, $adapter);
        $this->label_description = $this->getLabelDescriptionFromOptions($options, $adapter);
        $this->setBooleanShipmentOptions($options, $adapter);
    }

    /**
     * @param                                                                                  $options
     * @param  null|\MyParcelNL\Sdk\src\Adapter\DeliveryOptions\AbstractShipmentOptionsAdapter $adapter
     */
    public function setBooleanShipmentOptions($options, ?AbstractShipmentOptionsAdapter $adapter): void
    {
        foreach (self::PROPERTY_SHIPMENT_OPTIONS_METHOD_MAP as $property => $method) {
            $this->{$property} = $this->getBooleanOption($options, $adapter, $property, $method);
        }
    }

    /**
     * @param  array                                                                           $options
     * @param  null|\MyParcelNL\Sdk\src\Adapter\DeliveryOptions\AbstractShipmentOptionsAdapter $shipmentOptionsAdapter
     * @param  string                                                                          $optionKey
     * @param  string                                                                          $shipmentOptionsMethod
     *
     * @return null|bool
     */
    private function getBooleanOption(
        array                           $options,
        ?AbstractShipmentOptionsAdapter $shipmentOptionsAdapter,
        string                          $optionKey,
        string                          $shipmentOptionsMethod
    ): ?bool {
        $valueFromOptions = null;

        if (array_key_exists($optionKey, $options)) {
            $valueFromOptions = (bool) $options[$optionKey];
        }

        $valueFromAdapter = $shipmentOptionsAdapter ? $shipmentOptionsAdapter->{$shipmentOptionsMethod}() : null;

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
