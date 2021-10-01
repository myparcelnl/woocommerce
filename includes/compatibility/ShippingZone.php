<?php

namespace MyParcelNL\WooCommerce;

use WC_Shipping_Zone;
use WC_Data_Store;

if (! defined('ABSPATH')) {
    exit;
}

class ShippingZone extends WC_Shipping_Zone
{
    /**
     * Constructor for zones.
     *
     * @param  int|object|array $zone Zone ID to load from the DB or zone object.
     */
    public function __construct( $zone = null ) {
        if ( is_numeric( $zone ) && ! empty( $zone ) ) {
            $this->set_id( $zone );
            $this->call_wc_shipping_zone();
        } elseif ( is_object( $zone ) ) {
            $this->set_id( $zone->zone_id );
            $this->call_wc_shipping_zone();
        } elseif ( is_array($zone) ) {
            $this->transformToObject($zone);
        } elseif ( 0 === $zone || '0' === $zone ) {
            $this->set_id( 0 );
            $this->call_wc_shipping_zone();
        } else {
            $this->set_object_read( true );
        }
    }

    /**
     * @param  array $zoneData
     */
    private function transformToObject(array $zoneData)
    {
        $this->set_id($zoneData['id']);
        $this->set_zone_name($zoneData['zone_name']);
        $this->set_zone_order($zoneData['zone_order']);
        if( $zoneData['zone_locations'] ) {
            $this->set_zone_locations($zoneData['zone_locations']);
        }
        $this->set_meta_data($zoneData['meta_data']);
        $this->set_object_read( true );
    }

    /**
     *
     * @throws \Exception
     */
    private function call_wc_shipping_zone(): void
    {
        $this->data_store = WC_Data_Store::load( 'shipping-zone' );
        if ( false === $this->get_object_read() ) {
            $this->data_store->read( $this );
        }
    }
}