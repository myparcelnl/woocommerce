<?php

declare(strict_types=1);

use MyParcelNL\Pdk\Api\Contract\ApiServiceInterface;
use MyParcelNL\Pdk\Api\Contract\ClientAdapterInterface;
use MyParcelNL\Pdk\Api\Service\MyParcelApiService;
use MyParcelNL\Pdk\App\Account\Contract\PdkAccountRepositoryInterface;
use MyParcelNL\Pdk\App\Api\Contract\BackendEndpointServiceInterface;
use MyParcelNL\Pdk\App\Api\Contract\FrontendEndpointServiceInterface;
use MyParcelNL\Pdk\App\Cart\Contract\PdkCartRepositoryInterface;
use MyParcelNL\Pdk\App\DeliveryOptions\Contract\DeliveryOptionsServiceInterface;
use MyParcelNL\Pdk\App\Installer\Contract\InstallerServiceInterface;
use MyParcelNL\Pdk\App\Installer\Contract\MigrationServiceInterface;
use MyParcelNL\Pdk\App\Order\Contract\OrderStatusServiceInterface;
use MyParcelNL\Pdk\App\Order\Contract\PdkOrderNoteRepositoryInterface;
use MyParcelNL\Pdk\App\Order\Contract\PdkOrderRepositoryInterface;
use MyParcelNL\Pdk\App\Order\Contract\PdkProductRepositoryInterface;
use MyParcelNL\Pdk\App\ShippingMethod\Contract\PdkShippingMethodRepositoryInterface;
use MyParcelNL\Pdk\App\Tax\Contract\TaxServiceInterface;
use MyParcelNL\Pdk\App\Webhook\Contract\PdkWebhookServiceInterface;
use MyParcelNL\Pdk\App\Webhook\Contract\PdkWebhooksRepositoryInterface;
use MyParcelNL\Pdk\Audit\Contract\PdkAuditRepositoryInterface;
use MyParcelNL\Pdk\Base\Contract\CronServiceInterface;
use MyParcelNL\Pdk\Base\Contract\WeightServiceInterface;
use MyParcelNL\Pdk\Base\PdkBootstrapper;
use MyParcelNL\Pdk\Base\Support\Arr;
use MyParcelNL\Pdk\Context\Contract\ContextServiceInterface;
use MyParcelNL\Pdk\Facade\Language;
use MyParcelNL\Pdk\Facade\Pdk;
use MyParcelNL\Pdk\Facade\Pdk as PdkFacade;
use MyParcelNL\Pdk\Facade\Platform;
use MyParcelNL\Pdk\Facade\Settings;
use MyParcelNL\Pdk\Frontend\Contract\FrontendRenderServiceInterface;
use MyParcelNL\Pdk\Frontend\Contract\ScriptServiceInterface;
use MyParcelNL\Pdk\Frontend\Contract\ViewServiceInterface;
use MyParcelNL\Pdk\Language\Contract\LanguageServiceInterface;
use MyParcelNL\Pdk\Proposition\Service\PropositionService;
use MyParcelNL\Pdk\Settings\Contract\PdkSettingsRepositoryInterface;
use MyParcelNL\Pdk\Settings\Model\OrderSettings;
use MyParcelNL\WooCommerce\Contract\WooCommerceServiceInterface;
use MyParcelNL\WooCommerce\Contract\WordPressServiceInterface;
use MyParcelNL\WooCommerce\Contract\WpFilterServiceInterface;
use MyParcelNL\WooCommerce\Database\Contract\WpDatabaseServiceInterface;
use MyParcelNL\WooCommerce\Database\Service\WpDatabaseService;
use MyParcelNL\WooCommerce\Facade\WooCommerce;
use MyParcelNL\WooCommerce\Facade\WordPress;
use MyParcelNL\WooCommerce\Logger\WcLogger;
use MyParcelNL\WooCommerce\Pdk\Audit\Repository\WcPdkAuditRepository;
use MyParcelNL\WooCommerce\Pdk\Context\Service\WcContextService;
use MyParcelNL\WooCommerce\Pdk\Guzzle7ClientAdapter;
use MyParcelNL\WooCommerce\Pdk\Plugin\Action\WcBackendEndpointService;
use MyParcelNL\WooCommerce\Pdk\Plugin\Action\WcFrontendEndpointService;
use MyParcelNL\WooCommerce\Pdk\Plugin\Action\WcWebhookService;
use MyParcelNL\WooCommerce\Pdk\Plugin\Installer\WcMigrationService;
use MyParcelNL\WooCommerce\Pdk\Plugin\Repository\PdkAccountRepository;
use MyParcelNL\WooCommerce\Pdk\Plugin\Repository\PdkOrderRepository;
use MyParcelNL\WooCommerce\Pdk\Plugin\Repository\WcCartRepository;
use MyParcelNL\WooCommerce\Pdk\Plugin\Repository\WcOrderNoteRepository;
use MyParcelNL\WooCommerce\Pdk\Plugin\Service\WcDeliveryOptionsService;
use MyParcelNL\WooCommerce\Pdk\Plugin\Service\WcStatusService;
use MyParcelNL\WooCommerce\Pdk\Plugin\WcShippingMethodRepository;
use MyParcelNL\WooCommerce\Pdk\Product\Repository\WcPdkProductRepository;
use MyParcelNL\WooCommerce\Pdk\Service\LanguageService;
use MyParcelNL\WooCommerce\Pdk\Service\WcFrontendRenderService;
use MyParcelNL\WooCommerce\Pdk\Service\WcTaxService;
use MyParcelNL\WooCommerce\Pdk\Service\WcViewService;
use MyParcelNL\WooCommerce\Pdk\Service\WcWeightService;
use MyParcelNL\WooCommerce\Pdk\Settings\Repository\PdkSettingsRepository;
use MyParcelNL\WooCommerce\Pdk\Webhook\WcWebhooksRepository;
use MyParcelNL\WooCommerce\Service\WooCommerceService;
use MyParcelNL\WooCommerce\Service\WordPressService;
use MyParcelNL\WooCommerce\Service\WpCronService;
use MyParcelNL\WooCommerce\Service\WpFilterService;
use MyParcelNL\WooCommerce\Service\WpInstallerService;
use MyParcelNL\WooCommerce\Service\WpScriptService;
use MyParcelNL\WooCommerce\WooCommerce\Contract\WcOrderRepositoryInterface;
use MyParcelNL\WooCommerce\WooCommerce\Contract\WcShippingRepositoryInterface;
use MyParcelNL\WooCommerce\WooCommerce\Repository\WcOrderRepository;
use MyParcelNL\WooCommerce\WooCommerce\Repository\WcShippingRepository;
use Psr\Log\LoggerInterface;

use function DI\factory;
use function DI\get;
use function DI\value;

/**
 * @see \MyParcelNL\WooCommerce\Pdk\WcPdkBootstrapper for configuration based on the plugin itself.
 */
return [
    'wordPressVersion' => factory(function (): string {
        return get_bloginfo('version');
    }),

    'wooCommerceIsActive' => factory(function (): bool {
        $plugins = apply_filters('active_plugins', get_option('active_plugins'));

        return is_array($plugins) && in_array('woocommerce/woocommerce.php', $plugins, true);
    }),

    'wooCommerceVersion' => factory(function (): string {
        $plugins = get_plugins();

        return $plugins['woocommerce/woocommerce.php']['Version'] ?? '?';
    }),

    'minimumWooCommerceVersion' => value('5.0.0'),

    'isWooCommerceVersionSupported' => factory(function (): bool {
        return version_compare(WooCommerce::getVersion(), PdkFacade::get('minimumWooCommerceVersion'), '>=');
    }),

    'createProductDataIdentifier'    => factory(static function (): callable {
        return static function (string $productId): string {
            $appInfo = PdkFacade::getAppInfo();

            return sprintf('%s_product_data_%s', $appInfo->name, $productId);
        };
    }),

    /**
     * Error message to show when the current php version is not supported.
     */
    'errorMessagePhpVersion'         => factory(function (): string {
        return strtr(Language::translate('error_prerequisites_php_version'), [
            '{name}'    => Pdk::getAppInfo()->title,
            '{version}' => Pdk::get('minimumPhpVersion'),
            '{current}' => PHP_VERSION,
        ]);
    }),

    /**
     * Error message to show when the current php version is not supported.
     */
    'errorMessageWooCommerceVersion' => factory(function (): string {
        return strtr(Language::translate('error_prerequisites_woocommerce_version'), [
            '{name}'    => Pdk::getAppInfo()->title,
            '{version}' => Pdk::get('minimumWooCommerceVersion'),
        ]);
    }),

    'userAgent' => factory(function (): array {
        $propositionService = Pdk::get(PropositionService::class);
        return [
            'MyParcel-WooCommerce' => PdkFacade::getAppInfo()->version,
            'MyParcel-Proposition' => $propositionService->hasActivePropositionId() ? $propositionService->getPropositionConfig()->proposition->key : 'unknown',
            'WooCommerce'          => WooCommerce::getVersion(),
            'WordPress'            => WordPress::getVersion(),
        ];
    }),

    'bulkActions' => factory(static function (): array {
        $orderModeEnabled = Settings::get(OrderSettings::ORDER_MODE, OrderSettings::ID);
        $all              = PdkFacade::get('allBulkActions');

        return $orderModeEnabled
            ? Arr::get($all, 'orderMode', [])
            : Arr::get($all, 'default', []);
    }),

    'orderListPageId' => factory(static function (): string {
        if (! WooCommerce::isUsingHpos()) {
            return 'edit-shop_order';
        }

        return function_exists('wc_get_page_screen_id')
            ? wc_get_page_screen_id('shop_order')
            : 'woocommerce_page_wc-orders';
    }),

    ###
    # Single order page
    ###

    'orderPageId' => factory(static function (): string {
        if (! WooCommerce::isUsingHpos()) {
            return 'shop_order';
        }

        return function_exists('wc_get_page_screen_id')
            ? wc_get_page_screen_id('shop_order')
            : 'woocommerce_page_wc-order';
    }),

    ###
    # General
    ###

    'pluginBasename' => factory(function (): string {
        return plugin_basename(Pdk::getAppInfo()->path);
    }),

    'urlDocumentation' => value('https://developer.myparcel.nl/nl/documentatie/10.woocommerce.html'),
    'urlReleaseNotes'  => value('https://github.com/myparcelnl/woocommerce/releases'),

    'defaultWeightUnit' => value('kg'),

    'wcAddressTypeBilling'  => value('billing'),
    'wcAddressTypeShipping' => value('shipping'),

    'wcAddressTypes' => factory(static function (): array {
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

    'fieldNumber'       => value('house_number'),
    'fieldNumberSuffix' => value('house_number_suffix'),
    'fieldStreet'       => value('street_name'),

    ###
    # Settings
    ###

    'settingsMenuSlug'      => value('woocommerce_page_myparcel-settings'),
    'settingsMenuSlugShort' => value('myparcel-settings'),
    'settingsMenuTitle'     => value('MyParcel'),
    'settingsPageTitle'     => value('MyParcel WooCommerce'),

    ###
    # Routes
    ###

    'routeBackend'                   => value(PdkBootstrapper::PLUGIN_NAMESPACE . '/backend/v1'),
    'routeBackendPdk'                => value('pdk'),
    'routeBackendWebhookBase'        => value('webhook'),
    'routeBackendWebhook'            => factory(function (): string {
        return sprintf('%s/(?P<hash>.+)', Pdk::get('routeBackendWebhookBase'));
    }),
    'routeBackendPermissionCallback' => factory(static function (): string {
        if (! is_user_logged_in()) {
            return '__return_false';
        }

        foreach (wp_get_current_user()->roles as $role) {
            if (in_array($role, ['shop_manager', 'administrator'])) {
                return '__return_true';
            }
        }

        return '__return_false';
    }),

    'routeFrontend'         => value(PdkBootstrapper::PLUGIN_NAMESPACE . '/frontend/v1'),
    'routeFrontendMyParcel' => value(PdkBootstrapper::PLUGIN_NAMESPACE),

    /**
     * The meta key a product's MyParcel settings are saved in.
     *
     * @see \MyParcelNL\WooCommerce\Pdk\Product\Repository\WcPdkProductRepository
     */

    'metaKeyProductSettings' => value('_' . PdkBootstrapper::PLUGIN_NAMESPACE . '_product_settings'),

    ###
    # Custom services
    ###

    WordPressServiceInterface::class => get(WordPressService::class),
    WpFilterServiceInterface::class  => get(WpFilterService::class),

    WooCommerceServiceInterface::class   => get(WooCommerceService::class),
    WcShippingRepositoryInterface::class => get(WcShippingRepository::class),

    ###
    # PDK services
    ###

    /**
     * Repositories
     */

    PdkAccountRepositoryInterface::class        => get(PdkAccountRepository::class),
    PdkCartRepositoryInterface::class           => get(WcCartRepository::class),
    PdkOrderNoteRepositoryInterface::class      => get(WcOrderNoteRepository::class),
    PdkOrderRepositoryInterface::class          => get(PdkOrderRepository::class),
    PdkProductRepositoryInterface::class        => get(WcPdkProductRepository::class),
    PdkShippingMethodRepositoryInterface::class => get(WcShippingMethodRepository::class),
    PdkAuditRepositoryInterface::class          => get(WcPdkAuditRepository::class),
    PdkSettingsRepositoryInterface::class       => get(PdkSettingsRepository::class),
    WcOrderRepositoryInterface::class           => get(WcOrderRepository::class),

    /**
     * Services
     */

    ApiServiceInterface::class            => get(MyParcelApiService::class),
    ContextServiceInterface::class        => get(WcContextService::class),
    CronServiceInterface::class           => get(WpCronService::class),
    WpDatabaseServiceInterface::class     => get(WpDatabaseService::class),
    InstallerServiceInterface::class      => get(WpInstallerService::class),
    LanguageServiceInterface::class       => get(LanguageService::class),
    OrderStatusServiceInterface::class    => get(WcStatusService::class),
    FrontendRenderServiceInterface::class => get(WcFrontendRenderService::class),
    ViewServiceInterface::class           => get(WcViewService::class),
    WeightServiceInterface::class         => get(WcWeightService::class),
    TaxServiceInterface::class            => get(WcTaxService::class),

    /**
     * Endpoints
     */

    FrontendEndpointServiceInterface::class => get(WcFrontendEndpointService::class),
    BackendEndpointServiceInterface::class  => get(WcBackendEndpointService::class),

    /**
     * Webhooks
     */

    PdkWebhookServiceInterface::class     => get(WcWebhookService::class),
    PdkWebhooksRepositoryInterface::class => get(WcWebhooksRepository::class),

    /**
     * Miscellaneous
     */

    ClientAdapterInterface::class          => get(Guzzle7ClientAdapter::class),
    DeliveryOptionsServiceInterface::class => get(WcDeliveryOptionsService::class),
    LoggerInterface::class                 => get(WcLogger::class),
    MigrationServiceInterface::class       => get(WcMigrationService::class),
    ScriptServiceInterface::class          => get(WpScriptService::class),
];
