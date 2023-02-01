<?php

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Pdk\Plugin\Action;

use MyParcelNL\Pdk\Plugin\Webhook\AbstractPdkWebhookService;
use MyParcelNL\WooCommerce\Hooks\RestApiHooks;

class WcWebhookService extends AbstractPdkWebhookService
{
    /**
     * @return string
     */
    public function getBaseUrl(): string
    {
        return get_rest_url(null, sprintf('%s/%s', RestApiHooks::NAMESPACE, RestApiHooks::ROUTE_WEBHOOK));
    }
}
