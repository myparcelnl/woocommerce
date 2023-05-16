<?php

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Pdk\Plugin\Action;

use MyParcelNL\Pdk\App\Webhook\Service\AbstractPdkWebhookService;
use MyParcelNL\Pdk\Facade\Pdk;

class WcWebhookService extends AbstractPdkWebhookService
{
    /**
     * @return string
     */
    public function getBaseUrl(): string
    {
        return get_rest_url(
            null,
            sprintf('%s/%s', Pdk::get('routeBackend'), Pdk::get('routeBackendWebhook'))
        );
    }
}
