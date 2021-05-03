<?php

declare(strict_types=1);

use MyParcelNL\Sdk\src\Adapter\DeliveryOptions\AbstractDeliveryOptionsAdapter;
use MyParcelNL\Sdk\src\Adapter\DeliveryOptions\AbstractPickupLocationAdapter;

class WCMPBE_PickupLocationFromOrderAdapter extends AbstractPickupLocationAdapter
{
    /**
     * @param \MyParcelNL\Sdk\src\Adapter\DeliveryOptions\AbstractDeliveryOptionsAdapter|null $originAdapter
     * @param array                                                                           $inputData
     */
    public function __construct(?AbstractDeliveryOptionsAdapter $originAdapter, array $inputData)
    {
        $pickupLocationAdapter = $originAdapter ? $originAdapter->getPickupLocation() : null;
        $inputData             = $inputData['pickup_location'];

        $this->cc                = $this->countryFromOptions($inputData, $pickupLocationAdapter);
        $this->city              = $this->cityFromOptions($inputData, $pickupLocationAdapter);
        $this->location_code     = $this->locationCodeFromOptions($inputData, $pickupLocationAdapter);
        $this->location_name     = $this->locationNameFromOptions($inputData, $pickupLocationAdapter);
        $this->number            = $this->numberFromOptions($inputData, $pickupLocationAdapter);
        $this->postal_code       = $this->postalCodeFromOptions($inputData, $pickupLocationAdapter);
        $this->retail_network_id = $this->retailNetworkIdFromOptions($inputData, $pickupLocationAdapter);
        $this->street            = $this->streetFromOptions($inputData, $pickupLocationAdapter);
    }

    /**
     * @param array                                                                          $inputData
     * @param \MyParcelNL\Sdk\src\Adapter\DeliveryOptions\AbstractPickupLocationAdapter|null $adapter
     *
     * @return string
     */
    private function countryFromOptions(array $inputData, ?AbstractPickupLocationAdapter $adapter): string
    {
        if (array_key_exists('cc', $inputData)) {
            return (string) $inputData['cc'];
        }

        return $adapter->getCountry();
    }

    /**
     * @param array                                                                          $inputData
     * @param \MyParcelNL\Sdk\src\Adapter\DeliveryOptions\AbstractPickupLocationAdapter|null $adapter
     *
     * @return string
     */
    private function cityFromOptions(array $inputData, ?AbstractPickupLocationAdapter $adapter): string
    {
        if (array_key_exists('city', $inputData)) {
            return (string) $inputData['city'];
        }

        return $adapter->getCity();
    }

    /**
     * @param array                                                                          $inputData
     * @param \MyParcelNL\Sdk\src\Adapter\DeliveryOptions\AbstractPickupLocationAdapter|null $adapter
     *
     * @return string
     */
    private function locationCodeFromOptions(array $inputData, ?AbstractPickupLocationAdapter $adapter): string
    {
        if (array_key_exists('location_code', $inputData)) {
            return (string) $inputData['location_code'];
        }

        return $adapter->getLocationCode();
    }

    /**
     * @param array                                                                          $inputData
     * @param \MyParcelNL\Sdk\src\Adapter\DeliveryOptions\AbstractPickupLocationAdapter|null $adapter
     *
     * @return string
     */
    private function locationNameFromOptions(array $inputData, ?AbstractPickupLocationAdapter $adapter): string
    {
        if (array_key_exists('location_name', $inputData)) {
            return (string) $inputData['location_name'];
        }

        return $adapter->getLocationName();
    }

    /**
     * @param array                                                                          $inputData
     * @param \MyParcelNL\Sdk\src\Adapter\DeliveryOptions\AbstractPickupLocationAdapter|null $adapter
     *
     * @return string
     */
    private function numberFromOptions(array $inputData, ?AbstractPickupLocationAdapter $adapter): string
    {
        if (array_key_exists('number', $inputData)) {
            return (string) $inputData['number'];
        }

        return $adapter->getNumber();
    }

    /**
     * @param array                                                                          $inputData
     * @param \MyParcelNL\Sdk\src\Adapter\DeliveryOptions\AbstractPickupLocationAdapter|null $adapter
     *
     * @return string
     */
    private function postalCodeFromOptions(array $inputData, ?AbstractPickupLocationAdapter $adapter): string
    {
        if (array_key_exists('postal_code', $inputData)) {
            return (string) $inputData['postal_code'];
        }

        return $adapter->getPostalCode();
    }

    /**
     * @param array                                                                          $inputData
     * @param \MyParcelNL\Sdk\src\Adapter\DeliveryOptions\AbstractPickupLocationAdapter|null $adapter
     *
     * @return string
     */
    private function retailNetworkIdFromOptions(array $inputData, ?AbstractPickupLocationAdapter $adapter): string
    {
        if (array_key_exists('retail_network_id', $inputData)) {
            return (string) $inputData['retail_network_id'];
        }

        return $adapter->getRetailNetworkId();
    }

    /**
     * @param array                                                                          $inputData
     * @param \MyParcelNL\Sdk\src\Adapter\DeliveryOptions\AbstractPickupLocationAdapter|null $adapter
     *
     * @return string
     */
    private function streetFromOptions(array $inputData, ?AbstractPickupLocationAdapter $adapter): string
    {
        if (array_key_exists('street', $inputData)) {
            return (string) $inputData['street'];
        }

        return $adapter->getStreet();
    }
}
