<?php

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\includes\Webhook\Hooks;

defined('ABSPATH') or die();


use MyParcelNL\WooCommerce\includes\Concerns\HasApiKey;
use MyParcelNL\WooCommerce\includes\Concerns\HasInstance;
use MyParcelNL\WooCommerce\includes\Webhook\Service\WebhookSubscriptionService;

abstract class AbstractWebhook
{
    use HasApiKey;
    use HasInstance;

    abstract protected function initializeWebhooks();

    /**
     * @param array $hooks
     *
     * @throws \Exception
     */
    protected function setupWebhooks(array $hooks): void
    {
        $webhookSubscriptionService  = new WebhookSubscriptionService();

        foreach ($hooks as $webhookClass => $callback) {
            $service = (new $webhookClass())->setApiKey($this->ensureHasApiKey());
            $webhookSubscriptionService->create($service, $callback);
        }
    }
}
