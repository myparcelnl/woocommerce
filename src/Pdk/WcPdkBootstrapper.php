<?php

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Pdk;

use MyParcelNL\Pdk\Base\PdkBootstrapper;
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

            'userAgent'             => value([
                'MyParcelNL-WooCommerce' => $version,
                'WooCommerce'            => defined('WOOCOMMERCE_VERSION') ? constant('WOOCOMMERCE_VERSION') : '?',
                'WordPress'              => get_bloginfo('version'),
            ]),

            /**
             * Meta keys.
             */
            'metaKeyOrderData'      => value('myparcelnl_order_data'),
            'metaKeyShipments'      => value('myparcelnl_order_shipments'),

            ###
            # Order grid
            ###

            /**
             * The name of our column in the order grid.
             */
            'orderGridColumnName'   => value($name),

            /**
             * The name of the column we want to appear after.
             */
            'orderGridColumnBefore' => value('shipping_address'),

            /**
             * Bulk order actions.
             */
            'bulkActions'           => value([
                'action_print',
                'action_export_print',
                'action_export',
                'action_edit',
            ]),

            /**
             * Bulk order actions in order mode.
             */
            'bulkActionsOrderMode'  => value([
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
             * Prefix of each setting saved to the database. Prefixed with an underscore to prevent it from being shown and edited in ACF.
             */
            'settingKeyPrefix'      => value("_{$name}_"),

            /** Settings key where webhooks are saved */
            'settingKeyWebhooks'    => value('webhooks'),
            /** Settings key where the hashed webhook url is saved */
            'settingKeyWebhookHash' => value('webhook_hash'),

            ###
            # Routes
            ###

            'routeBackend'        => value("$name/backend/v1"),
            'routeBackendPdk'     => value('pdk'),
            'routeBackendWebhook' => value('webhook'),

            'routeFrontend'         => value("$name/frontend/v1"),
            'routeFrontendMyParcel' => value($name),
        ];
    }
}
