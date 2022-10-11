<?php

declare(strict_types=1);

use MyParcelNL\Pdk\Api\Adapter\ClientAdapterInterface;
use MyParcelNL\Pdk\Api\Service\ApiServiceInterface;
use MyParcelNL\Pdk\Api\Service\MyParcelApiService;
use MyParcelNL\Pdk\Base\Pdk;
use MyParcelNL\Pdk\Facade\LanguageService;
use MyParcelNL\Pdk\Language\Service\LanguageServiceInterface;
use MyParcelNL\Pdk\Plugin\Action\EndpointActionsInterface;
use MyParcelNL\Pdk\Plugin\Repository\AbstractPdkOrderRepository;
use MyParcelNL\Pdk\Settings\Repository\AbstractSettingsRepository;
use MyParcelNL\WooCommerce\includes\adapter\Guzzle7ClientAdapter;
use WPO\WC\MyParcel\Collections\SettingsCollection;
use function DI\autowire;
use function DI\value;

return [
    'platform' => WCMYPA::NAME,
    'mode'     => value(WP_DEBUG_LOG ? Pdk::MODE_DEVELOPMENT : Pdk::MODE_PRODUCTION),

    ApiServiceInterface::class => autowire(MyParcelApiService::class)->constructor(
        [
            'userAgent' => ['Woocommerce', WOOCOMMERCE_VERSION],
            'apiKey'    => SettingsCollection::getInstance()
                ->getByName(WCMYPA_Settings::SETTING_API_KEY),
        ]
    ),

    AbstractPdkOrderRepository::class => autowire(PdkOrderRepository::class),
    ClientAdapterInterface::class     => autowire(Guzzle7ClientAdapter::class),
    EndpointActionsInterface::class   => autowire(WooEndPointActions::class),
    LanguageServiceInterface::class   => autowire(LanguageService::class),
    PdkLogger::class             => autowire(WCMP_Log::class),
    AbstractSettingsRepository::class => autowire(PdkSettingsRepository::class),
];
