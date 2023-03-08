<?php

declare(strict_types=1);

use MyParcelNL\Pdk\Account\Repository\AccountRepositoryInterface;
use MyParcelNL\Pdk\Api\Adapter\ClientAdapterInterface;
use MyParcelNL\Pdk\Api\Service\ApiServiceInterface;
use MyParcelNL\Pdk\Api\Service\MyParcelApiService;
use MyParcelNL\Pdk\Base\Concern\WeightServiceInterface;
use MyParcelNL\Pdk\Base\CronServiceInterface;
use MyParcelNL\Pdk\Base\Pdk;
use MyParcelNL\Pdk\Frontend\Service\ScriptServiceInterface;
use MyParcelNL\Pdk\Language\Service\LanguageServiceInterface;
use MyParcelNL\Pdk\Plugin\Api\Backend\BackendEndpointServiceInterface;
use MyParcelNL\Pdk\Plugin\Api\Frontend\FrontendEndpointServiceInterface;
use MyParcelNL\Pdk\Plugin\Repository\PdkCartRepositoryInterface;
use MyParcelNL\Pdk\Plugin\Repository\PdkOrderRepositoryInterface;
use MyParcelNL\Pdk\Plugin\Repository\PdkShippingMethodRepositoryInterface;
use MyParcelNL\Pdk\Plugin\Service\DeliveryOptionsServiceInterface;
use MyParcelNL\Pdk\Plugin\Service\OrderStatusServiceInterface;
use MyParcelNL\Pdk\Plugin\Service\RenderServiceInterface;
use MyParcelNL\Pdk\Plugin\Service\TaxService;
use MyParcelNL\Pdk\Plugin\Service\ViewServiceInterface;
use MyParcelNL\Pdk\Plugin\Webhook\PdkWebhookServiceInterface;
use MyParcelNL\Pdk\Plugin\Webhook\Repository\PdkWebhooksRepositoryInterface;
use MyParcelNL\Pdk\Product\Repository\ProductRepositoryInterface;
use MyParcelNL\Pdk\Settings\Repository\SettingsRepositoryInterface;
use MyParcelNL\WooCommerce\Logger\WcLogger;
use MyParcelNL\WooCommerce\Pdk\Guzzle7ClientAdapter;
use MyParcelNL\WooCommerce\Pdk\Plugin\Action\WcBackendEndpointService;
use MyParcelNL\WooCommerce\Pdk\Plugin\Action\WcFrontendEndpointService;
use MyParcelNL\WooCommerce\Pdk\Plugin\Action\WcWebhookService;
use MyParcelNL\WooCommerce\Pdk\Plugin\Repository\PdkAccountRepository;
use MyParcelNL\WooCommerce\Pdk\Plugin\Repository\PdkOrderRepository;
use MyParcelNL\WooCommerce\Pdk\Plugin\Repository\WcCartRepository;
use MyParcelNL\WooCommerce\Pdk\Plugin\Service\WcDeliveryOptionsService;
use MyParcelNL\WooCommerce\Pdk\Plugin\Service\WcStatusService;
use MyParcelNL\WooCommerce\Pdk\Plugin\WcShippingMethodRepository;
use MyParcelNL\WooCommerce\Pdk\Product\Repository\PdkProductRepository;
use MyParcelNL\WooCommerce\Pdk\Service\LanguageService;
use MyParcelNL\WooCommerce\Pdk\Service\WcRenderService;
use MyParcelNL\WooCommerce\Pdk\Service\WcTaxService;
use MyParcelNL\WooCommerce\Pdk\Service\WcViewService;
use MyParcelNL\WooCommerce\Pdk\Service\WcWeightService;
use MyParcelNL\WooCommerce\Pdk\Settings\Repository\PdkSettingsRepository;
use MyParcelNL\WooCommerce\Pdk\Webhook\WcWebhooksRepository;
use MyParcelNL\WooCommerce\Service\WpCronService;
use MyParcelNL\WooCommerce\Service\WpScriptService;
use Psr\Log\LoggerInterface;
use function DI\autowire;
use function DI\value;

/**
 * @see \MyParcelNL\WooCommerce\Pdk\WcPdkBootstrapper for configuration based on the plugin itself.
 */
return [
    'mode'                                      => value(WP_DEBUG ? Pdk::MODE_DEVELOPMENT : Pdk::MODE_PRODUCTION),

    /**
     * Repositories
     */
    AccountRepositoryInterface::class           => autowire(PdkAccountRepository::class),
    PdkOrderRepositoryInterface::class          => autowire(PdkOrderRepository::class),
    ProductRepositoryInterface::class           => autowire(PdkProductRepository::class),
    SettingsRepositoryInterface::class          => autowire(PdkSettingsRepository::class),
    PdkCartRepositoryInterface::class           => autowire(WcCartRepository::class),
    PdkShippingMethodRepositoryInterface::class => autowire(WcShippingMethodRepository::class),

    /**
     * Services
     */
    ApiServiceInterface::class                  => autowire(MyParcelApiService::class),
    CronServiceInterface::class                 => autowire(WpCronService::class),
    LanguageServiceInterface::class             => autowire(LanguageService::class),
    OrderStatusServiceInterface::class          => autowire(WcStatusService::class),
    RenderServiceInterface::class               => autowire(WcRenderService::class),
    ViewServiceInterface::class                 => autowire(WcViewService::class),
    WeightServiceInterface::class               => autowire(WcWeightService::class),
    TaxService::class                           => autowire(WcTaxService::class),

    /**
     * Endpoints
     */
    FrontendEndpointServiceInterface::class     => autowire(WcFrontendEndpointService::class),
    BackendEndpointServiceInterface::class      => autowire(WcBackendEndpointService::class),

    /**
     * Webhooks
     */
    PdkWebhookServiceInterface::class           => autowire(WcWebhookService::class),
    PdkWebhooksRepositoryInterface::class       => autowire(WcWebhooksRepository::class),

    /**
     * Miscellaneous
     */
    ClientAdapterInterface::class               => autowire(Guzzle7ClientAdapter::class),
    LoggerInterface::class                      => autowire(WcLogger::class),

    DeliveryOptionsServiceInterface::class => autowire(WcDeliveryOptionsService::class),

    ScriptServiceInterface::class => autowire(WpScriptService::class),
];
