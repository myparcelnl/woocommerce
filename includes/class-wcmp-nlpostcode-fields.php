<?php
use WPO\WC\MyParcel\Compatibility\WC_Core as WCX;
use WPO\WC\MyParcel\Compatibility\Order as WCX_Order;
use WPO\WC\MyParcel\Compatibility\Product as WCX_Product;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( !class_exists( 'WC_NLPostcode_Fields' ) ) :

class WC_NLPostcode_Fields {

	public $version = '1.5.4';

	private $use_split_address_fields;

    /**
     * Regular expression used to split street name from house number.
     * This regex goes from right to left
     * Contains php keys to store the data in an array
     *
     * Taken from https://github.com/myparcelnl/sdk
     */
    const SPLIT_STREET_REGEX =
        '~(?P<street>.*?)' .            // The rest belongs to the street
        '\s?' .                         // Separator between street and number
        '(?P<number>\d{1,4})' .         // Number can contain a maximum of 4 numbers
        '[/\s\-]{0,2}' .                // Separators between number and addition
        '(?P<number_suffix>' .
        '[a-zA-Z]{1}\d{1,3}|' .         // Numbers suffix starts with a letter followed by numbers or
        '-\d{1,4}|' .                   // starts with - and has up to 4 numbers or
        '\d{2}\w{1,2}|' .               // starts with 2 numbers followed by letters or
        '[a-zA-Z]{1}[a-zA-Z\s]{0,3}' .  // has up to 4 letters with a space
        ')?$~';

    /**
     * WC_NLPostcode_Fields constructor.
     */
    public function __construct() {
        $this->use_split_address_fields = array_key_exists('use_split_address_fields', get_option('woocommerce_myparcel_checkout_settings'))
            ? get_option('woocommerce_myparcel_checkout_settings')['use_split_address_fields'] === '1'
            : false;

	    // Load styles
	    add_action( 'wp_enqueue_scripts', array( &$this, 'add_styles_scripts' ) );
        // Load scripts
        add_action( 'admin_enqueue_scripts', array( &$this, 'admin_scripts_styles' ) );

		if ( $this->use_split_address_fields ) {

            // Add street name & house number checkout fields.
            if (version_compare(WOOCOMMERCE_VERSION, '2.0') >= 0) {
                // WC 2.0 or newer is used, the filter got a $country parameter, yay!
                add_filter('woocommerce_billing_fields', [
                    &$this,
                    'nl_billing_fields'
                ], apply_filters('nl_checkout_fields_priority', 10, 'billing'), 2);
                add_filter('woocommerce_shipping_fields', [
                    &$this,
                    'nl_shipping_fields'
                ], apply_filters('nl_checkout_fields_priority', 10, 'shipping'), 2);
            } else {
                // Backwards compatibility
                add_filter('woocommerce_billing_fields', [&$this, 'nl_billing_fields']);
                add_filter('woocommerce_shipping_fields', [&$this, 'nl_shipping_fields']);
            }

            // Localize checkout fields (limit custom checkout fields to NL and BE)
            add_filter('woocommerce_country_locale_field_selectors', [&$this, 'country_locale_field_selectors']);
            add_filter('woocommerce_default_address_fields', [&$this, 'default_address_fields']);
            add_filter('woocommerce_get_country_locale', [&$this, 'woocommerce_locale_nl'], 1, 1); // !

            // Load custom order data.
            add_filter('woocommerce_load_order_data', [&$this, 'load_order_data']);

            // Custom shop_order details.
            add_filter('woocommerce_admin_billing_fields', [&$this, 'admin_billing_fields']);
            add_filter('woocommerce_admin_shipping_fields', [&$this, 'admin_shipping_fields']);
            add_filter('woocommerce_found_customer_details', [$this, 'customer_details_ajax']);
            add_action('save_post', [&$this, 'save_custom_fields']);

            // add to user profile page
            add_filter('woocommerce_customer_meta_fields', [&$this, 'user_profile_fields']);

            add_action('woocommerce_checkout_update_order_meta', array( &$this, 'merge_street_number_suffix' ), 20, 2 );
            add_filter('woocommerce_process_checkout_field_billing_postcode', array( &$this, 'clean_billing_postcode' ) );
            add_filter('woocommerce_process_checkout_field_shipping_postcode', array( &$this, 'clean_shipping_postcode' ) );

            // Save the order data in WooCommerce 2.2 or later.
            if ( version_compare( WOOCOMMERCE_VERSION, '2.2' ) >= 0 ) {
                add_action( 'woocommerce_checkout_update_order_meta', array( &$this, 'save_order_data' ), 10, 2 );
            }

            // Remove placeholder values (IE8 & 9)
            add_action('woocommerce_checkout_update_order_meta', array( &$this, 'remove_placeholders' ), 10, 2 );

            // Fix weird required field translations
            add_filter( 'woocommerce_checkout_required_field_notice', array( &$this, 'required_field_notices' ), 10, 2 );

		    $this->load_woocommerce_filters();
        } else { // if NOT using old fields
            add_action('woocommerce_after_checkout_validation', array(&$this, 'validate_address_fields'), 10, 2);
        }

        // Processing checkout
        add_filter('woocommerce_validate_postcode', array( &$this, 'validate_postcode' ), 10, 3 );

        // set later priority for woocommerce_billing_fields / woocommerce_shipping_fields
        // when Checkout Field Editor is active
        if ( function_exists('thwcfd_is_locale_field') || function_exists('wc_checkout_fields_modify_billing_fields') ) {
            add_filter( 'nl_checkout_fields_priority', 1001);
        }

        // Hide state field for countries without states (backwards compatible fix for bug #4223)
        if ( version_compare( WOOCOMMERCE_VERSION, '2.1', '<' ) ) {
            add_filter( 'woocommerce_countries_allowed_country_states', array( &$this, 'hide_states' ) );
        }
    }

    public function load_woocommerce_filters() {
		// Custom address format.
		if ( version_compare( WOOCOMMERCE_VERSION, '2.0.6', '>=' ) ) {
			add_filter( 'woocommerce_localisation_address_formats', array( $this, 'localisation_address_formats' ) );
			add_filter( 'woocommerce_formatted_address_replacements', array( $this, 'formatted_address_replacements' ), 1, 2 );
			add_filter( 'woocommerce_order_formatted_billing_address', array( $this, 'order_formatted_billing_address' ), 1, 2 );
			add_filter( 'woocommerce_order_formatted_shipping_address', array( $this, 'order_formatted_shipping_address' ), 1, 2 );
			add_filter( 'woocommerce_user_column_billing_address', array( $this, 'user_column_billing_address' ), 1, 2 );
			add_filter( 'woocommerce_user_column_shipping_address', array( $this, 'user_column_shipping_address' ), 1, 2 );
			add_filter( 'woocommerce_my_account_my_address_formatted_address', array( $this, 'my_account_my_address_formatted_address' ), 1, 3 );
		}
		
	}

	/**
	 * Load styles & scripts.
	 */
	public function add_styles_scripts(){
		if ( is_checkout() || is_account_page() ) {
		    if ( $this->use_split_address_fields ) {
                if (version_compare(WOOCOMMERCE_VERSION, '2.1', '<=')) {
                    // Backwards compatibility for https://github.com/woothemes/woocommerce/issues/4239
                    wp_register_script(
                        'nl-checkout',
                        WooCommerce_MyParcel()->plugin_url() . '/assets/js/nl-checkout.js',
                        array('wc-checkout'),
                        $this->version
                    );
                    wp_enqueue_script('nl-checkout');
                }

                if (is_account_page()) {
                    // Disable regular address fields for NL on account page - Fixed in WC 2.1 but not on init...
                    wp_register_script(
                        'nl-account-page',
                        WooCommerce_MyParcel()->plugin_url() . '/assets/js/nl-account-page.js',
                        array('jquery'),
                        $this->version
                    );
                    wp_enqueue_script('nl-account-page');
                }
            }
			wp_enqueue_style( 'nl-checkout', WooCommerce_MyParcel()->plugin_url() . '/assets/css/nl-checkout.css?MP=' . WC_MYPARCEL_VERSION);
		}

	}

	/**
	 * Load admin styles & scripts.
	 */
	public function admin_scripts_styles ( $hook ) {
		global $post_type;
		if ( $post_type == 'shop_order' ) {
			wp_enqueue_style(
				'nl-checkout-admin',
				WooCommerce_MyParcel()->plugin_url() . '/assets/css/nl-checkout-admin.css',
				array(), // deps
				$this->version
			);
		}
	}

	/**
	 * Hide default Dutch address fields
	 * @param  array $locale woocommerce country locale field settings
	 * @return array $locale
	 */
	public function woocommerce_locale_nl( $locale ) {
		$locale['NL']['address_1'] = array(
			'required'  => false,
			'hidden'	=> true,
		);

		$locale['NL']['address_2'] = array(
			'hidden'	=> true,
		);
		
		$locale['NL']['state'] = array(
			'hidden'	=> true,
			'required'	=> false,
		);

		$locale['NL']['street_name'] = array(
			'required'  => true,
			'hidden'	=> false,
		);

		$locale['NL']['house_number'] = array(
			'required'  => true,
			'hidden'	=> false,
		);

		$locale['NL']['house_number_suffix'] = array(
			'required'  => false,
			'hidden'	=> false,
		);



        $locale['BE']['address_1'] = array(
            'required'  => false,
            'hidden'	=> true,
        );

        $locale['BE']['address_2'] = array(
            'hidden'	=> true,
        );

        $locale['BE']['state'] = array(
            'hidden'	=> true,
            'required'	=> false,
        );

        $locale['BE']['street_name'] = array(
            'required'  => true,
            'hidden'	=> false,
        );

        $locale['BE']['house_number'] = array(
            'required'  => true,
            'hidden'	=> false,
        );

        $locale['BE']['house_number_suffix'] = array(
            'required'  => false,
            'hidden'	=> false,
        );

		return $locale;
	}

	public function nl_billing_fields( $fields, $country = '' ) {
		return $this->nl_checkout_fields( $fields, $country, 'billing');
	}

	public function nl_shipping_fields( $fields, $country = '' ) {
		return $this->nl_checkout_fields( $fields, $country, 'shipping');
	}

	/**
	 * New checkout billing/shipping fields
	 * @param  array $fields Default fields.
	 * @return array $fields New fields.
	 */
	public function nl_checkout_fields( $fields, $country, $form ) {
		if (isset($fields['_country'])) {
			// some weird bug on the my account page
			$form = '';
		}

		// Set required to true if country is NL
		$required = ($country == 'NL' || $country == 'BE')?true:false;

		// Add Street name
		$fields[$form.'_street_name'] = array(
			'label'			=> __( 'Street name', 'woocommerce-myparcel' ),
			'placeholder'	=> __( 'Street name', 'woocommerce-myparcel' ),
			'class'			=> apply_filters( 'nl_custom_address_field_class', array( 'form-row-first' ), $form, 'street_name' ),
			'required'		=> $required, // Only required for NL
		);

		// Add house number
		$fields[$form.'_house_number'] = array(
			'label'			=> __( 'Nr.', 'woocommerce-myparcel' ),
			// 'placeholder'	=> __( 'Nr.', 'woocommerce-myparcel' ),
			'class'			=> apply_filters( 'nl_custom_address_field_class', array( 'form-row-quart-first' ), $form, 'house_number' ),
			'required'		=> $required, // Only required for NL
			'type'			=> 'number',
		);

		// Add house number Suffix
		$fields[$form.'_house_number_suffix'] = array(
			'label'			=> __( 'Suffix', 'woocommerce-myparcel' ),
			// 'placeholder'	=> __( 'Suffix', 'woocommerce-myparcel' ),
			'class'			=> apply_filters( 'nl_custom_address_field_class', array( 'form-row-quart' ), $form, 'house_number_suffix' ),
			'required'		=> false,
		);

        // Create new ordering for checkout fields
        $order_keys = array (
            $form.'_first_name',
            $form.'_last_name',
            $form.'_company',
            $form.'_country',
            $form.'_address_1',
            $form.'_address_2',
            $form.'_street_name',
            $form.'_house_number',
            $form.'_house_number_suffix',
            $form.'_postcode',
            $form.'_city',
            $form.'_state',
        );

		if ($form == 'billing') {
			array_push ($order_keys,
				$form.'_email',
				$form.'_phone'
			);
		}

		$new_order = array();
		
		// Create reordered array and fill with old array values
		foreach ($order_keys as $key) {
			$new_order[$key] = $fields[$key];
		}
		
		// Merge (&overwrite) field array
		$fields = array_merge($new_order, $fields);
			
		return $fields;
	}

	/**
	 * Hide state field for countries without states (backwards compatible fix for WooCommerce bug #4223)
	 * @param  array $allowed_states states per country
	 * @return array                 
	 */
	public function hide_states($allowed_states) {

		$hidden_states = array(
			'AF' => array(),
			'AT' => array(),
			'BE' => array(),
			'BI' => array(),
			'CZ' => array(),
			'DE' => array(),
			'DK' => array(),
			'FI' => array(),
			'FR' => array(),
			'HU' => array(),
			'IS' => array(),
			'IL' => array(),
			'KR' => array(),
			'NL' => array(),
			'NO' => array(),
			'PL' => array(),
			'PT' => array(),
			'SG' => array(),
			'SK' => array(),
			'SI' => array(),
			'LK' => array(),
			'SE' => array(),
			'VN' => array(),
		);
		$states = $hidden_states + $allowed_states;
			
		return $states;
	}

	/**
	 * Localize checkout fields live
	 * @param  array $locale_fields list of fields filtered by locale
	 * @return array $locale_fields with custom fields added
	 */
	public function country_locale_field_selectors( $locale_fields ) {
		$custom_locale_fields = array(
			'street_name'  => '#billing_street_name_field, #shipping_street_name_field',
			'house_number'  => '#billing_house_number_field, #shipping_house_number_field',
			'house_number_suffix'  => '#billing_house_number_suffix_field, #shipping_house_number_suffix_field',
		);

		$locale_fields = array_merge( $locale_fields, $custom_locale_fields );

		return $locale_fields;
	}

	/**
	 * Make NL and BE checkout fields hidden by default
	 * @param  array $fields default checkout fields
	 * @return array $fields default + custom checkoud fields
	 */
	public function default_address_fields( $fields ) {
		$custom_fields = array(
			'street_name' => array(
				'hidden'	=> true,
				'required'	=> false,
			),
			'house_number' => array(
				'hidden'	=> true,
				'required'	=> false,
			),
			'house_number_suffix' => array(
				'hidden'	=> true,
				'required'	=> false,
			),

		);

		$fields = array_merge( $fields,$custom_fields );

		return $fields;
	}

	/**
	 * Load order custom data.
	 *
	 * @param  array $data Default WC_Order data.
	 * @return array       Custom WC_Order data.
	 */
	public function load_order_data( $data ) {

		// Billing
		$data['billing_street_name']			= '';
		$data['billing_house_number']			= '';
		$data['billing_house_number_suffix']	= '';		

		// Shipping
		$data['shipping_street_name']			= '';
		$data['shipping_house_number']			= '';
		$data['shipping_house_number_suffix']	= '';

		return $data;
	}

	/**
	 * Custom billing admin edit fields.
	 *
	 * @param  array $fields Default WC_Order data.
	 * @return array		 Custom WC_Order data.
	 */
	public function admin_billing_fields( $fields ) {

		$fields['street_name'] = array(
			'label' => __( 'Street name', 'woocommerce-myparcel' ),
			'show'  => true
		);

		$fields['house_number'] = array(
			'label' => __( 'Number', 'woocommerce-myparcel' ),
			'show'  => true
		);

		$fields['house_number_suffix'] = array(
			'label' => __( 'Suffix', 'woocommerce-myparcel' ),
			'show'  => true
		);

		return $fields;
	}

	/**
	 * Custom shipping admin edit fields.
	 *
	 * @param  array $fields Default WC_Order data.
	 * @return array		 Custom WC_Order data.
	 */
	public function admin_shipping_fields( $fields ) {

		$fields['street_name'] = array(
			'label' => __( 'Street name', 'woocommerce-myparcel' ),
			'show'  => true
		);

		$fields['house_number'] = array(
			'label' => __( 'Number', 'woocommerce-myparcel' ),
			'show'  => true
		);

		$fields['house_number_suffix'] = array(
			'label' => __( 'Suffix', 'woocommerce-myparcel' ),
			'show'  => true
		);

		return $fields;
	}

	/**
	 * Custom user profile edit fields.
	 */
	public function user_profile_fields ( $meta_fields ) {
		$myparcel_billing_fields = array(
			'billing_street_name' => array(
				'label'       => __( 'Street', 'woocommerce-myparcel' ),
				'description' => ''
			),
			'billing_house_number' => array(
				'label'       => __( 'Number', 'woocommerce-myparcel' ),
				'description' => ''
			),
			'billing_house_number_suffix' => array(
				'label'       => __( 'Suffix', 'woocommerce-myparcel' ),
				'description' => ''
			),
		);
		$myparcel_shipping_fields = array(
			'shipping_street_name' => array(
				'label'       => __( 'Street', 'woocommerce-myparcel' ),
				'description' => ''
			),
			'shipping_house_number' => array(
				'label'       => __( 'Number', 'woocommerce-myparcel' ),
				'description' => ''
			),
			'shipping_house_number_suffix' => array(
				'label'       => __( 'Suffix', 'woocommerce-myparcel' ),
				'description' => ''
			),
		);

		// add myparcel fields to billing section
		$billing_fields = array_merge($meta_fields['billing']['fields'], $myparcel_billing_fields);
		$billing_fields = $this->array_move_keys( $billing_fields, array( 'billing_street_name', 'billing_house_number', 'billing_house_number_suffix' ), 'billing_address_2', 'after' );
		$meta_fields['billing']['fields'] = $billing_fields;

		// add myparcel fields to shipping section
		$shipping_fields = array_merge($meta_fields['shipping']['fields'], $myparcel_shipping_fields);
		$shipping_fields = $this->array_move_keys( $shipping_fields, array( 'shipping_street_name', 'shipping_house_number', 'shipping_house_number_suffix' ), 'shipping_address_2', 'after' );
		$meta_fields['shipping']['fields'] = $shipping_fields;
		
		return $meta_fields;
	}

	/**
	 * Add custom fields in customer details ajax.
	 * called when clicking the "Load billing/shipping address" button on Edit Order view
	 *
	 * @return void
	 */
	public function customer_details_ajax( $customer_data ) {
		$user_id = (int) trim( stripslashes( $_POST['user_id'] ) );
		$type_to_load = esc_attr( trim( stripslashes( $_POST['type_to_load'] ) ) );

		$custom_data = array(
			$type_to_load . '_street_name' => get_user_meta( $user_id, $type_to_load . '_street_name', true ),
			$type_to_load . '_house_number' => get_user_meta( $user_id, $type_to_load . '_house_number', true ),
			$type_to_load . '_house_number_suffix' => get_user_meta( $user_id, $type_to_load . '_house_number_suffix', true ),
		);

		return array_merge( $customer_data, $custom_data );
	}

	/**
	 * Save custom fields from admin.
	 */
	public function save_custom_fields( $post_id ) {
		$post_type = get_post_type( $post_id );
		if ( ( $post_type == 'shop_order' || $post_type == 'shop_order_refund' ) && !empty($_POST) ) {
			$order = WCX::get_order( $post_id );
			$addresses = array( 'billing', 'shipping' );
			$address_fields = array( 'street_name', 'house_number', 'house_number_suffix' );
			foreach ($addresses as $address) {
				foreach ($address_fields as $address_field) {
					if (isset($_POST["_{$address}_{$address_field}"])) {
						WCX_Order::update_meta_data( $order, "_{$address}_{$address_field}", stripslashes( $_POST["_{$address}_{$address_field}"] ));
					}
				}
			}
		}
		return;
	}
	
	/**
	 * Merge streetname, street number and street suffix into the default 'address_1' field
	 *
	 * @param  string $order_id Order ID of checkout order.
	 * @return void
	 */
	public function merge_street_number_suffix ( $order_id ) {
		$order = WCX::get_order( $order_id );
		// file_put_contents('postdata.txt', print_r($_POST,true)); // for debugging
		if ( version_compare( WOOCOMMERCE_VERSION, '2.1', '<=' ) ) {
			// old versions use 'shiptobilling'
			$ship_to_different_address = isset($_POST['shiptobilling'])?false:true;
		} else {
			// WC2.1
			$ship_to_different_address = isset($_POST['ship_to_different_address'])?true:false;
		}

		// check if country is NL
		if ( $_POST['billing_country'] == 'NL' || $_POST['billing_country'] == 'BE' ) {
			// concatenate street & house number & copy to 'billing_address_1'
			$billing_house_number = $_POST['billing_house_number'] . (!empty($_POST['billing_house_number_suffix'])?'-' . $_POST['billing_house_number_suffix']:'');
			$billing_address_1 = $_POST['billing_street_name'] . ' ' . $billing_house_number;
			WCX_Order::set_address_prop( $order, 'address_1', 'billing', $billing_address_1 );

			// check if 'ship to billing address' is checked
			if ( $ship_to_different_address == false && $this->cart_needs_shipping_address() ) {
				// use billing address
				WCX_Order::set_address_prop( $order, 'address_1', 'shipping', $billing_address_1 );
			}
		}

		if (($_POST['shipping_country'] == 'NL' || $_POST['shipping_country'] == 'BE') && $ship_to_different_address == true ) {
			// concatenate street & house number & copy to 'shipping_address_1'
			$shipping_house_number = $_POST['shipping_house_number'] . (!empty($_POST['shipping_house_number_suffix'])?'-' . $_POST['shipping_house_number_suffix']:'');
			$shipping_address_1 = $_POST['shipping_street_name'] . ' ' . $shipping_house_number;
			WCX_Order::set_address_prop( $order, 'address_1', 'shipping', $shipping_address_1 );
		}
		return;
	}


	/**
	 * validate NL postcodes
	 *
	 * @return bool $valid
	 */
	public function validate_postcode( $valid, $postcode, $country ) {
		if ($country == 'NL') {
			$valid = (bool) preg_match( '/^[1-9][0-9]{3} ?(?!sa|sd|ss)[a-z]{2}$/i', trim($postcode) );
		}
        if ($country == 'BE') {
            $valid = (bool) preg_match( '/^[1-9][0-9]{3}/i', trim($postcode) );
        }
		return $valid;
	}

    /**
     * validate address field 1 for shipping and billing
     */
    public function validate_address_fields($address, $errors)
    {
        if ($address['billing_country'] == 'NL'
            && !(bool) preg_match(self::SPLIT_STREET_REGEX, trim($address['billing_address_1']))) {
            $errors->add('address', __('Please enter a valid billing address.', 'woocommerce-myparcel'));
        }

        if ($address['shipping_country'] == 'NL'
            && array_key_exists('ship_to_different_address', $address)
            && !(bool) preg_match(self::SPLIT_STREET_REGEX, trim($address['shipping_address_1']))) {
            $errors->add('address', __('Please enter a valid shipping address.', 'woocommerce-myparcel'));
        }
    }

	/**
	 * Clean postcodes : remove space, dashes (& other non alphanumeric characters)
	 *
	 * @return $billing_postcode
	 * @return $shipping_postcode
	 */
	public function clean_billing_postcode ( ) {
		if ( $_POST['billing_country'] == 'NL' || $_POST['billing_country'] == 'BE') {
			$billing_postcode = preg_replace('/[^a-zA-Z0-9]/', '', $_POST['billing_postcode']);
		} else {
			$billing_postcode = $_POST['billing_postcode'];
		}
		return $billing_postcode;
	}
	public function clean_shipping_postcode ( ) {
		if ( $_POST['shipping_country'] == 'NL' || $_POST['billing_country'] == 'BE' ) {
			$shipping_postcode = preg_replace('/[^a-zA-Z0-9]/', '', $_POST['shipping_postcode']);
		} else {
			$shipping_postcode = $_POST['shipping_postcode'];
		}
		return $shipping_postcode;
	}

	/**
	 * Remove placeholders from posted checkout data
	 * @param  string $order_id order_id of the new order
	 * @param  array  $posted   Array of posted form data
	 * @return void
	 */
	public function remove_placeholders( $order_id, $posted ) {
		$order = WCX::get_order( $order_id );
		// get default address fields with their placeholders
		$countries = new WC_Countries;
		$fields = $countries->get_default_address_fields();

		// define order_comments placeholder
		$order_comments_placeholder = _x('Notes about your order, e.g. special notes for delivery.', 'placeholder', 'woocommerce');

		// check if ship to billing is set
		if ( version_compare( WOOCOMMERCE_VERSION, '2.1', '<=' ) ) {
			// old versions use 'shiptobilling'
			$ship_to_different_address = isset($_POST['shiptobilling'])?false:true;
		} else {
			// WC2.1
			$ship_to_different_address = isset($_POST['ship_to_different_address'])?true:false;
		}

		// check the billing & shipping fields
		$field_types = array('billing','shipping');
		$check_fields = array('address_1','address_2','city','state','postcode');
		foreach ($field_types as $field_type) {
			foreach ($check_fields as $check_field) {
				// file_put_contents(ABSPATH.'field_check.txt', $posted[$field_type.'_'.$check_field] .' || '. $fields[$check_field]['placeholder']."\n",FILE_APPEND);
				if ( isset( $posted[$field_type.'_'.$check_field] ) && isset( $fields[$check_field]['placeholder'] ) && $posted[$field_type.'_'.$check_field] == $fields[$check_field]['placeholder'] ) {
					WCX_Order::set_address_prop( $order, $check_field, $field_type, '' );

					// also clear shipping field when ship_to_different_address is false
					if ( $ship_to_different_address == false && $field_type == 'billing') {
						WCX_Order::set_address_prop( $order, $check_field, 'shipping', '' );
					}
				}
			}
		}

		// check the order comments field		
		if ($posted['order_comments'] == $order_comments_placeholder ) {
			wp_update_post( array(
				'ID'			=> $order_id,
				'post_excerpt'	=> '',
				)
			);
		}
		
		return;
	}

	/**
	 * WooCommerce concatenates translations for required field notices that result in
	 * confusing messages, so we translate the full notice to prevent this
	 */
	function required_field_notices( $notice, $field_label ) {
		// concatenate translations
		$billing_nr = sprintf( __( 'Billing %s', 'woocommerce' ), __( 'Nr.', 'woocommerce-myparcel' ) );
		$shipping_nr = sprintf( __( 'Shipping %s', 'woocommerce' ), __( 'Nr.', 'woocommerce-myparcel' ) );
		// not used:
		// $billing_street = sprintf( __( 'Billing %s', 'woocommerce' ), __( 'Street name', 'woocommerce-myparcel' ) );
		// $shipping_street = sprintf( __( 'Shipping %s', 'woocommerce' ), __( 'Street name', 'woocommerce-myparcel' ) );

		switch ( $field_label ) {
			case $billing_nr:
				$notice = __( '<b>Billing Nr.</b> is a required field', 'woocommerce-myparcel' );
				break;
			case $shipping_nr:
				$notice = __( '<b>Shipping Nr.</b> is a required field', 'woocommerce-myparcel' );
				break;
			default:
				break;
		}
		return $notice;
	}

	/**
	 * Custom country address formats.
	 *
	 * @param  array $formats Defaul formats.
	 *
	 * @return array          New NL format.
	 */
	public function localisation_address_formats( $formats ) {
		// default = $postcode_before_city = "{company}\n{name}\n{address_1}\n{address_2}\n{postcode} {city}\n{country}";
		$formats['NL'] = "{company}\n{name}\n{address_1}\n{address_2}\n{postcode} {city}\n{country}";
        $formats['BE'] = "{company}\n{name}\n{address_1}\n{address_2}\n{postcode} {city}\n{country}";
		return $formats;
	}

	/**
	 * Custom country address format.
	 *
	 * @param  array $replacements Default replacements.
	 * @param  array $args         Arguments to replace.
	 *
	 * @return array               New replacements.
	 */
	public function formatted_address_replacements( $replacements, $args ) {
		extract( $args );

		if (!empty($street_name) && ($country == 'NL' || $country == 'BE')) {
			$replacements['{address_1}'] = $street_name.' '.$house_number.$house_number_suffix;
		}
		
		return $replacements;
	}

	/**
	 * Custom order formatted billing address.
	 *
	 * @param  array $address Default address.
	 * @param  object $order  Order data.
	 *
	 * @return array          New address format.
	 */
	public function order_formatted_billing_address( $address, $order ) {
		$address['street_name']			= WCX_Order::get_meta( $order, '_billing_street_name', true, 'view' );
		$address['house_number']		= WCX_Order::get_meta( $order, '_billing_house_number', true, 'view' );
		$address['house_number_suffix'] = WCX_Order::get_meta( $order, '_billing_house_number_suffix', true, 'view' );
		$address['house_number_suffix']	= !empty($address['house_number_suffix'])?'-'.$address['house_number_suffix']:'';

		return $address;
	}

	/**
	 * Custom order formatted shipping address.
	 *
	 * @param  array $address Default address.
	 * @param  object $order  Order data.
	 *
	 * @return array          New address format.
	 */
	public function order_formatted_shipping_address( $address, $order ) {
		$address['street_name']			= WCX_Order::get_meta( $order, '_shipping_street_name', true, 'view' );
		$address['house_number']		= WCX_Order::get_meta( $order, '_shipping_house_number', true, 'view' );
		$address['house_number_suffix'] = WCX_Order::get_meta( $order, '_shipping_house_number_suffix', true, 'view' );
		$address['house_number_suffix']	= !empty($address['house_number_suffix'])?'-'.$address['house_number_suffix']:'';

		return $address;
	}

	/**
	 * Custom user column billing address information.
	 *
	 * @param  array $address Default address.
	 * @param  int $user_id   User id.
	 *
	 * @return array          New address format.
	 */
	public function user_column_billing_address( $address, $user_id ) {
		$address['street_name']			= get_user_meta( $user_id, 'billing_street_name', true );
		$address['house_number']		= get_user_meta( $user_id, 'billing_house_number', true );
		$address['house_number_suffix']	= (get_user_meta( $user_id, 'billing_house_number_suffix', true ))?'-'.get_user_meta( $user_id, 'billing_house_number_suffix', true ):'';

		return $address;
	}

	/**
	 * Custom user column shipping address information.
	 *
	 * @param  array $address Default address.
	 * @param  int $user_id   User id.
	 *
	 * @return array          New address format.
	 */
	public function user_column_shipping_address( $address, $user_id ) {
		$address['street_name']			= get_user_meta( $user_id, 'shipping_street_name', true );
		$address['house_number']		= get_user_meta( $user_id, 'shipping_house_number', true );
		$address['house_number_suffix']	= (get_user_meta( $user_id, 'shipping_house_number_suffix', true ))?'-'.get_user_meta( $user_id, 'shipping_house_number_suffix', true ):'';

		return $address;
	}

	/**
	 * Custom my address formatted address.
	 *
	 * @param  array $address   Default address.
	 * @param  int $customer_id Customer ID.
	 * @param  string $name     Field name (billing or shipping).
	 *
	 * @return array            New address format.
	 */
	public function my_account_my_address_formatted_address( $address, $customer_id, $name ) {
		$address['street_name']			= get_user_meta( $customer_id, $name . '_street_name', true );
		$address['house_number']		= get_user_meta( $customer_id, $name . '_house_number', true );
		$address['house_number_suffix']	= (get_user_meta( $customer_id, $name . '_house_number_suffix', true ))?'-'.get_user_meta( $customer_id, $name . '_house_number_suffix', true ):'';

		return $address;
	}

	/**
	 * Get a posted address field after sanitization and validation.
	 *
	 * @param  string $key
	 * @param  string $type billing for shipping
	 *
	 * @return string
	 */
	public function get_posted_address_data( $key, $posted, $type = 'billing' ) {
		if ( 'billing' === $type || ( !$posted['ship_to_different_address'] && $this->cart_needs_shipping_address() ) ) {
			$return = isset( $posted[ 'billing_' . $key ] ) ? $posted[ 'billing_' . $key ] : '';
		} elseif ( 'shipping' === $type && !$this->cart_needs_shipping_address() ) {
			$return = '';
		} else {
			$return = isset( $posted[ 'shipping_' . $key ] ) ? $posted[ 'shipping_' . $key ] : '';
		}

		return $return;
	}

	public function cart_needs_shipping_address() {
		if ( is_object( WC()->cart ) && method_exists( WC()->cart, 'needs_shipping_address' ) && function_exists('wc_ship_to_billing_address_only') ) {
			if ( WC()->cart->needs_shipping_address() || wc_ship_to_billing_address_only() ) {
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
	 * @param  int   $order_id
	 * @param  array $posted
	 *
	 * @return void
	 */
	public function save_order_data( $order_id, $posted ) {
		$order = WCX::get_order( $order_id );
		// Billing.
		WCX_Order::update_meta_data( $order, '_billing_street_name', $this->get_posted_address_data( 'street_name', $posted ) );
		WCX_Order::update_meta_data( $order, '_billing_house_number', $this->get_posted_address_data( 'house_number', $posted ) );
		WCX_Order::update_meta_data( $order, '_billing_house_number_suffix', $this->get_posted_address_data( 'house_number_suffix', $posted ) );

		// Shipping.
		WCX_Order::update_meta_data( $order, '_shipping_street_name', $this->get_posted_address_data( 'street_name', $posted, 'shipping' ) );
		WCX_Order::update_meta_data( $order, '_shipping_house_number', $this->get_posted_address_data( 'house_number', $posted, 'shipping' ) );
		WCX_Order::update_meta_data( $order, '_shipping_house_number_suffix', $this->get_posted_address_data( 'house_number_suffix', $posted, 'shipping' ) );
	}

	/**
	 * Helper function to move array elements (one or more) to a position before a specific key
	 * @param  array  $array         Main array to modify
	 * @param  mixed  $keys          Single key or array of keys of element(s) to move
	 * @param  string $reference_key key to put elements before or after
	 * @param  string $position       before or after
	 * @return array                 reordered array
	 */
	public function array_move_keys ( $array, $keys, $reference_key, $position = 'before' ) {
		// cast $key as array
		$keys = (array) $keys;

		if (!isset($array[$reference_key])) {
			return $array;
		}

		$move = array();
		foreach ($keys as $key) {
			if (!isset($array[$key])) {
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

		$new_array =
			array_slice($array, 0, $move_to_pos, true)
			+ $move
			+ array_slice($array, $move_to_pos, NULL, true);

		return $new_array;
	}
}

endif; // class_exists

return new WC_NLPostcode_Fields();
