<?php

namespace WPO\WC\MyParcelBE\Entity;

use DateTime;
use Exception;
use MyParcelNL\Sdk\src\Model\Consignment\BpostConsignment;
use MyParcelNL\Sdk\src\Model\DeliveryOptions\DeliveryOptions;
use WC_Order;

defined('ABSPATH') or exit;

if (class_exists('\\WPO\\WC\\MyParcelBE\\Entity\\LegacyDeliveryOptions')) {
    return;
}

/**
 * To support pre 4.0.0 delivery options.
 *
 * @package WPO\WC\MyParcelBE\Entity
 */
class LegacyDeliveryOptions
{
    private const DEFAULT_CARRIER = BpostConsignment::CARRIER_NAME;

    private const PICKUP_TYPES = ["retail", "retail_express"];

    /**
     * @var array
     */
    private $legacyDeliveryOptions;

    /**
     * @var array The new 4.0.0+ delivery options.
     */
    private $migratedDeliveryOptions;

    /**
     * LegacyDeliveryOptions constructor.
     *
     * @param array    $deliveryOptions
     * @param WC_Order $order
     */
    public function __construct(array $deliveryOptions, WC_Order $order = null)
    {
        $this->legacyDeliveryOptions = $deliveryOptions;

        $priceComment = $this->legacyDeliveryOptions["time"][0]["price_comment"];

        $this->migratedDeliveryOptions = [
            "carrier"         => self::DEFAULT_CARRIER,
            "deliveryType"    => $priceComment,
            "deliveryDate"    => $this->migrateDate($deliveryOptions["date"]),
            "shipmentOptions" => [
                "signature" => (bool) $deliveryOptions["signature"],
            ],
            "isPickup"        => $this->migrateIsPickup($priceComment),
        ];

        if ($this->migratedDeliveryOptions["isPickup"]) {
            $this->migratedDeliveryOptions["pickupLocation"] = $this->migratePickupLocation();
        }
    }

    /**
     * Create and return the new DeliveryOptions class with the migrated options.
     *
     * @return DeliveryOptions
     * @throws Exception
     */
    public function getDeliveryOptions(): DeliveryOptions
    {
        return new DeliveryOptions($this->migratedDeliveryOptions);
    }

    /**
     * Get the boolean value for whether it's a pickup shipment using the legacy 'price_comment'.
     *
     * @param string $price_comment
     *
     * @return bool
     */
    private function migrateIsPickup(string $price_comment): bool
    {
        return in_array($price_comment, self::PICKUP_TYPES);
    }

    /**
     * Migrate the date to the format used in the new version.
     *
     * @param $date
     *
     * @return false|string
     * @throws Exception
     */
    private function migrateDate($date)
    {
        return (new DateTime($date))->format(DateTime::ATOM);
    }

    private function migratePickupLocation()
    {
        return [
            "carrier"           => self::DEFAULT_CARRIER,
            "cc"                => $this->legacyDeliveryOptions["cc"],
            "city"              => $this->legacyDeliveryOptions["city"],
            "location_name"     => $this->legacyDeliveryOptions["location"],
            "location_code"     => $this->legacyDeliveryOptions["location_code"],
            "number"            => $this->legacyDeliveryOptions["number"],
            "postal_code"       => $this->legacyDeliveryOptions["postal_code"],
            "retail_network_id" => $this->legacyDeliveryOptions["retail_network_id"],
            "street"            => $this->legacyDeliveryOptions["street"],
        ];
    }
}
