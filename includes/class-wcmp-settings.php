<?php
/**
 * Create & render settings page
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( !class_exists( 'WooCommerce_MyParcel_Settings' ) ) :

class WooCommerce_MyParcel_Settings {

	public $options_page_hook;

	public function __construct() {
		$this->callbacks = include( 'class-wcmp-settings-callbacks.php' );
		add_action( 'admin_menu', array( $this, 'menu' ) );
		add_filter( 'plugin_action_links_'.WooCommerce_MyParcel()->plugin_basename, array( $this, 'add_settings_link' ) );

		add_action( 'admin_init', array( $this, 'general_settings' ) );
		add_action( 'admin_init', array( $this, 'export_defaults_settings' ) );
		add_action( 'admin_init', array( $this, 'checkout_settings' ) );

		// notice for WC MyParcel Belgium plugin
		add_action( 'woocommerce_myparcel_before_settings_page', array( $this, 'myparcel_be_notice'), 10, 1 );
	}

	/**
	 * Add settings item to WooCommerce menu
	 */
	public function menu() {
		add_submenu_page(
			'woocommerce',
			__( 'MyParcel', 'woocommerce-myparcel' ),
			__( 'MyParcel', 'woocommerce-myparcel' ),
			'manage_options',
			'woocommerce_myparcel_settings',
			array( $this, 'settings_page' )
		);	
	}
	
	/**
	 * Add settings link to plugins page
	 */
	public function add_settings_link( $links ) {
		$settings_link = '<a href="admin.php?page=woocommerce_myparcel_settings">'. __( 'Settings', 'woocommerce-myparcel' ) . '</a>';
		array_push( $links, $settings_link );
		return $links;
	}

	public function settings_page() {
		$settings_tabs = apply_filters( 'woocommerce_myparcel_settings_tabs', array (
				'general'			=> __( 'General', 'woocommerce-myparcel' ),
				'export_defaults'	=> __( 'Default export settings', 'woocommerce-myparcel' ),
				'checkout'			=> __( 'Checkout', 'woocommerce-myparcel' ),
			)
		);

		$active_tab = isset( $_GET[ 'tab' ] ) ? $_GET[ 'tab' ] : 'general';
		?>
		<div class="wrap">
			<h1><?php _e( 'WooCommerce MyParcel Settings', 'woocommerce-myparcel' ); ?></h1>
			<h2 class="nav-tab-wrapper">
			<?php
			foreach ($settings_tabs as $tab_slug => $tab_titel ) {
				printf('<a href="?page=woocommerce_myparcel_settings&tab=%1$s" class="nav-tab nav-tab-%1$s %2$s">%3$s</a>', $tab_slug, (($active_tab == $tab_slug) ? 'nav-tab-active' : ''), $tab_titel);
			}
			?>
			</h2>

			<?php do_action( 'woocommerce_myparcel_before_settings_page', $active_tab ); ?>
				
			<form method="post" action="options.php" id="woocommerce-myparcel-settings" class="wcmp_shipment_options">
				<?php
					do_action( 'woocommerce_myparcel_before_settings', $active_tab );
					settings_fields( 'woocommerce_myparcel_'.$active_tab.'_settings' );
					do_settings_sections( 'woocommerce_myparcel_'.$active_tab.'_settings' );
					do_action( 'woocommerce_myparcel_after_settings', $active_tab );

					submit_button();
				?>
			</form>

			<?php do_action( 'woocommerce_myparcel_after_settings_page', $active_tab ); ?>

		</div>
		<?php
	}

	public function myparcel_be_notice( $active_tab ) {
		$base_country = WC()->countries->get_base_country();

		// save or check option to hide notice
		if ( isset( $_GET['myparcel_hide_be_notice'] ) ) {
			update_option( 'myparcel_hide_be_notice', true );
			$hide_notice = true;
		} else {
			$hide_notice = get_option( 'myparcel_hide_be_notice' );
		}

		// link to hide message when one of the premium extensions is installed
		if ( !$hide_notice && $base_country == 'BE' ) {
			$myparcel_belgium_link = '<a href="https://wordpress.org/plugins/wc-myparcel-belgium/" target="blank">WC MyParcel Belgium</a>';
			$text = sprintf(__( 'It looks like your shop is based in Belgium. This plugin is for MyParcel Netherlands. If you are using MyParcel Belgium, download the %s plugin instead!', 'woocommerce-myparcel' ), $myparcel_belgium_link);
			$dismiss_button = sprintf('<a href="%s" style="display:inline-block; margin-top: 10px;">%s</a>', add_query_arg( 'myparcel_hide_be_notice', 'true' ), __( 'Hide this message', 'woocommerce-myparcel' ) );
			printf('<div class="notice notice-warning"><p>%s %s</p></div>', $text, $dismiss_button);
		}
	}
	
	/**
	 * Register General settings
	 */
	public function general_settings() {
		$option_group = 'woocommerce_myparcel_general_settings';

		// Register settings.
		$option_name = 'woocommerce_myparcel_general_settings';
		register_setting( $option_group, $option_name, array( $this->callbacks, 'validate' ) );

		// Create option in wp_options.
		if ( false === get_option( $option_name ) ) {
			$this->default_settings( $option_name );
		}

		// API section.
		add_settings_section(
			'api',
			__( 'API settings', 'woocommerce-myparcel' ),
			array( $this->callbacks, 'section' ),
			$option_group
		);

		// add_settings_field(
		// 	'api_username',
		// 	__( 'Username', 'woocommerce-myparcel' ),
		// 	array( $this->callbacks, 'text_input' ),
		// 	$option_group,
		// 	'api',
		// 	array(
		// 		'option_name'	=> $option_name,
		// 		'id'			=> 'api_username',
		// 		'size'			=> 50,
		// 	)
		// );

		add_settings_field(
			'api_key',
			__( 'Key', 'woocommerce-myparcel' ),
			array( $this->callbacks, 'text_input' ),
			$option_group,
			'api',
			array(
				'option_name'	=> $option_name,
				'id'			=> 'api_key',
				'size'			=> 50,
			)
		);

		// General section.
		add_settings_section(
			'general',
			__( 'General settings', 'woocommerce-myparcel' ),
			array( $this->callbacks, 'section' ),
			$option_group
		);

		add_settings_field(
			'download_display',
			__( 'Label display', 'woocommerce-myparcel' ),
			array( $this->callbacks, 'radio_button' ),
			$option_group,
			'general',
			array(
				'option_name'	=> $option_name,
				'id'			=> 'download_display',
				'options' 		=> array(
					'download'	=> __( 'Download PDF' , 'woocommerce-myparcel' ),
					'display'	=> __( 'Open de PDF in a new tab' , 'woocommerce-myparcel' ),
				),
			)
		);
		add_settings_field(
			'label_format',
			__( 'Label format', 'woocommerce-myparcel' ),
			array( $this->callbacks, 'radio_button' ),
			$option_group,
			'general',
			array(
				'option_name'	=> $option_name,
				'id'			=> 'label_format',
				'options' 		=> array(
					'A4'	=> __( 'Standard printer (A4)' , 'woocommerce-myparcel' ),
					'A6'	=> __( 'Label Printer (A6)' , 'woocommerce-myparcel' ),
				),
			)
		);

		add_settings_field(
			'print_position_offset',
			__( 'Ask for print start position', 'woocommerce-myparcel' ),
			array( $this->callbacks, 'checkbox' ),
			$option_group,
			'general',
			array(
				'option_name'	=> $option_name,
				'id'			=> 'print_position_offset',
				'description'	=> __( 'This option enables you to continue printing where you left off last time', 'woocommerce-myparcel' )
			)
		);

		add_settings_field(
			'email_tracktrace',
			__( 'Track&trace in email', 'woocommerce-myparcel' ),
			array( $this->callbacks, 'checkbox' ),
			$option_group,
			'general',
			array(
				'option_name'	=> $option_name,
				'id'			=> 'email_tracktrace',
				'description'	=> __( 'Add the track&trace code to emails to the customer.<br/><strong>Note!</strong> When you select this option, make sure you have not enabled the track & trace email in your MyParcel backend.', 'woocommerce-myparcel' )
			)
		);

		add_settings_field(
			'myaccount_tracktrace',
			__( 'Track&trace in My Account', 'woocommerce-myparcel' ),
			array( $this->callbacks, 'checkbox' ),
			$option_group,
			'general',
			array(
				'option_name'	=> $option_name,
				'id'			=> 'myaccount_tracktrace',
				'description'	=> __( 'Show track&trace trace code & link in My Account.', 'woocommerce-myparcel' )
			)
		);

		add_settings_field(
			'process_directly',
			__( 'Process shipments directly', 'woocommerce-myparcel' ),
			array( $this->callbacks, 'checkbox' ),
			$option_group,
			'general',
			array(
				'option_name'	=> $option_name,
				'id'			=> 'process_directly',
				'description'	=> __( 'When you enable this option, shipments will be directly processed when sent to myparcel.', 'woocommerce-myparcel' )
			)
		);

		add_settings_field(
			'order_status_automation',
			__( 'Order status automation', 'woocommerce-myparcel' ),
			array( $this->callbacks, 'checkbox' ),
			$option_group,
			'general',
			array(
				'option_name'	=> $option_name,
				'id'			=> 'order_status_automation',
				'description'	=> __( 'Automatically set order status to a predefined status after succesfull MyParcel export.<br/>Make sure <strong>Process shipments directly</strong> is enabled when you use this option together with the <strong>Track&trace in email</strong> option, otherwise the track&trace code will not be included in the customer email.', 'woocommerce-myparcel' )
			)
		);		

		add_settings_field(
			'automatic_order_status',
			__( 'Automatic order status', 'woocommerce-myparcel' ),
			array( $this->callbacks, 'order_status_select' ),
			$option_group,
			'general',
			array(
				'option_name'	=> $option_name,
				'id'			=> 'automatic_order_status',
				'class'			=> 'automatic_order_status',
			)
		);		

		add_settings_field(
			'keep_shipments',
			__( 'Keep old shipments', 'woocommerce-myparcel' ),
			array( $this->callbacks, 'checkbox' ),
			$option_group,
			'general',
			array(
				'option_name'	=> $option_name,
				'id'			=> 'keep_shipments',
				'default'		=> 0,
				'description'	=> __( 'With this option enabled, data from previous shipments (track & trace links) will be kept in the order when you export more than once.', 'woocommerce-myparcel' )
			)
		);

		add_settings_field(
			'barcode_in_note',
			__( 'Place barcode inside note', 'woocommerce-myparcel' ),
			array( $this->callbacks, 'checkbox' ),
			$option_group,
			'general',
			array(
				'option_name'	=> $option_name,
				'id'			=> 'barcode_in_note',
				'class'			=> 'barcode_in_note',
				'description'	=> __( 'Place the barcode inside a note of the order', 'woocommerce-myparcel' )
			)
		);

		add_settings_field(
			'barcode_in_note_titel',
			__( 'Titel before the barcode', 'woocommerce-myparcel' ),
			array( $this->callbacks, 'text_input' ),
			$option_group,
			'general',
			array(
				'option_name'	=> $option_name,
				'id'			=> 'barcode_in_note_titel',
				'class'			=> 'barcode_in_note_titel',
				'default'		=> 'Tracking code:',
				'description'	=> __( 'You can change the text before the barcode inside an note', 'woocommerce-myparcel' ),
			)
		);


		// Diagnostics section.
		add_settings_section(
			'diagnostics',
			__( 'Diagnostic tools', 'woocommerce-myparcel' ),
			array( $this->callbacks, 'section' ),
			$option_group
		);

		add_settings_field(
			'error_logging',
			__( 'Log API communication', 'woocommerce-myparcel' ),
			array( $this->callbacks, 'checkbox' ),
			$option_group,
			'diagnostics',
			array(
				'option_name'	=> $option_name,
				'id'			=> 'error_logging',
				'description'	=> '<a href="'.esc_url_raw( admin_url( 'admin.php?page=wc-status&tab=logs' ) ).'" target="_blank">'.__( 'View logs', 'woocommerce-myparcel' ).'</a> (wc-myparcel)',
			)
		);

	}

	/**
	 * Register Export defaults settings
	 */
	public function export_defaults_settings() {
		$option_group = 'woocommerce_myparcel_export_defaults_settings';

		// Register settings.
		$option_name = 'woocommerce_myparcel_export_defaults_settings';
		register_setting( $option_group, $option_name, array( $this->callbacks, 'validate' ) );

		// Create option in wp_options.
		if ( false === get_option( $option_name ) ) {
			$this->default_settings( $option_name );
		}

		// API section.
		add_settings_section(
			'defaults',
			__( 'Default export settings', 'woocommerce-myparcel' ),
			array( $this->callbacks, 'section' ),
			$option_group
		);


		if ( version_compare( WOOCOMMERCE_VERSION, '2.1', '>=' ) ) {
			add_settings_field(
				'shipping_methods_package_types',
				__( 'Package types', 'woocommerce-myparcel' ),
				array( $this->callbacks, 'shipping_methods_package_types' ),
				$option_group,
				'defaults',
				array(
					'option_name'	=> $option_name,
					'id'			=> 'shipping_methods_package_types',
					'package_types'	=> WooCommerce_MyParcel()->export->get_package_types(),
					'description'	=> __( 'Select one or more shipping methods for each MyParcel package type', 'woocommerce-myparcel' ),
				)
			);
		} else {
			add_settings_field(
				'package_type',
				__( 'Shipment type', 'woocommerce-myparcel' ),
				array( $this->callbacks, 'select' ),
				$option_group,
				'defaults',
				array(
					'option_name'	=> $option_name,
					'id'			=> 'package_type',
					'default'		=> '1',
					'options' 		=> WooCommerce_MyParcel()->export->get_package_types(),
				)
			);			
		}

		add_settings_field(
			'connect_email',
			__( 'Connect customer email', 'woocommerce-myparcel' ),
			array( $this->callbacks, 'checkbox' ),
			$option_group,
			'defaults',
			array(
				'option_name'	=> $option_name,
				'id'			=> 'connect_email',
				'description'	=> sprintf(__( 'When you connect the customer email, MyParcel can send a Track&Trace email to this address. In your %sMyParcel backend%s you can enable or disable this email and format it in your own style.', 'woocommerce-myparcel' ), '<a href="https://backoffice.myparcel.nl/ttsettingstable" target="_blank">', '</a>')
			)
		);

		add_settings_field(
			'connect_phone',
			__( 'Connect customer phone', 'woocommerce-myparcel' ),
			array( $this->callbacks, 'checkbox' ),
			$option_group,
			'defaults',
			array(
				'option_name'	=> $option_name,
				'id'			=> 'connect_phone',
				'description'	=> __( "When you connect the customer's phone number, the courier can use this for the delivery of the parcel. This greatly increases the delivery success rate for foreign shipments.", 'woocommerce-myparcel' )
			)
		);

		add_settings_field(
			'large_format',
			__( 'Extra large size', 'woocommerce-myparcel' ).' (+ &euro;2.45)',
			array( $this->callbacks, 'checkbox' ),
			$option_group,
			'defaults',
			array(
				'option_name'	=> $option_name,
				'id'			=> 'large_format',
				'description'	=> __( 'Enable this option when your shipment is bigger than 100 x 70 x 50 cm, but smaller than 175 x 78 x 58 cm. An extra fee of &euro;&nbsp;2,45 will be charged.<br/><strong>Note!</strong> If the parcel is bigger than 175 x 78 x 58 of or heavier than 30 kg, the pallet rate of &euro;&nbsp;70,00 will be charged.', 'woocommerce-myparcel' )
			)
		);
		
		add_settings_field(
			'only_recipient',
			__( 'Home address only', 'woocommerce-myparcel' ).' (+ &euro;0.29)',
			array( $this->callbacks, 'checkbox' ),
			$option_group,
			'defaults',
			array(
				'option_name'	=> $option_name,
				'id'			=> 'only_recipient',
				'description'	=> __( "If you don't want the parcel to be delivered at the neighbours, choose this option.", 'woocommerce-myparcel' )
			)
		);
		
		add_settings_field(
			'signature',
			__( 'Signature on delivery', 'woocommerce-myparcel' ).' (+ &euro;0.36)',
			array( $this->callbacks, 'checkbox' ),
			$option_group,
			'defaults',
			array(
				'option_name'	=> $option_name,
				'id'			=> 'signature',
				'description'	=> __( 'The parcel will be offered at the delivery address. If the recipient is not at home, the parcel will be delivered to the neighbours. In both cases, a signuture will be required.', 'woocommerce-myparcel' )
			)
		);
		
		add_settings_field(
			'return',
			__( 'Return if no answer', 'woocommerce-myparcel' ),
			array( $this->callbacks, 'checkbox' ),
			$option_group,
			'defaults',
			array(
				'option_name'	=> $option_name,
				'id'			=> 'return',
				'description'	=> __( 'By default, a parcel will be offered twice. After two unsuccessful delivery attempts, the parcel will be available at the nearest pickup point for two weeks. There it can be picked up by the recipient with the note that was left by the courier. If you want to receive the parcel back directly and NOT forward it to the pickup point, enable this option.', 'woocommerce-myparcel' )
			)
		);
		
		add_settings_field(
			'insured',
			__( 'Insured shipment (from + &euro;0.50)', 'woocommerce-myparcel' ),
			array( $this->callbacks, 'checkbox' ),
			$option_group,
			'defaults',
			array(
				'option_name'	=> $option_name,
				'id'			=> 'insured',
				'description'	=> __( 'By default, there is no insurance on the shipments. If you still want to insure the shipment, you can do that from &euro;0.50. We insure the purchase value of the shipment, with a maximum insured value of &euro; 5.000. Insured parcels always contain the options "Home address only" en "Signature for delivery"', 'woocommerce-myparcel' ),
				'class'			=> 'insured',
			)
		);

		add_settings_field(
			'insured_amount',
			__( 'Insured amount', 'woocommerce-myparcel' ),
			array( $this->callbacks, 'select' ),
			$option_group,
			'defaults',
			array(
				'option_name'	=> $option_name,
				'id'			=> 'insured_amount',
				'default'		=> 'standard',
				'class'			=> 'insured_amount',
				'options' 		=> array(
					'49'		=> __( 'Insured up to &euro; 50 (+ &euro; 0.50)' , 'woocommerce-myparcel' ),
					'249'		=> __( 'Insured up to  &euro; 250 (+ &euro; 1.00)' , 'woocommerce-myparcel' ),
					'499'		=> __( 'Insured up to  &euro; 500 (+ &euro; 1.65)' , 'woocommerce-myparcel' ),
					''			=> __( '> &euro; 500 insured (+ &euro; 1.65 / &euro; 500)' , 'woocommerce-myparcel' ),
				),
			)
		);

		add_settings_field(
			'insured_amount_custom',
			__( 'Insured amount (in euro)', 'woocommerce-myparcel' ),
			array( $this->callbacks, 'text_input' ),
			$option_group,
			'defaults',
			array(
				'option_name'	=> $option_name,
				'id'			=> 'insured_amount_custom',
				'size'			=> '5',
				'class'			=> 'insured_amount',
			)
		);

		add_settings_field(
			'label_description',
			__( 'Label description', 'woocommerce-myparcel' ),
			array( $this->callbacks, 'text_input' ),
			$option_group,
			'defaults',
			array(
				'option_name'	=> $option_name,
				'id'			=> 'label_description',
				'size'			=> '25',
				'description'	=> __( "With this option, you can add a description to the shipment. This will be printed on the top left of the label, and you can use this to search or sort shipments in the MyParcel Backend. Use <strong>[ORDER_NR]</strong> to include the order number, <strong>[DELIVERY_DATE]</strong> to include the delivery date.", 'woocommerce-myparcel' ),
			)
		);

		add_settings_field(
			'empty_parcel_weight',
			__( 'Empty parcel weight (grams)', 'woocommerce-myparcel' ),
			array( $this->callbacks, 'text_input' ),
			$option_group,
			'defaults',
			array(
				'option_name'	=> $option_name,
				'id'			=> 'empty_parcel_weight',
				'size'			=> '5',
				'description'	=> __( 'Default weight of your empty parcel, rounded to grams.', 'woocommerce-myparcel' ),
			)
		);

		// World Shipments section.
		add_settings_section(
			'world_shipments',
			__( 'World Shipments', 'woocommerce-myparcel' ),
			array( $this->callbacks, 'section' ),
			$option_group
		);

		add_settings_field(
			'hs_code',
			__( 'Default HS Code', 'woocommerce-myparcel' ),
			array( $this->callbacks, 'text_input' ),
			$option_group,
			'world_shipments',
			array(
				'option_name'	=> $option_name,
				'id'			=> 'hs_code',
				'size'			=> '5',
				'description'	=> sprintf(__( 'HS Codes are used for MyParcel world shipments, you can find the appropriate code on the %ssite of the Dutch Customs%s.', 'woocommerce-myparcel' ), '<a href="http://tarief.douane.nl/tariff/index.jsf" target="_blank">', '</a>')
			)
		);
		add_settings_field(
			'package_contents',
			__( 'Customs shipment type', 'woocommerce-myparcel' ),
			array( $this->callbacks, 'select' ),
			$option_group,
			'world_shipments',
			array(
				'option_name'	=> $option_name,
				'id'			=> 'package_contents',
				'options' 		=> array(
					1 => __( 'Commercial goods' , 'woocommerce-myparcel' ),
					2 => __( 'Commercial samples' , 'woocommerce-myparcel' ),
					3 => __( 'Documents' , 'woocommerce-myparcel' ),
					4 => __( 'Gifts' , 'woocommerce-myparcel' ),
					5 => __( 'Return shipment' , 'woocommerce-myparcel' ),
				),
			)
		);

	}

	/**
	 * Register Checkout settings
	 */
	public function checkout_settings() {
		$option_group = 'woocommerce_myparcel_checkout_settings';

		// Register settings.
		$option_name = 'woocommerce_myparcel_checkout_settings';
		register_setting( $option_group, $option_name, array( $this->callbacks, 'validate' ) );

		// Create option in wp_options.
		if ( false === get_option( $option_name ) ) {
			$this->default_settings( $option_name );
		}

        add_settings_field(
            'myparcel_checkout',
            __( 'Enable MyParcel delivery options', 'woocommerce-myparcel' ),
            array( $this->callbacks, 'checkbox' ),
            $option_group,
            'processing_parameters',
            array(
                'option_name'	=> $option_name,
                'id'			=> 'myparcel_checkout',
            )
        );

        add_settings_field(
            'processing_parameters',
            __( 'Display for', 'woocommerce-myparcel' ),
            array( $this->callbacks, 'select' ),
            $option_group,
            'processing_parameters',
            array(
                'option_name'	=> $option_name,
                'id'			=> 'checkout_display',
                'options' 		=> array(
                    'selected_methods'	=> __( 'Shipping methods associated with Parcels' , 'woocommerce-myparcel' ),
                    'all_methods'		=> __( 'All shipping methods' , 'woocommerce-myparcel' ),
                ),
                'description'	=> __( 'To associate specific shipping methods with parcels, see the Default export settings tab. Note that the delivery options will be automatically hidden for foreign addresses, regardless of this setting', 'woocommerce-myparcel' ),
            )
        );

        // Checkout options section.
        add_settings_section(
            'processing_parameters',
            __( 'Shipment processing parameters', 'woocommerce-myparcel' ),
            array( $this->callbacks, 'section' ),
            $option_group
        );

        $days_of_the_week = array(
            '0' => __( 'Sunday', 'woocommerce-myparcel' ),
            '1' => __( 'Monday', 'woocommerce-myparcel' ),
            '2' => __( 'Tuesday', 'woocommerce-myparcel' ),
            '3' => __( 'Wednesday', 'woocommerce-myparcel' ),
            '4' => __( 'Thursday', 'woocommerce-myparcel' ),
            '5' => __( 'Friday', 'woocommerce-myparcel' ),
            '6' => __( 'Saturday', 'woocommerce-myparcel' ),
        );

        add_settings_field(
            'dropoff_days',
            __( 'Dropoff days', 'woocommerce-myparcel' ),
            array( $this->callbacks, 'enhanced_select' ),
            $option_group,
            'processing_parameters',
            array(
                'option_name'	=> $option_name,
                'id'			=> 'dropoff_days',
                'options'		=> $days_of_the_week,
                'description'	=> __( 'Days of the week on which you hand over parcels to PostNL', 'woocommerce-myparcel' ),
            )
        );

        add_settings_field(
            'cutoff_time',
            __( 'Cut-off time', 'woocommerce-myparcel' ),
            array( $this->callbacks, 'text_input' ),
            $option_group,
            'processing_parameters',
            array(
                'option_name'	=> $option_name,
                'id'			=> 'cutoff_time',
                'type'			=> 'text',
                'size'			=> '5',
                'description'	=> __( 'Time at which you stop processing orders for the day (format: hh:mm)', 'woocommerce-myparcel' ),
            )
        );

        add_settings_field(
            'dropoff_delay',
            __( 'Dropoff delay', 'woocommerce-myparcel' ),
            array( $this->callbacks, 'text_input' ),
            $option_group,
            'processing_parameters',
            array(
                'option_name'	=> $option_name,
                'id'			=> 'dropoff_delay',
                'type'			=> 'number',
                'size'			=> '2',
                'description'	=> __( 'Number of days you take to process an order', 'woocommerce-myparcel' ),
            )
        );

        add_settings_field(
            'deliverydays_window',
            __( 'Delivery days window', 'woocommerce-myparcel' ),
            array( $this->callbacks, 'text_input' ),
            $option_group,
            'processing_parameters',
            array(
                'option_name'	=> $option_name,
                'id'			=> 'deliverydays_window',
                'type'			=> 'number',
                'size'			=> '2',
                'description'	=> __( 'Number of days you allow the customer to postpone a shipment', 'woocommerce-myparcel' ),
            )
        );











// Delivery options section.
        add_settings_section(
            'delivery_options',
            __( 'Delivery options', 'woocommerce-myparcel' ),
            array( $this->callbacks, 'section' ),
            $option_group
        );

        add_settings_field(
            'at_home_delivery',
            __( 'Home delivery titel', 'woocommerce-myparcel' ),
            array( $this->callbacks, 'text_input' ),
            $option_group,
            'delivery_options',
            array(
                'option_name'	=> $option_name,
                'id'			=> 'at_home_delivery_titel',
                'size'			=> '53',
                'titel'         => 'Delivered at home or at work',
                'current'       => 'Delivered at home or at work',
            )
        );

        add_settings_field(
            'standard',
            __( 'Standard delivery titel', 'woocommerce-myparcel' ),
            array( $this->callbacks, 'text_input' ),
            $option_group,
            'delivery_options',
            array(
                'option_name'	=> $option_name,
                'id'			=> 'standard_titel',
                'size'			=> '53',
                'titel'         => 'Standard delivery',
                'current'       => 'Standard delivery',
            )
        );

        add_settings_field(
            'morning',
            __( 'Morning delivery', 'woocommerce-myparcel' ),
            array( $this->callbacks, 'delivery_option_enable' ),
            $option_group,
            'delivery_options',
            array(
                'has_titel'     => true,
                'has_price'     => true,
                'option_name'   => $option_name,
                'id'            => 'morning',
                'titel'         => 'Morning delivery',
                'current'       => 'Morning delivery',
                'size'              => 30,
            )
        );
        
        add_settings_field(
            'night',
            __( 'Evening delivery', 'woocommerce-myparcel' ),
            array( $this->callbacks, 'delivery_option_enable' ),
            $option_group,
            'delivery_options',
            array(
                'has_titel'     => true,
                'has_price'     => true,
                'option_name'	=> $option_name,
                'id'			=> 'night',
                'titel'         => 'Evening delivery',
                'current' 	    => 'Evening delivery',
                'size'              => 30,
            )
        );

		add_settings_field(
			'only_recipient',
			__( 'Home address only', 'woocommerce-myparcel' ),
			array( $this->callbacks, 'delivery_option_enable' ),
			$option_group,
			'delivery_options',
			array(
                'has_titel'     => true,
				'has_price'     => true,
				'option_name'	=> $option_name,
				'id'			=> 'only_recipient',
                'titel'         => 'Home address only',
                'current'       => 'Home address only',
                'size'              => 30,
			)
		);

		add_settings_field(
			'signed',
			__( 'Signature on delivery', 'woocommerce-myparcel' ),
			array( $this->callbacks, 'delivery_option_enable' ),
			$option_group,
			'delivery_options',
			array(
                'has_titel'     => true,
				'has_price'     => true,
				'option_name'	=> $option_name,
				'id'			=> 'signed',
                'titel'         => 'Signature on delivery',
                'current' 	    => 'Signature on delivery',
                'size'              => 30,
			)
		);

		add_settings_field(
			'pickup',
			__( 'PostNL pickup', 'woocommerce-myparcel' ),
			array( $this->callbacks, 'delivery_option_enable' ),
			$option_group,
			'delivery_options',
			array(
                'has_titel'     => true,
				'has_price'     => true,
				'option_name'	=> $option_name,
				'id'			=> 'pickup',
                'titel'         => 'Pickup',
                'current' 	    => 'Pick up at PostNL location',
                'size'              => 30,
			)
		);

		add_settings_field(
			'pickup_express',
			__( 'Early PostNL pickup', 'woocommerce-myparcel' ),
			array( $this->callbacks, 'delivery_option_enable' ),
			$option_group,
			'delivery_options',
			array(
                'has_titel'     => true,
				'has_price'     => true,
				'option_name'	=> $option_name,
				'id'			=> 'pickup_express',
                'titel'         => 'Pickup express',
                'current' 	    => 'Early PostNL pickup',
                'size'              => 30,
			)
		);


        add_settings_field(
            'monday_delivery',
            __( 'Enable monday delivery', 'woocommerce-myparcel' ),
            array( $this->callbacks, 'delivery_option_enable' ),
            $option_group,
            'delivery_options',
            array(
                'has_titel'         => false,
                'has_price'         => false,
                'has_cutoff_time'   => true,
                'option_name'	    => $option_name,
                'id'			    => 'saturday_cutoff',
                'size'              => 30,
            )
        );

		// Customizations section   
		add_settings_section(
			'customizations',
			__( 'Customizations', 'woocommerce-myparcel' ),
			array( $this->callbacks, 'section' ),
			$option_group
		);

		add_settings_field(
			'custom_css',
			__( 'Custom styles', 'woocommerce-myparcel' ),
			array( $this->callbacks, 'textarea' ),
			$option_group,
			'customizations',
			array(
				'option_name'	=> $option_name,
				'id'			=> 'custom_css',
				'width'			=> '80',
				'height'		=> '8',
			)
		);
	}

	
	/**
	 * Set default settings.
	 * 
	 * @return void.
	 */
	public function default_settings( $option ) {
		// $default = array(
		// 	'process'			=> '1',
		// 	'keep_consignments'	=> '0',
		// 	'download_display'	=> 'download',
		// 	'email'				=> '1',
		// 	'telefoon'			=> '1',
		// 	'extragroot'		=> '0',
		// 	'huisadres'			=> '0',
		// 	'handtekening'		=> '0',
		// 	'huishand'			=> '0',
		// 	'retourbgg'			=> '0',
		// 	'verzekerd'			=> '0',
		// 	'verzekerdbedrag'	=> '0',
		// 	'kenmerk'			=> '',
		// 	'verpakkingsgewicht'=> '0',
		// );
	
		// add_option( 'wcmyparcel_settings', $default );

		switch ( $option ) {
			case 'woocommerce_myparcel_general_settings':
				$default = array(

				);
				break;
			case 'woocommerce_myparcel_checkout_settings':
				$default = array (
					'pickup_enabled' => '1',
					'dropoff_days' => array ( 1,2,3,4,5 ),
					'dropoff_delay' => '0',
					'deliverydays_window' => '1',
				);
				break;
			case 'woocommerce_myparcel_export_defaults_settings':
			default:
				$default = array();
				break;
		}

		if ( false === get_option( $option ) ) {
			add_option( $option, $default );
		} else {
			update_option( $option, $default );
		}
	}
}

endif; // class_exists

return new WooCommerce_MyParcel_Settings();