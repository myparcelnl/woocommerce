<?php

declare(strict_types=1);

use MyParcelNL\Pdk\Account\Repository\AccountRepositoryInterface;
use MyParcelNL\Pdk\Api\Adapter\ClientAdapterInterface;
use MyParcelNL\Pdk\Api\Service\ApiServiceInterface;
use MyParcelNL\Pdk\Api\Service\MyParcelApiService;
use MyParcelNL\Pdk\Base\Concern\WeightServiceInterface;
use MyParcelNL\Pdk\Base\CronServiceInterface;
use MyParcelNL\Pdk\Base\Pdk;
use MyParcelNL\Pdk\Language\Service\LanguageServiceInterface;
use MyParcelNL\Pdk\Plugin\Api\EndpointActionsInterface;
use MyParcelNL\Pdk\Plugin\Repository\PdkCartRepositoryInterface;
use MyParcelNL\Pdk\Plugin\Repository\PdkOrderRepositoryInterface;
use MyParcelNL\Pdk\Plugin\Repository\PdkShippingMethodRepositoryInterface;
use MyParcelNL\Pdk\Plugin\Service\OrderStatusServiceInterface;
use MyParcelNL\Pdk\Plugin\Service\RenderServiceInterface;
use MyParcelNL\Pdk\Plugin\Service\ViewServiceInterface;
use MyParcelNL\Pdk\Plugin\Webhook\PdkWebhookServiceInterface;
use MyParcelNL\Pdk\Plugin\Webhook\Repository\PdkWebhooksRepositoryInterface;
use MyParcelNL\Pdk\Product\Repository\ProductRepositoryInterface;
use MyParcelNL\Pdk\Settings\Repository\SettingsRepositoryInterface;
use MyParcelNL\WooCommerce\Logger\WcLogger;
use MyParcelNL\WooCommerce\Pdk\Guzzle7ClientAdapter;
use MyParcelNL\WooCommerce\Pdk\Plugin\Action\WcEndpointActions;
use MyParcelNL\WooCommerce\Pdk\Plugin\Action\WcWebhookService;
use MyParcelNL\WooCommerce\Pdk\Plugin\Repository\PdkAccountRepository;
use MyParcelNL\WooCommerce\Pdk\Plugin\Repository\PdkOrderRepository;
use MyParcelNL\WooCommerce\Pdk\Plugin\Repository\WcCartRepository;
use MyParcelNL\WooCommerce\Pdk\Plugin\Service\WcStatusService;
use MyParcelNL\WooCommerce\Pdk\Plugin\WcShippingMethodRepository;
use MyParcelNL\WooCommerce\Pdk\Product\Repository\PdkProductRepository;
use MyParcelNL\WooCommerce\Pdk\Service\LanguageService;
use MyParcelNL\WooCommerce\Pdk\Service\WcRenderService;
use MyParcelNL\WooCommerce\Pdk\Service\WcViewService;
use MyParcelNL\WooCommerce\Pdk\Service\WcWeightService;
use MyParcelNL\WooCommerce\Pdk\Settings\Repository\PdkSettingsRepository;
use MyParcelNL\WooCommerce\Pdk\Webhook\WcWebhooksRepository;
use MyParcelNL\WooCommerce\Service\WpCronService;
use Psr\Log\LoggerInterface;
use function DI\autowire;
use function DI\value;

/**
 * @see \MyParcelNL\WooCommerce\Pdk\Boot::setupPdk() for configuration based on the plugin itself.
 */
return [
    'mode'                                      => value(WP_DEBUG ? Pdk::MODE_DEVELOPMENT : Pdk::MODE_PRODUCTION),

    /**
     * The version of the delivery options in the checkout.
     *
     * @see https://github.com/myparcelnl/delivery-options/releases
     */
    'deliveryOptionsVersion'                    => value('5.3.0'),

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

    /**
     * Endpoints
     */
    EndpointActionsInterface::class             => autowire(WcEndpointActions::class),

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
];
