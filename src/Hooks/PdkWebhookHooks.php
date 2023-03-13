<?php

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Hooks;

use MyParcelNL\Pdk\Facade\DefaultLogger;
use MyParcelNL\Pdk\Facade\Pdk;
use MyParcelNL\Pdk\Plugin\Api\PdkWebhook;
use MyParcelNL\WooCommerce\Hooks\Concern\UsesPdkRequestConverter;
use MyParcelNL\WooCommerce\Hooks\Contract\WordPressHooksInterface;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

final class PdkWebhookHooks implements WordPressHooksInterface
{
    use UsesPdkRequestConverter;

    public function apply(): void
    {
        add_action('rest_api_init', [$this, 'registerWebhookRoutes']);
    }

    /**
     * @return void
     */
    public function processWebhookRequest(WP_REST_Request $request): WP_REST_Response
    {
        DefaultLogger::info('Webhook received', ['request' => $request->get_params()]);

        /** @var \MyParcelNL\Pdk\Plugin\Api\PdkWebhook $webhooks */
        $webhooks = Pdk::get(PdkWebhook::class);
        $webhooks->call($this->convertRequest($request));

        $response = new WP_REST_Response();
        $response->set_status(202);
        return $response;
    }

    /**
     * @return void
     */
    public function registerWebhookRoutes(): void
    {
        register_rest_route(
            Pdk::get('routeBackend'),
            sprintf('%s/(?P<hash>.+)', Pdk::get('routeBackendWebhook')),
            [
                'methods'             => WP_REST_Server::CREATABLE,
                'callback'            => [$this, 'processWebhookRequest'],
                'permission_callback' => '__return_true',
            ]
        );
    }
}
