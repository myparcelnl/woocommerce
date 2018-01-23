<?php
/**
 * Create & render settings page
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( !class_exists( 'WooCommerce_PostNL_Settings' ) ) :

class WooCommerce_PostNL_Settings {

	public $options_page_hook;

	public function __construct() {
		$this->callbacks = include( 'class-wcmp-settings-callbacks.php' );
		add_action( 'admin_menu', array( $this, 'menu' ) );
		add_filter( 'plugin_action_links_'.WooCommerce_PostNL()->plugin_basename, array( $this, 'add_settings_link' ) );

		add_action( 'admin_init', array( $this, 'general_settings' ) );
		add_action( 'admin_init', array( $this, 'export_defaults_settings' ) );
		add_action( 'admin_init', array( $this, 'checkout_settings' ) );

		// notice for WC PostNL Belgium plugin
		add_action( 'woocommerce_postnl_before_settings_page', array( $this, 'postnl_be_notice'), 10, 1 );
	}

	/**
	 * Add settings item to WooCommerce menu
	 */
	public function menu() {
		add_submenu_page(
			'woocommerce',
			__( 'PostNL', 'woocommerce-postnl' ),
			__( 'PostNL', 'woocommerce-postnl' ),
			'manage_options',
			'woocommerce_postnl_settings',
			array( $this, 'settings_page' )
		);	
	}
	
	/**
	 * Add settings link to plugins page
	 */
	public function add_settings_link( $links ) {
		$settings_link = '<a href="admin.php?page=woocommerce_postnl_settings">'. __( 'Settings', 'woocommerce-postnl' ) . '</a>';
		array_push( $links, $settings_link );
		return $links;
	}

	public function settings_page() {
		$settings_tabs = apply_filters( 'woocommerce_postnl_settings_tabs', array (
				'general'			=> __( 'General', 'woocommerce-postnl' ),
				'export_defaults'	=> __( 'Default export settings', 'woocommerce-postnl' ),
				'checkout'			=> __( 'Checkout', 'woocommerce-postnl' ),
			)
		);

		$active_tab = isset( $_GET[ 'tab' ] ) ? $_GET[ 'tab' ] : 'general';
		?>
		<div class="wrap">
			<h1><?php _e( 'WooCommerce PostNL Settings', 'woocommerce-postnl' ); ?></h1>
			<h2 class="nav-tab-wrapper">
			<?php
			foreach ($settings_tabs as $tab_slug => $tab_title ) {
				printf('<a href="?page=woocommerce_postnl_settings&tab=%1$s" class="nav-tab nav-tab-%1$s %2$s">%3$s</a>', $tab_slug, (($active_tab == $tab_slug) ? 'nav-tab-active' : ''), $tab_title);
			}
			?>
			</h2>

			<?php do_action( 'woocommerce_postnl_before_settings_page', $active_tab ); ?>
				
			<form method="post" action="options.php" id="woocommerce-postnl-settings" class="wcmp_shipment_options">
				<?php
					do_action( 'woocommerce_postnl_before_settings', $active_tab );
					settings_fields( 'woocommerce_postnl_'.$active_tab.'_settings' );
					do_settings_sections( 'woocommerce_postnl_'.$active_tab.'_settings' );
					do_action( 'woocommerce_postnl_after_settings', $active_tab );

					submit_button();
				?>
			</form>

			<?php do_action( 'woocommerce_postnl_after_settings_page', $active_tab ); ?>

		</div>
		<?php
	}

	public function postnl_be_notice( $active_tab ) {
		$base_country = WC()->countries->get_base_country();

		// save or check option to hide notice
		if ( isset( $_GET['postnl_hide_be_notice'] ) ) {
			update_option( 'postnl_hide_be_notice', true );
			$hide_notice = true;
		} else {
			$hide_notice = get_option( 'postnl_hide_be_notice' );
		}

		// link to hide message when one of the premium extensions is installed
		if ( !$hide_notice && $base_country == 'BE' ) {
			$postnl_belgium_link = '<a href="https://wordpress.org/plugins/wc-postnl-belgium/" target="blank">WC PostNL Belgium</a>';
			$text = sprintf(__( 'It looks like your shop is based in Belgium. This plugin is for PostNL Netherlands. If you are using PostNL Belgium, download the %s plugin instead!', 'woocommerce-postnl' ), $postnl_belgium_link);
			$dismiss_button = sprintf('<a href="%s" style="display:inline-block; margin-top: 10px;">%s</a>', add_query_arg( 'postnl_hide_be_notice', 'true' ), __( 'Hide this message', 'woocommerce-postnl' ) );
			printf('<div class="notice notice-warning"><p>%s %s</p></div>', $text, $dismiss_button);
		}
	}
	
	/**
	 * Register General settings
	 */
	public function general_settings() {
		$option_group = 'woocommerce_postnl_general_settings';

		// Register settings.
		$option_name = 'woocommerce_postnl_general_settings';
		register_setting( $option_group, $option_name, array( $this->callbacks, 'validate' ) );

		// Create option in wp_options.
		if ( false === get_option( $option_name ) ) {
			$this->default_settings( $option_name );
		}

		// API section.
		add_settings_section(
			'api',
			__( 'API settings', 'woocommerce-postnl' ),
			array( $this->callbacks, 'section' ),
			$option_group
		);

		// add_settings_field(
		// 	'api_username',
		// 	__( 'Username', 'woocommerce-postnl' ),
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
			__( 'Key', 'woocommerce-postnl' ),
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
			__( 'General settings', 'woocommerce-postnl' ),
			array( $this->callbacks, 'section' ),
			$option_group
		);

		add_settings_field(
			'download_display',
			__( 'Label display', 'woocommerce-postnl' ),
			array( $this->callbacks, 'radio_button' ),
			$option_group,
			'general',
			array(
				'option_name'	=> $option_name,
				'id'			=> 'download_display',
				'options' 		=> array(
					'download'	=> __( 'Download PDF' , 'woocommerce-postnl' ),
					'display'	=> __( 'Open the PDF in a new tab' , 'woocommerce-postnl' ),
				),
			)
		);
		add_settings_field(
			'label_format',
			__( 'Label format', 'woocommerce-postnl' ),
			array( $this->callbacks, 'radio_button' ),
			$option_group,
			'general',
			array(
				'option_name'	=> $option_name,
				'id'			=> 'label_format',
				'options' 		=> array(
					'A4'	=> __( 'Standard printer (A4)' , 'woocommerce-postnl' ),
					'A6'	=> __( 'Label Printer (A6)' , 'woocommerce-postnl' ),
				),
			)
		);

		add_settings_field(
			'print_position_offset',
			__( 'Ask for print start position', 'woocommerce-postnl' ),
			array( $this->callbacks, 'checkbox' ),
			$option_group,
			'general',
			array(
				'option_name'	=> $option_name,
				'id'			=> 'print_position_offset',
				'description'	=> __( 'This option enables you to continue printing where you left off last time', 'woocommerce-postnl' )
			)
		);

		add_settings_field(
			'email_tracktrace',
			__( 'Track&trace in email', 'woocommerce-postnl' ),
			array( $this->callbacks, 'checkbox' ),
			$option_group,
			'general',
			array(
				'option_name'	=> $option_name,
				'id'			=> 'email_tracktrace',
				'description'	=> __( 'Set up the \'track & trace in email\' so that the track & trace of the order is included in your track & trace email.', 'woocommerce-postnl' )
			)
		);

		add_settings_field(
			'myaccount_tracktrace',
			__( 'Track&trace in My Account', 'woocommerce-postnl' ),
			array( $this->callbacks, 'checkbox' ),
			$option_group,
			'general',
			array(
				'option_name'	=> $option_name,
				'id'			=> 'myaccount_tracktrace',
				'description'	=> __( 'Show track&trace trace code & link in My Account.', 'woocommerce-postnl' )
			)
		);

		add_settings_field(
			'process_directly',
			__( 'Process shipments directly', 'woocommerce-postnl' ),
			array( $this->callbacks, 'checkbox' ),
			$option_group,
			'general',
			array(
				'option_name'	=> $option_name,
				'id'			=> 'process_directly',
				'description'	=> __( 'When you enable this option, shipments will be directly processed when sent to postnl.', 'woocommerce-postnl' )
			)
		);

		add_settings_field(
			'order_status_automation',
			__( 'Order status automation', 'woocommerce-postnl' ),
			array( $this->callbacks, 'checkbox' ),
			$option_group,
			'general',
			array(
				'option_name'	=> $option_name,
				'id'			=> 'order_status_automation',
				'description'	=> __( 'Automatically set order status to a predefined status after succesfull PostNL export.<br/>Make sure <strong>Process shipments directly</strong> is enabled when you use this option together with the <strong>Track&trace in email</strong> option, otherwise the track&trace code will not be included in the customer email.', 'woocommerce-postnl' )
			)
		);		

		add_settings_field(
			'automatic_order_status',
			__( 'Automatic order status', 'woocommerce-postnl' ),
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
			__( 'Keep old shipments', 'woocommerce-postnl' ),
			array( $this->callbacks, 'checkbox' ),
			$option_group,
			'general',
			array(
				'option_name'	=> $option_name,
				'id'			=> 'keep_shipments',
				'default'		=> 0,
				'description'	=> __( 'With this option enabled, data from previous shipments (track & trace links) will be kept in the order when you export more than once.', 'woocommerce-postnl' )
			)
		);

		// Diagnostics section.
		add_settings_section(
			'diagnostics',
			__( 'Diagnostic tools', 'woocommerce-postnl' ),
			array( $this->callbacks, 'section' ),
			$option_group
		);

		add_settings_field(
			'error_logging',
			__( 'Log API communication', 'woocommerce-postnl' ),
			array( $this->callbacks, 'checkbox' ),
			$option_group,
			'diagnostics',
			array(
				'option_name'	=> $option_name,
				'id'			=> 'error_logging',
				'description'	=> '<a href="'.esc_url_raw( admin_url( 'admin.php?page=wc-status&tab=logs' ) ).'" target="_blank">'.__( 'View logs', 'woocommerce-postnl' ).'</a> (wc-postnl)',
			)
		);

	}

	/**
	 * Register Export defaults settings
	 */
	public function export_defaults_settings() {
		$option_group = 'woocommerce_postnl_export_defaults_settings';

		// Register settings.
		$option_name = 'woocommerce_postnl_export_defaults_settings';
		register_setting( $option_group, $option_name, array( $this->callbacks, 'validate' ) );

		// Create option in wp_options.
		if ( false === get_option( $option_name ) ) {
			$this->default_settings( $option_name );
		}

		// API section.
		add_settings_section(
			'defaults',
			__( 'Default export settings', 'woocommerce-postnl' ),
			array( $this->callbacks, 'section' ),
			$option_group
		);


		if ( version_compare( WOOCOMMERCE_VERSION, '2.1', '>=' ) ) {
			add_settings_field(
				'shipping_methods_package_types',
				__( 'Package types', 'woocommerce-postnl' ),
				array( $this->callbacks, 'shipping_methods_package_types' ),
				$option_group,
				'defaults',
				array(
					'option_name'	=> $option_name,
					'id'			=> 'shipping_methods_package_types',
					'package_types'	=> WooCommerce_PostNL()->export->get_package_types(),
					'description'	=> __( 'Select one or more shipping methods for each PostNL package type', 'woocommerce-postnl' ),
				)
			);
		} else {
			add_settings_field(
				'package_type',
				__( 'Shipment type', 'woocommerce-postnl' ),
				array( $this->callbacks, 'select' ),
				$option_group,
				'defaults',
				array(
					'option_name'	=> $option_name,
					'id'			=> 'package_type',
					'default'		=> '1',
					'options' 		=> WooCommerce_PostNL()->export->get_package_types(),
				)
			);			
		}
		
		add_settings_field(
			'connect_phone',
			__( 'Connect customer phone', 'woocommerce-postnl' ),
			array( $this->callbacks, 'checkbox' ),
			$option_group,
			'defaults',
			array(
				'option_name'	=> $option_name,
				'id'			=> 'connect_phone',
				'description'	=> __( "When you connect the customer's phone number, the courier can use this for the delivery of the parcel. This greatly increases the delivery success rate for foreign shipments.", 'woocommerce-postnl' )
			)
		);
		
		add_settings_field(
			'only_recipient',
			__( 'Home address only', 'woocommerce-postnl' ),
			array( $this->callbacks, 'checkbox' ),
			$option_group,
			'defaults',
			array(
				'option_name'	=> $option_name,
				'id'			=> 'only_recipient',
				'description'	=> __( "If you don't want the parcel to be delivered at the neighbours, choose this option.", 'woocommerce-postnl' )
			)
		);
		
		add_settings_field(
			'signature',
			__( 'Signature on delivery', 'woocommerce-postnl'),
			array( $this->callbacks, 'checkbox' ),
			$option_group,
			'defaults',
			array(
				'option_name'	=> $option_name,
				'id'			=> 'signature',
				'description'	=> __( 'The parcel will be offered at the delivery address. If the recipient is not at home, the parcel will be delivered to the neighbours. In both cases, a signuture will be required.', 'woocommerce-postnl' )
			)
		);
		
		// add_settings_field(
		// 	'home_address_signature',
		// 	__( 'Home address only + signature on delivery', 'woocommerce-postnl' ).' (+ &euro;0.42)',
		// 	array( $this->callbacks, 'checkbox' ),
		// 	$option_group,
		// 	'defaults',
		// 	array(
		// 		'option_name'	=> $option_name,
		// 		'id'			=> 'home_address_signature',
		// 		'description'	=> __( 'This is the secure option. The parcel will only be delivered at the recipient address, who has to sign for delivery. This way you can be certain the parcel will be handed to the recipient.', 'woocommerce-postnl' )
		// 	)
		// );
		
		add_settings_field(
			'return',
			__( 'Return if no answer', 'woocommerce-postnl' ),
			array( $this->callbacks, 'checkbox' ),
			$option_group,
			'defaults',
			array(
				'option_name'	=> $option_name,
				'id'			=> 'return',
				'description'	=> __( 'By default, a parcel will be offered twice. After two unsuccessful delivery attempts, the parcel will be available at the nearest pickup point for two weeks. There it can be picked up by the recipient with the note that was left by the courier. If you want to receive the parcel back directly and NOT forward it to the pickup point, enable this option.', 'woocommerce-postnl' )
			)
		);
		
		add_settings_field(
			'insured',
			__( 'Insured shipment', 'woocommerce-postnl' ),
			array( $this->callbacks, 'checkbox' ),
			$option_group,
			'defaults',
			array(
				'option_name'	=> $option_name,
				'id'			=> 'insured',
				'description'	=> __( 'By default, there is no insurance on the shipments. If you still want to insure the shipment, you can use this function. We insure the purchase value of the shipment, with a maximum insured value of &euro; 5.000. Insured parcels always contain the options "Home address only" en "Signature for delivery"', 'woocommerce-postnl' ),
				'class'			=> 'insured',
			)
		);

		add_settings_field(
			'insured_amount',
			__( 'Insured amount', 'woocommerce-postnl' ),
			array( $this->callbacks, 'select' ),
			$option_group,
			'defaults',
			array(
				'option_name'	=> $option_name,
				'id'			=> 'insured_amount',
				'default'		=> 'standard',
				'class'			=> 'insured_amount',
				'options' 		=> array(
					'499'		=> __( 'Insured up to  &euro; 500' , 'woocommerce-postnl' ),
					''			=> __( '> &euro; 500 insured' , 'woocommerce-postnl' ),
				),
			)
		);

		add_settings_field(
			'insured_amount_custom',
			__( 'Insured amount (in euro)', 'woocommerce-postnl' ),
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
			__( 'Label description', 'woocommerce-postnl' ),
			array( $this->callbacks, 'text_input' ),
			$option_group,
			'defaults',
			array(
				'option_name'	=> $option_name,
				'id'			=> 'label_description',
				'size'			=> '25',
				'description'	=> __( "With this option, you can add a description to the shipment. This will be printed on the top left of the label, and you can use this to search or sort shipments in the PostNL Backend. Use <strong>[ORDER_NR]</strong> to include the order number, <strong>[DELIVERY_DATE]</strong> to include the delivery date.", 'woocommerce-postnl' ),
			)
		);

		add_settings_field(
			'empty_parcel_weight',
			__( 'Empty parcel weight (grams)', 'woocommerce-postnl' ),
			array( $this->callbacks, 'text_input' ),
			$option_group,
			'defaults',
			array(
				'option_name'	=> $option_name,
				'id'			=> 'empty_parcel_weight',
				'size'			=> '5',
				'description'	=> __( 'Default weight of your empty parcel, rounded to grams.', 'woocommerce-postnl' ),
			)
		);

		// World Shipments section.
		add_settings_section(
			'world_shipments',
			__( 'World Shipments', 'woocommerce-postnl' ),
			array( $this->callbacks, 'section' ),
			$option_group
		);

		add_settings_field(
			'hs_code',
			__( 'Default HS Code', 'woocommerce-postnl' ),
			array( $this->callbacks, 'text_input' ),
			$option_group,
			'world_shipments',
			array(
				'option_name'	=> $option_name,
				'id'			=> 'hs_code',
                'size'			=> '5',
                'description'	=> sprintf(__( 'You can find HS codes on the %ssite of the Dutch Customs%s.', 'woocommerce-postnl' ), '<a href="http://tarief.douane.nl/tariff/index.jsf" target="_blank">', '</a>')

            )
		);
		add_settings_field(
			'package_contents',
			__( 'Customs shipment type', 'woocommerce-postnl' ),
			array( $this->callbacks, 'select' ),
			$option_group,
			'world_shipments',
			array(
				'option_name'	=> $option_name,
				'id'			=> 'package_contents',
				'options' 		=> array(
					1 => __( 'Commercial goods' , 'woocommerce-postnl' ),
					2 => __( 'Commercial samples' , 'woocommerce-postnl' ),
					3 => __( 'Documents' , 'woocommerce-postnl' ),
					4 => __( 'Gifts' , 'woocommerce-postnl' ),
					5 => __( 'Return shipment' , 'woocommerce-postnl' ),
				),
			)
		);

	}

	/**
	 * Register Checkout settings
	 */
	public function checkout_settings() {
		$option_group = 'woocommerce_postnl_checkout_settings';

		// Register settings.
		$option_name = 'woocommerce_postnl_checkout_settings';
		register_setting( $option_group, $option_name, array( $this->callbacks, 'validate' ) );

		// Create option in wp_options.
		if ( false === get_option( $option_name ) ) {
			$this->default_settings( $option_name );
		}

		// Delivery options section.
		add_settings_section(
			'delivery_options',
			__( 'Delivery options', 'woocommerce-postnl' ),
			array( $this->callbacks, 'section' ),
			$option_group
		);


		add_settings_field(
			'postnl_checkout',
			__( 'Enable PostNL delivery options', 'woocommerce-postnl' ),
			array( $this->callbacks, 'checkbox' ),
			$option_group,
			'delivery_options',
			array(
				'option_name'	=> $option_name,
				'id'			=> 'postnl_checkout',
			)
		);

		add_settings_field(
			'checkout_display',
			__( 'Display for', 'woocommerce-postnl' ),
			array( $this->callbacks, 'select' ),
			$option_group,
			'delivery_options',
			array(
				'option_name'	=> $option_name,
				'id'			=> 'checkout_display',
				'options' 		=> array(
					'selected_methods'	=> __( 'Shipping methods associated with Parcels' , 'woocommerce-postnl' ),
					'all_methods'		=> __( 'All shipping methods' , 'woocommerce-postnl' ),
				),
				'description'	=> __( 'To associate specific shipping methods with parcels, see the Default export settings tab. Note that the delivery options will be automatically hidden for foreign addresses, regardless of this setting', 'woocommerce-postnl' ),
			)
		);

		add_settings_field(
			'only_recipient',
			__( 'Home address only', 'woocommerce-postnl' ),
			array( $this->callbacks, 'delivery_option_enable' ),
			$option_group,
			'delivery_options',
			array(
				'option_name'	=> $option_name,
				'id'			=> 'only_recipient',
			)
		);

		add_settings_field(
			'signed',
			__( 'Signature on delivery', 'woocommerce-postnl' ),
			array( $this->callbacks, 'delivery_option_enable' ),
			$option_group,
			'delivery_options',
			array(
				'option_name'	=> $option_name,
				'id'			=> 'signed',
			)
		);

		add_settings_field(
			'night',
			__( 'Evening delivery', 'woocommerce-postnl' ),
			array( $this->callbacks, 'delivery_option_enable' ),
			$option_group,
			'delivery_options',
			array(
				'option_name'	=> $option_name,
				'id'			=> 'night',
			)
		);

		add_settings_field(
			'morning',
			__( 'Morning delivery', 'woocommerce-postnl' ),
			array( $this->callbacks, 'delivery_option_enable' ),
			$option_group,
			'delivery_options',
			array(
				'option_name'	=> $option_name,
				'id'			=> 'morning',
			)
		);

		add_settings_field(
			'pickup',
			__( 'PostNL pickup', 'woocommerce-postnl' ),
			array( $this->callbacks, 'delivery_option_enable' ),
			$option_group,
			'delivery_options',
			array(
				'option_name'	=> $option_name,
				'id'			=> 'pickup',
			)
		);

		add_settings_field(
			'pickup_express',
			__( 'Early PostNL pickup', 'woocommerce-postnl' ),
			array( $this->callbacks, 'delivery_option_enable' ),
			$option_group,
			'delivery_options',
			array(
				'option_name'	=> $option_name,
				'id'			=> 'pickup_express',
			)
		);

		// Checkout options section.
		add_settings_section(
			'processing_parameters',
			__( 'Shipment processing parameters', 'woocommerce-postnl' ),
			array( $this->callbacks, 'section' ),
			$option_group
		);

		$days_of_the_week = array(
			'0' => __( 'Sunday', 'woocommerce-postnl' ),
			'1' => __( 'Monday', 'woocommerce-postnl' ),
			'2' => __( 'Tuesday', 'woocommerce-postnl' ),
			'3' => __( 'Wednesday', 'woocommerce-postnl' ),
			'4' => __( 'Thursday', 'woocommerce-postnl' ),
			'5' => __( 'Friday', 'woocommerce-postnl' ),
			'6' => __( 'Saturday', 'woocommerce-postnl' ),
		);

		add_settings_field(
			'dropoff_days',
			__( 'Dropoff days', 'woocommerce-postnl' ),
			array( $this->callbacks, 'enhanced_select' ),
			$option_group,
			'processing_parameters',
			array(
				'option_name'	=> $option_name,
				'id'			=> 'dropoff_days',
				'options'		=> $days_of_the_week,
				'description'	=> __( 'Days of the week on which you hand over parcels to PostNL', 'woocommerce-postnl' ),
			)
		);

		add_settings_field(
			'cutoff_time',
			__( 'Cut-off time', 'woocommerce-postnl' ),
			array( $this->callbacks, 'text_input' ),
			$option_group,
			'processing_parameters',
			array(
				'option_name'	=> $option_name,
				'id'			=> 'cutoff_time',
				'type'			=> 'text',
				'size'			=> '5',
				'description'	=> __( 'Time at which you stop processing orders for the day (format: hh:mm)', 'woocommerce-postnl' ),
			)
		);

		add_settings_field(
			'dropoff_delay',
			__( 'Dropoff delay', 'woocommerce-postnl' ),
			array( $this->callbacks, 'text_input' ),
			$option_group,
			'processing_parameters',
			array(
				'option_name'	=> $option_name,
				'id'			=> 'dropoff_delay',
				'type'			=> 'number',
				'size'			=> '2',
				'description'	=> __( 'Number of days you take to process an order', 'woocommerce-postnl' ),
			)
		);

		add_settings_field(
			'deliverydays_window',
			__( 'Delivery days window', 'woocommerce-postnl' ),
			array( $this->callbacks, 'text_input' ),
			$option_group,
			'processing_parameters',
			array(
				'option_name'	=> $option_name,
				'id'			=> 'deliverydays_window',
				'type'			=> 'number',
				'size'			=> '2',
				'description'	=> __( 'Number of days you allow the customer to postpone a shipment', 'woocommerce-postnl' ),
			)
		);

		add_settings_field(
			'monday_delivery',
			__( 'Enable monday delivery', 'woocommerce-postnl' ),
			array( $this->callbacks, 'checkbox' ),
			$option_group,
			'processing_parameters',
			array(
				'option_name'	=> $option_name,
				'id'			=> 'monday_delivery',
			)
		);

		add_settings_field(
			'saturday_cutoff_time',
			__( 'Cut-off time for monday delivery', 'woocommerce-postnl' ),
			array( $this->callbacks, 'text_input' ),
			$option_group,
			'processing_parameters',
			array(
				'option_name'	=> $option_name,
				'id'			=> 'saturday_cutoff_time',
				'type'			=> 'text',
				'size'			=> '5',
				'description'	=> __( 'Time at which you stop processing orders on saturday for monday delivery (format: hh:mm)', 'woocommerce-postnl' ),
			)
		);

		// Customizations section
		add_settings_section(
			'customizations',
			__( 'Customizations', 'woocommerce-postnl' ),
			array( $this->callbacks, 'section' ),
			$option_group
		);

		add_settings_field(
			'base_color',
			__( 'Base color', 'woocommerce-postnl' ),
			array( $this->callbacks, 'color_picker' ),
			$option_group,
			'customizations',
			array(
				'option_name'	=> $option_name,
				'id'			=> 'base_color',
				'size'			=> '10',
				'description'	=> __( 'Color of the header & tabs (cyan by default)', 'woocommerce-postnl' ),
			)
		);


		add_settings_field(
			'highlight_color',
			__( 'Highlight color', 'woocommerce-postnl' ),
			array( $this->callbacks, 'color_picker' ),
			$option_group,
			'customizations',
			array(
				'option_name'	=> $option_name,
				'id'			=> 'highlight_color',
				'size'			=> '10',
				'description'	=> __( 'Color of the selections/highlights (orange by default)', 'woocommerce-postnl' ),
			)
		);

		add_settings_field(
			'custom_css',
			__( 'Custom styles', 'woocommerce-postnl' ),
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

		add_settings_field(
			'autoload_google_fonts',
			__( 'Automatically load Google fonts', 'woocommerce-postnl' ),
			array( $this->callbacks, 'checkbox' ),
			$option_group,
			'customizations',
			array(
				'option_name'	=> $option_name,
				'id'			=> 'autoload_google_fonts',
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
	
		// add_option( 'wcpostnl_settings', $default );

		switch ( $option ) {
			case 'woocommerce_postnl_general_settings':
				$default = array(

				);
				break;
			case 'woocommerce_postnl_checkout_settings':
				$default = array (
					'pickup_enabled' => '1',
					'dropoff_days' => array ( 1,2,3,4,5 ),
					'dropoff_delay' => '0',
					'deliverydays_window' => '1',
				);
				break;
			case 'woocommerce_postnl_export_defaults_settings':
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

return new WooCommerce_PostNL_Settings();