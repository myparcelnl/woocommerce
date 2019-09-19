<?php

namespace WPO\WC\MyParcelBE\Entity;

use Exception;
use MyParcelNL\Sdk\src\Model\Consignment\BpostConsignment;

defined('ABSPATH') or exit;

if (class_exists('\\WPO\\WC\\MyParcelBE\\Entity\\DeliveryOptions')) {
    return;
}

class DeliveryOptions
{
    /**
     * @var string
     */
    private $date;

    /**
     * @var string
     */
    private $moment;

    /**
     * @var string
     */
    private $deliveryType;

    /**
     * @var array
     */
    private $shipmentOptions;

    /**
     * @var string
     */
    private $carrier;

    /**
     * @var PickupLocation
     */
    private $pickupLocation;

    /**
     * DeliveryOptions constructor.
     *
     * @param array $deliveryOptions
     *
     * @throws Exception
     */
    public function __construct(array $deliveryOptions)
    {
        if (array_key_exists("carrier", $deliveryOptions)) {
            $carrier = $deliveryOptions["carrier"];
        }

        $this->deliveryType    = $deliveryOptions["delivery"];
        $this->date            = $deliveryOptions["deliveryDate"];
        $this->shipmentOptions = $deliveryOptions["shipmentOptions"] ?? [];
        $this->carrier         = $carrier ?? BpostConsignment::CARRIER_NAME;

        if ($this->isPickup()) {
            $this->pickupLocation = new PickupLocation($deliveryOptions["pickupLocation"]);

            $this->moment = $deliveryOptions["pickupMoment"];
        } else {
            $this->moment = $deliveryOptions["deliveryMoment"];
        }
    }

    /**
     * @return string
     */
    public function getDate(): ?string
    {
        return $this->date;
    }

    /**
     * @return string
     */
    public function getMoment(): ?string
    {
        return $this->moment;
    }

    /**
     * @return string
     */
    public function getDeliveryType(): ?string
    {
        return $this->deliveryType;
    }

    /**
     * @return array
     */
    public function getShipmentOptions(): ?array
    {
        return $this->shipmentOptions;
    }

    /**
     * @return string
     */
    public function getCarrier(): ?string
    {
        return $this->carrier;
    }

    /**
     * @return PickupLocation
     */
    public function getPickupLocation(): ?PickupLocation
    {
        return $this->pickupLocation;
    }

    /**
     * @return bool
     */
    public function isPickup(): ?bool
    {
        return $this->deliveryType === "pickup";
    }
}

