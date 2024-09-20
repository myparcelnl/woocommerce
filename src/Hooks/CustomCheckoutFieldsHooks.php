<?php

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Hooks;

use MyParcelNL\WooCommerce\Hooks\Contract\WordPressHooksInterface;

class CustomCheckoutFieldsHooks implements WordPressHooksInterface
{
    public function apply(): void
    {
        add_action('woocommerce_init', [$this, 'registerAdditionalCheckoutFields']);
    }

    public function registerAdditionalCheckoutFields(): void
    {
        // Register street field
        woocommerce_register_additional_checkout_field(
            array(
                'id' => 'namespace/street',
                'label' => 'Street',
                'optionalLabel' => 'Street (optional)',
                'location' => 'address',
                'required' => true,
                'attributes' => array(
                    'autocomplete' => 'street-address',
                    'aria-describedby' => 'some-element',
                    'aria-label' => 'custom aria label',
                    'pattern' => '[A-Za-z0-9 ]+', // A string of letters, digits, and spaces.
                    'title' => 'Title to show on hover',
                    'data-custom' => 'custom data',
                ),
            )
        );
        // Register house number field
        woocommerce_register_additional_checkout_field(
            array(
                'id' => 'namespace/house-number',
                'label' => 'House Number',
                'optionalLabel' => 'House Number (optional)',
                'location' => 'address',
                'required' => true,
                'attributes' => array(
                    'autocomplete' => 'house-number',
                    'aria-describedby' => 'some-element',
                    'aria-label' => 'custom aria label',
                    'pattern' => '[0-9]+', // A string of digits.
                    'title' => 'Title to show on hover',
                    'data-custom' => 'custom data',
                ),
            )
        );


        // Register house number suffix field
        woocommerce_register_additional_checkout_field(
            array(
                'id' => 'namespace/house-number-suffix',
                'label' => 'House Number Suffix',
                'optionalLabel' => 'House Number Suffix (optional)',
                'location' => 'address',
                'required' => false,
                'attributes' => array(
                    'autocomplete' => 'house-number-suffix',
                    'aria-describedby' => 'some-element',
                    'aria-label' => 'custom aria label',
                    'pattern' => '[A-Za-z0-9]*', // A string of letters and digits.
                    'title' => 'Title to show on hover',
                    'data-custom' => 'custom data',
                ),
            )
        );
    }
}