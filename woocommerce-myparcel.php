<?php
/*
Plugin Name: WC MyParcel Belgium
Plugin URI: http://sendmyparcel.be/
Description: Export your WooCommerce orders to MyParcel BE (http://sendmyparcel.be/) and print labels directly from the WooCommerce admin
Author: Richard Perdaan
Version: 4.0.0
Text Domain: wcmyparcelbe_be

License: GPLv3 or later
License URI: http://www.opensource.org/licenses/gpl-license.php
*/

use MyParcelNL\Sdk\src\Model\Consignment\BpostConsignment;
use MyParcelNL\Sdk\src\Model\Consignment\DPDConsignment;
use WPO\WC\MyParcelBE\Collections\SettingsCollection;

if ( ! defined('ABSPATH')) exit; // Exit if accessed directly

if ( ! class_exists('WooCommerce_MyParcelBE')) :

class WooCommerce_MyParcelBE
{

    public $version = '4.0.0';
    public $plugin_basename;

    protected static $_instance = null;

    /**
     * @var string
     */
    private $minimumPhpVersion = '5.4';

    /**
     * @var string
     */
    private $legacySettingsPhpVersion = '7.1';

    /**
     * @var string
     */
    private $recommendedPhpVersion = '7.1';

    /**
     * @var WPO\WC\MyParcelBE\Collections\SettingsCollection
     */
    public $setting_collection;

    /**
     * @var string
     */
    public $includes;

    public $export;

    private $admin;

    /**
     * Main Plugin Instance
     * Ensures only one instance of plugin is loaded or can be loaded.
     */
    public static function instance()
    {
        if (is_null(self::$_instance)) {
            self::$_instance = new self();
        }

        return self::$_instance;
    }

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->define('WC_MYPARCEL_BE_VERSION', $this->version);
        $this->define('WC_CHANNEL_ENGINE_ACTIVE', class_exists('Channel_Engine'));
        $this->plugin_basename = plugin_basename(__FILE__);

        // load the localisation & classes
        add_action('plugins_loaded', [$this, 'translations']);
        add_action('init', [$this, 'load_classes']);

        // run lifecycle methods
        if (is_admin() && !defined('DOING_AJAX')) {
            add_action('wp_loaded', [$this, 'do_install']);
        }
    }

    /**
     * Define constant if not already set
     *
     * @param string      $name
     * @param string|bool $value
     */
    private function define($name, $value)
    {
        if (!defined($name)) {
            define($name, $value);
        }
    }

    /**
     * Load the translation / text-domain files
     * Note: the first-loaded translation file overrides any following ones if the same translation is present
     */
    public function translations()
    {
        $locale = apply_filters('plugin_locale', get_locale(), 'woocommerce-myparcelbe');
        $dir    = trailingslashit(WP_LANG_DIR);

        /**
         * Frontend/global Locale. Looks in:
         *        - WP_LANG_DIR/woocommerce-myparcelbe/woocommerce-myparcelbe-LOCALE.mo
         *        - WP_LANG_DIR/plugins/woocommerce-myparcelbe-LOCALE.mo
         *        - woocommerce-myparcelbe/languages/woocommerce-myparcelbe-LOCALE.mo (which if not found falls back to:)
         *        - WP_LANG_DIR/plugins/woocommerce-myparcelbe-LOCALE.mo
         */
        load_textdomain(
            'woocommerce-myparcelbe',
            $dir . 'woocommerce-myparcelbe/woocommerce-myparcelbe-' . $locale . '.mo'
        );
        load_textdomain('woocommerce-myparcelbe', $dir . 'plugins/woocommerce-myparcelbe-' . $locale . '.mo');
        load_plugin_textdomain('woocommerce-myparcelbe', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }

    /**
     * Load the main plugin classes and functions
     */
    public function includes()
    {
        // Use php version 5.6
        if (!$this->phpVersionMeets($this->legacySettingsPhpVersion)) {
            $this->includes = $this->plugin_path() . '/includes_php56';

            // include compatibility classes
            require_once('includes_php56/compatibility/abstract-wc-data-compatibility.php');
            require_once('includes_php56/compatibility/class-wc-date-compatibility.php');
            require_once('includes_php56/compatibility/class-wc-core-compatibility.php');
            require_once('includes_php56/compatibility/class-wc-order-compatibility.php');
            require_once('includes_php56/compatibility/class-wc-product-compatibility.php');

            require_once('includes_php56/class-wcmp-assets.php');
            $this->admin = require_once('includes_php56/class-wcmp-admin.php');
            require_once('includes_php56/class-wcmp-frontend-settings.php');
            require_once('includes_php56/class-wcmp-frontend.php');
            require_once('includes_php56/class-wcmp-settings.php');
            $this->export = require_once('includes_php56/class-wcmp-export.php');
            require_once('includes_php56/class-wcmp-bepostcode-fields.php');

            return;
        }

        $this->includes = $this->plugin_path() . '/includes_php71';
        // Use minimum php version 7.1
        require_once('includes_php71/vendor/autoload.php');

        // include compatibility classes
        require_once('includes_php71/compatibility/abstract-wc-data-compatibility.php');
        require_once('includes_php71/compatibility/class-wc-date-compatibility.php');
        require_once('includes_php71/compatibility/class-wc-core-compatibility.php');
        require_once('includes_php71/compatibility/class-wc-order-compatibility.php');
        require_once('includes_php71/compatibility/class-wc-product-compatibility.php');

        require_once('includes_php71/collections/settings-collection.php');
        require_once('includes_php71/entities/setting.php');

        require_once('includes_php71/class-wcmp-assets.php');
        $this->admin = require_once('includes_php71/class-wcmp-admin.php');
        require_once('includes_php71/class-wcmp-checkout.php');
        require_once('includes_php71/class-wcmp-settings.php');
        $this->export = require_once('includes_php71/class-wcmp-export.php');
        require_once('includes_php71/class-wcmp-bepostcode-fields.php');
    }

    /**
     * Instantiate classes when woocommerce is activated
     */
    public function load_classes()
    {
        if ($this->is_woocommerce_activated() === false) {
            add_action('admin_notices', [$this, 'need_woocommerce']);

            return;
        }

        if (!$this->phpVersionMeets($this->minimumPhpVersion)) {
            add_action('admin_notices', [$this, 'required_php_version']);

            return;
        }

        // all systems ready - GO!
        $this->includes();

        $this->initSettings();
    }

    /**
     * Check if woocommerce is activated
     */
    public function is_woocommerce_activated()
    {
        $blog_plugins = get_option('active_plugins', []);
        $site_plugins = get_site_option('active_sitewide_plugins', []);

        if (in_array('woocommerce/woocommerce.php', $blog_plugins)
            || isset($site_plugins['woocommerce/woocommerce.php'])) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * WooCommerce not active notice.
     */
    public function need_woocommerce()
    {
        $error =
            sprintf(
                __(
                    'WooCommerce MyParcel BE requires %sWooCommerce%s to be installed & activated!',
                    'woocommerce-myparcelbe'
                ),
                '<a href="http://wordpress.org/extend/plugins/woocommerce/">',
                '</a>'
            );

        $message = '<div class="error"><p>' . $error . '</p></div>';

        echo $message;
    }

    /**
     * PHP version requirement notice
     */

    public function required_php_version()
    {
        $error         =
            __(
                'WooCommerce MyParcel BE requires PHP 5.4 or higher (5.6 or later recommended).',
                'woocommerce-myparcelbe'
            );
        $how_to_update = __('How to update your PHP version', 'woocommerce-myparcelbe');
        $message       =
            sprintf(
                '<div class="error"><p>%s</p><p><a href="%s">%s</a></p></div>',
                $error,
                'http://docs.wpovernight.com/general/how-to-update-your-php-version/',
                $how_to_update
            );

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
    public function do_install()
    {
        $version_setting   = 'woocommerce_myparcelbe_version';
        $installed_version = get_option($version_setting);

        // installed version lower than plugin version?
        if (version_compare($installed_version, $this->version, '<')) {
            if (!$installed_version) {
                $this->install();
            } else {
                $this->upgrade($installed_version);
            }

            // new version number
            update_option($version_setting, $this->version);
        }
    }

    /**
     * Plugin install method. Perform any installation tasks here
     */
    protected function install()
    {
        // copy old settings if available (pre 2.0 didn't store the version, so technically, this is a new install)
        $old_settings = get_option('wcmyparcelbe_settings');
        if (!empty($old_settings)) {
            // map old key => new_key
            $general_settings_keys = [
                'api_key'              => 'api_key',
                'download_display'     => 'download_display',
                'email_tracktrace'     => 'email_tracktrace',
                'myaccount_tracktrace' => 'myaccount_tracktrace',
                'process'              => 'process_directly',
                'barcode_in_note'      => 'barcode_in_note',
                'keep_consignments'    => 'keep_shipments',
                'error_logging'        => 'error_logging',
            ];

            $general_settings = [];
            foreach ($general_settings_keys as $old_key => $new_key) {
                if (!empty($old_settings[$old_key])) {
                    $general_settings[$new_key] = $old_settings[$old_key];
                }
            }
            // auto_complete breaks down into:
            // order_status_automation & automatic_order_status
            if (!empty($old_settings['auto_complete'])) {
                $general_settings['order_status_automation'] = 1;
                $general_settings['automatic_order_status']  = 'completed';
            }

            // map old key => new_key
            $defaults_settings_keys = [
                'email'           => 'connect_email',
                'telefoon'        => 'connect_phone',
                'handtekening'    => 'signature',
                'retourbgg'       => 'return',
                'kenmerk'         => 'label_description',
                'verzekerd'       => 'insured',
                'verzekerdbedrag' => 'insured_amount',
            ];
            $defaults_settings      = [];
            foreach ($defaults_settings_keys as $old_key => $new_key) {
                if (!empty($old_settings[$old_key])) {
                    $defaults_settings[$new_key] = $old_settings[$old_key];
                }
            }
            // set custom insurance amount
            if (!empty($defaults_settings['insured']) && (int)$defaults_settings['insured_amount'] > 249) {
                $defaults_settings['insured_amount']        = 0;
                $defaults_settings['insured_amount_custom'] = $old_settings['verzekerdbedrag'];
            }

            // add options
            update_option('woocommerce_myparcelbe_general_settings', $general_settings);
            update_option('woocommerce_myparcelbe_export_defaults_settings', $defaults_settings);
        }
    }

    /**
     * Plugin upgrade method.  Perform any required upgrades here
     *
     * @param string $installed_version the currently installed ('old') version
     */
    protected function upgrade($installed_version)
    {
        if (version_compare($installed_version, '2.4.0-beta-4', '<')) {
            // remove log file (now uses WC logger)
            $upload_dir  = wp_upload_dir();
            $upload_base = trailingslashit($upload_dir['basedir']);
            $log_file    = $upload_base . 'myparcelbe_log.txt';
            if (@file_exists($log_file)) {
                @unlink($log_file);
            }
        }

        if (version_compare($installed_version, '3.0.4', '<=')) {
            $old_settings = get_option('woocommerce_myparcelbe_checkout_settings');
            $new_settings = $old_settings;

            // Add/replace new settings
            $new_settings['use_split_address_fields'] = '1';

            // Rename signed to signature for consistency
            $new_settings['signature_enabled'] = $old_settings['signed_enabled'];
            $new_settings['signature_title']   = $old_settings['signed_title'];
            $new_settings['signature_fee']     = $old_settings['signed_fee'];

            // Remove old settings
            unset($new_settings['signed_enabled']);
            unset($new_settings['signed_title']);
            unset($new_settings['signed_fee']);

            update_option('woocommerce_myparcelbe_checkout_settings', $new_settings);
        }

        if (version_compare($installed_version, '4.0.0', '<=')) {
            $checkoutSettings            = get_option('woocommerce_myparcelbe_checkout_settings');
            $defaultSettings             = get_option('woocommerce_myparcelbe_export_defaults_settings');
            $generalSettings             = get_option('woocommerce_myparcelbe_general_settings');
            $bpostSettings               = $this->setBpostSettings($checkoutSettings, $defaultSettings);
            $multiCarrierGeneralSettings =
                $this->setFromCheckoutToGeneralSettings($checkoutSettings, $generalSettings);

            $bpostSettings = array_merge($bpostSettings[0], $bpostSettings[1]);

            update_option('woocommerce_myparcelbe_bpost_settings', $bpostSettings);
            update_option('woocommerce_myparcelbe_general_settings', $multiCarrierGeneralSettings);
        }
    }

    /**
     * @param array $checkoutSettings
     * @param array $defaultSettings
     *
     * @return array
     */
    public function setBpostSettings(array $checkoutSettings, array $defaultSettings)
    {
        $bpostSettings         = $checkoutSettings;
        $singleCarrierDefaults = $defaultSettings;

        $bpostSettings         = $this->setFromCheckoutToBpostSettings($bpostSettings, $checkoutSettings);
        $singleCarrierDefaults = $this->setFromDefaultToBpostSettings($singleCarrierDefaults, $defaultSettings);

        return [$bpostSettings, $singleCarrierDefaults];
    }

    public function setFromDefaultToBpostSettings($bpostSettings, $defaultSettings)
    {
        $fromDefaultToBpost = ['signature', 'insured'];

        foreach ($fromDefaultToBpost as $carrierSettings) {
            $bpostSettings[$carrierSettings] = $defaultSettings[$carrierSettings];
        }

        return $bpostSettings;
    }

    /**
     * @param array $checkoutSettings
     * @param array $generalSettings
     *
     * @return array
     */
    public function setFromCheckoutToGeneralSettings(array $checkoutSettings, array $generalSettings)
    {
        $fromCheckoutToGeneral = [
            'use_split_address_fields',
            'checkout_display',
            'checkout_position',
            'header_delivery_options_title',
            'customs_css',
        ];

        foreach ($fromCheckoutToGeneral as $generalCarrierSettings) {
            $generalSettings[$generalCarrierSettings] = $checkoutSettings[$generalCarrierSettings];
        }

        return $generalSettings;
    }

    public function setFromCheckoutToBpostSettings($bpostSettings, $checkoutSettings)
    {
        $fromCheckoutToBpost = [
            'myparcelbe_checkout' => 'myparcelbe_carrier_enable_bpost',
            'dropoff_days'        => 'bpost_dropoff_days',
            'cutoff_time'         => 'bpost_cutoff_time',
            'dropoff_delay'       => 'bpost_dropoff_delay',
            'deliverydays_window' => 'bpost_deliverydays_window',
            'signature_enabled'   => 'bpost_signature_enabled',
            'signature_title'     => 'bpost_signature_title',
            'signature_fee'       => 'bpost_signature_fee',
            'pickup_enabled'      => 'bpost_pickup_enabled',
            'pickup_title'        => 'bpost_pickup_title',
            'pickup_fee'          => 'bpost_pickup_fee',
        ];

        foreach ($fromCheckoutToBpost as $singleCarrierSettings => $multiCarrierSettings) {
            $bpostSettings[$multiCarrierSettings] = $checkoutSettings[$singleCarrierSettings];
            unset($bpostSettings[$singleCarrierSettings]);
        }

        return $bpostSettings;
    }

    /**
     * Get the plugin url.
     *
     * @return string
     */
    public function plugin_url()
    {
        return untrailingslashit(plugins_url('/', __FILE__));
    }

    /**
     * Get the plugin path.
     *
     * @return string
     */
    public function plugin_path()
    {
        return untrailingslashit(plugin_dir_path(__FILE__));
    }

    /**
     * Initialize the settings.
     * Legacy: Before PHP 7.1, use old settings structure.
     */
    public function initSettings()
    {
        if (!$this->phpVersionMeets($this->legacySettingsPhpVersion)) {
            $this->general_settings  = get_option('woocommerce_myparcelbe_general_settings');
            $this->export_defaults   = get_option('woocommerce_myparcelbe_export_defaults_settings');
            $this->checkout_settings = get_option('woocommerce_myparcelbe_checkout_settings');

            return;
        } else {
            if ($this->setting_collection) {
                return;
            }
            // Load settings
            $settings = new SettingsCollection();
            $settings->setSettingsByType(get_option('woocommerce_myparcelbe_general_settings'), 'general');
            $settings->setSettingsByType(get_option('woocommerce_myparcelbe_export_defaults_settings'), 'export');
            $settings->setSettingsByType(
                get_option('woocommerce_myparcelbe_bpost_settings'),
                'carrier',
                BpostConsignment::CARRIER_NAME
            );
            $settings->setSettingsByType(
                get_option('woocommerce_myparcelbe_dpd_settings'),
                'carrier',
                DPDConsignment::CARRIER_NAME
            );

            $this->setting_collection = $settings;
        }
    }

    /**
     * @param string $version
     *
     * @return bool
     */
    private function phpVersionMeets($version)
    {
        return version_compare(PHP_VERSION, $version, '>=');
    }
} // class WooCommerce_MyParcelBE

endif; // class_exists

/**
 * Returns the main instance of the plugin class to prevent the need to use globals.
 * @since  2.0
 * @return WooCommerce_MyParcelBE
 */
function WooCommerce_MyParcelBE() {
    return WooCommerce_MyParcelBE::instance();
}

WooCommerce_MyParcelBE(); // load plugin
