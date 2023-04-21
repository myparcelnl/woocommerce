<?php

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Pdk;

use MyParcelNL\Pdk\Base\PdkBootstrapper;
use MyParcelNL\Pdk\Facade\Pdk;
use MyParcelNL\Pdk\Settings\Model\CheckoutSettings;
use function DI\factory;
use function DI\value;

class WcPdkBootstrapper extends PdkBootstrapper
{
    /**
     * @param  string $name
     * @param  string $title
     * @param  string $version
     * @param  string $path
     * @param  string $url
     *
     * @return array
     */
    protected function getAdditionalConfig(
        string $name,
        string $title,
        string $version,
        string $path,
        string $url
    ): array {
        return [
            ###
            # General
            ###

            'pluginBaseName' => value('woocommerce-myparcel'),

            'userAgent' => value([
                'MyParcelNL-WooCommerce' => $version,
                'WooCommerce'            => defined('WOOCOMMERCE_VERSION') ? constant('WOOCOMMERCE_VERSION') : '?',
                'WordPress'              => get_bloginfo('version'),
            ]),

            'urlDocumentation' => value('https://developer.myparcel.nl/nl/documentatie/10.woocommerce.html'),
            'urlReleaseNotes'  => value('https://github.com/myparcelnl/woocommerce/releases'),

            'wcAddressTypeBilling'  => value('billing'),
            'wcAddressTypeShipping' => value('shipping'),

            'wcAddressTypes' => factory(function () {
                return [
                    Pdk::get('wcAddressTypeBilling'),
                    Pdk::get('wcAddressTypeShipping'),
                ];
            }),

            'fieldAddress1'   => value('address_1'),
            'fieldAddress2'   => value('address_2'),
            'fieldCity'       => value('city'),
            'fieldCompany'    => value('company'),
            'fieldCountry'    => value('country'),
            'fieldEmail'      => value('email'),
            'fieldFirstName'  => value('first_name'),
            'fieldLastName'   => value('last_name'),
            'fieldPhone'      => value('phone'),
            'fieldPostalCode' => value('postcode'),
            'fieldRegion'     => value('state'),

            ###
            # Meta Keys
            ###

            /**
             * The meta key a PdkOrder's data is saved in.
             *
             * @see \MyParcelNL\Pdk\Plugin\Model\PdkOrder
             */

            'metaKeyOrderData' => value("{$name}_order_data"),

            /**
             * The meta key a PdkOrder's shipments are saved in.
             *
             * @see \MyParcelNL\Pdk\Shipment\Model\Shipment
             * @see \MyParcelNL\Pdk\Shipment\Collection\ShipmentCollection
             */

            'metaKeyShipments' => value("{$name}_order_shipments"),

            /** Key set on a product if its settings have been migrated to 5.0.0. */

            'metaKeyProductSettingsMigrated' => value("{$name}_product_migrated_pdk"),

            ###
            # Order grid
            ###

            /** The name of our column in the order grid. */

            'orderGridColumnName' => value($name),

            /** The name of the column our column appears after.*/

            'orderGridColumnBefore' => value('shipping_address'),

            /**
             * Bulk order actions.
             */

            'bulkActions' => value([
                'action_print',
                'action_export_print',
                'action_export',
                'action_edit',
            ]),

            /**
             * Bulk order actions in order mode.
             */

            'bulkActionsOrderMode' => value([
                'action_edit',
                'action_export',
            ]),

            ###
            # Single order page
            ###

            'orderMetaBoxId'    => value("{$name}_woocommerce_order_data"),
            'orderMetaBoxTitle' => value($title),

            ###
            # Settings
            ###

            'settingsMenuSlug'      => value("woocommerce_page_$name-settings"),
            'settingsMenuSlugShort' => value("$name-settings"),
            'settingsMenuTitle'     => value($title),
            'settingsPageTitle'     => value("$title WooCommerce"),

            /**
             * Prefix of each setting saved to the database. Prefixed with an underscore to prevent it from being shown
             * and edited in ACF.
             */

            'settingKeyPrefix' => value("_{$name}_"),

            /**
             * Settings defaults
             */

            'defaultSettings' => value([
                CheckoutSettings::ID => [
                    CheckoutSettings::ALLOWED_SHIPPING_METHODS => ['flat_rate', 'free_shipping'],
                ],
            ]),

            ###
            # Routes
            ###

            'routeBackend'        => value("$name/backend/v1"),
            'routeBackendPdk'     => value('pdk'),
            'routeBackendWebhook' => value('webhook'),

            'routeFrontend'         => value("$name/frontend/v1"),
            'routeFrontendMyParcel' => value($name),

            ###
            # Filters
            ###

            'filters' => value([
                'separateAddressFieldsPriority' => 'mpwc_checkout_separate_address_fields_priority',
                'taxFieldsPriority'             => 'mpwc_checkout_tax_fields_priority',

                /**
                 * Field classes
                 */
                'fieldEoriNumberClass'          => 'mpwc_checkout_field_eori_number_class',
                'fieldVatNumberClass'           => 'mpwc_checkout_field_vat_number_class',
                'fieldStreetClass'              => 'mpwc_checkout_field_street_class',
                'fieldNumberClass'              => 'mpwc_checkout_field_number_class',
                'fieldNumberSuffixClass'        => 'mpwc_checkout_field_number_suffix_class',

                /**
                 * Field priorities
                 */
                'fieldEoriNumberPriority'       => 'mpwc_checkout_field_eori_number_priority',
                'fieldVatNumberPriority'        => 'mpwc_checkout_field_vat_number_priority',
                'fieldStreetPriority'           => 'mpwc_checkout_field_street_priority',
                'fieldNumberPriority'           => 'mpwc_checkout_field_number_priority',
                'fieldNumberSuffixPriority'     => 'mpwc_checkout_field_number_suffix_priority',
            ]),

            'filterDefaults' => value([
                'separateAddressFieldsPriority' => 10,
                'taxFieldsPriority'             => 10,

                'fieldStreetClass'       => ['form-row-third', 'first'],
                'fieldNumberClass'       => ['form-row-third'],
                'fieldNumberSuffixClass' => ['form-row-third', 'last'],

                'fieldEoriNumberClass' => ['form-row'],
                'fieldVatNumberClass'  => ['form-row'],

                'fieldStreetPriority'       => 60,
                'fieldNumberPriority'       => 61,
                'fieldNumberSuffixPriority' => 62,

                'fieldEoriNumberPriority' => 900,
                'fieldVatNumberPriority'  => 901,
            ]),

            /**
             * Used to change the priority of the filter that adds the separate address fields to the checkout.
             */

            'filterCheckoutSeparateAddressFieldsPriority' => value('mpwc_checkout_separate_address_fields_priority'),

            /**
             * Used to change the priority of the filter that adds the tax fields to the checkout.
             */

            'filterCheckoutTaxPriority' => value('mpwc_checkout_tax_fields_priority'),
        ];
    }
}
