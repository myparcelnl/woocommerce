<?php

declare(strict_types=1);

use MyParcelNL\WooCommerce\includes\admin\Messages;
use MyParcelNL\WooCommerce\includes\admin\MessagesRepository;
use MyParcelNL\WooCommerce\includes\admin\settings\CarrierSettings;
use MyParcelNL\WooCommerce\includes\admin\settings\Status;
use MyParcelNL\WooCommerce\includes\Settings\Api\AccountSettings;
use WPO\WC\MyParcel\Entity\SettingsFieldArguments;

defined('ABSPATH') or die();

if (class_exists('WCMP_Settings_Data')) {
    return new WCMP_Settings_Data();
}

/**
 * This class contains all data for the admin settings screens created by the plugin.
 */
class WCMP_Settings_Data
{
    public const ENABLED  = '1';
    public const DISABLED = '0';

    public const EXPORT_MODE_PPS       = 'pps';
    public const EXPORT_MODE_SHIPMENTS = 'shipments';

    public const DISPLAY_FOR_SELECTED_METHODS = 'selected_methods';
    public const DISPLAY_FOR_ALL_METHODS      = 'all_methods';

    public const DISPLAY_TOTAL_PRICE     = 'total_price';
    public const DISPLAY_SURCHARGE_PRICE = 'surcharge';

    public const PICKUP_LOCATIONS_VIEW_MAP  = 'map';
    public const PICKUP_LOCATIONS_VIEW_LIST = 'list';

    public const CHANGE_STATUS_AFTER_PRINTING = 'after_printing';
    public const CHANGE_STATUS_AFTER_EXPORT   = 'after_export';

    public const NOT_ACTIVE        = 'notActive';
    public const NO_OPTIONS        = 'noOptions';
    public const EQUAL_TO_SHIPMENT = 'equalToShipment';

    /**
     * @var WCMP_Settings_Callbacks
     */
    private $callbacks;

    /**
     * @var \MyParcelNL\WooCommerce\includes\admin\settings\CarrierSettings
     */
    private $carrierSettings;

    public function __construct()
    {
        $this->callbacks       = require 'class-wcmp-settings-callbacks.php';
        $this->carrierSettings = new CarrierSettings();

        // Create the MyParcel settings with the admin_init hook.
        add_action('admin_init', [$this, 'createAllSettings']);
    }

    /**
     * Create all settings sections.
     *
     * @throws \Exception
     */
    public function createAllSettings(): void
    {
        $this->generateSettings(
            $this->getSectionsGeneral(),
            WCMYPA_Settings::SETTINGS_GENERAL
        );

        $this->generateSettings(
            $this->getSectionsExportDefaults(),
            WCMYPA_Settings::SETTINGS_EXPORT_DEFAULTS
        );

        $this->generateSettings(
            $this->getSectionsCheckout(),
            WCMYPA_Settings::SETTINGS_CHECKOUT
        );

        $this->generateCarrierSettings();
    }

    /**
     * @return array
     */
    public static function getTabs(): array
    {
        $array = [
            WCMYPA_Settings::SETTINGS_GENERAL         => __('General', 'woocommerce-myparcel'),
            WCMYPA_Settings::SETTINGS_EXPORT_DEFAULTS => __('Default export settings', 'woocommerce-myparcel'),
            WCMYPA_Settings::SETTINGS_CHECKOUT        => __('Checkout settings', 'woocommerce-myparcel'),
        ];

        foreach (AccountSettings::getInstance()->getEnabledCarriers() as $carrier) {
            $array[$carrier->getName()] = $carrier->getHuman();
        }

        return $array;
    }

    /**
     * Get the weekdays from WP_Locale and remove any given entries.
     *
     * @param  int[] $remove
     *
     * @return array
     */
    public static function getWeekdays(array $remove = []): array
    {
        $weekdays = (new WP_Locale())->weekday;

        foreach ($remove as $index) {
            unset($weekdays[$index]);
        }

        return $weekdays;
    }

    /**
     * @return void
     * @throws \Exception
     */
    private function generateCarrierSettings(): void
    {
        $enabledCarriers = AccountSettings::getInstance()
            ->getEnabledCarriers();

        foreach ($enabledCarriers as $carrier) {
            $this->generateSettings(
                [$carrier->getName() => $this->carrierSettings->getCarrierSection($carrier)],
                $carrier->getName()
            );
        }
    }

    /**
     * Generate settings sections and fields by the given $settingsArray.
     *
     * @param array  $settingsArray - Array of settings to loop through.
     * @param string $optionName    - Name to use in the identifier.
     *
     * @throws \Exception
     */
    private function generateSettings(array $settingsArray, string $optionName, bool $prefix = false): void
    {
        $optionIdentifier = WCMYPA_Settings::getOptionId($optionName);
        $defaults         = [];

        // Register settings.
        register_setting($optionIdentifier, $optionIdentifier, [$this->callbacks, 'validate']);

        foreach ($settingsArray as $name => $array) {
            foreach ($array as $section) {
                $sectionName = "{$name}_{$section['name']}";

                add_settings_section(
                    $sectionName,
                    $section['label'],
                    /**
                     * Allows a description to be shown with a section title.
                     */
                    static function() use ($section) {
                        WCMP_Settings_Callbacks::renderSection($section);
                    },
                    $optionIdentifier
                );

                foreach ($section['settings'] as $setting) {
                    if (isset($setting['condition']) && false === $setting['condition']) {
                        continue;
                    }
                    $namePrefix           = $prefix ? "{$name}_" : '';
                    $setting['option_id'] = $optionIdentifier;
                    if (isset($setting['name'])) {
                        $setting['id'] = $prefix ? "{$name}_{$setting['name']}" : $setting['name'];
                    }

                    $class = new SettingsFieldArguments($setting, "{$optionIdentifier}[{$namePrefix}", ']');

                    // Add the setting's default value to the defaults array.
                    if (isset($setting['id'])) {
                        $defaults[$setting['id']] = $class->getDefault();
                    }

                    if (isset(get_option($optionIdentifier)[$class->getId()])) {
                        $class->setValue(get_option($optionIdentifier)[$class->getId()]);
                    }

                    // Default callback
                    $callback = static function() use ($class) {
                        WCMP_Settings_Callbacks::renderField($class);
                    };

                    // Pass the class to custom callbacks as well.
                    if (isset($setting['callback'])) {
                        $callback = static function () use ($setting, $class) {
                            call_user_func($setting['callback'], $class);
                        };
                    }

                    add_settings_field(
                        $setting['id'] ?? null,
                        $setting['label'] ?? null,
                        $callback,
                        $optionIdentifier,
                        $sectionName,
                        // If a custom callback is used, send the $setting as arguments. Otherwise use the created
                        // arguments from the class.
                        isset($setting['callback']) ? $setting : $class->getArguments()
                    );
                }
            }
        }

        // Create option in wp_options with default settings if the option doesn't exist yet.
        if (false === get_option($optionIdentifier)) {
            add_option($optionIdentifier, $defaults);
        }

        // Merge any missing values into the settings
        update_option(
            $optionIdentifier,
            array_replace_recursive(
                $defaults,
                get_option($optionIdentifier) ?: []
            )
        );
    }

    /**
     * @return array
     */
    private function getSectionsGeneral(): array
    {
        return [
            WCMYPA_Settings::SETTINGS_GENERAL => [
                [
                    'name'     => 'api',
                    'label'    => __('settings_general_api_title', 'woocommerce-myparcel'),
                    'settings' => $this->getSectionGeneralApi(),
                ],
                [
                    'name'     => 'general',
                    'label'    => __('settings_general_general_title', 'woocommerce-myparcel'),
                    'settings' => $this->getSectionGeneralGeneral(),
                ],
                [
                    'name'     => 'diagnostics',
                    'label'    => __('settings_general_diagnostics_title', 'woocommerce-myparcel'),
                    'settings' => $this->getSectionGeneralDiagnostics(),
                ],
            ],
        ];
    }

    /**
     * @return array
     */
    private function getSectionsExportDefaults(): array
    {
        return [
            WCMYPA_Settings::SETTINGS_EXPORT_DEFAULTS => [
                [
                    'name'     => 'main',
                    'label'    => __('Default export settings', 'woocommerce-myparcel'),
                    'settings' => $this->getSectionExportDefaultsMain(),
                ],
            ],
        ];
    }

    private function getSectionsCheckout(): array
    {
        return [
            WCMYPA_Settings::SETTINGS_CHECKOUT => [
                [
                    'name'     => 'main',
                    'label'    => __('Checkout settings', 'woocommerce-myparcel'),
                    'settings' => $this->getSectionCheckoutMain(),
                ],
                [
                    'name'      => 'strings',
                    'label'     => __('Titles', 'woocommerce-myparcel'),
                    'condition' => WCMYPA_Settings::SETTING_DELIVERY_OPTIONS_ENABLED,
                    'settings'  => $this->getSectionCheckoutStrings(),
                ],
            ],
        ];
    }

    /**
     * @return array
     */
    private function getSectionGeneralApi(): array
    {
        return [
            [
                'name'      => WCMYPA_Settings::SETTING_API_KEY,
                'label'     => __('settings_general_api_key', 'woocommerce-myparcel'),
                'help_text' => __('settings_general_api_key_help_text', 'woocommerce-myparcel'),
            ],
            [
                'name'      => WCMYPA_Settings::SETTING_TRIGGER_MANUAL_UPDATE,
                'label'     => __('settings_trigger_manual_update', 'woocommerce-myparcel'),
                'help_text' => __('settings_trigger_manual_update_help_text', 'woocommerce-myparcel'),
                'callback'  => [$this, 'renderManualUpdateTrigger'],
            ],
        ];
    }

    public function renderManualUpdateTrigger(): void
    {
        $baseUrl = esc_url('admin-ajax.php?action=' . WCMYPA_Settings::SETTING_TRIGGER_MANUAL_UPDATE);
        printf('<a class="button wcmp__trigger" href="%s">', $baseUrl);
        esc_html_e('settings_trigger_manual_update_button', 'woocommerce-myparcel');
        WCMYPA_Admin::renderSpinner();
        echo '</a>';
    }

    /**
     * @return array
     */
    private function getSectionGeneralGeneral(): array
    {
        $exportModeSetting = WCMYPA()->setting_collection->getByName(WCMYPA_Settings::SETTING_EXPORT_MODE);

        if (self::EXPORT_MODE_PPS === $exportModeSetting) {
            Messages::showAdminNotice(
                __('message_export_mode_on', 'woocommerce-myparcel'),
                Messages::NOTICE_LEVEL_WARNING,
                null,
                [MessagesRepository::SETTINGS_PAGE]
            );
        }

        return [
            [
                'name'    => WCMYPA_Settings::SETTING_EXPORT_MODE,
                'label'   => __('setting_mode_title', 'woocommerce-myparcel'),
                'type'    => 'select',
                'options' => [
                    self::EXPORT_MODE_SHIPMENTS => __('setting_mode_shipments_title', 'woocommerce-myparcel'),
                    self::EXPORT_MODE_PPS       => __('setting_mode_pps_title', 'woocommerce-myparcel'),
                ],
                'default'   => self::EXPORT_MODE_SHIPMENTS,
                'help_text' => __('setting_modus_help_text', 'woocommerce-myparcel'),
                'append'    => $this->getExportModeDescription(),
            ],
            [
                'name'      => WCMYPA_Settings::SETTING_DOWNLOAD_DISPLAY,
                'label'     => __('Label display', 'woocommerce-myparcel'),
                'condition' => $this->conditionForModeShipmentsOnly(),
                'type'      => 'select',
                'options'   => [
                    'download' => __('Download PDF', 'woocommerce-myparcel'),
                    'display'  => __('Open the PDF in a new tab', 'woocommerce-myparcel'),
                ],
            ],
            [
                'name'      => WCMYPA_Settings::SETTING_LABEL_FORMAT,
                'label'     => __('Label format', 'woocommerce-myparcel'),
                'condition' => $this->conditionForModeShipmentsOnly(),
                'type'      => 'select',
                'options'   => [
                    'A4' => __('Standard printer (A4)', 'woocommerce-myparcel'),
                    'A6' => __('Label Printer (A6)', 'woocommerce-myparcel'),
                ],
            ],
            [
                'name'      => WCMYPA_Settings::SETTING_ASK_FOR_PRINT_POSITION,
                'label'     => __('Ask for print start position', 'woocommerce-myparcel'),
                'condition' => [
                    $this->conditionForModeShipmentsOnly(),
                    [
                        'parent_name'  => WCMYPA_Settings::SETTING_LABEL_FORMAT,
                        'type'         => 'disable',
                        'parent_value' => 'A4',
                        'set_value'    => self::DISABLED,
                    ],
                ],
                'type'      => 'toggle',
                'help_text' => __(
                    'This option enables you to continue printing where you left off last time',
                    'woocommerce-myparcel'
                ),
            ],
            [
                'name'      => WCMYPA_Settings::SETTING_TRACK_TRACE_EMAIL,
                'label'     => __('Track & Trace in email', 'woocommerce-myparcel'),
                'condition' => $this->conditionForModeShipmentsOnly(),
                'type'      => 'toggle',
                'help_text' => __(
                    'Add the Track & Trace code to emails to the customer.<br/><strong>Note!</strong> When you select this option, make sure you have not enabled the Track & Trace email in your MyParcel backend.',
                    'woocommerce-myparcel'
                ),
            ],
            [
                'name'      => WCMYPA_Settings::SETTING_TRACK_TRACE_MY_ACCOUNT,
                'label'     => __('Track & Trace in My Account', 'woocommerce-myparcel'),
                'condition' => $this->conditionForModeShipmentsOnly(),
                'type'      => 'toggle',
                'help_text' => __('Show Track & Trace trace code and link in My Account.', 'woocommerce-myparcel'),
            ],
            [
                'name'      => WCMYPA_Settings::SETTING_SHOW_DELIVERY_DAY,
                'label'     => __('setting_show_delivery_day_title', 'woocommerce-myparcel'),
                'type'      => 'toggle',
                'help_text' => __('setting_show_delivery_day_help_text', 'woocommerce-myparcel'),
            ],
            [
                'name'      => WCMYPA_Settings::SETTING_PROCESS_DIRECTLY,
                'label'     => __('Process shipments directly', 'woocommerce-myparcel'),
                'condition' => $this->conditionForModeShipmentsOnly(),
                'type'      => 'toggle',
                'help_text' => __(
                    'When you enable this option, shipments will be directly processed when sent to MyParcel.',
                    'woocommerce-myparcel'
                ),
            ],
            [
                'name'      => WCMYPA_Settings::SETTING_ORDER_STATUS_AUTOMATION,
                'label'     => __('Order status automation', 'woocommerce-myparcel'),
                'type'      => 'toggle',
                'help_text' => __(
                    'Automatically set order status to a predefined status after successful MyParcel export.<br/>Make sure <strong>Process shipments directly</strong> is enabled when you use this option together with the <strong>Track & Trace in email</strong> option, otherwise the Track & Trace code will not be included in the customer email.',
                    'woocommerce-myparcel'
                ),
            ],
            [
                'name'      => WCMYPA_Settings::SETTING_CHANGE_ORDER_STATUS_AFTER,
                'class'     => ['wcmp__child'],
                'label'     => __('setting_change_order_status_after', 'woocommerce-myparcel'),
                'type'      => 'select',
                'default'   => self::CHANGE_STATUS_AFTER_PRINTING,
                'options'   => [
                    self::CHANGE_STATUS_AFTER_PRINTING => __('setting_change_status_after_printing', 'woocommerce-myparcel'),
                    self::CHANGE_STATUS_AFTER_EXPORT   => __('setting_change_status_after_export', 'woocommerce-myparcel'),
                ],
                'help_text' => __(
                    'setting_change_status_after_help_text',
                    'woocommerce-myparcel'
                ),
            ],
            [
                'name'      => WCMYPA_Settings::SETTING_AUTOMATIC_ORDER_STATUS,
                'label'     => __('setting_automatic_order_status', 'woocommerce-myparcel'),
                'condition' => WCMYPA_Settings::SETTING_ORDER_STATUS_AUTOMATION,
                'class'     => ['wcmp__child'],
                'type'      => 'select',
                'options'   => WCMP_Settings_Callbacks::get_order_status_options(),
            ],
            [
                'name'      => WCMYPA_Settings::SETTING_BARCODE_IN_NOTE,
                'label'     => __('Place barcode inside note', 'woocommerce-myparcel'),
                'condition' => $this->conditionForModeShipmentsOnly(),
                'type'      => 'toggle',
                'help_text' => __('Place the barcode inside a note of the order', 'woocommerce-myparcel'),
            ],
            [
                'name'      => WCMYPA_Settings::SETTING_BARCODE_IN_NOTE_TITLE,
                'condition' => [
                    $this->conditionForModeShipmentsOnly(),
                    WCMYPA_Settings::SETTING_BARCODE_IN_NOTE,
                ],
                'class'     => ['wcmp__child'],
                'label'     => __('Title before the barcode', 'woocommerce-myparcel'),
                'default'   => __('Track & trace code:', 'woocommerce-myparcel'),
                'help_text' => __(
                    'You can change the text before the barcode inside an note',
                    'woocommerce-myparcel'
                ),
            ],
        ];
    }

    /**
     * @return array
     */
    private function getSectionGeneralDiagnostics(): array
    {
        return [
            [
                'name'        => WCMYPA_Settings::SETTING_ERROR_LOGGING,
                'label'       => __('Log API communication', 'woocommerce-myparcel'),
                'type'        => 'toggle',
                'description' => '<a href="' . esc_url_raw(
                        admin_url('admin.php?page=wc-status&tab=logs')
                    ) . '" target="_blank">' . __('View logs', 'woocommerce-myparcel') . '</a> (wc-myparcel)',
            ],
            [
                'callback' => [Status::class, 'renderDiagnostics'],
            ],
        ];
    }

    /**
     * @return array
     */
    private function getSectionExportDefaultsMain(): array
    {
        return [
            [
                'name'      => WCMYPA_Settings::SETTING_SHIPPING_METHODS_PACKAGE_TYPES,
                'label'     => __('Package types', 'woocommerce-myparcel'),
                'callback'  => [WCMP_Settings_Callbacks::class, 'enhanced_select'],
                'loop'      => WCMP_Data::getPackageTypesHuman(),
                'options'   => (new WCMP_Shipping_Methods())->getShippingMethods(),
                'default'   => [],
                'help_text' => __(
                    'Select one or more shipping methods for each MyParcel package type',
                    'woocommerce-myparcel'
                ),
            ],
            [
                'name'      => WCMYPA_Settings::SETTING_CONNECT_EMAIL,
                'label'     => __('Connect customer email', 'woocommerce-myparcel'),
                'type'      => 'toggle',
                'help_text' => __(
                    "When you connect the customer's email, MyParcel can send a Track & Trace email to this address. In your MyParcel backend you can enable or disable this email and format it in your own style.",
                    'woocommerce-myparcel'
                ),
            ],
            [
                'name'      => WCMYPA_Settings::SETTING_CONNECT_PHONE,
                'label'     => __('Connect customer phone', 'woocommerce-myparcel'),
                'type'      => 'toggle',
                'help_text' => __(
                    "When you connect the customer's phone number, the courier can use this for the delivery of the parcel. This greatly increases the delivery success rate for foreign shipments.",
                    'woocommerce-myparcel'
                ),
            ],
            [
                'name'      => WCMYPA_Settings::SETTING_SAVE_CUSTOMER_ADDRESS,
                'label'     => __('save_customer_address', 'woocommerce-myparcel'),
                'type'      => 'toggle',
                'help_text' => __('save_customer_address_help_text', 'woocommerce-myparcel'),
            ],
            [
                'name'      => WCMYPA_Settings::SETTING_LABEL_DESCRIPTION,
                'label'     => __('Label description', 'woocommerce-myparcel'),
                'help_text' => __(
                    'With this option you can add a description to the shipment. This will be printed on the top left of the label, and you can use this to search or sort shipments in your backoffice. Because of limited space on the label which varies per package type, we recommend that you keep the label description as short as possible.',
                    'woocommerce-myparcel'
                ),
                'append'    => $this->getLabelDescriptionAddition(),
            ],
            [
                'name'      => WCMYPA_Settings::SETTING_EMPTY_PARCEL_WEIGHT,
                'type'      => 'number',
                'default'   => 0,
                'step'      => 0.001,
                'label'     => sprintf(
                    '%s (%s)',
                    __('Empty parcel weight', 'woocommerce-myparcel'),
                    get_option('woocommerce_weight_unit')
                ),
                'help_text' => __(
                    'Default weight of your empty parcel.',
                    'woocommerce-myparcel'
                ),
            ],
            [
                'name'      => WCMYPA_Settings::SETTING_EMPTY_DIGITAL_STAMP_WEIGHT,
                'type'      => 'number',
                'default'   => 0,
                'step'      => 0.001,
                'label'     => sprintf(
                    '%s (%s)',
                    __('setting_empty_digital_stamp_weight', 'woocommerce-myparcel'),
                    get_option('woocommerce_weight_unit')
                ),
                'help_text' => __(
                    'setting_empty_digital_stamp_weight_description',
                    'woocommerce-myparcel'
                ),
            ],
            [
                'name'      => WCMYPA_Settings::SETTING_HS_CODE,
                'label'     => __('Default HS Code', 'woocommerce-myparcel'),
                'help_text' => __(
                    'HS Codes are used for MyParcel world shipments, you can find the appropriate code on the site of the Dutch Customs.',
                    'woocommerce-myparcel'
                ),
            ],
            [
                'name'    => WCMYPA_Settings::SETTING_PACKAGE_CONTENT,
                'label'   => __('Customs shipment type', 'woocommerce-myparcel'),
                'type'    => 'select',
                'options' => [
                    1 => __('Commercial goods', 'woocommerce-myparcel'),
                    2 => __('Commercial samples', 'woocommerce-myparcel'),
                    3 => __('Documents', 'woocommerce-myparcel'),
                    4 => __('Gifts', 'woocommerce-myparcel'),
                    5 => __('Return shipment', 'woocommerce-myparcel'),
                ],
            ],
            [
                'name'      => WCMYPA_Settings::SETTING_COUNTRY_OF_ORIGIN,
                'label'     => __('setting_country_of_origin', 'woocommerce-myparcel'),
                'type'      => 'select',
                'options'   => (new WC_Countries())->get_countries(),
                'default'   => (new WC_Countries())->get_base_country(),
                'help-text' => __(
                    'setting_country_of_origin_help_text',
                    'woocommerce-myparcel'
                ),
            ],
            [
                'name'      => WCMYPA_Settings::SETTING_AUTOMATIC_EXPORT,
                'label'     => __('Automatic export', 'woocommerce-myparcel'),
                'type'      => 'toggle',
                'help_text' => __(
                    'With this setting enabled orders are exported to MyParcel automatically after payment.',
                    'woocommerce-myparcel'
                ),
            ],
            [
                'name'      => WCMYPA_Settings::SETTING_AUTOMATIC_EXPORT_STATUS,
                'condition' => WCMYPA_Settings::SETTING_AUTOMATIC_EXPORT,
                'label'     => __('setting_export_automatic_status', 'woocommerce-myparcel'),
                'class'     => ['wcmp__child'],
                'type'      => 'select',
                'default'   => self::NOT_ACTIVE,
                'options'   =>
                    [self::NOT_ACTIVE => __('not_active', 'woocommerce-myparcel')]
                    + WCMP_Settings_Callbacks::get_order_status_options(),
                'help_text' => __('setting_export_automatic_status_help_text', 'woocommerce-myparcel'),
            ],
//            [
//                "name"      => WCMYPA_Settings::SETTING_RETURN_IN_THE_BOX,
//                "label"     => __("Print return label directly", "woocommerce-myparcel"),
//                "type"      => "select",
//                "options"   => [
//                    self::NOT_ACTIVE        => __("No", "woocommerce-myparcel"),
//                    self::NO_OPTIONS        => __("Without options", "woocommerce-myparcel"),
//                    self::EQUAL_TO_SHIPMENT => __("Options equal to shipment", "woocommerce-myparcel"),
//                ],
//                "help_text" => __(
//                    "Enabling this setting automatically creates a related return shipment for any shipment you export. When downloading the shipment labels the corresponding return shipment labels will be included.",
//                    "woocommerce-myparcel"
//                ),
//            ],
        ];
    }

    /**
     * @return array
     */
    private function getSectionCheckoutMain(): array
    {
        return [
            [
                'name'      => WCMYPA_Settings::SETTING_USE_SPLIT_ADDRESS_FIELDS,
                'label'     => __('MyParcel address fields', 'woocommerce-myparcel'),
                'type'      => 'toggle',
                'help_text' => __(
                    'When enabled the checkout will use the MyParcel address fields. This means there will be three separate fields for street name, number and suffix. Want to use the WooCommerce default fields? Leave this option unchecked.',
                    'woocommerce-myparcel'
                ),
            ],
            [
                'name'      => WCMYPA_Settings::SETTING_DELIVERY_OPTIONS_ENABLED,
                'label'     => __('Enable MyParcel delivery options', 'woocommerce-myparcel'),
                'type'      => 'toggle',
                'help_text' => __(
                    'The MyParcel delivery options allow your customers to select whether they want their parcel delivered at home or to a pickup point. Depending on the settings you can allow them to select a date, time and even options like requiring a signature on delivery.',
                    'woocommerce-myparcel'
                ),
            ],
            [
                'name'      => WCMYPA_Settings::SETTINGS_SHOW_DELIVERY_OPTIONS_FOR_BACKORDERS,
                'condition' => WCMYPA_Settings::SETTING_DELIVERY_OPTIONS_ENABLED,
                'label'     => __('Enable delivery options for backorders', 'woocommerce-myparcel'),
                'type'      => 'toggle',
                'help_text' => __(
                    'When this option is enabled, delivery options and delivery day will be also shown for backorders.',
                    'woocommerce-myparcel'
                ),
            ],
            [
                'name'      => WCMYPA_Settings::SETTING_DELIVERY_OPTIONS_DISPLAY,
                'condition' => WCMYPA_Settings::SETTING_DELIVERY_OPTIONS_ENABLED,
                'label'     => __('settings_checkout_display_for', 'woocommerce-myparcel'),
                'type'      => 'select',
                'help_text' => __('settings_checkout_display_for_help_text', 'woocommerce-myparcel'),
                'options'   => [
                    self::DISPLAY_FOR_SELECTED_METHODS => __(
                        'settings_checkout_display_for_selected_methods',
                        'woocommerce-myparcel'
                    ),
                    self::DISPLAY_FOR_ALL_METHODS      => __(
                        'settings_checkout_display_for_all_methods',
                        'woocommerce-myparcel'
                    ),
                ],
            ],
            [
                'name'      => WCMYPA_Settings::SETTING_DELIVERY_OPTIONS_POSITION,
                'condition' => WCMYPA_Settings::SETTING_DELIVERY_OPTIONS_ENABLED,
                'label'     => __('Checkout position', 'woocommerce-myparcel'),
                'type'      => 'select',
                'default'   => 'woocommerce_after_checkout_billing_form',
                'options'   => [
                    'woocommerce_after_checkout_billing_form'     => __(
                        'Show after billing details',
                        'woocommerce-myparcel'
                    ),
                    'woocommerce_after_checkout_shipping_form'    => __(
                        'Show after shipping details',
                        'woocommerce-myparcel'
                    ),
                    'woocommerce_checkout_after_customer_details' => __(
                        'Show after customer details',
                        'woocommerce-myparcel'
                    ),
                    'woocommerce_after_order_notes'               => __(
                        'Show after notes',
                        'woocommerce-myparcel'
                    ),
                    'woocommerce_review_order_before_payment'     => __(
                        'Show after subtotal',
                        'woocommerce-myparcel'
                    ),
                ],
                'help_text' => __(
                    'You can change the place of the delivery options on the checkout page. By default it will be placed after shipping details.',
                    'woocommerce-myparcel'
                ),
            ],
            [
                'name'      => WCMYPA_Settings::SETTING_DELIVERY_OPTIONS_PRICE_FORMAT,
                'condition' => WCMYPA_Settings::SETTING_DELIVERY_OPTIONS_ENABLED,
                'label'     => __('settings_checkout_price_format', 'woocommerce-myparcel'),
                'type'      => 'select',
                'default'   => self::DISPLAY_TOTAL_PRICE,
                'options'   => [
                    self::DISPLAY_TOTAL_PRICE     => __(
                        'settings_checkout_total_price',
                        'woocommerce-myparcel'
                    ),
                    self::DISPLAY_SURCHARGE_PRICE => __(
                        'settings_checkout_surcharge',
                        'woocommerce-myparcel'
                    ),
                ],
            ],
            [
                'name'      => WCMYPA_Settings::SETTING_PICKUP_LOCATIONS_DEFAULT_VIEW,
                'condition' => WCMYPA_Settings::SETTING_DELIVERY_OPTIONS_ENABLED,
                'label'     => __('settings_pickup_locations_default_view', 'woocommerce-myparcel'),
                'type'      => 'select',
                'default'   => self::PICKUP_LOCATIONS_VIEW_MAP,
                'options'   => [
                    self::PICKUP_LOCATIONS_VIEW_MAP  => __(
                        'settings_pickup_locations_default_view_map',
                        'woocommerce-myparcel'
                    ),
                    self::PICKUP_LOCATIONS_VIEW_LIST => __(
                        'settings_pickup_locations_default_view_list',
                        'woocommerce-myparcel'
                    ),
                ],
            ],
            [
                'name'              => WCMYPA_Settings::SETTING_DELIVERY_OPTIONS_CUSTOM_CSS,
                'condition'         => WCMYPA_Settings::SETTING_DELIVERY_OPTIONS_ENABLED,
                'label'             => __('Custom styles', 'woocommerce-myparcel'),
                'type'              => 'textarea',
                'append'            => $this->getCustomCssAddition(),
                'custom_attributes' => [
                    'style' => 'font-family: monospace;',
                    'rows'  => '8',
                    'cols'  => '12',
                ],
            ],
        ];
    }

    private function getSectionCheckoutStrings(): array
    {
        return [
            [
                'name'      => WCMYPA_Settings::SETTING_HEADER_DELIVERY_OPTIONS_TITLE,
                'condition' => WCMYPA_Settings::SETTING_DELIVERY_OPTIONS_ENABLED,
                'label'     => __('shipment_options_delivery_options_title', 'woocommerce-myparcel'),
                'help_text' => __('shipment_options_delivery_options_title_description', 'woocommerce-myparcel'),
            ],
            [
                'name'      => WCMYPA_Settings::SETTING_DELIVERY_TITLE,
                'condition' => WCMYPA_Settings::SETTING_DELIVERY_OPTIONS_ENABLED,
                'label'     => __('shipment_options_delivery_title', 'woocommerce-myparcel'),
                'default'   => __('shipment_options_delivery', 'woocommerce-myparcel'),
            ],
            [
                'name'      => WCMYPA_Settings::SETTING_MORNING_DELIVERY_TITLE,
                'condition' => WCMYPA_Settings::SETTING_DELIVERY_OPTIONS_ENABLED,
                'label'     => __('shipment_options_delivery_morning_title', 'woocommerce-myparcel'),
                'help_text' => __('shipment_options_delivery_standard_description', 'woocommerce-myparcel'),
                'default'   => __('shipment_options_delivery_morning', 'woocommerce-myparcel'),
            ],
            [
                'name'      => WCMYPA_Settings::SETTING_STANDARD_TITLE,
                'condition' => WCMYPA_Settings::SETTING_DELIVERY_OPTIONS_ENABLED,
                'label'     => __('shipment_options_delivery_standard_title', 'woocommerce-myparcel'),
                'help_text' => __('shipment_options_delivery_standard_description', 'woocommerce-myparcel'),
                'default'   => __('shipment_options_delivery_standard', 'woocommerce-myparcel'),
            ],
            [
                'name'      => WCMYPA_Settings::SETTING_EVENING_DELIVERY_TITLE,
                'condition' => WCMYPA_Settings::SETTING_DELIVERY_OPTIONS_ENABLED,
                'label'     => __('shipment_options_delivery_evening_title', 'woocommerce-myparcel'),
                'help_text' => __('shipment_options_delivery_standard_description', 'woocommerce-myparcel'),
                'default'   => __('shipment_options_delivery_evening', 'woocommerce-myparcel'),
            ],
            [
                'name'      => WCMYPA_Settings::SETTING_SAME_DAY_DELIVERY_TITLE,
                'condition' => WCMYPA_Settings::SETTING_DELIVERY_OPTIONS_ENABLED,
                'label'     => __('shipment_options_delivery_same_day_title', 'woocommerce-myparcel'),
                'help_text' => __('shipment_options_delivery_standard_description', 'woocommerce-myparcel'),
                'default'   => __('shipment_options_delivery_same_day', 'woocommerce-myparcel'),
            ],
            [
                'name'      => WCMYPA_Settings::SETTING_ONLY_RECIPIENT_TITLE,
                'condition' => WCMYPA_Settings::SETTING_DELIVERY_OPTIONS_ENABLED,
                'label'     => __('shipment_options_only_recipient_title', 'woocommerce-myparcel'),
                'default'   => __('shipment_options_only_recipient', 'woocommerce-myparcel'),
            ],
            [
                'name'      => WCMYPA_Settings::SETTING_SIGNATURE_TITLE,
                'condition' => WCMYPA_Settings::SETTING_DELIVERY_OPTIONS_ENABLED,
                'label'     => __('shipment_options_signature_title', 'woocommerce-myparcel'),
                'default'   => __('shipment_options_signature', 'woocommerce-myparcel'),
            ],
            [
                'name'      => WCMYPA_Settings::SETTING_PICKUP_TITLE,
                'condition' => WCMYPA_Settings::SETTING_DELIVERY_OPTIONS_ENABLED,
                'label'     => __('shipment_options_delivery_pickup_title', 'woocommerce-myparcel'),
                'default'   => __('shipment_options_delivery_pickup', 'woocommerce-myparcel'),
            ],
            [
                'name'      => WCMYPA_Settings::SETTING_ADDRESS_NOT_FOUND_TITLE,
                'condition' => WCMYPA_Settings::SETTING_DELIVERY_OPTIONS_ENABLED,
                'label'     => __('address_not_found_title', 'woocommerce-myparcel'),
                'default'   => __('address_not_found', 'woocommerce-myparcel'),
            ],
        ];
    }

    /**
     * Get the html string to render after the custom css select.
     *
     * @return string
     */
    private function getCustomCssAddition(): string
    {
        $currentTheme = wp_get_theme();

        $preset  = sanitize_title($currentTheme);
        $cssPath = WCMYPA()->plugin_path() . "/assets/css/delivery-options/delivery-options-preset-$preset.css";

        if (! file_exists($cssPath)) {
            return '';
        }

        return sprintf(
            '<p>%s <a class="" href="#" onclick="document.querySelector(`#delivery_options_custom_css`).value = `%s`">%s</a></p>',
            sprintf(__('Theme "%s" detected.', 'woocommerce-myparcel'), $currentTheme),
            esc_js(file_get_contents($cssPath)),
            __('Apply preset.', 'woocommerce-myparcel')
        );
    }

    /**
     * Created html for clickable hints for the variables that can be used in the label description.
     *
     * @return string
     */
    private function getLabelDescriptionAddition(): string
    {
        $output = '';
        $variables = [
            '[DELIVERY_DATE]' => __('Delivery date', 'woocommerce-myparcel'),
            '[ORDER_NR]'      => __('Order number', 'woocommerce-myparcel'),
            '[PRODUCT_ID]'    => __('Product id', 'woocommerce-myparcel'),
            '[PRODUCT_NAME]'  => __('Product name', 'woocommerce-myparcel'),
            '[PRODUCT_QTY]'   => __('Product quantity', 'woocommerce-myparcel'),
            '[PRODUCT_SKU]'   => __('Product SKU', 'woocommerce-myparcel'),
            '[CUSTOMER_NOTE]' => __('Customer note', 'woocommerce-myparcel'),
        ];

        foreach ($variables as $variable => $description) {
            $output .= "<br><a onclick=\"var el = document.querySelector('#label_description_field input');el.value += '$variable';el.focus();\">$variable</a>: $description";
        }

        return sprintf("<div class=\"label-description-variables\"><p>Available variables: %s</p>", $output);
    }

    /**
     * @return string
     */
    private function getExportModeDescription(): string
    {
        return sprintf('<br><div>%s</div>', __('setting_modus_append', 'woocommerce-myparcel'));
    }

    /**
     * @return array
     */
    private function conditionForModeShipmentsOnly(): array
    {
        return [
            'parent_name'  => WCMYPA_Settings::SETTING_EXPORT_MODE,
            'parent_value' => self::EXPORT_MODE_SHIPMENTS,
        ];
    }

    /**
     * @param string $name
     * @param string|array  $conditions
     *
     * @return array
     */
    public static function getFeeField(string $name, array $conditions): array
    {
        return [
            'name'      => $name,
            'condition' => $conditions,
            'class'     => ['wcmp__child'],
            'label'     => __('Fee (optional)', 'woocommerce-myparcel'),
            'type'      => 'currency',
            'help_text' => __(
                'Enter an amount that is either positive or negative. For example, do you want to give a discount for using this function or do you want to charge extra for this delivery option.',
                'woocommerce-myparcel'
            ),
        ];
    }
}

new WCMP_Settings_Data();
