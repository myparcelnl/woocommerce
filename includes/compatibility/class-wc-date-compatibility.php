<?php
/**
 * Copy of WC3.0 WC_DateTime class
 */

namespace WPO\WC\MyParcelBE\Compatibility;

use DateTime;

if (!defined('ABSPATH')) {
    exit;
} // Exit if accessed directly

if (class_exists('\\WPO\\WC\\MyParcelbe\\Compatibility\\WC_DateTime')) {
    return;
}

/**
 * WC Wrapper for PHP DateTime.
 *
 * @class    WC_DateTime
 * @category Class
 * @package  WooCommerce/Classes
 * @author   WooThemes
 * @since    3.0.0
 */
class WC_DateTime extends DateTime
{

    /**
     * Output an ISO 8601 date string in local timezone.
     *
     * @return string
     * @since  3.0.0
     */
    public function __toString()
    {
        return $this->format(DATE_ATOM);
    }

    /**
     * Missing in PHP 5.2.
     *
     * @return int
     * @since  3.0.0
     */
    public function getTimestamp()
    {
        return method_exists('DateTime', 'getTimestamp') ? parent::getTimestamp() : $this->format('U');
    }

    /**
     * Get the timestamp with the WordPress timezone offset added or subtracted.
     *
     * @return int
     * @since  3.0.0
     */
    public function getOffsetTimestamp()
    {
        return $this->getTimestamp() + $this->getOffset();
    }

    /**
     * Format a date based on the offset timestamp.
     *
     * @param string $format
     *
     * @return string
     * @since  3.0.0
     */
    public function date($format)
    {
        return gmdate($format, $this->getOffsetTimestamp());
    }

    /**
     * Return a localised date based on offset timestamp. Wrapper for date_i18n function.
     *
     * @param string $format
     *
     * @return string
     * @since  3.0.0
     */
    public function date_i18n($format = 'Y-m-d')
    {
        return date_i18n($format, $this->getOffsetTimestamp());
    }
}
