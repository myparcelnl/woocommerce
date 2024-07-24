<?php

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Pdk\Hooks;

use MyParcelNL\Pdk\App\Webhook\PdkWebhookManager;
use MyParcelNL\Pdk\Facade\Logger;
use MyParcelNL\Pdk\Facade\Pdk;
use MyParcelNL\WooCommerce\Hooks\Concern\UsesPdkRequestConverter;
use MyParcelNL\WooCommerce\Hooks\Contract\WordPressHooksInterface;
use Symfony\Component\HttpFoundation\Request;
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
     * @param  \WP_REST_Request $request
     *
     * @return \WP_REST_Response
     */
    public function processWebhookRequest(WP_REST_Request $request): WP_REST_Response
    {
        Logger::info('Incoming webhook', ['request' => $request->get_params()]);

        /** @var \MyParcelNL\Pdk\App\Webhook\PdkWebhookManager $webhooks */
        $webhooks = Pdk::get(PdkWebhookManager::class);
        $webhooks->call($this->normalizeRequest($request));

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
            Pdk::get('routeBackendWebhook'),
            [
                'methods'             => WP_REST_Server::CREATABLE,
                'callback'            => [$this, 'processWebhookRequest'],
                'permission_callback' => '__return_true',
            ]
        );
    }

    /**
     * WordPress strips the wp-json prefix from the route, but we expect it to be present in the url to validate the
     * webhook request.
     *
     * @param  \WP_REST_Request $wpRestRequest
     *
     * @return \Symfony\Component\HttpFoundation\Request
     */
    private function normalizeRequest(WP_REST_Request $wpRestRequest): Request
    {
        $wpRestRequest->set_route("/wp-json{$wpRestRequest->get_route()}");

        return $this->convertRequest($wpRestRequest);
    }
}
