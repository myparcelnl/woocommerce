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
use MyParcelNL\Pdk\App\Order\Contract\PdkOrderRepositoryInterface;
use MyParcelNL\Pdk\App\Order\Contract\PdkProductRepositoryInterface;
use MyParcelNL\Pdk\App\ShippingMethod\Contract\PdkShippingMethodRepositoryInterface;
use MyParcelNL\Pdk\App\Tax\Contract\TaxServiceInterface;
use MyParcelNL\Pdk\App\Webhook\Contract\PdkWebhookServiceInterface;
use MyParcelNL\Pdk\App\Webhook\Contract\PdkWebhooksRepositoryInterface;
use MyParcelNL\Pdk\Base\Contract\CronServiceInterface;
use MyParcelNL\Pdk\Base\Contract\WeightServiceInterface;
use MyParcelNL\Pdk\Base\Pdk;
use MyParcelNL\Pdk\Facade\Pdk as PdkFacade;
use MyParcelNL\Pdk\Frontend\Contract\FrontendRenderServiceInterface;
use MyParcelNL\Pdk\Frontend\Contract\ScriptServiceInterface;
use MyParcelNL\Pdk\Frontend\Contract\ViewServiceInterface;
use MyParcelNL\Pdk\Language\Contract\LanguageServiceInterface;
use MyParcelNL\Pdk\Settings\Contract\SettingsRepositoryInterface;
use MyParcelNL\WooCommerce\Logger\WcLogger;
use MyParcelNL\WooCommerce\Pdk\Guzzle7ClientAdapter;
use MyParcelNL\WooCommerce\Pdk\Plugin\Action\WcBackendEndpointService;
use MyParcelNL\WooCommerce\Pdk\Plugin\Action\WcFrontendEndpointService;
use MyParcelNL\WooCommerce\Pdk\Plugin\Action\WcWebhookService;
use MyParcelNL\WooCommerce\Pdk\Plugin\Installer\WcMigrationService;
use MyParcelNL\WooCommerce\Pdk\Plugin\Repository\PdkAccountRepository;
use MyParcelNL\WooCommerce\Pdk\Plugin\Repository\PdkOrderRepository;
use MyParcelNL\WooCommerce\Pdk\Plugin\Repository\WcCartRepository;
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
use MyParcelNL\WooCommerce\Service\WpCronService;
use MyParcelNL\WooCommerce\Service\WpInstallerService;
use MyParcelNL\WooCommerce\Service\WpScriptService;
use Psr\Log\LoggerInterface;
use function DI\autowire;
use function DI\factory;
use function DI\value;

/**
 * @see \MyParcelNL\WooCommerce\Pdk\WcPdkBootstrapper for configuration based on the plugin itself.
 */
return [
    'mode' => value(WP_DEBUG ? Pdk::MODE_DEVELOPMENT : Pdk::MODE_PRODUCTION),

    'pluginBaseName' => factory(function (): string {
        return plugin_basename(PdkFacade::getAppInfo()->path);
    }),

    'userAgent' => factory(function (): array {
        return [
            'MyParcelNL-WooCommerce' => PdkFacade::getAppInfo()->version,
            'WooCommerce'            => function_exists('WC') ? WC()->version : '?',
            'WordPress'              => get_bloginfo('version'),
        ];
    }),

    /**
     * Repositories
     */

    PdkAccountRepositoryInterface::class        => autowire(PdkAccountRepository::class),
    PdkCartRepositoryInterface::class           => autowire(WcCartRepository::class),
    PdkOrderRepositoryInterface::class          => autowire(PdkOrderRepository::class),
    PdkProductRepositoryInterface::class        => autowire(WcPdkProductRepository::class),
    PdkShippingMethodRepositoryInterface::class => autowire(WcShippingMethodRepository::class),
    SettingsRepositoryInterface::class          => autowire(PdkSettingsRepository::class),

    /**
     * Services
     */

    ApiServiceInterface::class            => autowire(MyParcelApiService::class),
    CronServiceInterface::class           => autowire(WpCronService::class),
    InstallerServiceInterface::class      => autowire(WpInstallerService::class),
    LanguageServiceInterface::class       => autowire(LanguageService::class),
    OrderStatusServiceInterface::class    => autowire(WcStatusService::class),
    FrontendRenderServiceInterface::class => autowire(WcFrontendRenderService::class),
    ViewServiceInterface::class           => autowire(WcViewService::class),
    WeightServiceInterface::class         => autowire(WcWeightService::class),
    TaxServiceInterface::class            => autowire(WcTaxService::class),

    /**
     * Endpoints
     */

    FrontendEndpointServiceInterface::class => autowire(WcFrontendEndpointService::class),
    BackendEndpointServiceInterface::class  => autowire(WcBackendEndpointService::class),

    /**
     * Webhooks
     */

    PdkWebhookServiceInterface::class     => autowire(WcWebhookService::class),
    PdkWebhooksRepositoryInterface::class => autowire(WcWebhooksRepository::class),

    /**
     * Miscellaneous
     */

    ClientAdapterInterface::class          => autowire(Guzzle7ClientAdapter::class),
    DeliveryOptionsServiceInterface::class => autowire(WcDeliveryOptionsService::class),
    LoggerInterface::class                 => autowire(WcLogger::class),
    MigrationServiceInterface::class       => autowire(WcMigrationService::class),
    ScriptServiceInterface::class          => autowire(WpScriptService::class),
];
