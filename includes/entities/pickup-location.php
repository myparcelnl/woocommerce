<?php

namespace WPO\WC\MyParcelBE\Entity;

use stdClass;

/**
 * Class PickupLocation
 *
 * @package WPO\WC\MyParcelBE\Entity
 */
class PickupLocation
{
    /**
     * @var string
     */
    private $location_name;

    /**
     * @var string
     */
    private $location_code;

    /**
     * @var string
     */
    private $street;

    /**
     * @var string
     */
    private $number;

    /**
     * @var string
     */
    private $postal_code;

    /**
     * @var string
     */
    private $city;

    public function __construct(stdClass $data)
    {
        $this->location_name = $data->location_name;
        $this->location_code = $data->location_code;
        $this->street        = $data->street;
        $this->number        = $data->number;
        $this->postal_code   = $data->postal_code;
        $this->city          = $data->city;
    }

    /**
     * @return string
     */
    public function getLocationName(): string
    {
        return $this->location_name;
    }

    /**
     * @return string
     */
    public function getLocationCode(): string
    {
        return $this->location_code;
    }

    /**
     * @return string
     */
    public function getStreet(): string
    {
        return $this->street;
    }

    /**
     * @return string
     */
    public function getNumber(): string
    {
        return $this->number;
    }

    /**
     * @return string
     */
    public function getPostalCode(): string
    {
        return $this->postal_code;
    }

    /**
     * @return string
     */
    public function getCity(): string
    {
        return $this->city;
    }
}
