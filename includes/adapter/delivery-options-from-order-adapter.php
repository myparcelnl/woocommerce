<?php

declare(strict_types=1);

use MyParcelNL\Sdk\src\Adapter\DeliveryOptions\AbstractDeliveryOptionsAdapter;
use MyParcelNL\Sdk\src\Model\Consignment\AbstractConsignment;

class WCMP_DeliveryOptionsFromOrderAdapter extends AbstractDeliveryOptionsAdapter
{
    /**
     * Creates delivery options but sets most missing data to null instead of default values.
     *
     * @param AbstractDeliveryOptionsAdapter|null $originAdapter
     * @param array                               $inputData
     */
    public function __construct(?AbstractDeliveryOptionsAdapter $originAdapter, array $inputData = [])
    {
        $adapterCarrier        = $originAdapter ? $originAdapter->getCarrier() : null;
        $adapterDate           = $originAdapter ? $originAdapter->getDate() : null;
        $adapterDeliveryType   = $originAdapter ? $originAdapter->getDeliveryType() : AbstractConsignment::DEFAULT_DELIVERY_TYPE_NAME;
        $adapterPackageType    = $originAdapter ? $originAdapter->getPackageType() : null;
        $adapterPickupLocation = $originAdapter ? $originAdapter->getPickupLocation() : null;

        $this->carrier         = $inputData['carrier'] ?? $adapterCarrier;
        $this->date            = $inputData['date'] ?? $adapterDate;
        $this->deliveryType    = $inputData['delivery_type'] ?? $adapterDeliveryType;
        $this->packageType     = $inputData['package_type'] ?? $adapterPackageType;
        $this->shipmentOptions = new WCMP_ShipmentOptionsFromOrderAdapter($originAdapter, $inputData);

        $hasInputPickupLocation = isset($inputData['pickup_location']) && ! empty($inputData['pickup_location']);
        $this->pickupLocation   = $hasInputPickupLocation
            ? new WCMPBE_PickupLocationFromOrderAdapter($originAdapter, $inputData)
            : $adapterPickupLocation;
    }
}
