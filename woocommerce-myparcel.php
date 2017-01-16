<?php
/*
Plugin Name: WooCommerce MyParcel
Plugin URI: http://www.myparcel.nl
Description: Export your WooCommerce orders to MyParcel (www.myparcel.nl) and print labels directly from the WooCommerce admin
Author: Ewout Fernhout
Author URI: http://www.wpovernight.com
Version: 2.1.2
Text Domain: woocommerce-myparcel

License: GPLv3 or later
License URI: http://www.opensource.org/licenses/gpl-license.php
*/

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( !class_exists( 'WooCommerce_MyParcel' ) ) :

class WooCommerce_MyParcel {

	public $version = '2.1.2';
	public $plugin_basename;

	protected static $_instance = null;

	/**
	 * Main Plugin Instance
	 *
	 * Ensures only one instance of plugin is loaded or can be loaded.
	 */
	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}

	/**
	 * Constructor
	 */
	 		
	public function __construct() {
		$this->define( 'WC_MYPARCEL_VERSION', $this->version );
		$this->plugin_basename = plugin_basename(__FILE__);

		// Load settings
		$this->general_settings = get_option( 'woocommerce_myparcel_general_settings' );
		$this->export_defaults = get_option( 'woocommerce_myparcel_export_defaults_settings' );
		$this->checkout_settings = get_option( 'woocommerce_myparcel_checkout_settings' );

		// load the localisation & classes
		add_action( 'plugins_loaded', array( $this, 'translations' ) );
		add_action( 'init', array( $this, 'load_classes' ) );

		// run lifecycle methods
		if ( is_admin() && ! defined( 'DOING_AJAX' ) ) {
			add_action( 'wp_loaded', array( $this, 'do_install' ) );
		}
	}

	/**
	 * Define constant if not already set
	 * @param  string $name
	 * @param  string|bool $value
	 */
	private function define( $name, $value ) {
		if ( ! defined( $name ) ) {
			define( $name, $value );
		}
	}

	/**
	 * Load the translation / textdomain files
	 * 
	 * Note: the first-loaded translation file overrides any following ones if the same translation is present
	 */
	public function translations() {
		$locale = apply_filters( 'plugin_locale', get_locale(), 'woocommerce-myparcel' );
		$dir    = trailingslashit( WP_LANG_DIR );

		/**
		 * Frontend/global Locale. Looks in:
		 *
		 * 		- WP_LANG_DIR/woocommerce-myparcel/woocommerce-myparcel-LOCALE.mo
		 * 	 	- WP_LANG_DIR/plugins/woocommerce-myparcel-LOCALE.mo
		 * 	 	- woocommerce-myparcel/languages/woocommerce-myparcel-LOCALE.mo (which if not found falls back to:)
		 * 	 	- WP_LANG_DIR/plugins/woocommerce-myparcel-LOCALE.mo
		 */
		load_textdomain( 'woocommerce-myparcel', $dir . 'woocommerce-myparcel/woocommerce-myparcel-' . $locale . '.mo' );
		load_textdomain( 'woocommerce-myparcel', $dir . 'plugins/woocommerce-myparcel-' . $locale . '.mo' );
		load_plugin_textdomain( 'woocommerce-myparcel', false, dirname( plugin_basename(__FILE__) ) . '/languages' );
	}

	/**
	 * Load the main plugin classes and functions
	 */
	public function includes() {
		include_once( 'includes/class-wcmp-assets.php' );
		$this->admin = include_once( 'includes/class-wcmp-admin.php' );
		include_once( 'includes/class-wcmp-frontend.php' );
		include_once( 'includes/class-wcmp-settings.php' );
		$this->export = include_once( 'includes/class-wcmp-export.php' );
		include_once( 'includes/class-wcmp-nlpostcode-fields.php' );
	}

	/**
	 * Instantiate classes when woocommerce is activated
	 */
	public function load_classes() {
		if ( $this->is_woocommerce_activated() ) {
			$this->includes();
		} else {
			// display notice instead
			add_action( 'admin_notices', array ( $this, 'need_woocommerce' ) );
		}

	}

	/**
	 * Check if woocommerce is activated
	 */
	public function is_woocommerce_activated() {
		$blog_plugins = get_option( 'active_plugins', array() );
		$site_plugins = get_site_option( 'active_sitewide_plugins', array() );

		if ( in_array( 'woocommerce/woocommerce.php', $blog_plugins ) || isset( $site_plugins['woocommerce/woocommerce.php'] ) ) {
			return true;
		} else {
			return false;
		}
	}
	
	/**
	 * WooCommerce not active notice.
	 *
	 * @return string Fallack notice.
	 */
	 
	public function need_woocommerce() {
		$error = sprintf( __( 'WooCommerce MyParcel requires %sWooCommerce%s to be installed & activated!' , 'woocommerce-myparcel' ), '<a href="http://wordpress.org/extend/plugins/woocommerce/">', '</a>' );

		$message = '<div class="error"><p>' . $error . '</p></div>';
	
		echo $message;
	}

	/** Lifecycle methods *******************************************************
	 * Because register_activation_hook only runs when the plugin is manually
	 * activated by the user, we're checking the current version against the
	 * version stored in the database
	****************************************************************************/

	/**
	 * Handles version checking
	 */
	public function do_install() {
		$version_setting = 'woocommerce_myparcel_version';
		$installed_version = get_option( $version_setting );

		// installed version lower than plugin version?
		if ( version_compare( $installed_version, $this->version, '<' ) ) {

			if ( ! $installed_version ) {
				$this->install();
			} else {
				$this->upgrade( $installed_version );
			}

			// new version number
			update_option( $version_setting, $this->version );
		}
	}


	/**
	 * Plugin install method. Perform any installation tasks here
	 */
	protected function install() {
		// copy old settings if available (pre 2.0 didn't store the version, so technically, this is a new install)
		$old_settings = get_option( 'wcmyparcel_settings' );
		if (!empty($old_settings)) {
			// copy old settins to new
			// Deprecated
			// api_username
			// pakjegemak
			// pakjegemak_description
			// pakjegemak_button
			// shipment_type
			// huishand

			// map old key => new_key
			$general_settings_keys = array(
				'api_key'				=> 'api_key',
				'download_display'		=> 'download_display',
				'email_tracktrace'		=> 'email_tracktrace',
				'myaccount_tracktrace'	=> 'myaccount_tracktrace',
				'process'				=> 'process_directly',
				'keep_consignments'		=> 'keep_shipments',
				'error_logging'			=> 'error_logging',
			);

			$general_settings = array();
			foreach ($general_settings_keys as $old_key => $new_key) {
				if (!empty($old_settings[$old_key])) {
					$general_settings[$new_key] = $old_settings[$old_key];
				}
			}
			// auto_complete breaks down into:
			// order_status_automation & automatic_order_status
			if (!empty($old_settings['auto_complete'])) {
				$general_settings['order_status_automation'] = 1;
				$general_settings['automatic_order_status'] = 'completed';
			}
			
			// map old key => new_key
			$defaults_settings_keys = array(
				'email'					=> 'connect_email',
				'telefoon'				=> 'connect_phone',
				'extragroot'			=> 'large_format',
				'huisadres'				=> 'only_recipient',
				'handtekening'			=> 'signature',
				'retourbgg'				=> 'return',
				'kenmerk'				=> 'label_description',
				'verpakkingsgewicht'	=> 'empty_parcel_weight',
				'verzekerd'				=> 'insured',
				'verzekerdbedrag'		=> 'insured_amount',
			);
			$defaults_settings = array();
			foreach ($defaults_settings_keys as $old_key => $new_key) {
				if (!empty($old_settings[$old_key])) {
					$defaults_settings[$new_key] = $old_settings[$old_key];
				}
			}
			// set custom insurance amount
			if (!empty($defaults_settings['insured']) && (int) $defaults_settings['insured_amount'] > 249) {
				$defaults_settings['insured_amount'] = 0;
				$defaults_settings['insured_amount_custom'] = $old_settings['verzekerdbedrag'];
			}
			
			// add options
			update_option( 'woocommerce_myparcel_general_settings', $general_settings );
			update_option( 'woocommerce_myparcel_export_defaults_settings', $defaults_settings );
		}
	}

	/**
	 * Plugin upgrade method.  Perform any required upgrades here
	 *
	 * @param string $installed_version the currently installed ('old') version
	 */
	protected function upgrade( $installed_version ) {
		# stub
	}		

	/**
	 * Get the plugin url.
	 * @return string
	 */
	public function plugin_url() {
		return untrailingslashit( plugins_url( '/', __FILE__ ) );
	}

	/**
	 * Get the plugin path.
	 * @return string
	 */
	public function plugin_path() {
		return untrailingslashit( plugin_dir_path( __FILE__ ) );
	}

} // class WooCommerce_MyParcel

endif; // class_exists

/**
 * Returns the main instance of the plugin class to prevent the need to use globals.
 *
 * @since  2.0
 * @return WooCommerce_MyParcel
 */
function WooCommerce_MyParcel() {
	return WooCommerce_MyParcel::instance();
}

WooCommerce_MyParcel(); // load plugin