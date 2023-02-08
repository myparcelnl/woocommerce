<?php

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Pdk\Plugin\Action;

use MyParcelNL;
use MyParcelNL\Pdk\Plugin\Webhook\AbstractPdkWebhookService;

class WcWebhookService extends AbstractPdkWebhookService
{
    public const ROUTE = 'webhook';

    /**
     * @return string
     */
    public function getBaseUrl(): string
    {
        return get_rest_url(null, sprintf('%s/%s', MyParcelNL::REST_ROUTE, self::ROUTE));
    }
}
