<?php
if ( !class_exists( 'WC_NLPostcode_Fields' ) ) {
class WC_NLPostcode_Fields {

    /**
     * Construct.
     */
     		
	public function __construct() {
		// Load custom styles
		add_action( 'wp_enqueue_scripts', array( &$this, 'add_styles' ) );

	    // New checkout fields.
	    add_filter( 'woocommerce_checkout_fields', array( &$this, 'checkout_billing_fields' ) );
	    add_filter( 'woocommerce_checkout_fields', array( &$this, 'checkout_shipping_fields' ) );

	    // Load custom order data.
	    add_filter( 'woocommerce_load_order_data', array( &$this, 'load_order_data' ) );

	    // Admin order billing fields.
	    add_filter( 'woocommerce_admin_billing_fields', array( &$this, 'admin_billing_fields' ) );

	    // Admin order shipping fields.
	    add_filter( 'woocommerce_admin_shipping_fields', array( &$this, 'admin_shipping_fields' ) );

		add_action( 'save_post', array( &$this,'save_custom_fields' ) );

		// Processing checkout
		add_action('woocommerce_checkout_update_order_meta', array( &$this, 'merge_street_number_suffix' ) );			
		add_filter('woocommerce_process_checkout_field_billing_postcode', array( &$this, 'clean_billing_postcode' ) );			
		add_filter('woocommerce_process_checkout_field_shipping_postcode', array( &$this, 'clean_shipping_postcode' ) );	
		}

    /**
     * Load styles.
     */
	function add_styles(){
		wp_register_style( 'wcmyparcel-styles', (dirname(plugin_dir_url(__FILE__)) . '/css/wcmyparcel-styles.css'), array(), '', 'all' );
		wp_enqueue_style( 'wcmyparcel-styles' );  
	}

    /**
     * New checkout billing fields
     * @param  array $fields Default fields.
     * @return array         New fields.
     */
    public function checkout_billing_fields( $fields ) {
        // Billing street name
        $fields['billing']['billing_street_name'] = array(
            'label'       => __( 'Street name', 'wcmyparcel' ),
            'placeholder' => __( 'Street name', 'wcmyparcel' ),
            'class'       => array( 'form-row-first' ),
            'required'    => true
        );

        // Billing house number
        $fields['billing']['billing_house_number'] = array(
            'label'       => __( 'Nr.', 'wcmyparcel' ),
            'placeholder' => __( 'Nr.', 'wcmyparcel' ),
            'class'       => array( 'form-row-quart-first' ),
            'required'    => true
        );

        // Billing house number Suffix
        $fields['billing']['billing_house_number_suffix'] = array(
            'label'       => __( 'Suffix', 'wcmyparcel' ),
            'placeholder' => __( 'Suffix', 'wcmyparcel' ),
            'class'       => array( 'form-row-quart' ),
            'required'    => false,
			'clear'		  => true
        );

		// Create new ordering for checkout fields
		$order_keys = array (
			'billing_country',
			'billing_first_name',
			'billing_last_name',
			'billing_company',
			'billing_street_name',
			'billing_house_number',
			'billing_house_number_suffix',
			'billing_postcode',
			'billing_city',
			'billing_state',
			'billing_email',
			'billing_phone',
			);
		$new_order = array();
		
		// Create reordered array and fill with old array values
		foreach ($order_keys as $key) {
    		$new_order['billing'][$key] = $fields['billing'][$key];
		}
		
		// Merge (&overwrite) field array
		$fields = array_merge($fields, $new_order);

		// Unset state ('provincie') field
	        unset( $fields['billing']['billing_state'] );
			$fields['billing']['billing_postcode']['class'] = array('form-row-first');

		$fields['billing']['billing_country']['class'] = array('form-row country_select update_totals_on_change');


		return $fields;
    }

    /**
     * New checkout shipping fields
     * @param  array $fields Default fields.
     * @return array         New fields.
     */
    public function checkout_shipping_fields( $fields ) {
        // Shipping street name
        $fields['shipping']['shipping_street_name'] = array(
            'label'       => __( 'Street name', 'wcmyparcel' ),
            'placeholder' => __( 'Street name', 'wcmyparcel' ),
            'class'       => array( 'form-row-first' ),
            'required'    => true
        );

        // Shipping house number
        $fields['shipping']['shipping_house_number'] = array(
            'label'       => __( 'Nr.', 'wcmyparcel' ),
            'placeholder' => __( 'Nr.', 'wcmyparcel' ),
            'class'       => array( 'form-row-quart-first' ),
            'required'    => true
        );

        // Shipping house number Suffix
        $fields['shipping']['shipping_house_number_suffix'] = array(
            'label'       => __( 'Suffix', 'wcmyparcel' ),
            'placeholder' => __( 'Suffix', 'wcmyparcel' ),
            'class'       => array( 'form-row-quart' ),
            'required'    => false,
			'clear'		  => true
        );

		// Create new ordering for checkout fields
		$order_keys = array (
			'shipping_country',
			'shipping_first_name',
			'shipping_last_name',
			'shipping_company',
			'shipping_street_name',
			'shipping_house_number',
			'shipping_house_number_suffix',
			'shipping_postcode',
			'shipping_city',
			'shipping_state',
			);
		$new_order = array();
		
		// Create reordered array and fill with old array values
		foreach ($order_keys as $key) {
    		$new_order['shipping'][$key] = $fields['shipping'][$key];
		}
		
		// Merge (&overwrite) field array
		$fields = array_merge($fields, $new_order);

		// Unset state ('provincie') field
	        unset( $fields['shipping']['shipping_state'] );
			$fields['shipping']['shipping_postcode']['class'] = array('form-row-first');

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
     * @return array         Custom WC_Order data.
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
     * @return array         Custom WC_Order data.
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
		// check for suffix
		if ( $_POST['billing_house_number_suffix'] ){
			$billing_house_number = $_POST['billing_house_number'] . '-' . $_POST['billing_house_number_suffix'];
		} else {
			$billing_house_number = $_POST['billing_house_number'];
		}

		// concatenate street & house number & copy to 'billing_address_1'
		$billing_address_1 = $_POST['billing_street_name'] . ' ' . $billing_house_number;
		update_post_meta( $order_id,  '_billing_address_1', $billing_address_1 );

		// check if 'ship to billing address' is checked
		if ( $_POST['shiptobilling'] ) {
			// use billing address
			update_post_meta( $order_id,  '_shipping_address_1', $billing_address_1 );
		} else {
			if ( $_POST['shipping_house_number_suffix'] ){
				$shipping_house_number = $_POST['shipping_house_number'] . '-' . $_POST['shipping_house_number_suffix'];
			} else {
				$shipping_house_number = $_POST['shipping_house_number'];
			}

			// concatenate street & house number & copy to 'shipping_address_1'
			$shipping_address_1 = $_POST['shipping_street_name'] . ' ' . $shipping_house_number;
			update_post_meta( $order_id,  '_shipping_address_1', $shipping_address_1 );			
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