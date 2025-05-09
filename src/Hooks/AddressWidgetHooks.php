<?php

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Hooks;

use MyParcelNL\Pdk\Facade\Pdk;
use MyParcelNL\Pdk\Facade\Settings;
use MyParcelNL\Pdk\Settings\Model\CheckoutSettings;
use MyParcelNL\WooCommerce\Facade\Filter;

/**
 * Adds separate address fields to the WooCommerce order fields.
 */
class AddressWidgetHooks extends AbstractFieldsHooks
{
    protected const ADDRESS_WIDGET_FIELDTYPE = 'MyParcelAddressWidget';

    public function apply(): void
    {
        if (! Settings::get(CheckoutSettings::ENABLE_ADDRESS_WIDGET, CheckoutSettings::ID)) {
            return;
        }

        // Add our custom field for the address widget
        add_filter('woocommerce_checkout_fields', [$this, 'addAddressWidgetToCheckout'], Filter::apply('separateAddressFieldsPriority'), 1);

        /**
         * This hook allows us to render our own arbitrary HTML as specified in the type in "addAddressWidgetToCheckout()".
         * Cannot call wooCommerce_form_field_TYPE
         * as we need to be the last to modify the output
         * and woocommerce_form_field() is called last.
         */
        add_filter('woocommerce_form_field', [$this, 'renderAddressWidgetContainer'], 1, 4);

        // Save our metadata
        add_action('woocommerce_checkout_update_order_meta', [$this, 'saveResolvedAddress']);

    }

    /**
     * Shortcode (non-Blocks) checkout only.
     * Adds the scripts, wrapper and fields for the separate address fields.
     *
     * @param  array $fields
     *
     * @return array
     */
    public function addAddressWidgetToCheckout(array $fields): array
    {
        /*
         * This custom field is rendered as an empty div, which will be replaced by the Vue component.
         * This is implemented through the filter 'woocommerce_form_field_XXX'.
         * @see renderAddressWidgetContainer().
         */
        $fields['billing']['billing_address_widget'] = [
            'type' => self::ADDRESS_WIDGET_FIELDTYPE,
            'id' => 'billing_address_widget',
            'priority' =>  $fields['billing']['billing_address_1']['priority'],
        ];

        $fields['shipping']['shipping_address_widget'] = [
            'type' => self::ADDRESS_WIDGET_FIELDTYPE,
            'id' => 'shipping_address_widget',
            'priority' =>  $fields['shipping']['shipping_address_1']['priority'],
        ];

        return $fields;
    }


    /**
     * Save resolved address to Wooc
     * @param mixed $order_id
     * @return void
     */
    public function saveResolvedAddress($order_id)
    {
        $post = wp_unslash(filter_input_array(INPUT_POST));

        $order = wc_get_order($order_id);

        if (!empty($post['billing_' . Pdk::get('checkoutAddressHiddenInputName')])) {
            $order->update_meta_data('_billing_' . Pdk::get('checkoutAddressHiddenInputName'), \wc_sanitize_textarea($post['billing_' . Pdk::get('checkoutAddressHiddenInputName')]));
        }

        if (!empty($post['shipping_' . Pdk::get('checkoutAddressHiddenInputName')])) {
            $order->update_meta_data('_shipping_' . Pdk::get('checkoutAddressHiddenInputName'), \wc_sanitize_textarea($post['shipping_' . Pdk::get('checkoutAddressHiddenInputName')]));
        }
        $order->save_meta_data();
    }


    /**
     * Callback for the 'woocommerce_form_field_XXX' filter.
     * Renders an empty div to be replaced by the Vue component.
     *
     * @see \woocommerce_form_field()
     *
     * @param $field
     * @param $key
     * @param $args
     * @param $value
     *
     * @return string
     */
    public function renderAddressWidgetContainer($field, $key, $args, $value): string
    {
        if ($args['type'] !== self::ADDRESS_WIDGET_FIELDTYPE) {
            return $field;
        }
        return '<div class="form-row address-widget" id="' . $args['id'] . '" data-priority="' . $args['priority'] . '"></div>';
    }
}
