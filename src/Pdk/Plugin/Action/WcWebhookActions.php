<?php

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Pdk\Plugin\Action;

use MyParcelNL\Pdk\Plugin\Webhook\AbstractPdkWebhookActions;
use MyParcelNL\WooCommerce\Hooks\RestApiHooks;

class WcWebhookActions extends AbstractPdkWebhookActions
{
    /**
     * @return string
     */
    public function getBaseUrl(): string
    {
        return get_rest_url(null, sprintf('%s/%s', RestApiHooks::NAMESPACE, RestApiHooks::ROUTE_WEBHOOK));
    }
}
