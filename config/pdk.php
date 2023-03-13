<?php

declare(strict_types=1);

use MyParcelNL\Pdk\Account\Contract\AccountRepositoryInterface;
use MyParcelNL\Pdk\Api\Contract\ApiServiceInterface;
use MyParcelNL\Pdk\Api\Contract\ClientAdapterInterface;
use MyParcelNL\Pdk\Api\Service\MyParcelApiService;
use MyParcelNL\Pdk\Base\Contract\CronServiceInterface;
use MyParcelNL\Pdk\Base\Contract\WeightServiceInterface;
use MyParcelNL\Pdk\Base\Pdk;
use MyParcelNL\Pdk\Frontend\Contract\ScriptServiceInterface;
use MyParcelNL\Pdk\Language\Contract\LanguageServiceInterface;
use MyParcelNL\Pdk\Plugin\Api\Contract\BackendEndpointServiceInterface;
use MyParcelNL\Pdk\Plugin\Api\Contract\FrontendEndpointServiceInterface;
use MyParcelNL\Pdk\Plugin\Contract\DeliveryOptionsServiceInterface;
use MyParcelNL\Pdk\Plugin\Contract\OrderStatusServiceInterface;
use MyParcelNL\Pdk\Plugin\Contract\PdkCartRepositoryInterface;
use MyParcelNL\Pdk\Plugin\Contract\PdkOrderRepositoryInterface;
use MyParcelNL\Pdk\Plugin\Contract\PdkShippingMethodRepositoryInterface;
use MyParcelNL\Pdk\Plugin\Contract\RenderServiceInterface;
use MyParcelNL\Pdk\Plugin\Contract\TaxServiceInterface;
use MyParcelNL\Pdk\Plugin\Contract\ViewServiceInterface;
use MyParcelNL\Pdk\Plugin\Webhook\Contract\PdkWebhookServiceInterface;
use MyParcelNL\Pdk\Plugin\Webhook\Contract\PdkWebhooksRepositoryInterface;
use MyParcelNL\Pdk\Product\Contract\ProductRepositoryInterface;
use MyParcelNL\Pdk\Settings\Contract\SettingsRepositoryInterface;
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
    TaxServiceInterface::class                  => autowire(WcTaxService::class),

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
