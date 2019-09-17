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
     * For hidden input field and database field
     */
    const FIELD_DELIVERY_OPTIONS = "_wcmp_delivery_options";

    /**
     * @var string
     * @deprecated
     */
    public $date;

    /**
     * @var string
     * @deprecated
     */
    public $time;

    /**
     * @var string
     * @deprecated
     */
    public $deliveryType;

    /**
     * @var array
     * @deprecated
     */
    public $additionalOptions;

    /**
     * @var string
     * @deprecated
     */
    public $carrier;

    /**
     * @var array
     * @deprecated
     */
    public $pickupLocation;

    /**
     * DeliveryOptions constructor.
     *
     * @param array|mixed|object $delivery_options
     *
     * @throws Exception
     */
    public function __construct(array $delivery_options)
    {
        if (array_key_exists("carrier", $delivery_options)) {
            $carrier = $delivery_options["carrier"];
        }

        $this->deliveryType      = $delivery_options["delivery"];
        $this->date              = $delivery_options["deliveryDate"];
        $this->additionalOptions = $delivery_options["additionalOptions"];
        $this->carrier           = $carrier ?? BpostConsignment::CARRIER_NAME;

        if ($this->isPickup()) {
            $this->pickupLocation = (object) $delivery_options["pickupLocation"];
            $this->time           = $delivery_options["pickupMoment"];
        } else {
            $this->time = $delivery_options["deliveryMoment"];
        }
    }

    /**
     * @return string
     */
    public function getDate(): string
    {
        return $this->date;
    }

    /**
     * @return string
     */
    public function getTime(): string
    {
        return $this->time;
    }

    /**
     * @return string
     */
    public function getDeliveryType(): string
    {
        return $this->deliveryType;
    }

    /**
     * @return array
     */
    public function getAdditionalOptions(): array
    {
        return $this->additionalOptions;
    }

    /**
     * @return string
     */
    public function getCarrier(): ?string
    {
        return $this->carrier;
    }

    /**
     * @return array
     */
    public function getPickupLocation(): array
    {
        return $this->pickupLocation;
    }

    /**
     * @return bool
     */
    public function isPickup(): bool
    {
        return $this->deliveryType === "pickup";
    }

}

