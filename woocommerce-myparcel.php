<?php
/*
Plugin Name: WooCommerce MyParcel
Plugin URI: http://www.myparcel.nl
Description: Export your WooCommerce orders to MyParcel (www.myparcel.nl) and print labels directly from the WooCommerce admin
Author: Ewout Fernhout
Author URI: http://www.wpovernight.com
Version: 2.0
Text Domain: woocommerce-myparcel

License: GPLv3 or later
License URI: http://www.opensource.org/licenses/gpl-license.php
*/

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( !class_exists( 'WooCommerce_MyParcel' ) ) :

class WooCommerce_MyParcel {

	public $version = '2.0';
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
		 * 		- WP_LANG_DIR/wc-payment-reminders/woocommerce-myparcel-LOCALE.mo
		 * 	 	- WP_LANG_DIR/plugins/woocommerce-myparcel-LOCALE.mo
		 * 	 	- wc-payment-reminders/languages/woocommerce-myparcel-LOCALE.mo (which if not found falls back to:)
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
		// include_once( 'includes/class-wcmp-writepanel.php' );
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
		// Set default settings?
	}

	/**
	 * Plugin upgrade method.  Perform any required upgrades here
	 *
	 * @param string $installed_version the currently installed ('old') version
	 */
	protected function upgrade( $installed_version ) {
		// Copy old settings
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