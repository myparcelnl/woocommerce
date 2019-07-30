<?php

namespace WPO\WC\MyParcelBE\Collections;

defined( 'ABSPATH' ) or exit;

if ( ! class_exists( '\\WPO\\WC\\MyParcelbe\\Entity\\Setting' ) ) :
    class Setting {
        public function __construct($name, $carrier = null)
        {
        }
    }
endif; // Class exists check