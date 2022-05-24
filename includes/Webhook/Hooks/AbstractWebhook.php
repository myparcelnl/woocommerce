<?php

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\includes\Webhook\Hooks;

defined('ABSPATH') or die();

use MyParcelNL\WooCommerce\includes\Concerns\HasApiKey;
use MyParcelNL\WooCommerce\includes\Webhook\Service\WebhookSubscriptionService;
use WP_REST_Request;
use WP_REST_Response;

abstract class AbstractWebhook
{
    use HasApiKey;

    /**
     * @throws \Exception
     */
    public function __construct()
    {
        if (! $this->validate()) {
            return;
        }

        $this->setup();
    }

    /**
     * @param  \WP_REST_Request $request
     *
     * @return \WP_REST_Response
     */
    abstract public function getCallback(WP_REST_Request $request): WP_REST_Response;

    /**
     * @return class-string<\MyParcelNL\Sdk\src\Services\Web\Webhook\AbstractWebhookWebService>[]
     */
    abstract protected function getHooks(): array;

    /**
     * @return void
     */
    public function validate(): bool
    {
        return true;
    }

    /**
     * @return \WP_REST_Response
     */
    protected function getNoContentResponse(): WP_REST_Response
    {
        $response = new WP_REST_Response();
        $response->set_status(204);
        return $response;
    }

    /**
     * @return \WP_REST_Response
     */
    protected function getSkippedResponse(): WP_REST_Response
    {
        $response = new WP_REST_Response();
        $response->set_status(200);
        $response->set_data(['message' => 'skipped']);
        return $response;
    }

    /**
     * @return \WP_REST_Response
     */
    protected function getUnprocessableEntityResponse(): WP_REST_Response
    {
        $response = new WP_REST_Response();
        $response->set_status(422);
        return $response;
    }

    /**
     * @throws \Exception
     */
    protected function setup(): void
    {
        $webhookSubscriptionService = new WebhookSubscriptionService();

        foreach ($this->getHooks() as $webhookClass) {
            $service = (new $webhookClass())->setApiKey($this->ensureHasApiKey());
            $webhookSubscriptionService->create($service, [$this, 'getCallback']);
        }
    }
}
