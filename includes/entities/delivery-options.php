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
    const HIDDEN_INPUT_NAME = "_wcmp_delivery_options";

    /**
     * @var string
     */
    public $date;

    /**
     * @var string
     */
    public $time;

    /**
     * @var string
     */
    public $deliveryType;

    /**
     * @var array
     */
    public $additionalOptions;

    /**
     * @var string
     */
    public $carrier;

    /**
     * @var array
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
     * @return bool
     */
    public function isPickup(): bool
    {
        return $this->deliveryType === "pickup";
    }
}

