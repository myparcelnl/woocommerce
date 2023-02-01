<?php

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Hooks;

use MyParcelNL\Pdk\Facade\DefaultLogger;
use MyParcelNL\Pdk\Facade\Pdk;
use MyParcelNL\Pdk\Plugin\Api\PdkEndpoint;
use MyParcelNL\Pdk\Plugin\Api\PdkWebhook;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

class RestApiHooks implements WordPressHooksInterface
{
    public const NAMESPACE     = 'myparcelnl/v1';
    public const ROUTE_PDK     = 'pdk';
    public const ROUTE_WEBHOOK = 'webhook';

    public function apply(): void
    {
        add_action('rest_api_init', [$this, 'registerPdkRoutes']);
        add_action('rest_api_init', [$this, 'registerWebhookRoutes']);
    }

    /**
     * @param  \WP_REST_Request $request
     *
     * @return \WP_REST_Response
     */
    public function processPdkRequest(WP_REST_Request $request): WP_REST_Response
    {
        /** @var \MyParcelNL\Pdk\Plugin\Api\PdkEndpoint $endpoint */
        $endpoint = Pdk::get(PdkEndpoint::class);

        $response = $endpoint->call($this->convertRequest($request));

        return $this->convertResponse($response);
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
    public function registerPdkRoutes(): void
    {
        register_rest_route(self::NAMESPACE, self::ROUTE_PDK, [
            'methods'             => WP_REST_Server::ALLMETHODS,
            'callback'            => [$this, 'processPdkRequest'],
            'permission_callback' => function () {
                return current_user_can('manage_options');
            },
        ]);
    }

    /**
     * @return void
     */
    public function registerWebhookRoutes(): void
    {
        register_rest_route(self::NAMESPACE, self::ROUTE_WEBHOOK . '/(?P<hash>.+)', [
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => [$this, 'processWebhookRequest'],
            'permission_callback' => '__return_true',
        ]);
    }

    /**
     * Convert a WP_REST_Request to a Symfony Request.
     *
     * @param  \WP_REST_Request $wpRestRequest
     *
     * @return \Symfony\Component\HttpFoundation\Request
     */
    private function convertRequest(WP_REST_Request $wpRestRequest): Request
    {
        $request = Request::create(
            $wpRestRequest->get_route(),
            $wpRestRequest->get_method(),
            $wpRestRequest->get_query_params(),
            [],
            $wpRestRequest->get_file_params(),
            [],
            $wpRestRequest->get_body()
        );

        $request->setMethod($wpRestRequest->get_method());
        $request->headers->replace($wpRestRequest->get_headers());

        return $request;
    }

    /**
     * Convert a WP_REST_Response to a Symfony Response.
     *
     * @param  \Symfony\Component\HttpFoundation\Response $response
     *
     * @return \WP_REST_Response
     */
    private function convertResponse(Response $response): WP_REST_Response
    {
        if ($response->headers->has('Content-Type') && $response->headers->get('Content-Type') === 'application/json') {
            $content = json_decode($response->getContent(), true);
        } else {
            $content = $response->getContent();
        }

        $wpResponse = new WP_REST_Response($content, $response->getStatusCode());
        $wpResponse->header('Content-Type', $response->headers->get('Content-Type'));

        return $wpResponse;
    }
}
