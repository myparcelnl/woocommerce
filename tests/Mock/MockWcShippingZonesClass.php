<?php

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Tests\Mock;

use Exception;
use WC_Shipping_Method;
use WC_Shipping_Zone;

/**
 * @extends \WC_Shipping_Zones
 */
class MockWcShippingZonesClass extends MockWcClass
{
    /**
     * Get shipping zone using it's ID.
     *
     * @param  int $instanceId Instance ID.
     *
     * @return bool|WC_Shipping_Method
     */
    public static function get_shipping_method(int $instanceId)
    {
        /** @var ?WC_Shipping_Method $instance */
        $instance = MockWcData::get($instanceId);
        if ($instance) {
            return $instance;
        }

        return false;
    }

    /**
     * Get shipping zones from the database.
     *
     * @param  string $context Getting shipping methods for what context. Valid values, admin, json.
     *
     * @return array Array of arrays.
     * @since 2.6.0
     */
    public static function get_zones($context = 'admin'): array
    {
        $allZones      = MockWcData::getByClass(WC_Shipping_Zone::class);
        $allZonesArray = [];

        foreach ($allZones as $zone) {
            $allZonesArray[$zone->get_id()] = $zone;
        }

        return $allZonesArray;
    }

    /**
     * Get shipping zone using its ID
     *
     * @param  int $zoneId Zone ID.
     *
     * @return WC_Shipping_Zone|bool
     * @throws \Throwable
     * @since 2.6.0
     */
    public static function get_zone(int $zoneId)
    {
        return self::get_zone_by('zone_id', $zoneId);
    }

    /**
     * Get shipping zone by an ID.
     *
     * @param  string $by Get by 'zone_id' or 'instance_id'.
     * @param  int    $id ID.
     *
     * @return WC_Shipping_Zone|bool
     * @throws \Throwable
     * @since 2.6.0
     */
    public static function get_zone_by($by = 'zone_id', $id = 0)
    {
        $zoneId = false;

        switch ($by) {
            case 'zone_id':
                $zoneId = $id;
                break;
        }

        if (false !== $zoneId) {
            try {
                return new WC_Shipping_Zone($zoneId);
            } catch (Exception $e) {
                return false;
            }
        }

        return false;
    }
}
