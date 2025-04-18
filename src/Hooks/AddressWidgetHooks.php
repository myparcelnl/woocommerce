<?php

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Hooks;

use MyParcelNL\Pdk\Facade\Logger;
use MyParcelNL\Pdk\Facade\Pdk;
use MyParcelNL\WooCommerce\Facade\Filter;
use MyParcelNL\WooCommerce\Hooks\Contract\WooCommerceInitHookInterface;
use MyParcelNL\WooCommerce\Hooks\Contract\WordPressHooksInterface;

/**
 * Adds separate address fields to the WooCommerce order fields.
 */
class AddressWidgetHooks implements WordPressHooksInterface, WooCommerceInitHookInterface
{
    protected const ADDRESS_WIDGET_FIELDTYPE = 'MyParcelAddressWidget';

    public function onWoocommerceInit(): void
    {
        $this->registerBlocksFields();
    }

    public function apply(): void
    {
        // Add our custom field for the address widget
        add_filter('woocommerce_checkout_fields', [$this, 'addAddressWidgetToCheckout'], Filter::apply('separateAddressFieldsPriority'), 2);

        /**
         * This hook allows us to render our own arbitrary HTML as specified in the type in "addAddressWidgetToCheckout()".
         * Cannot call wooCommerce_form_field_TYPE
         * as we need to be the last to modify the output
         * and woocommerce_form_field() is called last.
         */
        add_filter('woocommerce_form_field', [$this, 'renderAddressWidgetContainer'], 1, 4);

        // Save our metadata
        add_action('woocommerce_checkout_update_order_meta', [$this, 'saveResolvedAddress']);

        add_filter('render_block_woocommerce/checkout-shipping-address-block', [$this, 'renderBlock'], 9999, 3);
    }

    public function renderBlock($content, $block, $instance)
    {
        print \esc_html($content);
        return $content;
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
     * Register fields for the blocks checkout
     * @return void
     */
    public function registerBlocksFields(): void
    {
        \woocommerce_register_additional_checkout_field(
            array(
                'id'            => 'address/widget',
                'type'        => 'text', // ideally we'd use a div, but woocommerce is limited to checkbox/select/text
                'label'         => 'Address',
                // 'optionalLabel' => 'Government ID (optional)',
                'location'      => 'address',
                'required'      => false,
                // 'attributes'    => array(
                //     'autocomplete'     => 'government-id',
                //     'aria-describedby' => 'some-element',
                //     'aria-label'       => 'custom aria label',
                //     'pattern'          => '[A-Z0-9]{5}', // A 5-character string of capital letters and numbers.
                //     'title'            => 'Title to show on hover',
                //     'data-custom'      => 'custom data',
                // ),
            ),
        );
    }

    /**
     * Save resolved address to Wooc
     * @param mixed $order_id
     * @return void
     */
    public function saveResolvedAddress($order_id)
    {
        $post = wp_unslash(filter_input_array(INPUT_POST));

        if (!empty($post['billing_' . Pdk::get('checkoutAddressHiddenInputName')])) {
            update_post_meta($order_id, 'myparcel_resolved_billing_address', \wc_sanitize_textarea($post['billing_' . Pdk::get('checkoutAddressHiddenInputName')]));
        }

        if (!empty($post['shipping_' . Pdk::get('checkoutAddressHiddenInputName')])) {
            update_post_meta($order_id, 'myparcel_resolved_shipping_address', \wc_sanitize_textarea($post['shipping_' . Pdk::get('checkoutAddressHiddenInputName')]));
        }
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
