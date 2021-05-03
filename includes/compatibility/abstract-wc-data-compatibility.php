<?php
/**
 * Derived from SkyVerge WooCommerce Plugin Framework https://github.com/skyverge/wc-plugin-framework/
 */

namespace WPO\WC\MyParcelBE\Compatibility;

use WC_Data;

defined('ABSPATH') or exit;

if (class_exists('\\WPO\\WC\\MyParcelbe\\Compatibility\\Data')) {
    return;
}

/**
 * WooCommerce data compatibility class.
 *
 * @since 4.6.0-dev
 */
abstract class Data
{
    /**
     * Creates aliases for add_meta_data, update_meta_data and delete_meta_data without the _data box number
     *
     * @param string $name      static function name
     * @param array  $arguments function arguments
     */
    public static function __callStatic($name, $arguments)
    {
        if (substr($name, -strlen('_meta')) == '_meta' && method_exists(__CLASS__, $name . '_data')) {
            call_user_func_array([__CLASS__, $name . '_data'], $arguments);
        }
    }

    /**
     * Gets an object property.
     *
     * @param WC_Data $object       the data object, likely \WC_Order or \WC_Product
     * @param string  $prop         the property name
     * @param string  $context      if 'view' then the value will be filtered
     * @param array   $compat_props Compatibility properties.
     *
     * @return mixed
     * @since 4.6.0-dev
     */
    public static function get_prop($object, $prop, $context = 'edit', $compat_props = [])
    {
        $value = '';

        if (WC_Core::is_wc_version_gte_3_0()) {
            if (is_callable([$object, "get_{$prop}"])) {
                $value = $object->{"get_{$prop}"}($context);
            }
        } else {
            // backport the property name
            if (isset($compat_props[$prop])) {
                $prop = $compat_props[$prop];
            }

            // if this is the 'view' context and there is an accessor method, use it
            if (is_callable([$object, "get_{$prop}"]) && 'view' === $context) {
                $value = $object->{"get_{$prop}"}();
            } else {
                $value = $object->$prop;
            }
        }

        return $value;
    }

    /**
     * Sets an object's properties.
     * Note that this does not save any data to the database.
     *
     * @param WC_Data $object       the data object, likely \WC_Order or \WC_Product
     * @param array   $props        the new properties as $key => $value
     * @param array   $compat_props Compatibility properties.
     *
     * @return WC_Data
     * @since 4.6.0-dev
     */
    public static function set_props($object, $props, $compat_props = [])
    {
        if (WC_Core::is_wc_version_gte_3_0()) {
            $object->set_props($props);
        } else {
            foreach ($props as $prop => $value) {
                if (isset($compat_props[$prop])) {
                    $prop = $compat_props[$prop];
                }

                $object->$prop = $value;
            }
        }

        return $object;
    }

    /**
     * Gets an object's stored meta value.
     *
     * @param WC_Data $object  the data object, likely \WC_Order or \WC_Product
     * @param string  $key     the meta key
     * @param bool    $single  whether to get the meta as a single item. Defaults to `true`
     * @param string  $context if 'view' then the value will be filtered
     *
     * @return mixed
     * @throws \JsonException
     * @since 4.6.0-dev
     */
    public static function get_meta($object, $key = '', $single = true, $context = 'edit')
    {
        if (WC_Core::is_wc_version_gte_3_0()) {
            $value = $object->get_meta($key, $single, $context);
        } else {
            $object_id = is_callable([$object, 'get_id'])
                ? $object->get_id()
                : $object->id;
            $value     = get_post_meta($object_id, $key, $single);
        }

        $value = self::removeSerialization($object, $key, $value);

        if (is_string($value)) {
            $decoded = json_decode($value, true);
            // json_decode returns null if there was a syntax error, meaning input was not valid JSON.
            $value = $decoded ?? $value;
        }

        return $value;
    }

    /**
     * Check if an object has given meta key.
     *
     * @param WC_Data $object the data object, likely \WC_Order or \WC_Product
     * @param string  $key    the meta key
     *
     * @return bool
     */
    public static function has_meta(WC_Data $object, string $key): bool
    {
        if (WC_Core::is_wc_version_gte_3_0()) {
            return $object->meta_exists($key);
        }

        $object_id = is_callable([$object, 'get_id']) ? $object->get_id() : $object->id;
        return count(get_post_meta($object_id, $key)) > 0;
    }

    /**
     * Stores an object meta value.
     *
     * @param WC_Data $object the data object, likely \WC_Order or \WC_Product
     * @param string  $key    the meta key
     * @param string  $value  the meta value
     * @param bool    $unique Optional. Whether the meta should be unique.
     *
     * @since 4.6.0-dev
     */
    public static function add_meta_data($object, $key, $value, $unique = false)
    {
        if (WC_Core::is_wc_version_gte_3_0()) {
            $object->add_meta_data($key, $value, $unique);

            $object->save_meta_data();
        } else {
            $object_id = is_callable([$object, 'get_id']) ? $object->get_id() : $object->id;
            add_post_meta($object_id, $key, maybe_serialize($value), $unique);
        }
    }

    /**
     * Updates an object's stored meta value.
     *
     * @param WC_Data      $object  the data object, likely \WC_Order or \WC_Product
     * @param string       $key     the meta key
     * @param string|array $value   the meta value, will be encoded if it's an array
     * @param int|string   $meta_id Optional. The specific meta ID to update
     *
     * @since 4.6.0-dev
     */
    public static function update_meta_data($object, $key, $value, $meta_id = ''): void
    {
        if (is_array($value)) {
            $value = json_encode($value);
        }

        if (WC_Core::is_wc_version_gte_3_0()) {
            $object->update_meta_data($key, $value, $meta_id);

            $object->save_meta_data();
        } else {
            $object_id = is_callable([$object, 'get_id']) ? $object->get_id() : $object->id;

            update_post_meta($object_id, $key, $value);
        }
    }

    /**
     * Deletes an object's stored meta value.
     *
     * @param WC_Data $object the data object, likely \WC_Order or \WC_Product
     * @param string  $key    the meta key
     *
     * @since 4.6.0-dev
     */
    public static function delete_meta_data($object, $key)
    {
        if (WC_Core::is_wc_version_gte_3_0()) {
            $object->delete_meta_data($key);

            $object->save_meta_data();
        } else {
            $object_id = is_callable([$object, 'get_id']) ? $object->get_id() : $object->id;
            delete_post_meta($object_id, $key);
        }
    }

    /**
     * Fix data stored as objects or serialized strings. Converts everything to array and updates it in the meta data.
     *
     * @param \WC_Data $object
     * @param string   $key
     * @param mixed    $value
     *
     * @return mixed
     * @throws \JsonException
     */
    private static function removeSerialization(WC_Data $object, string $key, $value)
    {
        if (is_serialized($value)) {
            $value = @unserialize(trim($value));

            self::update_meta_data($object, $key, $value);
        }

        if (is_object($value)) {
            if (is_callable([$value, 'toArray'])) {
                $value = $value->toArray();
            } else {
                $value = (array) $value;
            }

            self::update_meta_data($object, $key, $value);
        }

        return $value;
    }
}
