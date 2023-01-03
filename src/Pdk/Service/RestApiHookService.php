<?php

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Pdk\Service;

use MyParcelNL\Pdk\Base\PdkEndpoint;
use MyParcelNL\Pdk\Facade\DefaultLogger;
use MyParcelNL\Pdk\Facade\Pdk;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Throwable;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

class RestApiHookService implements WordPressHookServiceInterface
{
    public const NAMESPACE     = 'myparcelnl/v1';
    public const ROUTE_PDK     = 'pdk';
    public const ROUTE_WEBHOOK = 'webhook';

    public function initialize(): void
    {
        add_action('rest_api_init', [$this, 'registerApiRoutes']);
    }

    /**
     * @param  \WP_REST_Request $request
     *
     * @return \WP_REST_Response
     */
    public function processPdkRequest(WP_REST_Request $request): WP_REST_Response
    {
        try {
            /** @var \MyParcelNL\Pdk\Base\PdkEndpoint $endpoint */
            $endpoint = Pdk::get(PdkEndpoint::class);

            $response = $endpoint->call($this->convertRequest($request));
        } catch (Throwable $e) {
            DefaultLogger::error($e->getMessage(), []);
            return new WP_REST_Response($e->getMessage(), 400);
        }

        return $this->convertResponse($response);
    }

    /**
     * @return void
     */
    public function processWebhookRequest(): void
    {
        DefaultLogger::info('Webhook received', ['values' => $_POST]);
    }

    /**
     * @return void
     */
    public function registerApiRoutes(): void
    {
        register_rest_route(self::NAMESPACE, self::ROUTE_PDK, [
            'methods'             => WP_REST_Server::ALLMETHODS,
            'callback'            => [$this, 'processPdkRequest'],
            'permission_callback' => function () {
                return current_user_can('manage_options');
            },
        ]);

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

        return new WP_REST_Response($content, $response->getStatusCode());
    }
}
