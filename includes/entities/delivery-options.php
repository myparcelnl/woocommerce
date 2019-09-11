<?php

namespace WPO\WC\MyParcelBE\Entity;

use DateTime;
use Exception;
use MyParcelNL\Sdk\src\Model\Consignment\BpostConsignment;

defined('ABSPATH') or exit;

if (! class_exists('\\WPO\\WC\\MyParcelBE\\Entity\\DeliveryOptions')) :

    class DeliveryOptions
    {
        const HIDDEN_INPUT_NAME = "_wcmp_delivery_options";

        /**
         * @var mixed
         */
        public $date;

        /**
         * @var mixed
         */
        public $time;

        /**
         * @var mixed
         */
        public $deliveryType;

        /**
         * @var mixed
         */
        public $additionalOptions;

        /**
         * @var string
         */
        public $carrier;

        /**
         * @var mixed
         */
        public $deliveryMoment;

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

            if ("pickup" === $this->deliveryType) {
                $this->pickupLocation = (object) $delivery_options["pickupLocation"];
                $this->time           = $delivery_options["pickupMoment"];
            } else {
                $this->time = $delivery_options["deliveryMoment"];
            }
        }
    }

endif;
