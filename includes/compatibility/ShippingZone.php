<?php

namespace MyParcelNL\WooCommerce\Includes\Compatibility;

use WC_Shipping_Zone;
use WC_Data_Store;

if (! defined('ABSPATH')) {
    exit;
}

class ShippingZone extends WC_Shipping_Zone
{
    /**
     * @param int|object|array $zone Zone ID to load from the DB or zone object.
     */
    public function __construct($zone = null)
    {
        if (is_array($zone) && $zone) {
            $this->transformToObject($zone);
        } else {
            parent::__construct($zone);
        }
    }

    /**
     * @param array $zoneData
     */
    private function transformToObject(array $zoneData)
    {
        $this->set_id($zoneData['id']);
        $this->set_zone_name($zoneData['zone_name']);
        $this->set_zone_order($zoneData['zone_order']);
        if ($zoneData['zone_locations']) {
            $this->set_zone_locations($zoneData['zone_locations']);
        }
        $this->set_meta_data($zoneData['meta_data']);
        $this->set_object_read(true);
    }
}
