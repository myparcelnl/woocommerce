<?php

declare(strict_types=1);

use MyParcelNL\Pdk\Api\Adapter\ClientAdapterInterface;
use MyParcelNL\Pdk\Api\Service\ApiServiceInterface;
use MyParcelNL\Pdk\Api\Service\MyParcelApiService;
use MyParcelNL\Pdk\Base\Pdk;
use MyParcelNL\Pdk\Language\Service\LanguageServiceInterface;
use MyParcelNL\Pdk\Plugin\Action\EndpointActionsInterface;
use MyParcelNL\Pdk\Plugin\Repository\AbstractPdkOrderRepository;
use MyParcelNL\Pdk\Product\Repository\AbstractProductRepository;
use MyParcelNL\Pdk\Settings\Repository\AbstractSettingsRepository;
use MyParcelNL\WooCommerce\Pdk\Guzzle7ClientAdapter;
use MyParcelNL\WooCommerce\Pdk\Plugin\Action\WcEndpointActions;
use MyParcelNL\WooCommerce\Pdk\Plugin\Repository\PdkOrderRepository;
use MyParcelNL\WooCommerce\Pdk\Product\Repository\PdkProductRepository;
use MyParcelNL\WooCommerce\Pdk\Service\LanguageService;
use MyParcelNL\WooCommerce\Pdk\Settings\Repository\PdkSettingsRepository;
use function DI\autowire;
use function DI\value;

return [
    'mode' => value(WP_DEBUG_LOG ? Pdk::MODE_DEVELOPMENT : Pdk::MODE_PRODUCTION),

    ApiServiceInterface::class        => autowire(MyParcelApiService::class),
    AbstractPdkOrderRepository::class => autowire(PdkOrderRepository::class),
    ClientAdapterInterface::class     => autowire(Guzzle7ClientAdapter::class),
    EndpointActionsInterface::class   => autowire(WcEndpointActions::class),
    LanguageServiceInterface::class   => autowire(LanguageService::class),
    AbstractSettingsRepository::class => autowire(PdkSettingsRepository::class),
    AbstractProductRepository::class  => autowire(PdkProductRepository::class),

    //AbstractLogger::class                  => autowire(PdkLogger::class),
];
