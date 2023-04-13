<?php

use WPO\WC\MyParcel\Compatibility\Order as WCX_Order;
use WPO\WC\MyParcel\Compatibility\WC_Core as WCX;

if (! defined('ABSPATH')) {
    exit;
} // Exit if accessed directly

if (class_exists('WCMP_NL_Postcode_Fields')) {
    return new WCMP_NL_Postcode_Fields();
}

class WCMP_NL_Postcode_Fields
{
    /*
     * Regular expression used to split street name from house number.
     * This regex goes from right to left
     * Contains php keys to store the data in an array
     * Taken from https://github.com/myparcel/sdk
     */
    public const SPLIT_STREET_REGEX = '~(?P<street>.*?)\s?(?P<street_suffix>(?P<number>[\d]+)[\s-]{0,2}(?P<extension>[a-zA-Z/\s]{0,5}$|[0-9/]{0,5}$|\s[a-zA-Z]{1}[0-9]{0,3}$|\s[0-9]{2}[a-zA-Z]{0,3}$))$~';

    public const COUNTRIES_WITH_SPLIT_ADDRESS_FIELDS = ['NL', 'BE'];

    /**
     * @var array|string
     */
    private $postedValues;

    public function __construct()
    {
        $this->postedValues = wp_unslash(filter_input_array(INPUT_POST));
        if ($this->postedValues) {
            wp_verify_nonce('_wpnonce');
            $this->fixAddressInPost();
        }

        // Load styles
        add_action('wp_enqueue_scripts', [$this, 'add_styles_scripts']);

        // Load scripts
        add_action('admin_enqueue_scripts', [$this, 'admin_scripts_styles']);

        add_action("wp_loaded", [$this, "initialize"], 9999);
    }

    private function fixAddressInPost(): void
    {
        $post = $this->postedValues;
        foreach (['billing', 'shipping'] as $type) {
            if (isset($post["{$type}_address_1"], $post["{$type}_street_name"])
                && '' === $post["{$type}_address_1"]) {
                $_POST["{$type}_address_1"] = trim(
                    $post["{$type}_street_name"]
                    . ' '
                    . ($post["{$type}_house_number"] ?? '')
                    . ' '
                    . ($post["{$type}_house_number_suffix"] ?? '')
                );
            }
        }
    }

    /**
     * @return bool
     */
    private function shouldShowAddressFields(): ?bool
    {
        if (! WC()->cart){
            return true;
        }

        foreach (WC()->cart->get_cart() as $cartItem) {
            /** @var WC_Product $product */
            $product = $cartItem['data'];

            if (! $product->is_virtual()) {
                return true;
            }
        }

        return false;
    }

    public function initialize(): void
    {
        if (WCMYPA()->setting_collection->isEnabled('use_split_address_fields')) {

            if (! $this->shouldShowAddressFields()) {
                return;
            }

            // Add street name & house number checkout fields.
            if (version_compare(WOOCOMMERCE_VERSION, '2.0') >= 0) {
                // WC 2.0 or newer is used, the filter got a $country parameter, yay!
                add_filter(
                    'woocommerce_billing_fields',
                    [$this, 'modifyBillingFields'],
                    apply_filters('wcmp_checkout_fields_priority', 10, 'billing'),
                    2
                );
                add_filter(
                    'woocommerce_shipping_fields',
                    [$this, 'modifyShippingFields'],
                    apply_filters('wcmp_checkout_fields_priority', 10, 'shipping'),
                    2
                );
            } else {
                // Backwards compatibility
                add_filter('woocommerce_billing_fields', [$this, 'modifyBillingFields']);
                add_filter('woocommerce_shipping_fields', [$this, 'modifyShippingFields']);
            }

            // Localize checkout fields (limit custom checkout fields to NL and NL)
            add_filter('woocommerce_country_locale_field_selectors', [$this, 'country_locale_field_selectors']);
            add_filter('woocommerce_default_address_fields', [$this, 'default_address_fields']);
            add_filter('woocommerce_get_country_locale', [$this, 'woocommerce_locale_be'], 1, 1); // !

            // Load custom order data.
            add_filter('woocommerce_load_order_data', [$this, 'load_order_data']);

            add_filter('woocommerce_admin_billing_fields', [$this, 'addAdminSplitAddressFields']);
            add_filter('woocommerce_admin_shipping_fields', [$this, 'addAdminSplitAddressFields']);
            add_filter('woocommerce_found_customer_details', [$this, 'customer_details_ajax']);
            add_action('save_post', [$this, 'save_custom_fields']);

            // add to user profile page
            add_filter('woocommerce_customer_meta_fields', [$this, 'user_profile_fields']);

            add_action(
                'woocommerce_checkout_update_order_meta',
                [$this, 'merge_street_number_suffix'],
                20,
                2
            );
            add_filter(
                'woocommerce_process_checkout_field_billing_postcode',
                [$this, 'clean_billing_postcode']
            );
            add_filter(
                'woocommerce_process_checkout_field_shipping_postcode',
                [$this, 'clean_shipping_postcode']
            );

            // Save the order data in WooCommerce 2.2 or later.
            if (version_compare(WOOCOMMERCE_VERSION, '2.2') >= 0) {
                add_action('woocommerce_checkout_update_order_meta', [$this, 'save_order_data'], 10, 2);
            }

            // Remove placeholder values (IE8 & 9)
            add_action('woocommerce_checkout_update_order_meta', [$this, 'remove_placeholders'], 10, 2);

            // Fix weird required field translations
            add_filter(
                'woocommerce_checkout_required_field_notice',
                [$this, 'required_field_notices'],
                10,
                2
            );

            $this->load_woocommerce_filters();
        } else { // if NOT using old fields
            add_action('woocommerce_after_checkout_validation', [$this, 'validate_address_fields'], 10, 2);
        }

        // Processing checkout
        add_filter('woocommerce_validate_postcode', [$this, 'validate_postcode'], 10, 3);

        // set later priority for woocommerce_billing_fields / woocommerce_shipping_fields
        // when Checkout Field Editor is active
        if (function_exists('thwcfd_is_locale_field')
            || function_exists(
                'wc_checkout_fields_modify_billing_fields'
            )) {
            add_filter('be_checkout_fields_priority', 1001);
        }

        // Hide state field for countries without states (backwards compatible fix for bug #4223)
        if (version_compare(WOOCOMMERCE_VERSION, '2.1', '<')) {
            add_filter('woocommerce_countries_allowed_country_states', [$this, 'hide_states']);
        }
    }

    public function load_woocommerce_filters()
    {
        // Custom address format.
        if (version_compare(WOOCOMMERCE_VERSION, '2.0.6', '>=')) {
            add_filter('woocommerce_localisation_address_formats', [$this, 'localisation_address_formats']);
            add_filter(
                'woocommerce_formatted_address_replacements',
                [$this, 'formatted_address_replacements'],
                1,
                2
            );
            add_filter(
                'woocommerce_order_formatted_billing_address',
                [$this, 'order_formatted_billing_address'],
                1,
                2
            );
            add_filter(
                'woocommerce_order_formatted_shipping_address',
                [$this, 'order_formatted_shipping_address'],
                1,
                2
            );
            add_filter(
                'woocommerce_user_column_billing_address',
                [$this, 'user_column_billing_address'],
                1,
                2
            );
            add_filter(
                'woocommerce_user_column_shipping_address',
                [$this, 'user_column_shipping_address'],
                1,
                2
            );
            add_filter(
                'woocommerce_my_account_my_address_formatted_address',
                [$this, 'my_account_my_address_formatted_address'],
                1,
                3
            );
        }
    }

    /**
     * Load styles & scripts.
     */
    public function add_styles_scripts()
    {
        if (! is_checkout() && ! is_account_page()) {
            return;
        }

        // Enqueue styles for delivery options
        wp_enqueue_style(
            'checkout',
            WCMYPA()->plugin_url() . '/assets/css/checkout.css',
            false,
            WC_MYPARCEL_NL_VERSION
        );

        if (! WCMYPA()->setting_collection->isEnabled('use_split_address_fields')) {
            return;
        }

        if (version_compare(WOOCOMMERCE_VERSION, '2.1', '<=')) {
            // Backwards compatibility for https://github.com/woothemes/woocommerce/issues/4239
            wp_register_script(
                'checkout',
                WCMYPA()->plugin_url() . '/assets/js/checkout.js',
                ['jquery', 'wc-checkout'],
                WC_MYPARCEL_NL_VERSION
            );
            wp_enqueue_script('checkout');
        }
    }

    /**
     * Load admin styles & scripts.
     */
    public function admin_scripts_styles($hook)
    {
        global $post_type;
        if ($post_type == 'shop_order') {
            wp_enqueue_style(
                'checkout-admin',
                WCMYPA()->plugin_url() . '/assets/css/checkout-admin.css',
                [], // deps
                WC_MYPARCEL_NL_VERSION
            );
        }
    }

    /**
     * Hide default Dutch address fields
     *
     * @param array $locale woocommerce country locale field settings
     *
     * @return array $locale
     */
    public function woocommerce_locale_be(array $locale): array
    {
        foreach (self::COUNTRIES_WITH_SPLIT_ADDRESS_FIELDS as $cc) {
            $locale[$cc]['address_1'] = [
                'required' => false,
                'hidden'   => true,
            ];

            $locale[$cc]['address_2'] = [
                'hidden' => true,
            ];

            $locale[$cc]['state'] = [
                'hidden'   => true,
                'required' => false,
            ];

            $locale[$cc]['street_name'] = [
                'required' => true,
                'hidden'   => false,
            ];

            $locale[$cc]['house_number'] = [
                'required' => true,
                'hidden'   => false,
            ];

            $locale[$cc]['house_number_suffix'] = [
                'required' => false,
                'hidden'   => false,
            ];
        }

        return $locale;
    }

    /**
     * @param array  $fields
     *
     * @return array
     */
    public function modifyBillingFields(array $fields): array
    {
        return $this->addSplitAddressFields($fields, 'billing');
    }

    /**
     * @param array $fields
     *
     * @return array
     */
    public function modifyShippingFields(array $fields): array
    {
        return $this->addSplitAddressFields($fields, 'shipping');
    }

    /**
     * New checkout and account page billing/shipping fields
     *
     * @param array  $fields Default fields.
     * @param string $form
     *
     * @return array
     */
    public function addSplitAddressFields(array $fields, string $form): array
    {
        return array_merge_recursive(
            $fields,
            [
                "{$form}_street_name"         => [
                    'label'    => __("street_name", "woocommerce-myparcel"),
                    'class'    => apply_filters('wcmp_custom_address_field_class', ['form-row-third first']),
                    'priority' => 60,
                ],
                "{$form}_house_number"        => [
                    'label'    => __("abbreviation_house_number", "woocommerce-myparcel"),
                    'class'    => apply_filters('wcmp_custom_address_field_class', ['form-row-third']),
                    'type'     => 'number',
                    'priority' => 61,
                ],
                "{$form}_house_number_suffix" => [
                    'label'     => __("suffix", "woocommerce-myparcel"),
                    'class'     => apply_filters('wcmp_custom_address_field_class', ['form-row-third last']),
                    'maxlength' => 6,
                    'priority'  => 62,
                ],
            ]
        );
    }

    /**
     * Hide state field for countries without states (backwards compatible fix for WooCommerce bug #4223)
     *
     * @param array $allowed_states states per country
     *
     * @return array
     */
    public function hide_states($allowed_states)
    {
        $hidden_states = [
            'AF' => [],
            'AT' => [],
            'BE' => [],
            'BI' => [],
            'CZ' => [],
            'DE' => [],
            'DK' => [],
            'FI' => [],
            'FR' => [],
            'HU' => [],
            'IS' => [],
            'IL' => [],
            'KR' => [],
            'NL' => [],
            'NO' => [],
            'PL' => [],
            'PT' => [],
            'SG' => [],
            'SK' => [],
            'SI' => [],
            'LK' => [],
            'SE' => [],
            'VN' => [],
        ];
        return $hidden_states + $allowed_states;
    }

    /**
     * Localize checkout fields live
     *
     * @param array $locale_fields list of fields filtered by locale
     *
     * @return array $locale_fields with custom fields added
     */
    public function country_locale_field_selectors($locale_fields)
    {
        $custom_locale_fields = [
            'street_name'         => '#billing_street_name_field, #shipping_street_name_field',
            'house_number'        => '#billing_house_number_field, #shipping_house_number_field',
            'house_number_suffix' => '#billing_house_number_suffix_field, #shipping_house_number_suffix_field',
        ];

        $locale_fields = array_merge($locale_fields, $custom_locale_fields);

        return $locale_fields;
    }

    /**
     * Make NL checkout fields hidden by default
     *
     * @param array $fields default checkout fields
     *
     * @return array $fields default + custom checkout fields
     */
    public function default_address_fields($fields)
    {
        $custom_fields = [
            'street_name'         => [
                'hidden'   => true,
                'required' => false,
            ],
            'house_number'        => [
                'hidden'   => true,
                'required' => false,
            ],
            'house_number_suffix' => [
                'hidden'   => true,
                'required' => false,
            ],
        ];

        $fields = array_merge($fields, $custom_fields);

        return $fields;
    }

    /**
     * Load order custom data.
     *
     * @param array $data Default WC_Order data.
     *
     * @return array       Custom WC_Order data.
     */
    public function load_order_data($data)
    {
        // Billing
        $data['billing_street_name']         = '';
        $data['billing_house_number']        = '';
        $data['billing_house_number_suffix'] = '';

        // Shipping
        $data['shipping_street_name']         = '';
        $data['shipping_house_number']        = '';
        $data['shipping_house_number_suffix'] = '';

        return $data;
    }

    /**
     * @param array $fields
     *
     * @return array
     */
    public function addAdminSplitAddressFields(array $fields): array
    {
        return array_merge_recursive(
            $fields,
            [
                'street_name'         => [
                    'label' => __('street_name', 'woocommerce-myparcel'),
                    'show'  => true,
                ],
                'house_number'        => [
                    'label' => __('house_number', 'woocommerce-myparcel'),
                    'show'  => true,
                ],
                'house_number_suffix' => [
                    'label' => __('suffix', 'woocommerce-myparcel'),
                    'show'  => true,
                ]
            ]);
    }

    /**
     * Custom user profile edit fields.
     */
    public function user_profile_fields($meta_fields)
    {
        $myparcel_billing_fields  = [
            'billing_street_name'         => [
                'label'       => __("Street", "woocommerce-myparcel"),
                'description' => '',
            ],
            'billing_house_number'        => [
                'label'       => __("Number", "woocommerce-myparcel"),
                'description' => '',
            ],
            'billing_house_number_suffix' => [
                'label'       => __("Suffix", "woocommerce-myparcel"),
                'description' => '',
            ],
        ];
        $myparcel_shipping_fields = [
            'shipping_street_name'         => [
                'label'       => __("Street", "woocommerce-myparcel"),
                'description' => '',
            ],
            'shipping_house_number'        => [
                'label'       => __("Number", "woocommerce-myparcel"),
                'description' => '',
            ],
            'shipping_house_number_suffix' => [
                'label'       => __("Suffix", "woocommerce-myparcel"),
                'description' => '',
            ],
        ];

        // add myparcel fields to billing section
        $billing_fields                   = array_merge(
            $meta_fields['billing']['fields'],
            $myparcel_billing_fields
        );
        $billing_fields                   = $this->array_move_keys(
            $billing_fields,
            ['billing_street_name', 'billing_house_number', 'billing_house_number_suffix'],
            'billing_address_2',
            'after'
        );
        $meta_fields['billing']['fields'] = $billing_fields;

        // add myparcel fields to shipping section
        $shipping_fields                   = array_merge(
            $meta_fields['shipping']['fields'],
            $myparcel_shipping_fields
        );
        $shipping_fields                   = $this->array_move_keys(
            $shipping_fields,
            ['shipping_street_name', 'shipping_house_number', 'shipping_house_number_suffix'],
            'shipping_address_2',
            'after'
        );
        $meta_fields['shipping']['fields'] = $shipping_fields;

        return $meta_fields;
    }

    /**
     * Add custom fields in customer details ajax.
     * called when clicking the "Load billing/shipping address" button on Edit Order view
     *
     * @return array
     */
    public function customer_details_ajax($customer_data)
    {
        $user_id      = (int) trim($this->postedValues['user_id']);
        $type_to_load = esc_attr(trim($this->postedValues['type_to_load']));

        $custom_data = [
            $type_to_load . '_street_name'         => get_user_meta($user_id, $type_to_load . '_street_name', true),
            $type_to_load . '_house_number'        => get_user_meta(
                $user_id,
                $type_to_load . '_house_number',
                true
            ),
            $type_to_load . '_house_number_suffix' => get_user_meta(
                $user_id,
                $type_to_load . '_house_number_suffix',
                true
            ),
        ];

        return array_merge($customer_data, $custom_data);
    }

    /**
     * Save custom fields from admin.
     */
    public function save_custom_fields($post_id)
    {
        if (! $this->postedValues) {
            return;
        }

        $post_type = get_post_type($post_id);
        if ('shop_order' === $post_type || 'shop_order_refund' === $post_type) {
            $order          = WCX::get_order($post_id);
            $addresses      = ['billing', 'shipping'];
            $address_fields = ['street_name', 'house_number', 'house_number_suffix'];
            foreach ($addresses as $address) {
                foreach ($address_fields as $address_field) {
                    if (isset($this->postedValues["_{$address}_{$address_field}"])) {
                        WCX_Order::update_meta_data(
                            $order,
                            "_{$address}_{$address_field}",
                            sanitize_text_field($this->postedValues["_{$address}_{$address_field}"])
                        );
                    }
                }
            }
        }

        return;
    }

    /**
     * Merge street name, street number and street suffix into the default 'address_1' field
     *
     * @param mixed $order_id Order ID of checkout order.
     *
     * @return void
     */
    public function merge_street_number_suffix($order_id): void
    {
        $order                          = WCX::get_order($order_id);
        $billingHasCustomAddressFields  = self::isCountryWithSplitAddressFields($this->postedValues['billing_country'] ?? null);
        $shippingHasCustomAddressFields = self::isCountryWithSplitAddressFields($this->postedValues['shipping_country'] ?? null);

        $shipToDifferentAddress = isset($this->postedValues['ship_to_different_address']);

        if ($billingHasCustomAddressFields) {
            $billingAddress1 = $this->getAddress1FromPost('billing');
            WCX_Order::set_address_prop($order, 'address_1', 'billing', $billingAddress1);

            if (! $shipToDifferentAddress && $this->cart_needs_shipping_address()) {
                // use billing address
                WCX_Order::set_address_prop($order, 'address_1', 'shipping', $billingAddress1);
            }
        }

        if ($shippingHasCustomAddressFields && $shipToDifferentAddress) {
            $shippingAddress1 = $this->getAddress1FromPost('shipping');
            WCX_Order::set_address_prop($order, 'address_1', 'shipping', $shippingAddress1);
        }
    }

    /**
     * @param  string $type
     *
     * @return string
     */
    private function getAddress1FromPost(string $type = 'billing'): string
    {
        $suffix    = '';
        $suffixKey = "{$type}_house_number_suffix";

        if (isset($this->postedValues[$suffixKey]) && $this->postedValues[$suffixKey]) {
            $suffix = sprintf('-%s', sanitize_text_field($this->postedValues[$suffixKey]));
        }

        $houseNumber = sanitize_text_field($this->postedValues["{$type}_house_number"]);

        return sprintf(
            '%s %s%s',
            sanitize_text_field($this->postedValues["{$type}_street_name"]),
            $houseNumber,
            $suffix
        );
    }

    /**
     * validate NL postcodes
     *
     * @return bool $valid
     */
    public function validate_postcode($valid, $postcode, $country)
    {
        if ($country == 'NL') {
            $valid = (bool) preg_match('/^[1-9][0-9]{3}/i', trim($postcode));
        }

        return $valid;
    }

    /**
     * validate address field 1 for shipping and billing
     */
    public function validate_address_fields($address, $errors)
    {
        if (self::isCountryWithSplitAddressFields($address['billing_country'])
            && ! (bool) preg_match(
                self::SPLIT_STREET_REGEX,
                trim(
                    $address['billing_address_1']
                )
            )) {
            $errors->add('address', __("Please enter a valid billing address.", "woocommerce-myparcel"));
        }

        if (self::isCountryWithSplitAddressFields($address['shipping_country'])
            && ! empty($address['ship_to_different_address'])
            && ! (bool) preg_match(
                self::SPLIT_STREET_REGEX,
                trim(
                    $address['shipping_address_1']
                )
            )) {
            $errors->add('address', __("Please enter a valid shipping address.", "woocommerce-myparcel"));
        }
    }

    /**
     * Clean postcodes : remove space, dashes (& other non alphanumeric characters)
     *
     * @return $billing_postcode
     * @return $shipping_postcode
     */
    public function clean_billing_postcode()
    {
        if ($this->postedValues['billing_country'] == 'NL') {
            $billing_postcode = preg_replace('/[^a-zA-Z0-9]/', '', $this->postedValues['billing_postcode']);
        } else {
            $billing_postcode = $this->postedValues['billing_postcode'];
        }

        return $billing_postcode;
    }

    public function clean_shipping_postcode()
    {
        if ($this->postedValues['billing_country'] == 'NL') {
            $shipping_postcode = preg_replace('/[^a-zA-Z0-9]/', '', $this->postedValues['shipping_postcode']);
        } else {
            $shipping_postcode = $this->postedValues['shipping_postcode'];
        }

        return $shipping_postcode;
    }

    /**
     * Remove placeholders from posted checkout data
     *
     * @param string $order_id order_id of the new order
     * @param array  $posted   Array of posted form data
     *
     * @return void
     */
    public function remove_placeholders($order_id, $posted)
    {
        $order = WCX::get_order($order_id);
        // get default address fields with their placeholders
        $countries = new WC_Countries();
        $fields    = $countries->get_default_address_fields();

        // define order_comments placeholder
        $order_comments_placeholder = _x(
            'Notes about your order, e.g. special notes for delivery.',
            'placeholder',
            'woocommerce'
        );

        // check if ship to billing is set
        if (version_compare(WOOCOMMERCE_VERSION, '2.1', '<=')) {
            // old versions use 'shiptobilling'
            $ship_to_different_address = isset($this->postedValues['shiptobilling']) ? false : true;
        } else {
            // WC2.1
            $ship_to_different_address = isset($this->postedValues['ship_to_different_address']) ? true : false;
        }

        // check the billing & shipping fields
        $field_types  = ['billing', 'shipping'];
        $check_fields = ['address_1', 'address_2', 'city', 'state', 'postcode'];
        foreach ($field_types as $field_type) {
            foreach ($check_fields as $check_field) {
                if (isset($posted[$field_type . '_' . $check_field])
                    && isset($fields[$check_field]['placeholder'])
                    && $posted[$field_type . '_' . $check_field] == $fields[$check_field]['placeholder']) {
                    WCX_Order::set_address_prop($order, $check_field, $field_type, '');

                    // also clear shipping field when ship_to_different_address is false
                    if ($ship_to_different_address == false && $field_type == 'billing') {
                        WCX_Order::set_address_prop($order, $check_field, 'shipping', '');
                    }
                }
            }
        }

        // check the order comments field
        if ($posted['order_comments'] == $order_comments_placeholder) {
            wp_update_post(
                [
                    'ID'           => $order_id,
                    'post_excerpt' => '',
                ]
            );
        }

        return;
    }

    /**
     * WooCommerce concatenates translations for required field notices that result in
     * confusing messages, so we translate the full notice to prevent this
     */
    public function required_field_notices($notice, $field_label)
    {
        // concatenate translations
        $billing_nr  = sprintf(__('Billing %s', 'woocommerce'), __('No.'));
        $shipping_nr = sprintf(__('Shipping %s', 'woocommerce'), __('No.'));

        switch ($field_label) {
            case $billing_nr:
                $notice = __('<strong>Billing No.</strong> is a required field', 'woocommerce-myparcel');
                break;
            case $shipping_nr:
                $notice = __('<strong>Shipping No.</strong> is a required field', 'woocommerce-myparcel');
                break;
            default:
                break;
        }

        return $notice;
    }

    /**
     * Custom country address formats.
     *
     * @param array $formats Defaul formats.
     *
     * @return array          New NL format.
     */
    public function localisation_address_formats($formats)
    {
        $formats['NL'] = "{company}\n{name}\n{address_1}\n{address_2}\n{postcode} {city}\n{country}";

        return $formats;
    }

    /**
     * Custom country address format.
     *
     * @param array $replacements Default replacements.
     * @param array $args         Arguments to replace.
     *
     * @return array               New replacements.
     */
    public function formatted_address_replacements(array $replacements, array $args): array
    {
        $country             = $args['country'] ?? null;
        $house_number        = $args['house_number'] ?? null;
        $house_number_suffix = $args['house_number_suffix'] ?? null;
        $street_name         = $args['street_name'] ?? null;

        if (! empty($street_name) && self::isCountryWithSplitAddressFields($country)) {
            $replacements['{address_1}'] = $street_name . ' ' . $house_number . $house_number_suffix;
        }

        return $replacements;
    }

    /**
     * Custom order formatted billing address.
     *
     * @param array  $address Default address.
     * @param object $order   Order data.
     *
     * @return array          New address format.
     */
    public function order_formatted_billing_address($address, $order)
    {
        $address['street_name']         = WCX_Order::get_meta($order, '_billing_street_name', true, 'view');
        $address['house_number']        = WCX_Order::get_meta($order, '_billing_house_number', true, 'view');
        $address['house_number_suffix'] = WCX_Order::get_meta($order, '_billing_house_number_suffix', true, 'view');
        $address['house_number_suffix'] =
            ! empty($address['house_number_suffix']) ? '-' . $address['house_number_suffix'] : '';

        return $address;
    }

    /**
     * Custom order formatted shipping address.
     *
     * @param array  $address Default address.
     * @param object $order   Order data.
     *
     * @return array          New address format.
     */
    public function order_formatted_shipping_address($address, $order)
    {
        $address['street_name']         = WCX_Order::get_meta($order, '_shipping_street_name', true, 'view');
        $address['house_number']        = WCX_Order::get_meta($order, '_shipping_house_number', true, 'view');
        $address['house_number_suffix'] = WCX_Order::get_meta(
            $order,
            '_shipping_house_number_suffix',
            true,
            'view'
        );
        $address['house_number_suffix'] =
            ! empty($address['house_number_suffix']) ? '-' . $address['house_number_suffix'] : '';

        return $address;
    }

    /**
     * Custom user column billing address information.
     *
     * @param array $address Default address.
     * @param int   $user_id User id.
     *
     * @return array          New address format.
     */
    public function user_column_billing_address($address, $user_id)
    {
        $address['street_name']         = get_user_meta($user_id, 'billing_street_name', true);
        $address['house_number']        = get_user_meta($user_id, 'billing_house_number', true);
        $address['house_number_suffix'] =
            (get_user_meta($user_id, 'billing_house_number_suffix', true)) ? '-' . get_user_meta(
                    $user_id,
                    'billing_house_number_suffix',
                    true
                ) : '';

        return $address;
    }

    /**
     * Custom user column shipping address information.
     *
     * @param array $address Default address.
     * @param int   $user_id User id.
     *
     * @return array          New address format.
     */
    public function user_column_shipping_address($address, $user_id)
    {
        $address['street_name']         = get_user_meta($user_id, 'shipping_street_name', true);
        $address['house_number']        = get_user_meta($user_id, 'shipping_house_number', true);
        $address['house_number_suffix'] =
            (get_user_meta($user_id, 'shipping_house_number_suffix', true)) ? '-' . get_user_meta(
                    $user_id,
                    'shipping_house_number_suffix',
                    true
                ) : '';

        return $address;
    }

    /**
     * Custom my address formatted address.
     *
     * @param array  $address     Default address.
     * @param int    $customer_id Customer ID.
     * @param string $name        Field name (billing or shipping).
     *
     * @return array            New address format.
     */
    public function my_account_my_address_formatted_address($address, $customer_id, $name)
    {
        $address['street_name']         = get_user_meta($customer_id, $name . '_street_name', true);
        $address['house_number']        = get_user_meta($customer_id, $name . '_house_number', true);
        $address['house_number_suffix'] =
            (get_user_meta($customer_id, $name . '_house_number_suffix', true)) ? '-' . get_user_meta(
                    $customer_id,
                    $name . '_house_number_suffix',
                    true
                ) : '';

        return $address;
    }

    /**
     * Get a posted address field after sanitization and validation.
     *
     * @param string $key
     * @param string $type billing for shipping
     *
     * @return string
     */
    public function get_posted_address_data($key, $posted, $type = 'billing')
    {
        if ('billing' === $type
            || (! $posted['ship_to_different_address']
                && $this->cart_needs_shipping_address())) {
            $return = isset($posted['billing_' . $key]) ? $posted['billing_' . $key] : '';
        }     elseif ('shipping' === $type && ! $this->cart_needs_shipping_address()) {
            $return = '';
        } else {
            $return = isset($posted['shipping_' . $key]) ? $posted['shipping_' . $key] : '';
        }

        return $return;
    }

    public function cart_needs_shipping_address()
    {
        if (is_object(WC()->cart) && method_exists(WC()->cart, 'needs_shipping_address')
            && function_exists('wc_ship_to_billing_address_only')) {
            if (WC()->cart->needs_shipping_address() || wc_ship_to_billing_address_only()) {
                return true;
            } else {
                return false;
            }
        } else {
            return true;
        }
    }

    /**
     * Save order data.
     *
     * @param int   $order_id
     * @param array $posted
     *
     * @return void
     */
    public function save_order_data($order_id, $posted)
    {
        $order = WCX::get_order($order_id);
        // Billing.
        WCX_Order::update_meta_data(
            $order,
            '_billing_street_name',
            $this->get_posted_address_data('street_name', $posted)
        );
        WCX_Order::update_meta_data(
            $order,
            '_billing_house_number',
            $this->get_posted_address_data('house_number', $posted)
        );
        WCX_Order::update_meta_data(
            $order,
            '_billing_house_number_suffix',
            $this->get_posted_address_data('house_number_suffix', $posted)
        );

        // Shipping.
        WCX_Order::update_meta_data(
            $order,
            '_shipping_street_name',
            $this->get_posted_address_data('street_name', $posted, 'shipping')
        );
        WCX_Order::update_meta_data(
            $order,
            '_shipping_house_number',
            $this->get_posted_address_data('house_number', $posted, 'shipping')
        );
        WCX_Order::update_meta_data(
            $order,
            '_shipping_house_number_suffix',
            $this->get_posted_address_data('house_number_suffix', $posted, 'shipping')
        );
    }

    /**
     * Helper function to move array elements (one or more) to a position before a specific key
     *
     * @param array  $array         Main array to modify
     * @param mixed  $keys          Single key or array of keys of element(s) to move
     * @param string $reference_key key to put elements before or after
     * @param string $position      before or after
     *
     * @return array                 reordered array
     */
    public function array_move_keys($array, $keys, $reference_key, $position = 'before')
    {
        // cast $key as array
        $keys = (array) $keys;

        if (! isset($array[$reference_key])) {
            return $array;
        }

        $move = [];
        foreach ($keys as $key) {
            if (! isset($array[$key])) {
                continue;
            }
            $move[$key] = $array[$key];
            unset ($array[$key]);
        }

        if ($position == 'before') {
            $move_to_pos = array_search($reference_key, array_keys($array));
        } else { // after
            $move_to_pos = array_search($reference_key, array_keys($array)) + 1;
        }

        return array_slice($array, 0, $move_to_pos, true) + $move + array_slice(
                $array,
                $move_to_pos,
                null,
                true
            );
    }

    /**
     * @param string|null $country
     *
     * @return bool
     */
    private static function isCountryWithSplitAddressFields(?string $country): bool
    {
        return in_array($country, self::COUNTRIES_WITH_SPLIT_ADDRESS_FIELDS);
    }
}

return new WCMP_NL_Postcode_Fields();
