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
class SeparateAddressFieldsHooks extends AbstractFieldsHooks
{
    protected const ADDRESS_WIDGET_FIELDTYPE = 'MyParcelAddressWidget';

    public function apply(): void
    {
        // Hide existing address fields
        // add_filter('woocommerce_get_country_locale', [$this, 'hideLocaleFields'], 1);
        // add_filter('woocommerce_country_locale_field_selectors', [$this, 'destroyJsSelectors']);
        add_filter('woocommerce_default_address_fields', [$this, 'hideDefaultAddressFields']);

        // Add new address fields
        add_filter('woocommerce_checkout_fields', [$this, 'addAddressWidgetToCheckout'], Filter::apply('separateAddressFieldsPriority'), 2);

        // Custom field type rendering. Cannot call wooCommerce_form_field_TYPE
        // as we need to be the last to modify the output
        // and woocommerce_form_field is called later.
        add_filter('woocommerce_form_field', [$this, 'renderAddressWidgetContainer'], 1, 4);


        add_action('woocommerce_checkout_update_order_meta', [$this, 'saveResolvedAddress']);

    }

    /**
     * Non-blocks (shortcode) checkout only.
     * Hides all default woocommerce address fields.
     *
     * @param  array $fields
     *
     * @return array
     */
    public function hideDefaultAddressFields(array $fields): array
    {
        // TODO: check pdk-fields.php and add an "address fields"  group to hide them easily
        $hidden = [
            Pdk::get('fieldAddress1'),
            Pdk::get('fieldAddress2'),
            Pdk::get('fieldStreet'),
            Pdk::get('fieldNumber'),
            Pdk::get('fieldNumberSuffix'),
            Pdk::get('fieldCity'),
            Pdk::get('fieldPostalCode'),
            Pdk::get('fieldCountry'),
            Pdk::get('fieldState'),
        ];
        foreach ($hidden as $field) {
            unset($fields[$field]);
        }

        return $fields;
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
        // This custom field is rendered as an empty div, which will be replaced by the Vue component.
        // This is implemented through the filter 'woocommerce_form_field_XXX'.
        $fields['billing']['billing_address_widget'] = [
            'type' => self::ADDRESS_WIDGET_FIELDTYPE,
            'label' => 'mount',
            'id' => 'form',
            'priority' =>  9999,
        ]; // TODO: implement with $this->createField instead
        // TODO: re-implement wooc defaults fields

        $fields['billing']['billing_address_resolved'] = [
            'type' => 'hidden'
        ];

        $fields['billing']['billing_country'] = [
            'type' => 'hidden'
        ];

        $fields['billing']['billing_address_1'] = [
            'type' => 'hidden'
        ];

        $fields['billing']['billing_city'] = [
            'type' => 'hidden'
        ];

        $fields['billing']['billing_postcode'] = [
            'type' => 'hidden'
        ];


        // $fields[] = woocommerce_form_field( 'fieldResolvedAddressBilling', ['type' => 'hidden', 'id' => 'resolvedAddress', 'return' => true, 'label' => 'Hidden field for resolved address']);
        return $fields;
    }


    /**
     * Save resolved address to Wooc
     * TODO: Needs to be done through PDK
     * @param mixed $order_id
     * @return void
     */
    public function saveResolvedAddress($order_id)
    {
        if (!empty($_POST['billing_address_resolved'])) {
            update_post_meta($order_id, 'myparcel_resolved_billing_address', \wc_sanitize_textarea($_POST['billing_address_resolved']));
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
        // TODO:  ID based on config
        // TODO: Sorting is done through regex or JS by woocommerce and will not work without the correct wrapper
        // Find a better way to get our own HTML without having to duplicate the wapper HTML here
        return '<div class="form-row form-row-first" id="form" data-priority="9999">REPLACE ME</div>';
    }
}
