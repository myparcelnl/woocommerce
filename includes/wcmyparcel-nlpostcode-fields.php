<?php
if ( !class_exists( 'WC_NLPostcode_Fields' ) ) {
class WC_NLPostcode_Fields {

	/**
	 * Construct.
	 */
	 		
	public function __construct() {
		// Load styles & scripts
		add_action( 'wp_enqueue_scripts', array( &$this, 'add_styles_scripts' ) );

		// Hide default address fields & state
		add_filter('woocommerce_get_country_locale', array( &$this, 'woocommerce_locale_nl' ), 1, 1);

		// Add street name & house number checkout fields.
		add_filter( 'woocommerce_checkout_fields', array( &$this, 'nl_checkout_fields' ) );

		// Hide state field for countries without states (backwards compatible fix for bug #4223)
		add_filter( 'woocommerce_countries_allowed_country_states', array( &$this, 'hide_states' ) );

		// Load custom order data.
		add_filter( 'woocommerce_load_order_data', array( &$this, 'load_order_data' ) );

		// Custom shop_order details.
		add_filter( 'woocommerce_admin_billing_fields', array( &$this, 'admin_billing_fields' ) );
		add_filter( 'woocommerce_admin_shipping_fields', array( &$this, 'admin_shipping_fields' ) );
		add_filter( 'woocommerce_found_customer_details', array( $this, 'customer_details_ajax' ) );
		add_action( 'save_post', array( &$this,'save_custom_fields' ) );

		// Processing checkout
		add_action('woocommerce_checkout_update_order_meta', array( &$this, 'merge_street_number_suffix' ) );			
		add_filter('woocommerce_process_checkout_field_billing_postcode', array( &$this, 'clean_billing_postcode' ) );			
		add_filter('woocommerce_process_checkout_field_shipping_postcode', array( &$this, 'clean_shipping_postcode' ) );	
		}

	/**
	 * Load styles & scripts.
	 */
	public function add_styles_scripts(){
   		if ( is_checkout() ) {
			wp_register_script( 'nl-checkout', (dirname(plugin_dir_url(__FILE__)) . '/js/nl-checkout.js'), array( 'wc-checkout' ) );
			wp_enqueue_script( 'nl-checkout' );
			wp_enqueue_style( 'nl-checkout', (dirname(plugin_dir_url(__FILE__)) . '/css/nl-checkout.css') );
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

		return $locale;
	}

	/**
	 * New checkout billing/shipping fields
	 * @param  array $fields Default fields.
	 * @return array		 New fields.
	 */
	public function nl_checkout_fields( $fields ) {
		$forms = array( 'billing', 'shipping' );
		
		foreach ($forms as $form) {
			// Add Street name
				$fields[$form][$form.'_street_name'] = array(
				'label'		 => __( 'Street name', 'wcmyparcel' ),
				'placeholder'   => __( 'Street name', 'wcmyparcel' ),
				'class'		 => array( 'form-row-first' ),
				'required'	  => false, // not required by default - handled on locale level
			);

			// Add house number
			$fields[$form][$form.'_house_number'] = array(
				'label'		 => __( 'Nr.', 'wcmyparcel' ),
				'placeholder'   => __( 'Nr.', 'wcmyparcel' ),
				'class'		 => array( 'form-row-quart-first' ),
				'required'	  => false, // not required by default - handled on locale level
			);

			// Add house number Suffix
			$fields[$form][$form.'_house_number_suffix'] = array(
				'label'		 => __( 'Suffix', 'wcmyparcel' ),
				'placeholder'   => __( 'Suffix', 'wcmyparcel' ),
				'class'		 => array( 'form-row-quart' ),
				'required'	  => false, // not required by default - handled on locale level
			);

			// Create new ordering for checkout fields
			$order_keys = array (
				$form.'_country',
				$form.'_first_name',
				$form.'_last_name',
				$form.'_company',
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
				$new_order[$form][$key] = $fields[$form][$key];
			}
			
			// Merge (&overwrite) field array
			$fields = array_merge($fields, $new_order);
			
		} 
		return $fields;
	}

	/**
	 * Hide state field for countries without states (backwards compatible fix for WooCommerce bug #4223)
	 * @param  array $allowed_states states per country
	 * @return array                 
	 */
	function hide_states($allowed_states) {

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
			'label' => __( 'Street name', 'wcmyparcel' ),
			'show'  => true
		);

		$fields['house_number'] = array(
			'label' => __( 'Number', 'wcmyparcel' ),
			'show'  => true
		);

		$fields['house_number_suffix'] = array(
			'label' => __( 'Suffix', 'wcmyparcel' ),
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
			'label' => __( 'Street name', 'wcmyparcel' ),
			'show'  => true
		);

		$fields['house_number'] = array(
			'label' => __( 'Number', 'wcmyparcel' ),
			'show'  => true
		);

		$fields['house_number_suffix'] = array(
			'label' => __( 'Suffix', 'wcmyparcel' ),
			'show'  => true
		);

		return $fields;
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
	public function save_custom_fields($post_id) {
		global $post_type;
		if( $post_type == 'shop_order' ) {
			update_post_meta( $post_id, '_billing_street_name', stripslashes( $_POST['_billing_street_name'] ));
			update_post_meta( $post_id, '_billing_house_number', stripslashes( $_POST['_billing_house_number'] ));
			update_post_meta( $post_id, '_billing_house_number_suffix', stripslashes( $_POST['_billing_house_number_suffix'] ));

			update_post_meta( $post_id, '_shipping_street_name', stripslashes( $_POST['_shipping_street_name'] ));
			update_post_meta( $post_id, '_shipping_house_number', stripslashes( $_POST['_shipping_house_number'] ));
			update_post_meta( $post_id, '_shipping_house_number_suffix', stripslashes( $_POST['_shipping_house_number_suffix'] ));
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
		// check if country is NL
		if ( $_POST['shipping_country'] == 'NL' ) {
			// concatenate street & house number & copy to 'billing_address_1'
			$billing_house_number = $_POST['billing_house_number'] . (!empty($_POST['billing_house_number_suffix'])?'-' . $_POST['billing_house_number_suffix']:'');
			$billing_address_1 = $_POST['billing_street_name'] . ' ' . $billing_house_number;
			update_post_meta( $order_id,  '_billing_address_1', $billing_address_1 );

			// check if 'ship to billing address' is checked
			if ( $_POST['shiptobilling'] ) {
				// use billing address
				update_post_meta( $order_id,  '_shipping_address_1', $billing_address_1 );
			} else {
				// concatenate street & house number & copy to 'shipping_address_1'
				$shipping_house_number = $_POST['shipping_house_number'] . (!empty($_POST['shipping_house_number_suffix'])?'-' . $_POST['shipping_house_number_suffix']:'');
				$shipping_address_1 = $_POST['shipping_street_name'] . ' ' . $shipping_house_number;
				update_post_meta( $order_id,  '_shipping_address_1', $shipping_address_1 );
			}
		}
		return;
	}

	/**
	 * Clean postcodes : remove space, dashes (& other non alfanumeric characters)
	 *
	 * @return $billing_postcode
	 * @return $shipping_postcode
	 */
	public function clean_billing_postcode ( ) {
		$billing_postcode = preg_replace('/[^a-zA-Z0-9]/', '', $_POST['billing_postcode']);		
		return $billing_postcode;
	}
	public function clean_shipping_postcode ( ) {
		$shipping_postcode = preg_replace('/[^a-zA-Z0-9]/', '', $_POST['shipping_postcode']);		
		return $shipping_postcode;
	}

}
}